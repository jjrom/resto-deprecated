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
 * Collection manager module
 * 
 * This class allows to manage collection :
 *  - create a new collection - i.e. create a collection table within RESTo database 
 *  - update an existing collection
 *  - delete an existing collection
 * 
 */
class CollectionManager {

    private $dbh;
    private $request;
    private $collection;
    private $user;

    /**
     * Constructor
     * 
     * By default a $name table is created under $schema schema.
     * 
     * The table contains every columns defined within the RESTo model as defined in DefaultController
     *  
     * @param Object $R - RESTo instance
     * 
     */
    public function __construct($R) {

        /*
         * Get module configuration and set collection name
         */
        $config = $R->getModuleConfig('CollectionManager');
        $r = $R->getRequest();
        $this->collection = $r['collection'];

        /*
         * If secure option is set, only HTTPS requests are processed
         */
        if (!$config || ($config['secure'] && $_SERVER['HTTPS'] !== 'on')) {
            throw new Exception('This service supports https only', 403);
        }

        /*
         * Only authenticated user can post files
         * TODO - set authorization level within database (i.e. canPost, canPut, canDelete, etc. ?)
         */
        if (!$R->checkAuth()) {
            throw new Exception('Unauthorized', 401);
        }

        /*
         * Case 1.
         *  POST file
         */
        $this->request = array();
        if (count($_FILES) === 1 && $_FILES['file']) {
            if (count($_FILES['file']) > 0) {
                if (is_uploaded_file($_FILES['file']['tmp_name'][0])) {
                    $this->request = json_decode(join('', file($_FILES['file']['tmp_name'][0])), true);
                }
            }
        }
        /*
         * Case 2.
         *  POST parameters through key=value
         *  where
         *      - 'key' should be equal to 'data'
         *      - 'value' should be an encoded JSON string
         */
        else {
            $request = $R->getRequest();
            if ($request['params'] && $request['params']['data']) {
                $this->request = json_decode(urldecode($request['params']['data']), true);
            }
            else {
                $this->request = $request['params'];
            }
        }

        /*
         * Get DatabaseConnector instance
         */
        if ($config && $config['activate']) {
            $dbConnector = $R->getDatabaseConnectorInstance();
            if (is_array($config['db'])) {
                $dbConnector->update($config['db']);
            }
            $this->dbh = $dbConnector->getConnection(true);
            $this->user = $dbConnector->get('user');
        }
    }

