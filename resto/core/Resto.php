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
 * RESTo entry point
 * 
 * This class checks for mandatory config/Resto.config.php file
 * An HTTP error 500 is returned if configuration file is not present
 * 
 * 
 * This class should be instantiate with
 * 
 *      $resto = new Resto();
 * 
 * Afterward send() function should be called
 * 
 *      $resto->send();
 * 
 * 
 * Assuming the url general model below : 
 * 
 *      http(s)://host/resto/collection/identifier/modifier?key1=value1&key2=value2&...
 *      \__________________/\_____________________________/\__________________________/
 *             resto URL                relative URI             query parameters
 * 
 * Where :
 *      
 *      The 'collection' is the name of a collection (e.g. 'Charter', 'SPIRIT', etc.).
 *      A collection contains a list of resources.
 *      
 *      A 'resource' is a metadata file describing a product identified by 'identifier'.
 * 
 *      The 'modifier' allows special action on the resource (e.g. $download to download
 *      the product related to resource)
 *
 * One URI = one action as follow
 * 
 *      |        Action                                | HTTP method | Relative URI
 *      |______________________________________________|_____________|______________________________
 *      | Create a new collection                      |     POST    |   /
 *      | List all collections                         |     GET     |   /
 *      | Analyze search query (standalone service)    |     GET     |   /$analyzeQuery?q=....
 *      | List all resources within collection         |     GET     |   /collection
 *      | Describe collection (OpenSearch.xml)         |     GET     |   /collection/$describe
 *      | Delete a collection                          |     DELETE  |   /collection
 *      | Update a collection                          |     PUT     |   /collection
 *      | Get resource within the collection           |     GET     |   /collection/identifier
 *      | Insert a new resource within the collection  |     POST    |   /collection
 *      | Add/update rights for this collection        |     POST    |   /collection/$rights
 *      | Update a resource from the collection        |     PUT     |   /collection/identifier
 *      | Delete a resource from the collection        |     DELETE  |   /collection/identifier
 *      | Download product linked to resource          |     GET     |   /collection/identifier/$download
 *      | Add tags to a collection                     |     POST    |   /collection/$tags
 *      | List all tags from a resource                |     GET     |   /collection/identifier/$tags
 *      | Add tags to resource                         |     POST    |   /collection/identifier/$tags
 * 
 * Note 1 : HTTP methods on relative URI that are not listed in the table returns an HTTP error 405
 * (i.e. 'Method Not Allowed')
 *
 * Note 2 : Collections and resources names should not start with character '$'. Names starting by a
 * '$' have special meanings (see $describe, $download, $tags, etc. special meanings in previous table)
 *
 * 
 * Query parameters
 * ----------------
 * Query parameters are described within OpenSearch description file (accessible through a GET request
 * to /collection/$describe URI)
 *
 * Special query parameters can be used to modify the query. These parameters are not specified
 * within the OpenSearch description file (see list below)
 *
 *      | Query parameter    |      Type      | Description
 *      |______________________________________________________________________________________________
 *      | _pretty            |     boolean    | (For JSON output only) true to return pretty print JSON
 *      | _showQuery         |     boolean    | (For HTML output only) true to display query analysis result
 * 
 */
class Resto {
    
    /*
     * RESTo major version number
     */
    private $version = 'RESTo v1.0-alpha';
    
    /*
     * Config object
     */
    private $config = array();

    /*
     * List of collections
     */
    private $collections = array();

    /*
     * Dictionary object
     */
    private $dictionary;

    /*
     * Array storing request
     */
    private $request = array();

    /*
     * Array storing response
     */
    private $response;

    /*
     * Reference to RestoUser instance for rights management
     */
    private $restoUser;
    
    /*
     * If set to true, each query include returns a real count
     * of the total number of resources relative to the query
     * using PostgreSQL count(*) over()
     */
    public $realCount;

    /*
     * Default response format is HTML for nominal GET requests
     * and JSON for other requests (including GET request in error)
     */
    const DEFAULT_RESPONSE_FORMAT = 'json';
    const DEFAULT_GET_RESPONSE_FORMAT = 'html';
    
