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
 * User description
 */
class RestoUser {
    
    /**
     * Reference to user rights array 
     */
    private $rights = array();
    
    /**
     * Reference to the user profile
     */
    private $profile = array();
    
    /**
     * Constructor retrieves user rights for all collections
     * and stores it within $this->rights array
     * 
     * @param Object $R - RESTo object
     * @param String $userid - user unique identifier (should be an email)
     * 
     */
    final public function __construct($R, $userid) {
        
        if (!isset($userid)) {
            $userid = 'default';
        }
        
        /*
         * Previously retrieved 'rights' should be stored within session
         * otherwise retrieves rights from database
         */
        if (!isset($_SESSION['rights']) || count($_SESSION['rights']) === 0) {
            $_SESSION['rights'] = array();            
            $dbh = $R->getDatabaseConnectorInstance()->getConnection(true);
            if (!$dbh) {
                throw new Exception('Database connection error', 500);
            }
            $rights = pg_query($dbh, 'SELECT groupid, collection, rights from admin.rights WHERE groupid=\'' . pg_escape_string($userid). '\'');
            if (!$rights) {
                pg_close($dbh);
                throw new Exception('Database connection error', 500);
            }

            while ($right = pg_fetch_assoc($rights)) {
                $_SESSION['rights'][$right['collection']] = json_decode($right['rights'], true);
            }
        }
        
        $this->rights = $_SESSION['rights'];
        
    }
    
    /*
     * Get rights filters for a given collection
     *
     * @param {String} collection : name of the collection
     * @param {String} method - HTTP method 'get', 'post', 'put' or 'delete'
     * @param {String} action - 'search', 'visualize' or 'download'
     * @return {Array} filters
     */
    public function getRights($collection, $method, $action = null) {
        
        /*
         * Unlikely cases
         */
        if (!isset($collection) || !isset($method) || !isset($this->rights[$collection]) || !isset($this->rights[$collection][$method])) {
            return array(
                "enabled" => false
            );
        }
        
        /*
         * Action cases
         */
        if (isset($action)) {
            if (isset($this->rights[$collection][$method][$action])) {
                return $this->rights[$collection][$method][$action];
            }
            else {
                return array(
                    "enabled" => false
                );
            }
        }
        
        return $this->rights[$collection][$method];
    }
    
    /*
     * Get the user profile
     *      guest or registered 
     *
     * @return {String} profile
     */
    public function getProfile() {
        return $this->profile;
    }
    
}