    /**
     * 
     * Called by HTTP POST
     * 
     * Build function performs the following tasks :
     * 
     *  1. Create schema with name = strtolower($name)
     *      => Throw exception if schema already exist
     * 
     *  2. Create table $table under schema $schema
     *      => Default table name is 'products'
     * 
     *  3. Create collection configuration file under TBD
     * 
     *  4. Add collection to RESTo admin.collections table
     */
    public function create() {

        if (!$this->dbh) {
            throw new Exception('Database connection error', 500);
        }

        /*
         * Collection name must be set in request
         */
        if (!$this->request['name']) {
            throw new Exception('Collection name must be defined', 500);
        }

        /*
         * Collection name starting with '$' is not permitted
         * (special action - see Resto.php)
         */
        if (substr($this->request['name'], 0, 1) === '$') {
            throw new Exception('Collection name cannot start with "$" character', 500);
        }

        /*
         * Collection name '_ALL_' is reserved (see rights management)
         */
        if ($this->request['name'] === '_ALL_') {
            throw new Exception('_ALL_ is a reserved name and cannot be used for Collection name', 500);
        }
        
        /*
         * Collection must not already exist
         */
        if ($this->collectionExists($this->request['name'])) {
            throw new Exception('Collection already exists', 500);
        }

        /*
         * Set controller to DefaultController if not set
         */
        $controller = isset($this->request['controller']) ? $this->request['controller'] : 'DefaultController';

        /*
         * Controller must exist !!
         */
        if (!class_exists($controller)) {
            throw new Exception('Controller ' . $controller . ' is no defined', 500);
        }

        /*
         * Request should contain a dbDescription array
         */
        if (!is_array($this->request['dbDescription'])) {
            $this->request['dbDescription'] = array();
        }

        /*
         * Set database name to resto database name
         */
        if (!$this->request['dbDescription']['dbname']) {
            $this->request['dbDescription']['dbname'] = pg_dbname($this->dbh);
        }

        /*
         * Set database host name
         */
        if (!$this->request['dbDescription']['host']) {
            $this->request['dbDescription']['host'] = pg_host($this->dbh);
        }

        /*
         * Set database port
         */
        if (!$this->request['dbDescription']['port']) {
            $this->request['dbDescription']['port'] = pg_port($this->dbh);
        }

        /*
         * Set default schema name to name if not set
         */
        if (!$this->request['dbDescription']['schema']) {
            $this->request['dbDescription']['schema'] = strtolower($this->request['name']);
        }

        /*
         * Set default table name to 'products' if not set
         */
        if (!$this->request['dbDescription']['table']) {
            $this->request['dbDescription']['table'] = 'products';
        }

        /*
         * Database creation
         *      Create $schema
         *      Create $schema.$table
         * 
         * A rollback is sent on all above steps if one step is on error
         */
        if ($this->request['createdb']) {

            /*
             * RESTo cannot create schema+table in another database than itself !
             */
            if (pg_dbname($this->dbh) !== $this->request['dbDescription']['dbname'] || pg_port($this->dbh) !== $this->request['dbDescription']['port'] || pg_host($this->dbh) !== $this->request['dbDescription']['host']) {
                throw new Exception('Cannot create schema outside of ' . pg_dbname($this->dbh) . ' database', 500);
            }

            /*
             * Schema should not already exist
             * 
             * Note : no need to check if table does not exist in schema aferwards
             * because is schema does not exist, table cannot exist either
             */
            if ($this->schemaExists($this->request['dbDescription']['schema'])) {
                throw new Exception('Schema already exits', 500);
            }

            /*
             * Initialize model with Controller model model description
             * and superseeded values with request['model'] if set
             */
            $model = $controller::$model;
            if ($this->request['model']) {
                foreach ($this->request['model'] as $searchFiltersDescriptionName => $columnName) {
                    $model[$searchFiltersDescriptionName] = $columnName;
                }
            }

            /*
             * Prepare one column for each key entry in model if value is prefixed by 'db:'
             */
            $creationFields = array();
            $table = array();
            foreach ($model as $searchFiltersDescriptionName => $columnName) {
                if ($columnName) {

                    /*
                     * geometry column is processed through AddGeometryColumn mechanism
                     * to support PostGIS < 2.0
                     */
                    if ($searchFiltersDescriptionName === 'geometry') {
                        continue;
                    }

                    /*
                     * Get column type
                     */
                    $columnType = getModelType($model, $searchFiltersDescriptionName);

                    /*
                     * Array should contains
                     *  - "column" : the name of the column in table (mandatory)
                     *  - "template" : a template to replace the value with (optional)
                     *  - "type" : SQL type of the column (optional) 
                     */
                    if (is_array($columnName)) {
                        if (isset($columnName['dbKey'])) {
                            if ($columnType) {
                                $creationFields[substr($columnName['dbKey'], 3, strlen($columnName['dbKey']) - 1)] = $columnType;
                            }
                            continue;
                        }
                    }
                    if ($columnType && substr($columnName, 0, 3) === 'db:') {
                        $creationFields[substr($columnName, 3, strlen($columnName) - 1)] = $columnType;
                    }
                }
            }
            foreach ($creationFields as $field => $type) {
                array_push($table, $field . ' ' . $type);
            }

            /*
             * Transaction =
             *  - create schema and table
             *  - create indices
             *  - grant select privileges to resto user
             */
            pg_query($this->dbh, 'BEGIN');
            pg_query($this->dbh, 'CREATE SCHEMA ' . $this->request['dbDescription']['schema']);
            pg_query($this->dbh, 'CREATE TABLE ' . $this->request['dbDescription']['schema'] . '.' . $this->request['dbDescription']['table'] . ' (' . join(',', $table) . ')');
            if (isset($model['geometry'])) {
                pg_query($this->dbh, 'SELECT AddGeometryColumn(\'' . $this->request['dbDescription']['schema'] . '\', \'' . $this->request['dbDescription']['table'] . '\', \'' . getModelName($model, 'geometry') . '\', \'4326\',\'' . getModelType($model, 'geometry') . '\', 2)');
            }

            /*
             * Create indices on identifier, platform, resolution, geometry, startDate, completionDate and keywords
             * 
             * The first loop is needed to avoid eventual duplication of index creation
             * when various model properties are linked to one database column 
             */
            $modelNames = array(
                'identifier' => 'btree',
                'platform' => 'btree',
                'resolution' => 'btree',
                'startDate' => 'btree',
                'completionDate' => 'btree',
                'geometry' => 'gist',
                'keywords' => 'gin'
            );
            $indices = array();
            foreach ($modelNames as $key => $indexType) {
                $md = getModelName($model, $key);
                if ($md) {
                    $indices[$md] = $indexType;
                }
            }
            foreach ($indices as $key => $indexType) {
                pg_query($this->dbh, 'CREATE INDEX ' . $this->request['dbDescription']['table'] . '_' . $key . '_idx ON ' . $this->request['dbDescription']['schema'] . '.' . $this->request['dbDescription']['table'] . ' USING ' . $indexType . ' (' . $key . ')');
            }

            pg_query($this->dbh, 'GRANT ALL ON SCHEMA ' . $this->request['dbDescription']['schema'] . ' TO ' . $this->user);
            pg_query($this->dbh, 'GRANT SELECT ON TABLE ' . $this->request['dbDescription']['schema'] . '.' . $this->request['dbDescription']['table'] . ' TO ' . $this->user);
            pg_query($this->dbh, 'COMMIT');

            /*
             * Rollback on error
             */
            if (!$this->schemaExists($this->request['dbDescription']['schema'])) {
                pg_query($this->dbh, 'ROLLBACK');
                throw new Exception('Cannot create table ' . $this->request['dbDescription']['schema'] . '.' . $this->request['dbDescription']['table'], 500);
            }
        }

        /*
         * Prepare json configuration string i.e. 
         *  {
         *      "model":
         *      "searchFiltersDescription":
         *      "searchFiltersList":
         *      "dictionary_*":
         *  }
         */
        $json = array();
        foreach ($this->request as $key => $value) {
            if ($key === 'model' || $key === 'searchFiltersDescription' || $key === 'searchFiltersList' || substr($key, 0, 11) === 'dictionary_') {
                $json[$key] = $value;
            }
        }

        /*
         * Insert collection within collections table
         * 
         * CREATE TABLE admin.collections (
         *  collection          VARCHAR(50) PRIMARY KEY,
         *  creationdate        TIMESTAMP,
         *  controller          VARCHAR(50) DEFAULT 'DefaultController',
         *  theme               VARCHAR(50),
         *  status              VARCHAR(10) DEFAULT 'public',
         *  dbname              VARCHAR(10) DEFAULT 'resto',
         *  hostname            VARCHAR(50) DEFAULT 'localhost',
         *  port                VARCHAR(5)  DEFAULT '5432',
         *  schemaname          VARCHAR(20) NOT NULL,
         *  tablename           VARCHAR(20) DEFAULT 'products',
         *  configuration       TEXT
         * );
         * 
         */
        $collectionFields = array(
            '\'' . pg_escape_string($this->request['name']) . '\'',
            'now()',
            '\'' . pg_escape_string($controller) . '\'',
            '\'' . (isset($this->request['theme']) ? pg_escape_string($this->request['theme']) : 'default') . '\'',
            '\'' . (isset($this->request['status']) ? pg_escape_string($this->request['status']) : 'public') . '\'',
            '\'' . pg_escape_string($this->request['dbDescription']['dbname']) . '\'',
            '\'' . pg_escape_string($this->request['dbDescription']['host']) . '\'',
            '\'' . pg_escape_string($this->request['dbDescription']['port']) . '\'',
            '\'' . pg_escape_string($this->request['dbDescription']['schema']) . '\'',
            '\'' . pg_escape_string($this->request['dbDescription']['table']) . '\'',
            '\'' . pg_escape_string(json_encode($json)) . '\''
        );

        pg_query($this->dbh, 'BEGIN');
        pg_query($this->dbh, 'INSERT INTO admin.collections (collection, creationdate, controller, theme, status, dbname, hostname, port, schemaname, tablename, configuration) VALUES(' . join(',', $collectionFields) . ')');
        $this->insertOpenSearchDescription();
        pg_query($this->dbh, 'COMMIT');

        /*
         * Rollback on error
         */
        if (!$this->collectionExists($this->request['name'])) {
            pg_query($this->dbh, 'ROLLBACK');
            throw new Exception('Cannot insert collection ' . $this->request['name'] . ' in resto collections table', 500);
        }
        
        /*
         * Insert rights for collection
         * 
         * Default rights are :
         *   - 'search' service is enabled without restriction
         *   - 'visualize' service is disabled
         *   - 'download' service is disabled
         *   - PUT, POST and DELETE services are disabled
         *
        $rights = array(
            'get' => array(
                'search' => array(
                    'enabled' => true
                ),
                'visualize' => array(
                    'enabled' => false
                ),
                'download' => array(
                    'enabled' => false
                )
            ),
            'post' => array(
                'enabled' => false
            ),
            'put' => array(
                'enabled' => false
            ),
            'delete' => array(
                'enabled' => false
            )
        );
        
        if (isset($this->request['rights'])) {
            $rights = $this->request['rights'];
        }
        $values= array(
            '\'' . pg_escape_string($this->request['name']) . '\'',
            '\'' . pg_escape_string('default') . '\'',
            '\'' . pg_escape_string(json_encode($rights)) . '\''
        );
        try {
            pg_query($this->dbh, 'DELETE FROM admin.rights WHERE collection=\'' . pg_escape_string($this->request['name']) . '\' AND groupid=\'default\'');
            $query = pg_query($this->dbh, 'INSERT INTO admin.rights (collection, groupid, rights) VALUES(' . join(',', $values) . ')');
            if (!$query) {
                throw new Exception();
            }
        } catch (Exception $e) {
            throw new Exception('Error : cannot update rights', 500);
        }
        */
        return array('Status' => 'success', 'Message' => 'Collection ' . $this->request['name'] . ' created');
    }

