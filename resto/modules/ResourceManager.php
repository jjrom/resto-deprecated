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
 * 
 * Resource manager module
 * 
 * This class allows to manage resource from a given collection :
 *  - create a resource - i.e. insert a non existing resource within collection database 
 *  - update an existing resource
 *  - delete an existing resource
 * 
 */
class ResourceManager {

    protected $Controller;
    protected $description;
    protected $dbh;
    private $iTag;
    
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
        $config = $Controller->getParent()->getModuleConfig('ResourceManager');
        
        /*
         * If secure option is set, only HTTPS requests are processed
         */
        if (!$config || ($config['secure'] && $_SERVER['HTTPS'] !== 'on')) {
            throw new Exception('This service supports https only', 403);
        }
        
        if (!$config || !$config['activate']) {
            throw new Exception('Forbidden', 403);
        }
        if (isset($config['iTag'])) {
            $this->iTag = $config['iTag'];
        }
        
        /*
         * Set instance references
         */
        $this->Controller = $Controller;
        $this->description = $Controller->getDescription();
        $this->dbh = $this->Controller->getDbConnector()->getConnection(true);
        
    }

    /**
     * 
     * Insert input resources within collection database 
     * 
     *  !! VERY IMPORTANT !!
     *
     *  It is assumes that input $resources is an array of GeoJSON featureCollection
     * 
     * @param array $resources
     * @return type
     * @throws Exception
     */
    public function create($resources = array()) {

        if (!$this->dbh) {
            throw new Exception('Database connection error', 500);
        }
        
        /*
         * Only authenticated user can post files
         * TODO - set authorization level within database (i.e. canPost, canPut, canDelete, etc. ?)
         */
        if (!$this->Controller->getParent()->checkAuth()) {
            throw new Exception('Unauthorized', 401);
        }
        
        /*
         * This should not happens
         */
        if (!is_array($resources)) {
            throw new Exception('Invalid posted file(s)', 500);
        }
        
        /*
         * Nothing to POST
         */
        if (count($resources) === 0) {
            throw new Exception('Nothing to post', 200);
        }
        
        /*
         * Insert features to Collection database
         */
        $inserted = array();
        $alreadyInDatabase = array();
        $inError = array();
        $status = 'success';
        for($i = 0, $l = count($resources); $i < $l; $i++) {
            
            if (!is_array($resources[$i]['features'])) {
                throw new Exception('Invalid posted file(s)', 500);
            }
            
            for($j = 0, $k = count($resources[$i]['features']); $j < $k; $j++) {
                
                /*
                 * Process unitary feature
                 */
                $feature = $resources[$i]['features'][$j];
                
                /*
                 * Get remapped properties
                 */
                $properties = $this->remap($feature['properties']);
                
                /*
                 * Check that resource does not already exist in database
                 */
                if ($this->resourceExists($properties['identifier'])) {
                    array_push($alreadyInDatabase, $properties['identifier']);
                    $status = 'partially';
                    continue;
                }
                
                /*
                 * !! VERY IMPORTANT !!
                 * 
                 * It is assumes that GeoJSON property names are the same as
                 * the property names of the RESTo model. This is the way we
                 * are able to guess the equivalent column name in the collection
                 * database
                 */
                $keys = array();
                $values = array();
                foreach ($properties as $key => $value) {
                    $columnName = getModelName($this->description['model'], $key);
                    if ($columnName) {
                        array_push($keys, pg_escape_string($columnName));
                        $columnType = getRESToType(getModelType($this->description['model'], $key));
                        array_push($values, $columnType === 'numeric' ? pg_escape_string($value) : '\'' . pg_escape_string($value) . '\'');
                    }
                }
                
                /*
                 * Special columns
                 */
                $wkt = geoJSONGeometryToWKT($feature['geometry']);
                array_push($keys, getModelName($this->description['model'], 'updated'));
                array_push($values, 'now()');
                array_push($keys, getModelName($this->description['model'], 'geometry'));
                array_push($values, 'ST_GeomFromText(\'' . $wkt . '\', 4326)');
                
                /*
                 * Tag metadata
                 */
                if ($this->iTag) {
                    $tags = $this->tagResource($wkt);
                    if ($tags) {
                        array_push($keys, getModelName($this->description['model'], 'keywords'));
                        array_push($values, $tags);
                    }
                }
                
                try {
                    $query = pg_query($this->dbh, 'INSERT INTO ' .  $this->Controller->getDbConnector()->getSchema() . '.' . $this->Controller->getDbConnector()->getTable() . ' (' . join(',', $keys) . ') VALUES (' . join(',', $values) . ')');
                    if (!$query) {
                        throw new Exception();
                    }
                } catch (Exception $e) {
                    array_push($inError, $properties['identifier']);
                    $status = 'error';
                    continue;
                }
                array_push($inserted, $properties['identifier']);
            }
            
        }
        
        return array('Status' => $status, 'Message' => count($inserted) . ' resources inserted', 'Inserted' => $inserted,'AlreadyInDatabase' => $alreadyInDatabase, 'InError' => $inError);
    }

    /**
     * Update an existing resource
     * 
     * TODO - Not implemented yet
     */
    public function update() {
        throw new Exception('Not Implemented', 501);
    }

    /**
     * Delete an existing resource
     * 
     * Note : deletion is voluntary logical. Physical deletion (i.e. drop schema and tables) must
     * be done manually by database administrator
     */
    public function delete() {
        throw new Exception('Not Implemented', 501);
    }

    /**
     * Check if resource $identifier exists within collection database
     * 
     * @param string $identifier - resource unique identifier 
     */
    private function resourceExists($identifier) {
        
        $results = pg_query($this->dbh, 'SELECT 1 FROM ' . $this->Controller->getDbConnector()->getSchema() . '.' . $this->Controller->getDbConnector()->getTable() . ' WHERE ' . getModelName($this->description['model'], 'identifier') . '=\'' . pg_escape_string($identifier) . '\'');
        
        if (!$results) {
            throw new Exception('Database connection error', 500);
        }
        while ($result = pg_fetch_assoc($results)) {
            return true;
        }

        return false;
    }
    
    /**
     * Tag POLYGON WKT
     * 
     * @param type $wkt
     */
    private function tagResource($wkt) {
    
        if (substr(strtolower($wkt), 0, 7) !== 'polygon') {
            return null;
        }

        /*
         * Call iTag
         */
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->iTag . '&ordered=true&footprint=' . urlencode($wkt));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $json = json_decode(curl_exec($curl), true);
        curl_close($curl);

        if (!is_array($json) || !is_array($json['features'])) {
            return null;
        }

        /*
         * "properties":{
         * "political":{
         *  "countries":[
         *      {"name":"Switzerland","pcover":54.74},
         *      {"name":"France","pcover":25.48},
         *      {"name":"Italy","pcover":19.76}
         *  ],
         *  "continents":[
         *      "Europe"
         *  ]
         */
        $pairs = array();
        $properties = $json['features'][0]['properties'];
        if ($properties['political']) {
            foreach (array_values($properties['political']['continents']) as $continent) {
                array_push($pairs, $this->quoteForHstore('continent:' . $continent) . '=>NULL');
            }
            foreach (array_values($properties['political']['countries']) as $country) {
                array_push($pairs, $this->quoteForHstore('country:' . $country['name']) . '=>"' . $country['pcover'] . '"');
            }
            foreach (array_values($properties['political']['cities']) as $city) {
                array_push($pairs, $this->quoteForHstore('city:' . $city) . '=>NULL');
            }
        }
        if ($properties['landCover']) {
            foreach (array_values($properties['landCover']['landUse']) as $landuse) {
                array_push($pairs, $this->quoteForHstore('landuse:' . $landuse['name']) . '=>"' . $landuse['pcover'] . '"');
            }
            foreach (array_values($properties['landCover']['landUseDetails']) as $landuse) {
                array_push($pairs, $this->quoteForHstore('landuse_details:' . $landuse['name']) . '=>"' . $landuse['pcover'] . '"');
            }
        }
        return '\'' . pg_escape_string(join(',', $pairs)) . '\'';
    }
    
    /**
     * Quote string for hstore
     * 
     * @param type $string
     */
    private function quoteForHstore($string) {
        $string = trim($string);
        $splitted = split(' ', $string);
        $quote = count($splitted) > 1 ? '"' : '';
        return $quote . strtolower($string) . $quote;
        
    }
    
    /**
     * Remap properties array accordingly to $Controller::$inputPropertiesMapping
     * 
     * @param array $properties
     */
    private function remap($properties) {

        /*
         * Rewrite feature if Controller::inputPropertiesMapping
         */
        if (property_exists($this->Controller, 'inputPropertiesMapping')) {
            $Controller = $this->Controller;
            foreach ($Controller::$inputPropertiesMapping as $key => $modelName) {
                if (isset($properties[$key])) {
                    $properties[$modelName] = $properties[$key];
                    unset($properties[$key]);
                }
            }
        }
        
        return $properties;
    }

}