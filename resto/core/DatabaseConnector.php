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

/*
 * Class to store RESTo database configuration
 */

class DatabaseConnector {
    
    /*
     * Not used (yet)
     */
    public $postgisVersion = 2;
    
    /*
     * Object to store default configuration
     */
    public $config = array(
        'dbname' => 'resto',
        'host' => 'localhost',
        'port' => '5432',
        'schema' => 'public',
        'table' => 'products', // Default name of collection table within a schema
        'user' => 'resto',
        'suser' => 'sresto',
        'resultsPerPage' => 50, // Number of results returned by page
        'maximumResultsPerPage' => 500 // Maximum number of results returned by page
    );

    /**
     * Create database connector object from RESTo database configuration
     * Input structure should contain parts or the totality of $defaultStructure
     * parameters + 'user', 'password', 'suser' and 'spassword' parameters
     * 
     * @param array $config
     *
     */
    public function __construct($config = array()) {
        $this->update($config);
    }

    /**
     * 
     * Update database parameters
     * 
     * @param string $name
     * @param array $config
     */
    public function update($config = array()) {
        foreach($config as $key => $value) {
            $this->config[$key] = $value;
        }
    }
    
    /**
     * Return a connection object to the database
     * 
     * @param boolean $admin (if true set a connection with write privilege (if defined))
     */
    public function getConnection($admin = false) {
        return pg_connect('host=' . $this->config['host'] . ' port=' . $this->config['port'] . ' dbname=' . $this->config['dbname'] . ' user=' . $this->config[$admin ? 'suser' : 'user'] . ' password=' . $this->config[$admin ? 'spassword' : 'password']);    
    }
    
    /**
     * Get schema name for database $name or
     * 'public' if schema is not found
     * 
     * @param string $name
     */
    public function getSchema() {
       return $this->config['schema'];
    }
    
    /**
     * Return number of results returned by page
     * 
     * @param string $name
     */
    public function getResultsPerPage() {
        return intval($this->config['resultsPerPage']);
    }
    
    /**
     * Return maximum number of results returned by page
     */
    public function getMaximumResultsPerPage() {
        return intval($this->config['resultsPerPage']);
    }
    
    /**
     * Return products table number for database $name
     * 
     * @param string $name
     */
    public function getTable() {
        return $this->config['table'];
    }
    
    /**
     * Return config parameters (except passwords and suser name)
     * 
     * @param string $name
     */
    public function get($param) {
        if ($param === 'suser' || $param === 'password' || $param === 'spassword') {
            return null;
        }
        else {
            return $this->config[$param];
        }
    }
    
}