    /**
     * 
     * HTTP PUT
     * 
     * Update an existing collection
     * 
     * TODO - Not implemented yet
     */
    public function update() {

        if (!$this->dbh) {
            throw new Exception('Database connection error', 500);
        }

        /*
         * Collection must be defined
         */
        if (!$this->collection) {
            throw new Exception('Method Not Allowed', 405);
        }

        /*
         * Collection must exist
         */
        if (!$this->collectionExists($this->collection)) {
            throw new Exception('Collection does not exist', 500);
        }
        
        $this->insertOpenSearchDescription();
        
        return array('Status' => 'success', 'Message' => 'Collection ' . $this->request['name'] . ' updated');
    }

    /**
     * Delete an existing collection
     * 
     * By default deletion is voluntary logical. Physical deletion (i.e. drop schema and tables) must
     * be done manually by database administrator
     */
    public function delete() {

        /*
         * Comment
         */
        $comment = '';
        $status = 'success';

        if (!$this->dbh) {
            throw new Exception('Database connection error', 500);
        }

        /*
         * Collection must be defined
         */
        if (!$this->collection) {
            throw new Exception('Method Not Allowed', 405);
        }

        if (!$this->collectionExists($this->collection)) {
            throw new Exception('Collection does not exist', 500);
        }

        /*
         * WARNING ! Physical deletion
         * Only available for schema within RESTo database
         */
        if ($this->request['physical']) {
            $results = pg_query($this->dbh, 'SELECT collection, dbname, schemaname, tablename FROM admin.collections WHERE collection=\'' . pg_escape_string($this->collection) . '\'');
            if (!$results) {
                throw new Exception('Database connection error', 500);
            }
            $result = pg_fetch_assoc($results);
            if ($result) {

                /*
                 * Delete (within transaction)
                 *  - entry within osdescriptions table
                 *  - entry within collections table
                 *  - collection schema
                 */
                pg_query($this->dbh, 'BEGIN');
                pg_query($this->dbh, 'DELETE FROM admin.osdescriptions WHERE collection=\'' . pg_escape_string($this->collection) . '\'');
                pg_query($this->dbh, 'DELETE FROM admin.collections WHERE collection=\'' . pg_escape_string($this->collection) . '\'');

                /*
                 * Only schema within RESTo database can be deleted
                 */
                if (pg_dbname($this->dbh) === $result['dbname'] && $result['schemaname'] !== 'public' && $result['schemaname'] !== 'admin') {

                    /*
                     * Do not drop schema if product table is not empty
                     */
                    if (!$this->tableExists($result['tablename'], $result['schemaname']) || $this->tableIsEmpty($result['tablename'], $result['schemaname'])) {
                        if ($this->schemaExists($result['schemaname'])) {
                            pg_query($this->dbh, 'DROP SCHEMA ' . $result['schemaname'] . ' CASCADE');
                        }
                    } else {
                        $comment = '. WARNING! Schema ' . $result['schemaname'] . ' not dropped (not empty)';
                        $status = 'partially';
                    }
                }

                pg_query($this->dbh, 'COMMIT');

                /*
                 * Rollback on error
                 */
                if ($this->collectionExists($this->collection)) {
                    pg_query($this->dbh, 'ROLLBACK');
                    throw new Exception('Cannot physically delete collection ' . $this->collection, 500);
                }
            }
        }

        /*
         * Logical deletion
         */ else {
            $results = pg_query($this->dbh, 'UPDATE admin.collections SET status = \'deleted\' WHERE collection=\'' . pg_escape_string($this->collection) . '\'');
            if (!$results) {
                throw new Exception('Database connection error', 500);
            }
        }

        return array('Status' => $status, 'Message' => 'Collection ' . $this->collection . ($this->request['physical'] ? ' physically' : ' logically') . ' deleted' . $comment);
    }

