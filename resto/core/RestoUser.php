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
    
    /*
     * Database connector instance
     */
    private $dbConnector = null;
    
    /*
     * User profile
     */
    private $profile = array(
        'userid' => -1,
        'groupid' => 'default'
    );
    
    /*
     * Reference to user rights array 
     */
    private $rights = array();
    
    /*
     * SSO server configuration (i.e. copy of resto.ini [sso] block)
     */
    private $sso = null;
    
    /*
     * Default groups rights
     */
    private $defaultRights = array(
        'default' => array(
            'get' => true,
            'post' => false,
            'put' => false,
            'delete' => false,
            'search' => true,
            /*'search' => array(
                'exclude' => array(
                    'keywords' => array(
                        'city:toulouse'
                    )
                )
            ),*/
            'visualize' => false,
            'download' => false,
            'tag' => false,
            'rights' => false
        ),
        'admin' => array(
            'get' => true,
            'post' => true,
            'put' => true,
            'delete' => true,
            'search' => true,
            'visualize' => true,
            'download' => true,
            'tag' => true,
            'rights' => true
        )
    );

    /**
     * Constructor retrieves user rights for all collections
     * and stores it within $this->rights array
     * 
     * @param Object $dbConnector - DatabaseConnector instance
     * @param Array options - 
     *          'forceAuth' => true to force authentication/rights refresh even if session is set
     *          'sso' => SSO configuration array
     *          'access_token' => oauth token
     */
    final public function __construct($dbConnector, $options = array()) {
        
        $this->dbConnector = $dbConnector;
        
        $forceAuth = isset($options['forceAuth']) ? $options['forceAuth'] : false;
        
        /*
         * SSO configuration
         */
        $this->sso = isset($options['sso']) ? $options['sso'] : null;
        
        /*
         * Authenticate if not already done
         */
        if ($forceAuth || !isset($_SESSION['profile']) || count($_SESSION['profile']) === 0) {
            $this->authenticate(isset($options['access_token']) ? $options['access_token'] : null);
        }
        else {
            $this->profile = $_SESSION['profile'];
        }
        
        /*
         * Refresh rights from database if not set within session
         */
        if ($forceAuth || !isset($_SESSION['rights']) || count($_SESSION['rights']) === 0) {
            $this->refreshRights();
        }
        else {
            $this->rights = $_SESSION['rights'];
        }
        
    }

    /**
     * Get rights from the database and update $_SESSION['rights']
     */
    public function refreshRights() {
        
        $_SESSION['rights'] = array(
            'default' => isset($this->defaultRights[$this->profile['groupid']]) ? $this->defaultRights[$this->profile['groupid']] : $this->defaultRights['default'],
            'collections' => array()
        );

        $dbh = $this->dbConnector->getConnection(true);
        if (!$dbh) {
            throw new Exception('Database connection error', 500);
        }
        $rights = pg_query($dbh, 'SELECT groupid, collection, rights from admin.rights WHERE groupid=\'' . pg_escape_string($this->profile['groupid']) . '\'');
        if (!$rights) {
            pg_close($dbh);
            throw new Exception('Database connection error', 500);
        }

        while ($right = pg_fetch_assoc($rights)) {
            $_SESSION['rights']['collections'][$right['collection']] = json_decode($right['rights'], true);
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
        $validMethods = array('get', 'post', 'put', 'delete');
        $validActions = array('search', 'download', 'visualize', 'tag', 'rights');
        if (!isset($method) || !in_array($method, $validMethods)) {
            return false;
        }
        
        /*
         * Unknown collection or no rights on collection
         *   => apply default rights
         */
        if (!isset($collection) || !isset($this->rights['collections'][$collection]) || !isset($this->rights['collections'][$collection][$method])) {
            return isset($action) && in_array($action, $validActions) ? $this->rights['default'][$action] : $this->rights['default'][$method];
        }

        /*
         * Action cases
         */
        if (isset($action) && in_array($action, $validActions)) {
            if (isset($this->rights['collections'][$collection][$action])) {
                return $this->rights['collections'][$collection][$action];
            } else {
                return $this->rights['default'][$action];
            }
        }

        return $this->rights['collections'][$collection][$method];
    }

    /*
     * Return true if user can POST
     */
    public function canPOST($collection = null) {
        $rights = $this->getRights($collection, 'post');
        return is_bool($rights) ? $rights : true;
    }
    
    /*
     * Return true if user can PUT
     */
    public function canPUT($collection = null) {
        $rights = $this->getRights($collection, 'put');
        return is_bool($rights) ? $rights : true;
    }
    
    /*
     * Return true if user can DELETE
     */
    public function canDELETE($collection = null) {
        $rights = $this->getRights($collection, 'delete');
        return is_bool($rights) ? $rights : true;
    }
    
    /*
     * Return true if user can Tag resources or collection
     */
    public function canTag($collection = null) {
        $rights = $this->getRights($collection, 'post', 'tag');
        return is_bool($rights) ? $rights : true;
    }
    
    /*
     * Return true if user can Tag resources or collection
     */
    public function canChangeRights($collection = null) {
        $rights = $this->getRights($collection, 'post', 'rights');
        return is_bool($rights) ? $rights : true;
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

    /**
     * Authenticate user
     */
    private function authenticate($access_token = null) {
        
        /*
         * Authentication through oauth
         */
        if (isset($this->sso) && isset($access_token)) {
            
            /*
             * Get user profile
             */
            $ch = curl_init($this->sso['userInfoUrl']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $access_token));
            $userInfo = json_decode(curl_exec($ch), true);
            curl_close($ch);
            
            /*
             * Wrong credential -> user is not authenticated
             */
            if (!$userInfo || !$userInfo[$this->sso['uidKey']]) {
                $_SESSION['profile'] = array(
                    'userid' => -1,
                    'groupid' => 'default'
                );
                $this->profile = $_SESSION['profile'];
                return true;
            }
            
            $email = pg_escape_string(trim(strtolower($userInfo[$this->sso['uidKey']])));

            $dbh = $this->dbConnector->getConnection(true);
            if (!$dbh) {
                throw new Exception('Database connection error', 500);
            }

            /*
             * User does not exist, create it in database
             */
            $results = pg_query($dbh, 'SELECT 1 FROM admin.users WHERE email=\'' . $email . '\'');
            if (!$results) {
                throw new Exception('Database connection error', 500);
            }
            if (!pg_fetch_assoc($results)) {
                $groups = 'default';
                $activationcode = md5($email + microtime());
                $results = pg_query($dbh, 'INSERT INTO admin.users (email,groups,password,activationcode,activated,registrationdate) VALUES (\'' . $email . '\',\'' . $groups . '\',\'' . str_repeat('*', 32) . '\',\'' . $activationcode . '\', TRUE, now()) RETURNING userid');
                if (!$results) {
                    pg_close($dbh);
                    throw new Exception('Database connection error', 500);
                }
            }

            $where = 'email=\'' . $email . '\'';
        }
        /*
         * Basic authentication
         */
        else {
            $where = 'email=\'' . pg_escape_string(strtolower($_SERVER['PHP_AUTH_USER'])) . '\' AND password=\'' . pg_escape_string(md5($_SERVER['PHP_AUTH_PW'])) . '\'';
        }
        
        $dbh = $this->dbConnector->getConnection(true);
        if (!$dbh) {
            throw new Exception('Database connection error', 500);
        }
        $profiles = pg_query($dbh, 'SELECT userid, email, groups, username, givenname, lastname, password from admin.users WHERE ' . $where . ' AND activated = TRUE');
        if (!$profiles) {
            pg_close($dbh);
            throw new Exception('Database connection error', 500);
        }
        $profile = pg_fetch_assoc($profiles);
        if ($profile) {
            $_SESSION['profile'] = array(
                'userid' => $profile['userid'],
                'email' => $profile['email'],
                'userhash' => md5($profile['email']),
                'groupid' => $profile['groups'],
                'username' => $profile['username'],
                'givenname' => $profile['givenname'],
                'lastname' => $profile['lastname'] 
            );
        }
        else {
            $_SESSION['profile'] = array(
                'userid' => -1,
                'groupid' => 'default'
            );
        }
        pg_close($dbh);
        
        /*
         * Add oauth token if defined
         */
        if (isset($access_token)) {
            $_SESSION['profile']['access_token'] = $access_token;
        }
        $this->profile = $_SESSION['profile'];
        
        return true;
        
    }
    
    public function disconnect() {
        unset($_SESSION['profile'], $_SESSION['rights']);
    }
}