    /*
     * RESTo v4 UUID generated at http://uuidgenerator.net/
     * 
     * This UUID is used to generate UUID for search resource
     * (see UUIDv5 function in $RESTO_HOME/resto/lib/functions.php)
     */
    const UUID = '92708059-2077-45a3-a4f3-1eb428789cff';
    
    /**
     * Constructor
     * 
     * Note : throws HTTP error 500 if resto.ini file does not exist or cannot be read
     * 
     */
    public function __construct() {

        try {

            /*
             * Set PHP_AUTH_USER and PHP_AUTH_PW from HTTP_AUTHORIZATION
             * (http://stackoverflow.com/questions/3663520/php-auth-user-not-set)
             */
            if (isset($_SERVER['HTTP_AUTHORIZATION']) || isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $tmp = explode(':', base64_decode(substr(isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 6)));
                $_SERVER['PHP_AUTH_USER'] = isset($tmp[0]) ? $tmp[0] : null;
                $_SERVER['PHP_AUTH_PW'] = isset($tmp[1]) ? $tmp[1] : null;
            }

            /*
             * READ configuration file
             */
            $configFile = realpath(dirname(__FILE__)) . '/../resto.ini';
            if (!file_exists($configFile)) {
                throw new Exception('Missing mandatory configuration file', 500);
            }
            $this->config = IniParser::read($configFile);

            /*
             * If set to true, each query include returns a real count
             * of the total number of resources relative to the query
             */
            $this->realCount = isset($this->config['general']['realCount']) ? $this->config['general']['realCount'] : true;

            /*
             * Set dictionary instance
             * 
             * Note: dictionary constructor require a 2 digits input language (i.e. fr instead of fr-FR)
             */
            $this->dictionary = new Dictionary(substr(isset($_GET[RestoController::$searchFiltersDescription['language']['osKey']]) ? $_GET[RestoController::$searchFiltersDescription['language']['osKey']] : $this->getLanguage(), 0, 2));
            $this->request['language'] = $this->dictionary->language;

            /*
             * Retrieve collections from resto database using a connection
             * with read privileges on 'admin' schema
             */
            $this->collections = array();
            $dbh = $this->getDatabaseConnectorInstance()->getConnection(true);
            $results = pg_query($dbh, 'SELECT * FROM admin.collections WHERE status <> \'deleted\' ORDER BY creationdate ASC');
            while ($collection = pg_fetch_assoc($results)) {
                $this->collections[$collection['collection']] = array(
                    'controller' => $collection['controller'],
                    'theme' => $collection['theme'],
                    'creationDate' => $collection['creationdate'],
                    'status' => $collection['status'],
                    'os' => $this->getOpenSearchDescription($dbh, $collection['collection']),
                    'dbDescription' => array(
                        'dbname' => $collection['dbname'],
                        'host' => $collection['hostname'],
                        'port' => $collection['port'],
                        'schema' => $collection['schemaname'],
                        'table' => $collection['tablename']
                    )
                );
                $json = json_decode($collection['configuration'], true);
                foreach ($json as $key => $value) {
                    $this->collections[$collection['collection']][$key] = $value;
                }
            }
        } catch (Exception $e) {
            $this->request['format'] = self::DEFAULT_RESPONSE_FORMAT;
            $this->response = array('ErrorCode' => $e->getCode(), 'ErrorMessage' => $e->getMessage());
            $this->responseStatus = $e->getCode();
            $this->response()->send();
            exit();
        }

        /*
         * Explode relative URI into collection, identifier and modifier (see .htaccess)
         */
        $RESToURL = isset($_GET['RESToURL']) && !empty($_GET['RESToURL']) ? $_GET['RESToURL'] : null;
        if ($RESToURL) {
            $RESToURL = substr($RESToURL, -1) === '/' ? substr($RESToURL, 0, strlen($RESToURL) - 1) : $RESToURL;
            $splitted = explode('/', $RESToURL);
            if (isset($splitted[0])) {
                $this->request['collection'] = $splitted[0];
            }
            if (isset($splitted[1])) {
                $this->request['identifier'] = urldecode($splitted[1]);
            }
            if (isset($splitted[2])) {
                $this->request['modifier'] = urldecode($splitted[2]);
            }
        }

        /*
         * restoUrl is the root url of the webapp (e.g. http(s)://host/resto/)
         */
        $this->request['restoUrl'] = $RESToURL ? substr($this->getBaseURL(), 0, -(strlen($RESToURL) + 1)) : $this->getBaseURL();

        /*
         * Method is one of GET, POST, PUT or DELETE
         */
        $this->request['method'] = strtolower($_SERVER['REQUEST_METHOD']);

        /*
         * Output format should be listed in self::$contentTypes otherwise
         * it is set to self::DEFAULT_RESPONSE_FORMAT if method is not GET and 
         * self::DEFAULT_GET_RESPONSE_FORMAT if method is GET
         */
        $this->request['format'] = isset($_GET['format']) && array_key_exists(trim($_GET['format']), self::$contentTypes) ? trim($_GET['format']) : ($this->request['method'] === 'get' ? self::DEFAULT_GET_RESPONSE_FORMAT : self::DEFAULT_RESPONSE_FORMAT);

        /*
         * Set special parameters
         */
        $this->request['special'] = array();
        foreach (array('_pretty', '_showQuery') as $key) {
            if (isset($_GET[$key])) {
                $this->request['special'][$key] = trueOrFalse($_GET[$key]);
                unset($_GET[$key]);
            }
            else {
                $this->request['special'][$key] = false;
            }
        }
        
        /*
         * Query parameters
         */
        unset($_GET['RESToURL'], $_GET['format']);
        switch ($this->request['method']) {
            case 'get':
                $this->request['params'] = $_GET;
                break;
            case 'post':
                $this->request['params'] = array_merge($_POST, $_GET);
                break;
            case 'put':
                parse_str(file_get_contents('php://input'), $this->request['params']);
                break;
            case 'delete':
                $this->request['params'] = $_GET;
                break;
            default:
                break;
        }

        /*
         * Trim all values
         */
        if (!function_exists('trim_value')) {

            function trim_value(&$value) {
                $value = trim($value);
            }

        }
        array_walk_recursive($this->request, 'trim_value');
        
        /*
         * Set TimeZone
         */
        date_default_timezone_set(isset($this->config['general']['timezone']) ? $this->config['general']['timezone'] : 'Europe/Paris');
        
        /*
         * Initialize RestoUser object 
         */
        try {
            $this->restoUser = new RestoUser($this->getDatabaseConnectorInstance());
        }
        catch (Exception $e) {
            $this->request['format'] = self::DEFAULT_RESPONSE_FORMAT;
            $this->response = array('ErrorCode' => $e->getCode(), 'ErrorMessage' => $e->getMessage());
            $this->responseStatus = $e->getCode();
            $this->response()->send();
            exit();
        }
  
        /*
         * Store output for performance
         */
        ob_start();
        
        return $this;
    }

