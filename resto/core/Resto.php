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
 *      http(s)://host/resto/collection/identifier/modifier
 *      \__________________/\____________________________/
 *             resto URL                relative URI    
 * 
 * Where :
 *      
 *      The 'collection' is the name of a collection (e.g. 'Charter', 'SPIRIT', etc.).
 *      A collection contains a list of resources.
 *      
 *      A 'resource' is a metadata file describing a product identified by 'identifier'.
 *      The product itself (e.g. an image) can be access through resource 'modifier' (when set to 'download')
 * 
 * Resto handles the following actions
 * 
 * |        Action                                | HTTP method | Relative URI
 * |______________________________________________|_____________|______________________________
 * | Create a new collection                      |     POST    |   /
 * | List all collections                         |     GET     |   /
 * | List all resources within collection         |     GET     |   /collection
 * | Describe collection (OpenSearch.xml)         |     GET     |   /collection/_describe
 * | Delete a collection                          |     DELETE  |   /collection
 * | Update a collection                          |     PUT     |   /collection
 * | Get resource within the collection           |     GET     |   /collection/identifier
 * | Insert a new resource within the collection  |     POST    |   /collection
 * | Update a resource from the collection        |     PUT     |   /collection/identifier
 * | Delete a resource from the collection        |     DELETE  |   /collection/identifier
 * | Download product linked to resource          |     GET     |   /collection/identifier/download
 * 
 * 
 * Note: HTTP methods on relative URI that are not listed in the table
 * returns an HTTP error 405 (i.e. 'Method Not Allowed')
 * 
 */
