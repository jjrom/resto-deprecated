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
 * Rights manager module
 *
 * This class allows to manage user and group rights for collections
 *  - add/update a new entry in admin.rights table
 *  - delete an existing entry
 *
 * See $RESTO_HOME/_examples/rights/rights_Example.json
 *
 */
class RightsManager {

    private $dbh;
    private $Controller;
    private $request;

    /**
     * Constructor
     *
     * @param Object $Controller - RestoController instance
     *
     */
    public function __construct($Controller) {

        /*
         * Get module configuration and set collection name
         */
        $config = $Controller->getParent()->getModuleConfig('RightsManager');
        $this->Controller = $Controller;
        $this->request = $Controller->getParent()->getRequest();

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
         * Get DatabaseConnector instance
         */
        $this->dbh = $this->Controller->getParent()->getDatabaseConnectorInstance()->getConnection(true);

    }

    /**
     *
     * Called by HTTP POST
     *
     * Add/Update an entry in admin.rights
     */
    public function add($arr) {

        if (!$this->dbh) {
            throw new Exception('Database connection error', 500);
        }
        
        /*
         * Only authenticated user can change rights
         */
        if (!$this->Controller->getParent()->getUser()->canChangeRights($this->description['name'])) {
            throw new Exception('Unauthorized', 401);
        }
        
        /*
         * Request is an array of rights file
         */
        $count = 0;
        for ($j = count($arr); $j--;) {
            $entry = $arr[$j];
            if ($entry['groupid'] && $entry['rights']) {
                $values= array(
                    '\'' . pg_escape_string($this->request['collection']) . '\'',
                    '\'' . pg_escape_string($entry['groupid']) . '\'',
                    '\'' . pg_escape_string(json_encode($entry['rights'])) . '\''
                );

                /*
                 * Update database
                 */
                try {
                    /*
                     * Delete existing entry
                     */
                    if ($this->entryExists($this->request['collection'], $entry['groupid'])) {
                        pg_query($this->dbh, 'DELETE FROM admin.rights WHERE collection=\'' . pg_escape_string($this->request['collection']) . '\' AND groupid=\'' . pg_escape_string($entry['groupid']) . '\'');
                    }
                    $query = pg_query($this->dbh, 'INSERT INTO admin.rights (collection, groupid, rights) VALUES(' . join(',', $values) . ')');
                    if (!$query) {
                        throw new Exception();
                    }
                } catch (Exception $e) {
                    throw new Exception('Error : cannot update rights', 500);
                }
                $count++;
            }

        }

        return array('Status' => 'success', 'Message' => $count . ' rights updated for collection ' . $this->request['collection']);

    }

    /**
     * Delete an existing rights entry in admin.rights table
     */
    public function delete() {

        if (!$this->dbh) {
            throw new Exception('Database connection error', 500);
        }

        /*
         * Collection must be defined
         */
        if (!$this->request['collection']) {
            throw new Exception('Method Not Allowed', 405);
        }

        /*
         * groupid must be defined
         */
        if (!$this->request['params']['groupid']) {
            throw new Exception('Forbidden', 403);
        }

        /*
         * Delete entry
         */
        try {
            $query = pg_query($this->dbh, 'DELETE FROM admin.rights WHERE collection=\'' . pg_escape_string($this->request['collection']) . '\' AND groupid=\'' . pg_escape_string($this->request['params']['groupid']) . '\'');
            if (!$query) {
                throw new Exception();
            }
        } catch (Exception $e) {
            throw new Exception('Error : cannot update rights', 500);
        }

        return array('Status' => 'success', 'Message' => 'Rights deleted for collection "' . $this->request['collection'] . '" and group "' . $this->request['params']['groupid'] . '"');

    }

    /**
     * Check if entry collection/groupid exists within admin.rights table
     *
     * @param string $collection - collection name
     * @param string $groupid - groupid
     */
    private function entryExists($collection, $groupid) {

        $results = pg_query($this->dbh, 'SELECT collection FROM admin.rights WHERE collection=\'' . pg_escape_string($collection) . '\' AND groupid=\'' . pg_escape_string($groupid) . '\'');
        if (!$results) {
            throw new Exception('Database connection error', 500);
        }
        while ($result = pg_fetch_assoc($results)) {
            return true;
        }

        return false;
    }
}