    /**
     * 
     * Process request
     * 
     * First resolve controller based on collection name and http
     * method (GET/POST/PUT/DELETE) using reflection and get the response.
     * 
     * Passes the response to the response *Response class.
     * 
     */
    public function process() {

        try {
            
            /*
             * !! IMPORTANT !!
             * Collections with a name starting with '$' character are not collection
             * but reserved actions 
             */
            if (isset($this->request['collection']) && substr($this->request['collection'], 0 , 1) === '$') {
                
                /*
                 * QueryAnalyzer standalone service
                 */
                if ($this->request['collection'] === '$analyzeQuery' && class_exists('QueryAnalyzer')) {
                    
                   /*
                    * QueryAnalyzer always returns JSON
                    */
                   $this->request['format'] = self::DEFAULT_RESPONSE_FORMAT;
                
                   /*
                    * Change parameter keys to model parameter key
                    * and remove unset parameters
                    */
                    $params = array();
                    foreach ($this->request['params'] as $key => $value) {
                        if ($value) {
                            foreach(array_keys(RestoController::$searchFiltersDescription) as $filterKey) {
                                if ($key === RestoController::$searchFiltersDescription[$filterKey]['osKey']) {
                                    $params[$filterKey] = $value;
                                }
                            }
                        }
                    }
                    $qa = new QueryAnalyzer($this->dictionary, RestoController::$searchFiltersDescription, class_exists('Gazetteer') ? new Gazetteer($this) : null);
                    $this->response = $qa->analyze($params);
                    $this->responseStatus = 200;
                }
                else {
                    throw new Exception('Not Found', 404);
                }
            }
            /*
             * Collection is requested
             */
            else if (isset($this->request['collection'])) {
                
                /*
                 * Collection does not exist
                 */
                if (!isset($this->collections[$this->request['collection']])) {
                    throw new Exception('Not Found', 404);
                }
                
                /*
                 * Search collection Controller name within collections list
                 */
                $controllerName = null;
                foreach (glob(realpath(dirname(__FILE__)) . '/../controllers/*.php', GLOB_NOSORT) as $controller) {
                    if ($this->collections[$this->request['collection']]['controller'] === basename($controller, '.php')) {
                        $controllerName = basename($controller, '.php');
                        break;
                    }
                }

                /*
                 * Invalid controller  
                 */
                if (!$controllerName) {
                    throw new Exception('Invalid collection', 500);
                }
                
                /*
                 * HTTP method is PUT, POST or DELETE and no identifier is set
                 * 
                 *      HTTP DELETE - Delete new collection (if CollectionManager module is active)
                 *      HTTP PUT - Update new collection (if CollectionManager module is active)
                 */
                if ($this->request['method'] === 'put' && !$this->request['identifier']) {
                    if (class_exists('CollectionManager')) {
                        $collectionManager = new CollectionManager($this);
                        $this->response = $collectionManager->update();
                    }
                    else {
                        throw new Exception('Forbidden', 403);
                    }
                }
                else if ($this->request['method'] === 'delete' && $this->request['identifier'] !== '$rights') {
                    if (!$this->request['identifier'] && class_exists('CollectionManager')) {
                        $collectionManager = new CollectionManager($this);
                        $this->response = $collectionManager->delete();
                    }
                    else {
                        throw new Exception('Forbidden', 403);
                    }
                }
                /*
                 * HTTP GET method on /collection
                 * HTTP POST, PUT DELETE on /collection/identifier
                 */
                else {
                    
                    /*
                     * Instantiate RestoController
                     */
                    $controller = new ReflectionClass($controllerName);
                    if (!$controller->isInstantiable()) {
                        throw new Exception('Bad Request', 400);
                    }
                    try {
                        $method = $controller->getMethod($this->request['method']);
                    } catch (ReflectionException $re) {
                        throw new Exception('Forbidden', 403);
                    }

                    /*
                     * Initialize a controller instance
                     */
                    if (!$method->isStatic()) {
                        $controllerInstance = $controller->newInstance($this);
                        $method->invoke($controllerInstance);
                        $this->response = $controllerInstance->getResponse();
                        $this->responseStatus = $controllerInstance->getResponseStatus();
                        $this->responseDescription = $controllerInstance->getDescription();
                    } else {
                        throw new Exception('Static methods not supported in Controllers', 500);
                    }
                    if (is_null($this->response)) {
                        throw new Exception('Method not allowed', 405);
                    }
                }
            }
            /*
             * No collection requested 
             * 
             *      HTTP GET - HTTP 200 (will redirect to home page - see htmlResponse)
             *      HTTP POST - Create new collection (if CollectionManager module is active)
             *      HTTP PUT - Not allowed
             *      HTTP DELETE - Not allowed
             * 
             */
            else {
                if ($this->request['method'] === 'get') {
                    $this->responseStatus = 200;
                }
                else if ($this->request['method'] === 'post' && class_exists('CollectionManager')) {
                    $collectionManager = new CollectionManager($this);
                    $this->response = $collectionManager->create();
                }
                else {
                    throw new Exception('Method Not Allowed', 405);
                }
            }
        } catch (Exception $re) {
            $this->responseStatus = $re->getCode();
            $this->response = array('ErrorCode' => $re->getCode(), 'ErrorMessage' => $re->getMessage());
        }

        /*
         * Special case for stream !
         */
        if (isset($controllerInstance) && isset($this->responseDescription['forceStream']) && $this->responseDescription['forceStream']) {
            return;
        }

        /*
         * Output result
         */
        $this->response()->send();
    }