    /**
     * Check if collection $name exists within RESTo collections table
     * 
     * @param string $name - collection name
     */
    private function collectionExists($name) {

        $results = pg_query($this->dbh, 'SELECT collection FROM admin.collections WHERE collection=\'' . pg_escape_string($name) . '\'');
        if (!$results) {
            throw new Exception('Database connection error', 500);
        }
        while ($result = pg_fetch_assoc($results)) {
            return true;
        }

        return false;
    }

    /**
     * Check if schema $name exists within resto database
     * 
     * @param string $name - schema name
     */
    private function schemaExists($name) {

        $results = pg_query($this->dbh, 'SELECT EXISTS(SELECT 1 FROM pg_namespace WHERE nspname = \'' . $name . '\') AS exists');
        if (!$results) {
            throw new Exception('Database connection error', 500);
        }
        $result = pg_fetch_assoc($results);
        if ($result['exists'] === 't') {
            return true;
        }

        return false;
    }

    /**
     * Check if table $name exists within resto database
     * 
     * @param string $name - table name
     * @param string $schema - schema name
     */
    private function tableExists($name, $schema = 'public') {

        $results = pg_query($this->dbh, 'select EXISTS(SELECT 1 FROM pg_tables WHERE schemaname=\'' . $schema . ' \' AND tablename=\'' . $name . '\') AS exists');
        if (!$results) {
            throw new Exception('Database connection error', 500);
        }
        $result = pg_fetch_assoc($results);
        if ($result['exists'] === 't') {
            return true;
        }

        return false;
    }