class Resto {
    
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
                list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr(isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 6)));
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
             * Note: dictionary constructor require a 2 digits input lang (i.e. fr instead of fr-FR)
             */
            $this->dictionary = new Dictionary(substr(isset($_GET['lang']) ? $_GET['lang'] : $this->getLanguage(), 0, 2));
            $this->request['lang'] = $this->dictionary->lang;

            /*
             * Retrieve collections from resto database using a connection
             * with read privileges on 'admin' schema
             */
            $this->collections = array();
            $dbh = $this->getDatabaseConnectorInstance()->getConnection(true);
            $results = pg_query($dbh, 'SELECT * FROM admin.collections WHERE status <> \'deleted\' ORDER BY creationdate DESC');
            while ($collection = pg_fetch_assoc($results)) {
                $this->collections[$collection['collection']] = array(
                    'controller' => $collection['controller'],
                    'template' => $collection['template'],
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
            if ($splitted[0]) {
                $this->request['collection'] = $splitted[0];
            }
            if ($splitted[1]) {
                $this->request['identifier'] = urldecode($splitted[1]);
            }
            if ($splitted[2]) {
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
         * Query parameters
         */
        unset($_GET['RESToURL'], $_GET['format'], $_GET['lang']);
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
             * Collection is requested
             */
            if ($this->request['collection']) {

                /*
                 * Collection does not exist
                 */
                if (!$this->collections[$this->request['collection']]) {
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
                else if ($this->request['method'] === 'delete' && !$this->request['identifier']) {
                    if (class_exists('CollectionManager')) {
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
        if ($controllerInstance && $this->responseDescription['forceStream']) {
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
     * Return accepted langs
     */
    public function getAcceptedLangs() {
        return $this->config['general']['acceptedLangs'];
    }

    /**
     * Return template name
     */
    public function getTemplateName() {
        return isset($this->config['general']['template']) ? $this->config['general']['template'] : 'default';
    }

    /**
     * Return mapshup configuration
     */
    public function getMapshupConfig() {
        return $this->config['general']['mapshup'];
    }

    /**
     * Return a new DatabaseConnector instance
     */
    public function getDatabaseConnectorInstance() {
        return new DatabaseConnector($this->config['general']['db']);
    }

    /**
     * Check authorization
     * 
     * TODO : add users within resto database
     * 
     * @return boolean
     */
    public function checkAuth() {

        /*
         * Dummy stuff - just to test the authentication loop
         */
        if ($_SERVER['PHP_AUTH_USER'] === $this->config['general']['admin']['user'] && $_SERVER['PHP_AUTH_PW'] === $this->config['general']['admin']['password']) {
            return true;
        }

        return false;
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
     * Get browser lang
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
                    if ($val === '')
                        $langs[$lang] = 1;
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

        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument($version, $encoding);

        /*
         * feed - Start element
         */
        $xml->startElement('feed');
        $xml->writeAttribute('xml:lang', 'en');
        $xml->writeAttribute('xmlns', 'http://www.w3.org/2005/Atom');
        $xml->writeAttribute('xmlns:time', 'http://a9.com/-/opensearch/extensions/time/1.0/');
        $xml->writeAttribute('xmlns:os', 'http://a9.com/-/spec/opensearch/1.1/');
        $xml->writeAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $xml->writeAttribute('xmlns:georss', 'http://www.georss.org/georss');
        $xml->writeAttribute('xmlns:gml', 'http://www.opengis.net/gml');
        $xml->writeAttribute('xmlns:geo', 'http://a9.com/-/opensearch/extensions/geo/1.0/');
        $xml->writeAttribute('xmlns:eo', 'http://a9.com/-/opensearch/extensions/eo/1.0/');
        $xml->writeAttribute('xmlns:metalink', 'urn:ietf:params:xml:ns:metalink');
        $xml->writeAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');

        /*
         * Element 'title' 
         *  read from $this->response['title']
         */
        $xml->writeElement('title', isset($this->response['title']) ? $this->response['title'] : '');

        /*
         * Element 'subtitle' 
         *  constructed from $this->response['title']
         */
        $subtitle = $this->dictionary->translate($this->response['totalResults'] === 1 ? '_oneResult' : '_multipleResult', $this->response['totalResults']);
        $previous = isset($this->response['links']['previous']) ? '<a href="' . updateURL($this->response['links']['previous'], array('format' => 'atom')) . '">' . $this->dictionary->translate('_previousPage') . '</a>&nbsp;' : '';
        $next = isset($this->response['links']['next']) ? '&nbsp;<a href="' . updateURL($this->response['links']['next'], array('format' => 'atom')) . '">' . $this->dictionary->translate('_nextPage') . '</a>' : '';
        $subtitle .= isset($this->response['startIndex']) ? '&nbsp;|&nbsp;' . $previous . $this->dictionary->translate('_pagination', $this->response['startIndex'], $this->response['lastIndex']) . $next : '';

        $xml->startElement('subtitle');
        $xml->writeAttribute('type', 'html');
        $xml->text($subtitle);
        $xml->endElement(); // subtitle

        /*
         * Updated time is now
         */
        $xml->startElement('generator');
        $xml->writeAttribute('uri', 'http://mapshup.info');
        $xml->writeAttribute('version', '1.0');
        $xml->text('RESTo');
        $xml->endElement(); // generator
        $xml->writeElement('updated', date('Y-m-d\TH:i:s\Z'));

        /*
         * Element 'id' 
         *  read from $this->response['??']
         */
        $xml->writeElement('id', 'TODO');

        /*
         * Self link
         */
        $xml->startElement('link');
        $xml->writeAttribute('rel', 'self');
        $xml->writeAttribute('type', 'application/atom+xml');
        $xml->writeAttribute('href', updateURL($this->response['links']['self'], array('format' => 'atom')));
        $xml->endElement(); // link

        /*
         * First link
         */
        if (isset($this->response['links']['first'])) {
            $xml->startElement('link');
            $xml->writeAttribute('rel', 'first');
            $xml->writeAttribute('type', 'application/atom+xml');
            $xml->writeAttribute('href', updateURL($this->response['links']['first'], array('format' => 'atom')));
            $xml->endElement(); // link
        }

        /*
         * Next link
         */
        if (isset($this->response['links']['next'])) {
            $xml->startElement('link');
            $xml->writeAttribute('rel', 'next');
            $xml->writeAttribute('type', 'application/atom+xml');
            $xml->writeAttribute('href', updateURL($this->response['links']['next'], array('format' => 'atom')));
            $xml->endElement(); // link
        }

        /*
         * Previous link
         */
        if (isset($this->response['links']['previous'])) {
            $xml->startElement('link');
            $xml->writeAttribute('rel', 'previous');
            $xml->writeAttribute('type', 'application/atom+xml');
            $xml->writeAttribute('href', updateURL($this->response['links']['previous'], array('format' => 'atom')));
            $xml->endElement(); // link
        }
        /*
         * Last link
         */
        if (isset($this->response['links']['last'])) {
            $xml->startElement('link');
            $xml->writeAttribute('rel', 'last');
            $xml->writeAttribute('type', 'application/atom+xml');
            $xml->writeAttribute('href', updateURL($this->response['links']['last'], array('format' => 'atom')));
            $xml->endElement(); // link
        }

        /*
         * Total results, startIndex and itemsPerpage
         */
        $xml->writeElement('os:totalResults', $this->response['totalResults']);
        $xml->writeElement('os:startIndex', $this->response['startIndex']);
        $xml->writeElement('os:itemsPerPage', $this->response['lastIndex'] - $this->response['startIndex'] + 1);

        /*
         * Query is made from request parameters
         */
        $xml->startElement('os:Query');
        $xml->writeAttribute('role', 'request');
        if (isset($this->response['query'])) {
            foreach ($this->response['query']['original'] as $key => $value) {
                $xml->writeAttribute($key, $value);
            }
        }
        $xml->endElement(); // os:Query

        /*
         * Loop over all products
         */
        for ($i = 0, $l = count($this->response['features']); $i < $l; $i++) {

            $product = $this->response['features'][$i];

            /*
             * entry - add element
             */
            $xml->startElement('entry');

            /*
             * Element 'id'
             *  read from $product['properties']['identifier']
             * 
             * !! THIS SHOULD BE AN ABSOLUTE UNIQUE  AND PERMANENT IDENTIFIER !!
             * 
             */
            $xml->writeElement('id', $product['properties']['identifier']);

            /*
             * Element 'title'
             *  read from $product['properties']['title']
             */
            $xml->writeElement('title', $product['properties']['title']);

            /*
             * Element 'published' - date of metadata first publication
             *  read from $product['properties']['title']
             */
            $xml->writeElement('published', $product['properties']['published']);

            /*
             * Element 'updated' - date of metadata last modification
             *  read from $product['properties']['title']
             */
            $xml->writeElement('updated', $product['properties']['updated']);

            /*
             * Element 'dc:date' - date of the resource is beginning of acquisition i.e. startDate
             *  read from $product['properties']['startDate']
             */
            $xml->writeElement('dc:date', $product['properties']['startDate']);

            /*
             * Element 'gml:validTime' - acquisition duration between startDate and completionDate
             *  read from $product['properties']['startDate'] and $this->response['properties']['completionDate']
             */
            $xml->startElement('gml:validTime');
            $xml->startElement('gml:TimePeriod');
            $xml->writeElement('gml:beginPosition', $product['properties']['startDate']);
            $xml->writeElement('gml:endPosition', $product['properties']['completionDate']);
            $xml->endElement(); // gml:TimePeriod
            $xml->endElement(); // gml:validTime

            /*
             * georss:polygon from geojson entry
             * 
             * WARNING !
             * 
             *      GeoJSON coordinates order is longitude,latitude
             *      GML coordinates order is latitude,longitude 
             *      
             * 
             */
            $geometry = array();
            foreach ($product['geometry']['coordinates'] as $key) {
                foreach ($key as $value) {
                    array_push($geometry, $value[1] . ' ' . $value[0]);
                }
            }
            $xml->startElement('georss:where');
            $xml->startElement('gml:Polygon');
            $xml->startElement('gml:exterior');
            $xml->startElement('gml:LinearRing');
            $xml->startElement('gml:posList');
            $xml->writeAttribute('srsDimensions', '2');
            $xml->text(join(' ', $geometry));
            $xml->endElement(); // gml:posList
            $xml->endElement(); // gml:LinearRing
            $xml->endElement(); // gml:exterior
            $xml->endElement(); // gml:Polygon
            $xml->endElement(); // georss:where

            /*
             * Alternate links
             */
            $atomUrl = updateURL($product['properties']['self'], array('format' => 'atom'));
            $xml->startElement('link');
            $xml->writeAttribute('rel', 'alternate');
            $xml->writeAttribute('type', 'application/atom+xml');
            $xml->writeAttribute('title', $this->dictionary->translate('_atomLink', $product['properties']['identifier']));
            $xml->writeAttribute('href', $atomUrl);
            $xml->endElement(); // link

            $htmlUrl = updateURL($product['properties']['self'], array('format' => 'html'));
            $xml->startElement('link');
            $xml->writeAttribute('rel', 'alternate');
            $xml->writeAttribute('type', 'text/html');
            $xml->writeAttribute('title', $this->dictionary->translate('_htmlLink', $product['properties']['identifier']));
            $xml->writeAttribute('href', $htmlUrl);
            $xml->endElement(); // link

            $jsonUrl = updateURL($product['properties']['self'], array('format' => 'json'));
            $xml->startElement('link');
            $xml->writeAttribute('rel', 'alternate');
            $xml->writeAttribute('type', 'application/json');
            $xml->writeAttribute('title', $this->dictionary->translate('_geojsonLink', $product['properties']['identifier']));
            $xml->writeAttribute('href', $jsonUrl);
            $xml->endElement(); // link

            /* TODO - RDF
              $xml->startElement('link');
              $xml->writeAttribute('rel', 'alternate');
              $xml->writeAttribute('type', 'application/rdf+xml');
              $xml->writeAttribute('title', '_rdfLink');
              $xml->writeAttribute('href', updateURL($product['properties']['self'], array('format' => 'rdf')));
              $xml->endElement(); // link
             */

            /* TODO - KML
              $xml->startElement('link');
              $xml->writeAttribute('rel', 'alternate');
              $xml->writeAttribute('type', 'application/vnd.google-earth.kml+xml');
              $xml->writeAttribute('title', '_kmlLink');
              $xml->writeAttribute('href', updateURL($product['properties']['self'], array('format' => 'kml')));
              $xml->endElement(); // link
             */

            /*
             * Element 'enclosure' - download product
             *  read from $product['properties']['archive']
             */
            $xml->startElement('link');
            $xml->writeAttribute('rel', 'enclosure');
            $xml->writeAttribute('type', $product['properties']['services']['download']['mimeType']);
            //$xml->writeAttribute('length', 'TODO');
            $xml->writeAttribute('title', 'File for ' . $product['properties']['identifier'] . ' product');
            $xml->writeAttribute('metalink:priority', 50);
            $xml->writeAttribute('href', $product['properties']['services']['download']['url']);
            $xml->endElement(); // link

            /*
             * Element 'summary' - HTML description
             *  construct from $product['properties'][*]
             */
            $content = '<p>' . $product['properties']['platform'] . ($product['properties']['platform'] && $product['properties']['instrument'] ? '/' : '') . $product['properties']['instrument'] . ' ' . $this->dictionary->translate('_acquiredOn', $product['properties']['startDate']) . '</p>';
            if ($product['properties']['keywords']) {
                $keywords = array();
                foreach ($product['properties']['keywords'] as $keyword => $value) {
                    array_push($keywords, '<a href="' . updateURL($value['url'], array('format' => 'atom')) . '">' . $keyword . '</a>');
                }
                $content .= '<p>' . $this->dictionary->translate('Keywords') . ' ' . join(' | ', $keywords) . '</p>';
            }
            $content .= '<p>' . $this->dictionary->translate('_viewMetadata', '<a href="' . updateURL($product['properties']['self'], array('format' => 'html')) . '">HTML</a>&nbsp;|&nbsp;<a href="' . updateURL($product['properties']['self'], array('format' => 'atom')) . '">ATOM</a>&nbsp;|&nbsp;<a href="' . updateURL($product['properties']['self'], array('format' => 'json')) . '">GeoJSON</a>') . '</p>';
            $xml->startElement('content');
            $xml->writeAttribute('type', 'html');
            $xml->text($content);
            $xml->endElement(); // content

            /*
             * entry - close element
             */
            $xml->endElement(); // entry
        }

        /*
         * feed - End element
         */
        $xml->endElement();

        /*
         * Return ATOM result
         */
        return $xml->outputMemory(true);
    }

    /**
     * Function implementating json response helper.
     * Converts response array to json.
     */
    private function jsonResponse() {
        return json_encode($this->response);
    }

    /**
     * Function implementing HTML response helper
     * Converts response array to html
     */
    private function htmlResponse() {

        /*
         * In case of error, return json
         */
        if (is_array($this->response) && $this->response['ErrorCode'] === 500) {
            $this->request['content-type'] = self::DEFAULT_RESPONSE_FORMAT;
            return $this->jsonResponse();
        }

        /*
         * No collection set => renders home page
         */
        if (!$this->request['collection']) {
            $template = new Template(realpath(dirname(__FILE__)) . '/../templates/home.php', $this);
        }
        /*
         * Identifier set => renders resource page
         */ else if ($this->request['identifier']) {
            $template = new Template(realpath(dirname(__FILE__)) . '/../templates/' . $this->responseDescription['template'] . '/' . $this->request['method'] . 'Resource' . '.php', $this, $this->response, $this->responseDescription);
        }
        /*
         * Renders collection
         */ else {
            $template = new Template(realpath(dirname(__FILE__)) . '/../templates/' . $this->responseDescription['template'] . '/' . $this->request['method'] . 'Collection' . '.php', $this, $this->response, $this->responseDescription);
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
     * @param name $collection
     */
    private function getOpenSearchDescription($dbh, $name) {

        /*
         * Get description in request lang
         */
        $results = pg_query($dbh, 'SELECT * FROM admin.osdescriptions WHERE collection = \'' . pg_escape_string($name) . '\' AND lang=\'' . $this->request['lang'] . '\'');
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
         * Store output for performance
         */
        ob_start();

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