    /**
     * Return request array
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * Return dictionary object
     */
    public function getDictionary() {
        return $this->dictionary;
    }

    /**
     * Return module configuration array
     * 
     * @param string $name - module name
     */
    public function getModuleConfig($name) {
        return $this->config['modules'][$name];
    }

    /**
     * Return all collections collection configuration array
     * 
     * @param string $name - collection name
     */
    public function getCollectionsDescription() {
        return $this->collections;
    }

    /**
     * Return collection configuration array
     * 
     * @param string $name - collection name
     */
    public function getCollectionDescription($name) {
        return $this->collections[$name];
    }

    /**
     * Return RESTo homepage title
     */
    public function getTitle() {
        return $this->config['general']['title'];
    }

    /**
     * Return RESTo homepage description
     */
    public function getDescription() {
        return $this->config['general']['description'];
    }

    /**
     * Return accepted languages
     */
    public function getAcceptedLangs() {
        return $this->config['general']['acceptedLangs'];
    }

    /**
     * Return theme name
     */
    public function getThemeName() {
        return isset($this->config['general']['theme']) ? $this->config['general']['theme'] : 'default';
    }

    /**
     * Return a new DatabaseConnector instance
     */
    public function getDatabaseConnectorInstance() {
        return new DatabaseConnector($this->config['general']['db']);
    }