    /**
     * Check if table $name is empty
     * 
     * @param string $name - table name
     * @param string $schema - schema name
     */
    private function tableIsEmpty($name, $schema = 'public') {

        $results = pg_query($this->dbh, 'SELECT EXISTS(SELECT 1 FROM ' . $schema . '.' . $name . ') AS exists');
        if (!$results) {
            throw new Exception('Database connection error', 500);
        }
        $result = pg_fetch_assoc($results);
        if ($result['exists'] === 't') {
            return false;
        }

        return true;
    }
    
    /**
     * Insert OpenSearch descriptions within osdescriptions table
     * 
     * CREATE TABLE admin.osdescriptions (
     *  collection          VARCHAR(50),
     *  lang                VARCHAR(2),
     *  shortname           VARCHAR(50),
     *  longname            VARCHAR(255),
     *  description         TEXT,
     *  tags                TEXT,
     *  developper          VARCHAR(50),
     *  contact             VARCHAR(50),
     *  query               VARCHAR(255),
     *  attribution         VARCHAR(255)
     * );
     * 
     * 
     */
    private function insertOpenSearchDescription() {
        
        /*
         * First delete existing description
         */
        pg_query($this->dbh, 'DELETE FROM admin.osdescriptions WHERE collection=\'' . pg_escape_string($this->request['name']) . '\'');
        
        /*
         * Insert one description per lang
         */
        foreach ($this->request['osDescription'] as $lang => $description) {
            $osFields = array(
                'collection',
                'lang'
            );
            $osValues = array(
                '\'' . pg_escape_string($this->request['name']) . '\'',
                '\'' . pg_escape_string($lang) . '\''
            );
            foreach (array_keys($description) as $key) {
                array_push($osFields, strtolower($key));
                array_push($osValues, '\'' . pg_escape_string($description[$key]) . '\'');
            }
            pg_query($this->dbh, 'INSERT INTO admin.osdescriptions (' . join(',', $osFields) . ') VALUES(' . join(',', $osValues) . ')');
        }
    }

}
