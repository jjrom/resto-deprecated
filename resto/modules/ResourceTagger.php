<?php

/*
 * RESTo
 * 
 * RESTo - REstful Semantic search Tool for geOspatial 
 * 
 * Copyright 2013 Jérôme Gasperi <https://github.com/jjrom>
 * 
 * jerome[dot]gasperi[at]gmail[dot]com
 * 
 * 
 * This software is governed by the CeCILL-B license under French law and
 * abiding by the rules of distribution of free software.  You can  use,
 * modify and/ or redistribute the software under the terms of the CeCILL-B
 * license as circulated by CEA, CNRS and INRIA at the following URL
 * "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and  rights to copy,
 * modify and redistribute granted by the license, users are provided only
 * with a limited warranty  and the software's author,  the holder of the
 * economic rights,  and the successive licensors  have only  limited
 * liability.
 *
 * In this respect, the user's attention is drawn to the risks associated
 * with loading,  using,  modifying and/or developing or reproducing the
 * software by the user in light of its specific status of free software,
 * that may mean  that it is complicated to manipulate,  and  that  also
 * therefore means  that it is reserved for developers  and  experienced
 * professionals having in-depth computer knowledge. Users are therefore
 * encouraged to load and test the software's suitability as regards their
 * requirements in conditions enabling the security of their systems and/or
 * data to be ensured and,  more generally, to use and operate it in the
 * same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL-B license and that you accept its terms.
 * 
 */

/**
 * Resource tagger module
 * 
 * This module allows to tag a resource
 * A tag is a string starting with a dash (e.g '#pipeline')
 * 
 */
class ResourceTagger {

    protected $Controller;
    protected $description;
    protected $request;
    protected $dbh;
    
    /**
     * Constructor
     * 
     * @param Object $Controller - RestoController instance
     * 
     */
    public function __construct($Controller) {
        
        /*
         * Check that module is activated
         */
        $config = $Controller->getParent()->getModuleConfig('ResourceTagger');
        
        /*
         * If secure option is set, only HTTPS requests are processed
         */
        if (!$config || ($config['secure'] && $_SERVER['HTTPS'] !== 'on')) {
            throw new Exception('This service supports https only', 403);
        }
        
        if (!$config || !$config['activate']) {
            throw new Exception('Forbidden', 403);
        }
        
        /*
         * Set instance references
         */
        $this->Controller = $Controller;
        $this->description = $Controller->getDescription();
        $this->request = $Controller->getParent()->getRequest();
        $this->dbh = $this->Controller->getDbConnector()->getConnection(true);
        
    }

    /**
     *
     * Tag resources
     *
     * @param array $arr with the following structure
     *
     *              array(
     *                  array(
     *                      // This is optional to constrain resources to tag
     *                      'query' => array(
     *
     *                      ),
     *                      'tags' => array(
     *                          'tag1',
     *                          'tag2',
     *                          etc.
     *                      )
     *                  ),
     *                  etc.
     *              )
     * @return type
     * @throws Exception
     */
    public function tag($arr) {

        if (!$this->dbh) {
            throw new Exception('Database connection error', 500);
        }

        /*
         * Only authenticated user can add tags
         */
        if (!$this->Controller->getParent()->getUser()->canTag($this->description['name'])) {
            throw new Exception('Unauthorized', 401);
        }
        
        /*
         * Check if keywords column is set for the collection
         */
        $tagColumn = getModelName($this->description['model'], 'keywords');
        if (!$tagColumn) {
            throw new Exception('Collection does not support tagging', 500);
        }

        /*
         * This should not happens
         */
        if (!is_array($arr) || count($arr) === 0) {
            throw new Exception('Nothing to tag', 500);
        }

        /*
         * Roll over input array
         */
        $count = 0;
        for ($j = count($arr); $j--;) {

            $options = $arr[$j];

            /*
             * Prepare tagging within products table keywords column
             * Note : keywords column should be of type 'hstore'
             *
             * Note 1 : valid tags should start with hash '#' character
             * Note 2 : spaces are removed from tags
             */
            $terms = array();
            for ($i = 0, $l = count($options['tags']); $i < $l; $i++) {
                if (substr($options['tags'][$i] , 0, 1) === '#') {
                    $terms[] = pg_escape_string(strtolower(str_replace(' ', '', $options['tags'][$i]))) . ' => NULL';
                }
            }
            $baseQuery = 'UPDATE ' . $this->Controller->getDbConnector()->getSchema(). '.' . $this->Controller->getDbConnector()->getTable() . ' SET ' . $tagColumn . ' = ' . $tagColumn . ' || \'' . join(',', $terms) . '\'';
            $where = '';
            
            /*
             * Case 1 :
             * 
             *   Both collection and identifier are set within request => tag resource
             */
            if ($this->request['identifier'] !== '$tags') {

                /*
                 * Check if resource exists in collection
                 */
                if (!$this->resourceExists($this->request['identifier'])) {
                    throw new Exception('Error : resource ' . $this->request['identifier'] . ' does not exist in collection', 500);
                }
                
                $where = ' WHERE ' . getModelName($this->description['model'], 'identifier') . ' = \'' . pg_escape_string($this->request['identifier']) . '\'';
                
            }
            /*
             * Case 2 :
             * 
             *   Only collection is set within request => tag collection resources using "query" filters
             *   
             */
            else {
                
                /*
                 * Check for tag filters
                 */
                if ($options['query']) {
                    
                    $whereFilters = array();
                    
                    /*
                     * Spatial filter
                     */
                    if ($options['query']['geometry']) {
                        $whereFilters[] = 'ST_intersects(' . getModelName($this->description['model'], 'geometry') . ', ST_GeomFromText(\'' . geoJSONGeometryToWKT($options['query']['geometry']) . '\', 4326))';
                    }
                    
                    /*
                     * Temporal filter
                     */
                    
                    $where = ' WHERE ' . join(' AND ', $whereFilters);
                    
                }
                
            }
            
            /*
             * Update database
             */
            try {
                $query = pg_query($this->dbh, $baseQuery . $where);
                //echo $baseQuery . $where;
                if (!$query) {
                    throw new Exception();
                }
                $count = $count + pg_affected_rows($query);
            } catch (Exception $e) {
                throw new Exception('Error : cannot update collection', 500);
            }

        }
        
        return array('Status' => 'Success', 'Message' => $count . ' resource(s) tagged');
    }

    /**
     * Check if resource $identifier exists within collection database
     *
     * @param string $identifier - resource unique identifier
     */
    protected function resourceExists($identifier) {

        $results = pg_query($this->dbh, 'SELECT 1 FROM ' . $this->Controller->getDbConnector()->getSchema() . '.' . $this->Controller->getDbConnector()->getTable() . ' WHERE ' . getModelName($this->description['model'], 'identifier') . '=\'' . pg_escape_string($identifier) . '\'');

        if (!$results) {
            throw new Exception('Database connection error', 500);
        }
        while ($result = pg_fetch_assoc($results)) {
            return true;
        }

        return false;
    }
}