    /**
     * Get User information 
     */
    public function getUser() {
        return $this->restoUser;
    }
    
    /**
     * Get url with no parameters
     * 
     * @return string $pageUrl
     */
    private function getBaseURL() {
        $pageURL = 'http';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $pageURL .= 's';
        }
        $pageURL .= '://';
        if ($_SERVER['SERVER_PORT'] !== '80') {
            $pageURL .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
        } else {
            $pageURL .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        }
        $splitted = explode('?', $pageURL);
        if (substr($splitted[0], -1) === '/') {
            return $splitted[0];
        }

        return $splitted[0] . '/';
    }

    /**
     * Get browser language
     * (see http://www.thefutureoftheweb.com/blog/use-accept-language-header)
     * 
     * @return string $lang
     */
    private function getLanguage() {
        $langs = array();
        $lang_parse = array();
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // break up string into pieces (languages and q factors)
            preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);

            if (count($lang_parse[1])) {
                // create a list like "en" => 0.8
                $langs = array_combine($lang_parse[1], $lang_parse[4]);

                // set default to 1 for any without q factor
                foreach ($langs as $lang => $val) {
                    if ($val === '') {
                        $langs[$lang] = 1;
                    }
                }

                // sort list based on value	
                arsort($langs, SORT_NUMERIC);

                // Return prefered language
                foreach ($langs as $lang => $val) {
                    return $lang;
                }
            }
        }
    }

    /**
     * 
     * Return an ATOM feed conform to OGC 13-026 - OpenSearch Extension for Earth Observation
     * 
     * @param {String} $version - default "1.0"
     * @param {String} $encoding - default "UTF-8"
     *  
     */
    private function atomResponse($version = '1.0', $encoding = 'UTF-8') {
        return toAtom($this->response, $this->dictionary, $version, $encoding);
    }

    /**
     * Function implementating json response helper.
     * Converts response array to json.
     */
    private function jsonResponse() {
        return json_format($this->response, isset($this->request['special']['_pretty']) ? $this->request['special']['_pretty'] : false);
    }

    /**
     * Function implementing HTML response helper
     * Converts response array to html
     */
    private function htmlResponse() {

        /*
         * In case of error, return json
         */
        if (is_array($this->response) && isset($this->response['ErrorCode']) && $this->response['ErrorCode'] === 500) {
            $this->request['content-type'] = self::DEFAULT_RESPONSE_FORMAT;
            return $this->jsonResponse();
        }
        
        /*
         * Templates path is infered from theme name
         */
        $templatesPath = realpath(dirname(__FILE__)) . '/../../themes/' . (isset($this->responseDescription['theme']) ? $this->responseDescription['theme'] : $this->getThemeName()) . '/templates/';
        
        /*
         * Default description (for home and admin)
         */
        $description = array(
            'name' => $this->request['collection'],
            'theme' => $this->getThemeName(),
            'acceptedLangs' => $this->getAcceptedLangs(),
            'dictionary' => $this->getDictionary()
        );
        
        /*
         * No collection set => renders home page
         */
        if (!isset($this->request['collection']) || !$this->request['collection']) {
            $template = new Template($templatesPath . 'home.php', $this, null, $description);
        }
        /*
         * Identifier set => renders resource page
         */
        else if (isset($this->request['identifier'])) {
            $template = new Template($templatesPath . $this->request['method'] . 'Resource' . '.php', $this, $this->response, $this->responseDescription);
        }
        /*
         * Renders collection
         */
        else {
            $template = new Template($templatesPath . $this->request['method'] . 'Collection' . '.php', $this, $this->response, $this->responseDescription);
        }

        return $template->render();
    }

    /**
     * Function to get HTTP headers
     */
    private function getHeaders() {
        if (function_exists('apache_request_headers')) {
            return apache_request_headers();
        }
        $headers = array();
        $keys = preg_grep('{^HTTP_}i', array_keys($_SERVER));
        foreach ($keys as $val) {
            $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($val, 5)))));
            $headers[$key] = $_SERVER[$val];
        }
        return $headers;
    }

    /**
     * Retrieve OpenSearch description for collection $name
     * 
     * @param object $dbh
     * @param object $name
     */
    private function getOpenSearchDescription($dbh, $name) {

        /*
         * Get description in request language
         */
        $results = pg_query($dbh, 'SELECT * FROM admin.osdescriptions WHERE collection = \'' . pg_escape_string($name) . '\' AND lang=\'' . $this->request['language'] . '\'');
        /*
         * No result => switch to english
         */
        if (pg_num_rows($results) === 0) {
            $results = pg_query($dbh, 'SELECT * FROM admin.osdescriptions WHERE collection = \'' . pg_escape_string($name) . '\' AND lang=\'en\'');
        }
        while ($description = pg_fetch_assoc($results)) {
            return array(
                'ShortName' => $description['shortname'],
                'LongName' => $description['longname'],
                'Description' => $description['description'],
                'Tags' => $description['tags'],
                'Developper' => $description['developper'],
                'Contact' => $description['contact'],
                'Query' => $description['query'],
                'Attribution' => $description['attribution']
            );
        }

        return array();
    }

    /*
     * Returns response array
     *  (status, body)
     */

    private function response() {
        if ($this->responseStatus !== 200) {
            $this->request['format'] = self::DEFAULT_RESPONSE_FORMAT;
        }
        $method = $this->request['format'] . 'Response';
        $this->response = array('status' => $this->responseStatus, 'body' => $this->$method());
        return $this;
    }

    /*
     * Send request
     */

    private function send() {

        /*
         * Set headers from $this->request['content-type'] and $this->response['status']
         * Includes cross-origin resource sharing (CORS)
         * http://en.wikipedia.org/wiki/Cross-origin_resource_sharing
         */
        $status = (isset($this->response['status'])) ? $this->response['status'] : 200;
        header('HTTP/1.1 ' . $status . ' ' . (isset(self::$codes[$status]) ? self::$codes[$status] : self::$codes[200]));
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: ' . self::$contentTypes[$this->request['format']]);
        
        /*
         * Flush result
         */
        echo (empty($this->response['body'])) ? '' : $this->response['body'];
        ob_end_flush();
    }

    /*
     * HTTP codes
     */

    public static $codes = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    );

    /*
     * List of supported formats
     */
    public static $contentTypes = array(
        'atom' => 'application/atom+xml',
        'html' => 'text/html',
        'json' => 'application/json'
    );

}
