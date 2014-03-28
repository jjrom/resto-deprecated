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
        $this->dbh = $this->Controller->getDbConnector()->getConnection(true);
        
    }

    /**
     *
     * Tag resources
     *
     * @param array $options with the following structure
     *
     *              array(
     *                  // This is optional to constrain resources to tag
     *                  'query' => array(
     *
     *                  ),
     *                  'tags' => array(
     *                      'tag1',
     *                      'tag2',
     *                      etc.
     *                  )
     *              )
     * @return type
     * @throws Exception
     */
    public function tag($options) {

        throw new Exception('Not implemented yet', 401);

        if (!$this->dbh) {
            throw new Exception('Database connection error', 500);
        }

        /*
         * Only authenticated user can add tags
         * TODO : anybody should post ?
         */
        if (!$this->Controller->getParent()->checkAuth()) {
            throw new Exception('Unauthorized', 401);
        }

        /*
         * This should not happens
         */
        if (!is_array($options)) {
            throw new Exception('Nothing to tag', 500);
        }
    }

}
