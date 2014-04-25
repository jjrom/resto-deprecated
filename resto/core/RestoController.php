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
 * Abstract Controller
 * 
 * This class must be extended by a controller class located under
 * $RESTO_HOME/controllers
 */
abstract class RestoController {
    
    /*
     * RESTo generic OpenSearch model
     */
    static public $searchFiltersDescription = array(
        'searchTerms' => array(
            'key' => 'keywords',
            'osKey' => 'q',
            'operation' => 'hstore'
        ),
        'count' => array(
            'osKey' => 'maxRecords'
        ),
        'startIndex' => array(
            'osKey' => 'nextRecord'
        ),
        'startPage' => array(
            'osKey' => 'nextPage'
        ),
        'language' => array(
            'osKey' => 'lang'
        ),
        'geo:box' => array(
            'key' => 'geometry',
            'osKey' => 'box',
            'operation' => 'intersects'
        ),
        'geo:name' => array(
            'key' => 'geometry',
            'osKey' => 'location',
            'operation' => 'distance'
        ),
        'geo:lon' => array(
            'key' => 'geometry',
            'osKey' => 'lon',
            'operation' => 'distance'
        ),
        'geo:lat' => array(
            'key' => 'geometry',
            'osKey' => 'lat',
            'operation' => 'distance'
        ),
        'geo:radius' => array(
            'key' => 'geometry',
            'osKey' => 'radius',
            'operation' => 'distance'
        ),
        'time:start' => array(
            'key' => 'startDate',
            'osKey' => 'startDate',
            'operation' => '>='
        ),
        'time:end' => array(
            'key' => 'completionDate',
            'osKey' => 'completionDate',
            'operation' => '<='
        ),
        'eo:parentIdentifier' => array(
            'key' => 'parentIdentifier',
            'osKey' => 'parentIdentifier',
            'operation' => '='
        ),
        'eo:productType' => array(
            'key' => 'productType',
            'osKey' => 'product',
            'operation' => '='
        ),
        'eo:processingLevel' => array(
            'key' => 'processingLevel',
            'osKey' => 'processingLevel',
            'operation' => '='
        ),
        'eo:platformShortName' => array(
            'key' => 'platform',
            'osKey' => 'platform',
            'operation' => '=',
            'keyword' => array(
                'value' => '{a:1}',
                'type' => 'platform'
            )
        ),
        'eo:instrument' => array(
            'key' => 'instrument',
            'osKey' => 'instrument',
            'operation' => '=',
            'keyword' => array(
                'value' => '{a:1}',
                'type' => 'instrument'
            )
        ),
        'eo:resolution' => array(
            'key' => 'resolution',
            'osKey' => 'resolution',
            'operation' => 'interval',
            'quantity' => array(
                'value' => 'resolution',
                'unit' => 'm'
            )
        ),
        'eo:organisationName' => array(
            'key' => 'authority',
            'osKey' => 'authority',
            'operation' => '='
        ),
        'eo:orbitNumber' => array(
            'key' => 'orbitNumber',
            'osKey' => 'orbitNumber',
            'operation' => 'interval',
            'quantity' => array(
                'value' => 'orbit'
            )
        ),
        'eo:sensorMode' => array(
            'key' => 'sensorMode',
            'osKey' => 'sensorMode',
            'operation' => '='
        ),
        'ptsc:modifiedDate' => array(
            'key' => 'updated',
            'osKey' => 'updated',
            'operation' => '='
        )
    );
    
    /*
     * Reference to RESTo instance
     */
    private $R;
    
    /*
     * Description array 
     * 
     *  name =>                     ==  Collection name (same as request['collection']
     * 
     *  theme =>                    ==  Theme ('default' if not set)
     * 
     *  acceptedLangs =>            ==  Array of acceptedLangs (i.e. ['en', 'fr', ...])
     * 
     *  os => array(                ==
     *      ShortName               ==
     *      LongName                ==  OpenSearch description
     *      ...                     ==
     *  )                           ==
     * 
     *  dictionary =>               ==  dictionary reference
     * 
     *  model =>                    ==  list of database columns
     *
     *  searchFiltersList =>        ==  list of available searchFiltersList
     * 
     *  searchFiltersDescription    ==  see below
     * 
     *  The 'searchFiltersDescription' array defines the mapping between OpenSearch filters name and the
     *  corresponding column name within the database model on which they applied.
     *  Filters can be added to model within the collection.config.php file
     * 
     *      Structure
     * 
     *          'OpenSearch parameter' => array(
     *              'osKey' => OpenSearch key
     *              'key' => Equivalent column name within RESTo database model
     *              'type' => string | numeric | date
     *              'operation' => hstore | intersects | distance | interval | = | >= | <=
     *              'keyword' => array(
     *                  'value' => value display as keyword (form "vcsc{a:1}")
     *                  'type' => type of keyword
     *              )
     *          )
     * 
     */
    protected $description = array();

    /*
     * Reference to the RESTo request array
     * 
     * Exemple :
     * 
     *  [collection] => Charter
     *  [method] => get
     *  [format] => json
     *  [params] => Array(
     *      [q] => europe
     *  )
     */
    protected $request;

    /*
     * Reference to the response object
     * Response should be :
     *  - a GeoJSON array for nominal GET search requests
     *  - a JSON array for nominal POST, PUT, DELETE requests and error requests
     *  - an XML string for nominal GET describe requests
     */
    protected $response;

    /*
     * HTTP response code
     */
    protected $responseStatus;

    /*
     * Reference to DatabasConnector object
     */
    protected $dbConnector;
    
    /**
     * Constructor checks that controller defines mandatory $model and $filters arrays
     * If not present, controller is invalid and not loaded
     * 
     * @param Object $R - RESTo object
     * 
     */
    final public function __construct($R) {

        /*
         * Set RESTo and request instance references 
         */
        $this->R = $R;
        $this->request = $R->getRequest();
        $collectionDescription = $R->getCollectionDescription($this->request['collection']);
        $controller = get_class($this);
        /*
         * Initialize $this->description array
         */
        $this->description = array(
            'name' => $this->request['collection'],
            'theme' => isset($collectionDescription['theme']) ? $collectionDescription['theme'] : $R->getThemeName(),
            'acceptedLangs' => $R->getAcceptedLangs(),
            'dictionary' => $R->getDictionary(),
            'os' => $collectionDescription['os'],
            'model' => $controller::$model,
            'searchFiltersList' => $controller::$searchFiltersList,
            'searchFiltersDescription' => self::$searchFiltersDescription
        );
        
        /*
         * Update description with Controller model
         */
        if (property_exists($controller, 'searchFiltersDescription')) {
            foreach($controller::$searchFiltersDescription as $key => $value) {
                $this->description['searchFiltersDescription'][$key] = $value;
            }
        }
        
        /*
         * Initialize database connector
         */
        $this->dbConnector = $R->getDatabaseConnectorInstance();
       
        /*
         * Get collection configuration
         */
        foreach ($collectionDescription as $key => $value) {

            /*
             * If searchFiltersList is set then it replaces
             * $this->description['searchFiltersList']
             */
            if ($key === 'searchFiltersList') {
                $this->description['searchFiltersList'] = $value;
            }

            /*
             * Update model
             */
            else if ($key === 'model') {
                foreach ($value as $key2 => $value2) {
                    $this->description['model'][$key2] = $value2;
                }
            }

            /*
             * Update database description fields
             */
            else if ($key === 'dbDescription') {
                $this->dbConnector->update($value);
            }

            /*
             * Update dictionary
             */
            else if ($key === 'dictionary_' . $this->description['dictionary']->language) {
                $this->description['dictionary']->add(array($key => $value));
            }

            /*
             * Update model
             */
            else if ($key === 'searchFiltersDescription') {
                foreach ($value as $key2 => $value2) {
                    $this->description['searchFiltersDescription'][$key2] = $value2;
                }
            }
        }
        
        /*
         * Change parameter keys to model parameter key
         * and remove unset parameters
         */
        $this->request['params'] = array();
        $request = $R->getRequest();
        foreach ($request['params'] as $key => $value) {
            if ($value !== "") {
                $this->request['params'][$this->modelName($key)] = $value;
            }
        }
    }

    /**
     * Default process of HTTP GET Requests
     * 
     * Redirects to one of the following function
     *  
     *      getCollection - return results metadata from a search in collection
     *      describeCollection - return OpenSearch description document for collection
     *      describeResource - return metadata for a given resource
     *      getResource - return the resource itself (i.e. download product)
     *           
     */
    public function defaultGet() {

        /*
         * GET on /collection
         */
        if (!isset($this->request['identifier'])) {
            return $this->getCollection($this->getFields());
        }

        /*
         * GET on /collection/$describe
         */
        if (isset($this->request['identifier']) && $this->request['identifier'] === '$describe') {
            return $this->describeCollection();
        }

        /*
         * GET on /collection/identifier
         */
        if (isset($this->request['identifier']) && !isset($this->request['modifier'])) {
            return $this->describeResource($this->getFields(), $this->request['identifier']);
        }

        /*
         * GET on /collection/identifier/modifier
         */
        if (isset($this->request['modifier'])) {
            if ($this->request['modifier'] === '$download') {
                return $this->getResource($this->request['identifier']);
            }
            else if ($this->request['modifier'] === '$tags') {
                return $this->getTags($this->request['identifier']);
            }
        }

        /*
         * If you get there, resource does not exist !
         */
        return $this->error('Not Found', 404);
    }

    /**
     * Default process of HTTP POST requests
     *
     * defaultPost() wait for one or more file containing JSON
     * 
     *      curl -F "file[]=@file1.json" -X POST http://localhost/resto/Collection
     * 
     * @param array $array an array of GeoJSON featureCollection or an array of JSON
     * 
     */
    public function defaultPost($array) {
        
        /*
         * POST on /collection/identifier/$tags
         * See ResourceTagger module
         */
        if ($this->request['identifier'] && $this->request['modifier'] === '$tags') {
            
            /*
             * POST on /collection/identifier/$tags is processed by ResourceTagger module
             */
            if (!class_exists('ResourceTagger')) {
                throw new Exception('Forbidden', 403);
            }
            
            /*
             * Tag /collection/identifier resource
             */
            $resourceTagger = new ResourceTagger($this);
            $this->response = $resourceTagger->tag(isset($array) ? $array : getFiles(array(
                'permissive' => true
            )));

        }
        /*
         * POST on /collection
         * $array should be an array of GeoJSON featureCollections
         */
        else {
            
            /*
             * Identifier should not be set unless it is the special keyword $tags
             */
            if ($this->request['identifier']) {

                if ($this->request['identifier'] === '$tags') {

                    /*
                     * POST on /collection/$tags is processed by ResourceTagger module
                     */
                    if (!class_exists('ResourceTagger')) {
                        throw new Exception('Forbidden', 403);
                    }

                    /*
                     * Tag resources within /collection/
                     */
                    $resourceTagger = new ResourceTagger($this);
                    $this->response = $resourceTagger->tag(isset($array) ? $array : getFiles(array(
                        'permissive' => true
                    )));

                }
                /*
                 * POST on /collection/$rights
                 * See RightsManager module
                 */
                else if ($this->request['identifier'] === '$rights') {

                    /*
                     * POST on /collection/$rights is processed by RightsManager module
                     */
                    if (!class_exists('RightsManager')) {
                        throw new Exception('Forbidden', 403);
                    }

                    /*
                     * Set rights for collection
                     */
                    $rightsManager = new RightsManager($this);
                    $this->response = $rightsManager->add(isset($array) ? $array : getFiles(array(
                        'permissive' => true
                    )));

                }
                else {
                    throw new Exception('Forbidden', 403);
                }
            }
            /*
             * POST on /collection is processed by ResourceManager module
             */
            else {

                if (!class_exists('ResourceManager')) {
                    throw new Exception('Forbidden', 403);
                }

                $resourceManager = new ResourceManager($this);

                /*
                 * Important : if $array is not set, then it is assume that POST data is GeoJSON (files or stream)
                 *
                 * If it is not the case, you should superseed this function is the child Controller
                 */
                $this->response = $resourceManager->create(isset($array) ? $array : getFiles(array()));
            }
        }
        
        $this->responseStatus = 200;

    }

    /**
     * Default process of HTTP PUT requests
     * 
     * TODO - Update a resource from the collection |PUT| /collection/identifier
     */
    public function defaultPut() {
        
        /*
         * Identifier should be set
         */
        if (!$this->request['identifier']) {
            throw new Exception('Forbidden', 403);
        }
        
        $this->response = array('PUT' => 'Forbidden');
        $this->responseStatus = 403;
    }

    /**
     * Default process of HTTP DELETE requests
     * 
     * TODO - Delete a resource from the collection |DELETE| /collection/identifier
     */
    public function defaultDelete() {
        
        /*
         * DELETE rights for groupid on collection
         */
        if ($this->request['identifier'] === '$rights') {
            if (class_exists('RightsManager')) {
                $rightsManager = new RightsManager($this);
                $this->response = $rightsManager->delete();
            }
            else {
                throw new Exception('Forbidden', 403);
            }
        }
        /*
         * Identifier should be set
         */
        else if (!$this->request['identifier']) {
            throw new Exception('Forbidden', 403);
        }
        else {
            $this->response = array('DELETE' => 'Forbidden');
            $this->responseStatus = 403;
        }
    }
    
    /**
     * Return description array
     */
    final public function getDescription() {
        return $this->description;
    }

    final public function getResponseStatus() {
        return $this->responseStatus;
    }

    final public function getResponse() {
        return $this->response;
    }
    
    /*
     * Set error message
     * 
     * @param {String} $message error message
     * @param {Integer} $code HTTP error code
     */

    final protected function error($message, $code) {
        $this->response = array('ErrorCode' => $code, 'ErrorMessage' => $message);
        $this->responseStatus = $code;
    }

    /*
     * Return database column name for RESTo model $key or null if there is none
     * 
     * Considering $this->description['model']($key) as "value"
     * 
     *  - IF "value" is a string prefixed with 'db:' THEN "value" without 'db:' prefixed is returned
     *  - IF "value" is an array THEN element 'dbKey' is returned without 'db:' prefix
     *  - Otherwise null is returned
     *  
     */
    final protected function getModelName($key) {
        return getModelName($this->description['model'], $key);
    }

    /**
     * Return database column value for $key 
     * 
     * @param string $key - RESTo model key name
     * @param array/string $values - Values returned by the database
     */
    final protected function getModelValue($key, $values) {
        return getModelValue($this->description['model'], $key, $values);
    }
    
    /**
     * Return database column type for $key 
     * 
     * @param string $key - RESTo model key name
     */
    final protected function getModelType($key) {
        return getModelType($this->description['model'], $key);
    }
    
    /*
     * Return an array of request parameters formated for output url
     * 
     * @param {array} $list - list of parameters to add/modify
     * 
     */

    final private function writeRequestParams($list = null) {

        $arr = array();

        /*
         * No input $list - returns all params unmodified
         * Note : assertion checks if $list is an associative array
         */
        if (!$list || !($list !== array_values($list))) {
            foreach ($this->request['params'] as $key => $value) {
                
                /*
                 * Support key tuples
                 */
                if (is_array($value)) {
                    for ($i = 0, $l = count($value); $i < $l; $i++) {
                        $arr[$this->outputName($key) . '[]'] = $value[$i];
                    }
                }
                else {
                    $arr[$this->outputName($key)] = $value;
                }
            }
        }
        /*
         * Input $list - modify params accordingly and add $list elements
         * that are not present in params
         */
        else {
            foreach ($this->request['params'] as $key => $value) {
                $skip = false;
                foreach (array_keys($list) as $key2) {
                    if ($key2 === $key) {
                        $skip = true;
                        break;
                    }
                }
                if (!$skip) {
                    
                   /*
                    * Support key tuples
                    */
                   if (is_array($value)) {
                       for ($i = 0, $l = count($value); $i < $l; $i++) {
                           $arr[$this->outputName($key) . '[]'] = $value[$i];
                       }
                   }
                   else {
                       $arr[$this->outputName($key)] = $value;
                   }
                }
            }
            foreach ($list as $key => $value) {
                
                /*
                 * Support key tuples
                 */
                if (is_array($value)) {
                    for ($i = 0, $l = count($value); $i < $l; $i++) {
                        $arr[$this->outputName($key) . '[]'] = $value[$i];
                    }
                }
                else {
                    $arr[$this->outputName($key)] = $value;
                }
            }
        }

        $arr['format'] = $this->request['format'];

        return $arr;
    }

    /**
     * 
     * Prepare an SQL WHERE clause from input filterName
     * 
     * @param {Array} $requestParams (with model keys)
     * @param {String} $filterName
     * @param {boolean} $exclusion - if true, exclude instead of include filter (WARNING ! only works for geometry and keywords)
     * 
     */
    final protected function prepareFilterQuery($requestParams, $filterName, $exclusion = false) {

        if (!$filterName) {
            return null;
        }

        /*
         * Remove ? from filterName
         */
        $filterName = str_replace('?', '', $filterName);

        /*
         * Do not process special filters
         */
        $exclude = array(
            'count',
            'startIndex',
            'startPage',
            'language',
            // geo:lat and geo:radius are linked to geo:lon
            'geo:lat',
            'geo:radius'
        );

        if (in_array($filterName, $exclude)) {
            return null;
        }
        
        /*
         * Spatial filter preseance is
         *  - geo:lon / geo:lat
         *  - geo:name
         *  - geo:box
         */
        if ($filterName === 'geo:box') {
            if ($requestParams['geo:lon'] && (in_array('geo:lon', $this->description['searchFiltersList']) || in_array('geo:lon?', $this->description['searchFiltersList']))) {
                unset($this->request['realParams']['geo:box']);
                return null;
            }
            if ($requestParams['geo:name'] && (in_array('geo:name', $this->description['searchFiltersList']) || in_array('geo:name?', $this->description['searchFiltersList']))) {
                unset($this->request['realParams']['geo:box']);
                return null;
            }
        }
        if ($filterName === 'geo:name') {
             if ($requestParams['geo:lon'] && (in_array('geo:lon', $this->description['searchFiltersList']) || in_array('geo:lon?', $this->description['searchFiltersList']))) {
                return null;
            }
        }
        
        /*
         * Get filter type
         */
        $type = getRESToType($this->getModelType($this->description['searchFiltersDescription'][$filterName]['key']));

        /*
         * Get operation
         */
        $operation = $this->description['searchFiltersDescription'][$filterName]['operation'];

        if (isset($requestParams[$filterName]) && $requestParams[$filterName]) {

            /*
             * Check if filter as an associated column within database
             */
            if (!$this->getModelName($this->description['searchFiltersDescription'][$filterName]['key'])) {
                return null;
            }

            /*
             * Check if date is valid
             */
            if ($type === 'date') {

                if (!isISO8601($requestParams[$filterName])) {
                    return null;
                }

                /*
                 * time:start and time:end cannot be processed separately
                 * 
                 * The following schema show cases where input (time:start/time:end) pairs 
                 * intersect (db:startDate/db:completionDate) resources 
                 * 
                 * 
                 *     db:startDate               db:completionDate
                 *          X============================X
                 *                  
                 * 
                 * Case 1 : (db:startDate) >= (time:start) && (db:startDate) <= (time:end)
                 * 
                 *   time:start      time:end
                 *       X===============X
                 * 
                 * 
                 * Case 2 : (db:startDate) <= (time:start) && (db:completionDate) >= (time:end) 
                 * 
                 *             time:start      time:end
                 *                  X===============X
                 * 
                 * 
                 * Case 3 : (db:startDate) <= (time:start) && (db:completionDate) <= (time:end) && (db:completionDate) >= (time:start)
                 * 
                 *                        time:start      time:end
                 *                            X===============X
                 */
                if ($requestParams['time:start'] && $requestParams['time:end']) {
                    
                    /*
                     * time:start and time:end are linked to two differents colums in database
                     */
                    if (($this->getModelName($this->description['searchFiltersDescription']['time:start']['key']) !== $this->getModelName($this->description['searchFiltersDescription']['time:end']['key']))) {
                        return '((' . $this->getModelName($this->description['searchFiltersDescription']['time:start']['key']) . ' >= \'' . pg_escape_string($requestParams['time:start']) . '\' AND ' . $this->getModelName($this->description['searchFiltersDescription']['time:start']['key']) . ' <= \'' . pg_escape_string($requestParams['time:end']) . '\')'
                                . ' OR (' . $this->getModelName($this->description['searchFiltersDescription']['time:start']['key']) . ' <= \'' . pg_escape_string($requestParams['time:start']) . '\' AND ' . $this->getModelName($this->description['searchFiltersDescription']['time:end']['key']) . ' >= \'' . pg_escape_string($requestParams['time:end']) . '\')'
                                . ' OR (' . $this->getModelName($this->description['searchFiltersDescription']['time:start']['key']) . ' <= \'' . pg_escape_string($requestParams['time:start']) . '\' AND ' . $this->getModelName($this->description['searchFiltersDescription']['time:end']['key']) . ' <= \'' . pg_escape_string($requestParams['time:end']) . '\' AND ' . $this->getModelName($this->description['searchFiltersDescription']['time:end']['key']) . ' >= \'' . pg_escape_string($requestParams['time:start']) . '\'))';
                    }
                    /*
                     * time:start and time:end are linked to the same colum in database
                     */
                    else {
                        return '(' . $this->getModelName($this->description['searchFiltersDescription']['time:start']['key']) . ' >= \'' . pg_escape_string($requestParams['time:start']) . '\' AND ' . $this->getModelName($this->description['searchFiltersDescription']['time:end']['key']) . ' <= \'' . pg_escape_string($requestParams['time:end']) . '\')';
                    }
                }
            }

            /*
             * Set quote to "'" for non numeric filter types
             */
            $quote = $type === 'numeric' ? '' : '\'';

            /*
             * Simple case - non 'interval' operation
             * 
             * if operation is '=' and last character of input value is a '%' sign then perform a like instead of an =
             */
            if ($operation === '=' || $operation === '>' || $operation === '>=' || $operation === '<' || $operation === '<=') {
                if ($operation === '=' && substr($requestParams[$filterName], -1) === '%') {
                    return $this->getModelName($this->description['searchFiltersDescription'][$filterName]['key']) . ' LIKE ' . $quote . pg_escape_string($requestParams[$filterName]) . $quote;
                }
                return $this->getModelName($this->description['searchFiltersDescription'][$filterName]['key']) . ' ' . $operation . ' ' . $quote . pg_escape_string($requestParams[$filterName]) . $quote;
            }
            /*
             * Spatial operation ST_Intersects (Input bbox or polygon)
             */
            else if ($operation === 'intersects') {
                
                /*
                 * Default bounding box is the whole earth
                 */
                if ($filterName === 'geo:box') {
                    $lonmin = -180;
                    $lonmax = 180;
                    $latmin = -90;
                    $latmax = 90;
                    $coords = explode(',', $requestParams[$filterName]);
                    if (count($coords) === 4) {
                        $lonmin = is_numeric($coords[0]) ? $coords[0] : $lonmin;
                        $latmin = is_numeric($coords[1]) ? $coords[1] : $latmin;
                        $lonmax = is_numeric($coords[2]) ? $coords[2] : $lonmax;
                        $latmax = is_numeric($coords[3]) ? $coords[3] : $latmax;
                    }
                    if ($lonmin <= -180 && $latmin <= -90 && $lonmax >= 180 && $latmax >= 90) {
                        return null;
                    }
                    else {
                        return 'ST_' . $operation . '(' . $this->getModelName($this->description['searchFiltersDescription'][$filterName]['key']) . ", ST_GeomFromText('" . pg_escape_string('POLYGON((' . $lonmin . ' ' . $latmin . ',' . $lonmin . ' ' . $latmax . ',' . $lonmax . ' ' . $latmax . ',' . $lonmax . ' ' . $latmin . ',' . $lonmin . ' ' . $latmin . '))') . "', 4326))";
                    }
                }
                
            }
            /*
             * Spatial operation ST_Distance (Center point + radius)
             * 
             * WARNING ! Quick benchmark show that st_distance is 100x slower than st_intersects
             * 
             * TODO - check if st_distance performance can be improved.
             * 
             */
            else if ($operation === 'distance') {
                
                $use_distance = false;
                
                /*
                 * geo:lon and geo:lat have preseance to geo:name
                 * (avoid double call to Gazetteer)
                 */
                if ($requestParams['geo:lon'] && $requestParams['geo:lat']) {
                    $radius = radiusInDegrees(isset($requestParams['geo:radius']) ? floatval($requestParams['geo:radius']) : 10000, $requestParams['geo:lat']);
                    if ($use_distance) {
                        $lon = $requestParams['geo:lon'];
                        $lat = $requestParams['geo:lat'];
                    }
                    else {
                        $lonmin = $requestParams['geo:lon'] - $radius;
                        $latmin = $requestParams['geo:lat'] - $radius;
                        $lonmax = $requestParams['geo:lon'] + $radius;
                        $latmax = $requestParams['geo:lat'] + $radius;
                    }
                }
                /*
                 * Location case 
                 * Check in Gazetteer
                 */
                else if ($filterName === 'geo:name') {
                    if (class_exists('Gazetteer')) {
                        $gazetteer = new Gazetteer($this->R);
                        $locations = $gazetteer->locate($requestParams[$filterName], $this->description['dictionary']->language, null, $requestParams['geo:box']);
                        if (count($locations) > 0) {
                            $radius = radiusInDegrees(isset($requestParams['geo:radius']) ? floatval($requestParams['geo:radius']) : 10000, $requestParams['geo:lat']);
                            if ($use_distance){
                                $lon = $locations[0]['longitude'];
                                $lat = $locations[0]['latitude'];
                            }
                            else {
                                $lonmin = $locations[0]['longitude'] - $radius;
                                $latmin = $locations[0]['latitude'] - $radius;
                                $lonmax = $locations[0]['longitude'] + $radius;
                                $latmax = $locations[0]['latitude'] + $radius;
                            }        
                        }
                    }
                }
                
                if ($use_distance) {
                    return 'ST_distance(' . $this->getModelName($this->description['searchFiltersDescription'][$filterName]['key']) . ', ST_GeomFromText(\'' . pg_escape_string('POINT(' . $lon . ' ' . $lat . ')') . '\', 4326)) < ' . $radius;
                }
                
                return 'ST_intersects(' . $this->getModelName($this->description['searchFiltersDescription'][$filterName]['key']) . ", ST_GeomFromText('" . pg_escape_string('POLYGON((' . $lonmin . ' ' . $latmin . ',' . $lonmin . ' ' . $latmax . ',' . $lonmax . ' ' . $latmax . ',' . $lonmax . ' ' . $latmin . ',' . $lonmin . ' ' . $latmin . '))') . "', 4326))";
            
            }
            /*
             * hstore case - i.e. searchTerms in keywords column
             */
            else if ($operation === 'hstore') {
                $terms = array();
                $splitted = explode(' ', $requestParams[$filterName]);
                for ($i = 0, $l = count($splitted); $i < $l; $i++) {

                    /*
                     * If term as a '-' prefix then performs a "NOT hstore"
                     * If keyword contain a + then transform it into a ' '
                     */
                    $s = ($exclusion ? '-' : '') . $splitted[$i];
                    $not = '';
                    if (substr($s, 0, 1) === '-') {
                        $not = ' NOT ';
                        $s = substr($s, 1);
                    }
                    array_push($terms, $not . $this->getModelName($this->description['searchFiltersDescription'][$filterName]['key']) . "?'" . pg_escape_string(str_replace('-', ' ', $s)) . "'");
                }
                return join(' AND ', $terms);
            }

            /*
             * Interval case 
             * 
             *  If
             *      A is the value of $this->request['params'][$this->description['searchFiltersDescription'][$filterName]['osKey']]
             *  Then
             *      A = n1 then returns = n1
             *      A = {n1,n2} then returns  = n1 or  = n2
             *      A = [n1,n2] then returns  ≤ n1 and ≤ n2
             *      A = [n1,n2[ then returns ≤ n1 and B < n2
             *      A = ]n1,n2[ then returns < n1 and B < n2
             */
            else if ($operation === 'interval') {

                $values = explode(',', $requestParams[$filterName]);

                /*
                 * Check for simple equality (i.e. no ',' present)
                 */
                if (count($values) === 1) {
                    return $this->getModelName($this->description['searchFiltersDescription'][$filterName]['key']) . ' = ' . $quote . pg_escape_string($requestParams[$filterName]) . $quote;
                }
                /*
                 * Two values
                 */
                else if (count($values) === 2) {

                    /*
                     * First and last characters give operators
                     */
                    $op1 = substr(trim($values[0]), 0, 1);
                    $val1 = substr(trim($values[0]), 1);
                    $op2 = substr(trim($values[1]), -1);
                    $val2 = substr(trim($values[1]), 0, strlen(trim($values[1])) - 1);

                    /*
                     * A = {n1,n2} then returns  = n1 or = n2
                     */
                    if ($op1 === '{' && $op2 === '}') {
                        return '(' . $this->getModelName($this->description['searchFiltersDescription'][$filterName]['key']) . ' = ' . $quote . pg_escape_string($val1) . $quote . ' OR ' . $this->getModelName($this->description['searchFiltersDescription'][$filterName]['key']) . ' = ' . $quote . pg_escape_string($val2) . ')';
                    }

                    /*
                     * Other cases i.e. 
                     * A = [n1,n2] then returns <= n1 and <= n2
                     * A = [n1,n2[ then returns <= n1 and B < n2
                     * A = ]n1,n2[ then returns < n1 and B < n2
                     * 
                     */
                    if (($op1 === '[' || $op1 === ']') && ($op2 === '[' || $op2 === ']')) {
                        return $this->getModelName($this->description['searchFiltersDescription'][$filterName]['key']) . ($op1 === '[' ? ' >= ' : ' > ') . $quote . pg_escape_string($val1) . $quote . ' AND ' . $this->getModelName($this->description['searchFiltersDescription'][$filterName]['key']) . ($op2 === ']' ? ' <= ' : ' < ') . $quote . pg_escape_string($val2);
                    }
                }
            }
        }

        return null;
    }

    /**
     * 
     * Prepare SQL request from input request parameters
     * 
     * This can be superseeded in RestoController_*
     *
     * @param {Array} $fields
     * @param {Integer} $limit
     * @param {Integer} $offset
     * 
     */
    protected function prepareQuery($fields, $limit, $offset) {

        $missing = array();

        /*
         * By default, the real parameters are identicals to
         * the request parameters
         */
        $this->request['realParams'] = $this->request['params'];

        /*
         * If QueryAnalyzer module is set, the request parameters
         * are recomputed based on their analysis
         */
        if (class_exists('QueryAnalyzer')) {
            $qa = new QueryAnalyzer($this->description['dictionary'], $this->description['searchFiltersDescription'], class_exists('Gazetteer') ? new Gazetteer($this->R) : null);
            $analyze = $qa->analyze($this->request['params']);
            $this->request['realParams'] = $analyze['analyze'];
            $this->request['queryAnalyzeProcessingTime'] = $analyze['queryAnalyzeProcessingTime'];
        }

        /*
         * Check that mandatory filters are set
         */
        for ($i = 0, $l = count($this->description['searchFiltersList']); $i < $l; $i++) {
            if (substr($this->description['searchFiltersList'][$i], -1) !== '?' && (!$this->request['params'][$this->description['searchFiltersList'][$i]])) {
                array_push($missing, $this->description['searchFiltersList'][$i]);
            }
        }

        /*
         * Missing mandatory elements ?
         */
        if (count($missing) > 0) {
            return array('missing' => $missing);
        }

        /*
         * Prepare WHERE clause from filter
         */
        $filters = array();
        for ($i = 0, $l = count($this->description['searchFiltersList']); $i < $l; $i++) {

            /*
             * time:end is processed along with time:start
             * (see $this->prepareFilterQuery(...) function
             */
            if (rtrim($this->description['searchFiltersList'][$i], '?') !== 'time:end') {
                array_push($filters, $this->prepareFilterQuery($this->request['realParams'], $this->description['searchFiltersList'][$i]));
            }
        }
        
        /**
         * Add filters depending on user rights
         */
        $rights = $this->R->getUser()->getRights($this->description['name'], 'get', 'search');
        if (is_array($rights)) {
            foreach(array('include', 'exclude') as $modifier) {
                if (isset($rights[$modifier])) {
                    foreach ($rights[$modifier] as $key => $value) {
                        $modelKey = $this->modelNameFromRestoKey($key);
                        $arr = null;
                        if ($key === 'keywords') {
                            $arr = array(
                                $modelKey => join(' ', $value)
                            );
                        }
                        /*
                         * TODO for geometry !
                         */
                        else if ($key === 'geometry') {

                        }
                        else {
                            /*
                             * Currently only exclusion of 'keywords' is supported
                             */
                            if ($rights['modifier'] === 'include') {
                                $arr = array(
                                    $modelKey => $value
                                );
                            }
                        }
                        if (isset($arr)) {
                            array_push($filters, $this->prepareFilterQuery($arr, $modelKey, $modifier === 'exclude' ? true : false));
                        }        
                    }
                }
            }
        }
        /**
         * Default order is acquisition startDate
         */
        $orderBy = $this->dbConnector->get('orderBy') ? $this->dbConnector->get('orderBy') : $this->getModelName('startDate') . ' DESC';

        /**
         * Filters
         */
        $oFilter = superImplode(' AND ', $filters);
        
        /*
         * Note that the total number of results (i.e. with no LIMIT constraint)
         * is retrieved with PostgreSQL "count(*) OVER()" technique
         */
        return array('query' => 'SELECT ' . superImplode(',', $fields) . ($this->R->realCount ? ',count(' . $this->getModelName('identifier') . ') OVER() AS totalcount' : '') . ' FROM ' . $this->dbConnector->getSchema() . '.' . $this->dbConnector->getTable() . ($oFilter ? ' WHERE ' . $oFilter : '') . ' ORDER BY ' . $orderBy . ' LIMIT ' . $limit . ' OFFSET ' . $offset);
    }

    /**
     * Return OpenSearch Description xml file
     * e.g.
     * <OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/" xmlns:geo="http://a9.com/-/opensearch/extensions/geo/1.0/" xmlns:time="http://a9.com/-/opensearch/extensions/time/1.0/">
     *      <ShortName>OpenSearch search</ShortName>
     *      <Description>My OpenSearch search interface</Description>
     *      <Tags>opensearch</Tags>
     *      <Contact>admin@myserver.org</Contact>
     *      <Url type="application/atom+xml" template="http://myserver.org/Controller_name/?q={searchTerms}&bbox={geo:box?}&format=atom&startDate={time:start?}&completionDate={time:end?}&modified={time:start?}&platform={take5:platform?}&instrument={take5:instrument?}&product={take5:product?}&maxRecords={count?}&nextRecord={startIndex?}"/>
     *      <LongName>My OpenSearch search interface</LongName>
     *      <Query role="example" searchTerms="observatory"/>
     *      <Attribution>mapshup.info</Attribution>
     *      <Language>fr</Language>
     * </OpenSearchDescription>
     * 
     * @param {String} $version - default "1.0"
     * @param {String} $encoding - default "UTF-8"
     */
    final protected function describeCollection($version = '1.0', $encoding = 'UTF-8') {

        /*
         * Hack - avoir RESTo::send() to be called
         */
        $this->description['forceStream'] = true;

        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument($version, $encoding);

        /*
         * OpsenSearchDescription - Start element
         */
        $xml->startElement('OpenSearchDescription');
        $xml->writeAttribute('xmlns', 'http://a9.com/-/spec/opensearch/1.1/');
        $xml->writeAttribute('xmlns:os', 'http://a9.com/-/spec/opensearch/1.1/');
        $xml->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $xml->writeAttribute('xmlns:time', 'http://a9.com/-/opensearch/extensions/time/1.0/');
        $xml->writeAttribute('xmlns:geo', 'http://a9.com/-/opensearch/extensions/geo/1.0/');
        $xml->writeAttribute('xmlns:eo', 'http://a9.com/-/opensearch/extensions/eo/1.0/');
        $xml->writeAttribute('xmlns:rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
        $xml->writeAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $xml->writeAttribute('xmlns:sru', 'http://a9.com/-/opensearch/extensions/sru/2.0/');

        /*
         * Read from config file
         */
        $xml->writeElement('ShortName', $this->description['os']['ShortName']);
        $xml->writeElement('Description', $this->description['os']['Description']);
        $xml->writeElement('Tags', $this->description['os']['Tags']);
        $xml->writeElement('Contact', $this->description['os']['Contact']);

        /*
         * Generate search urls from $this->description['searchFiltersList'] and $formats
         */
        foreach (Resto::$contentTypes as $format => $mimeType) {

            $url = updateUrl($this->request['restoUrl'] . $this->request['collection'], array('format' => $format));

            /*
             * Roll over filters
             */
            foreach ($this->description['searchFiltersList'] as $filterType) {
                $url .= '&' . $this->description['searchFiltersDescription'][str_replace('?', '', $filterType)]['osKey'] . '={' . $filterType . '}';
            }
            $xml->startElement('Url');
            $xml->writeAttribute('type', $mimeType);
            $xml->writeAttribute('template', $url);
            $xml->endElement(); // Url
        }
        // URLS
        $xml->writeElement('LongName', $this->description['os']['LongName']);
        $xml->startElement('Query');
        $xml->writeAttribute('role', 'example');
        $xml->writeAttribute('searchTerms', $this->description['os']['Query']);
        $xml->endElement(); // Query
        $xml->writeElement('Developper', $this->description['os']['Developper']);
        $xml->writeElement('Attribution', $this->description['os']['Attribution']);
        $xml->writeElement('SyndicationRight', 'open');
        $xml->writeElement('AdultContent', 'false');
        for ($i = 0, $l = count($this->description['acceptedLangs']); $i < $l; $i++) {
            $xml->writeElement('Language', $this->description['acceptedLangs'][$i]);
        }
        $xml->writeElement('OutputEncoding', 'UTF-8');
        $xml->writeElement('InputEncoding', 'UTF-8');


        /*
         * OpsenSearchDescription - end element
         */
        $xml->endElement();

        $this->response = $xml->outputMemory(true);
        $this->responseStatus = 200;

        /*
         * Store output for performance
         */
        ob_start();

        header('HTTP/1.1 200 OK');
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/xml');

        /*
         * Flush result
         */
        echo $this->response;
        ob_end_flush();
    }

    /**
     * Get resources metadata within collection
     * 
     * @param {array} $fields - list of fields to retrieve
     */
    final protected function getCollection($fields) {
        
        /*
         * Check authorization
         */
        $rights = $this->R->getUser()->getRights($this->description['name'], 'get', 'search');
        if ($rights === false) {
            return $this->error('Forbidden', 403);
        }
        
        /*
         * Number of returned results
         */
        $limit = $this->dbConnector->getResultsPerPage($this->description['name']);

        /*
         * Superseed number of returned results - never greater than MAXIMUM_LIMIT
         */
        if (isset($this->request['params']['count']) && is_numeric($this->request['params']['count'])) {
            $limit = min($this->request['params']['count'], $this->dbConnector->getMaximumResultsPerPage());
        }

        /**
         * Search offset - first element starts at offset 0
         */
        $offset = 0;

        /*
         * Superseed startIndex
         */
        if (isset($this->request['params']['startIndex']) && is_numeric($this->request['params']['startIndex'])) {
            $offset = ($this->request['params']['startIndex']) - 1;
        }

        /*
         * Prepare query
         */
        $prepared = $this->prepareQuery($fields, $limit, $offset);
        
        /*
         * Invalid query (i.e. missing mandatory elements)
         */
        if (isset($prepared['missing'])) {

            /*
             * Create Empty GeoJSON
             */
            ksort($prepared['missing']);
            $this->response = array(
                'type' => 'FeatureCollection',
                'totalResults' => 0,
                'id' => UUIDv5(Resto::UUID, $this->request['collection'] . ':' . implode($prepared['missing'])),
                'missing' => $prepared['missing'],
                'links' => array(),
                'features' => array()
            );
            $this->responseStatus = 200;

            return;
        }

        /*
         * Request start time
         */
        $requestStartTime = microtime(true);

        /*
         * Retrieve products from database
         */
        try {
            $dbh = $this->dbConnector->getConnection();
            if (!$dbh) {
                throw new Exception('Database connection error', 500);
            }
            $products = pg_query($dbh, $prepared['query']);
            if (!$products) {
                pg_close($dbh);
                throw new Exception('Database connection error', 500);
            }
        } catch (Exception $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }

        $features = array();

        /*
         * BaseUrl can be modified if language is forced !
         */
        $collectionUrl = $this->request['restoUrl'] . $this->request['collection'] . '/';
        $baseUrl = updateUrl($collectionUrl, array($this->description['searchFiltersDescription']['language']['osKey'] => $this->description['dictionary']->language));

        /*
         * Loop over all products
         */
        while ($product = pg_fetch_assoc($products)) {
            
            $product['links'] = array();
            
            /*
             * Prepare keyword urls
             */
            $product['keywords'] = $this->getKeywords($product, $baseUrl);
            
            /*
             * Set alternate urls
             */
            array_push($product['links'], array(
                'rel' => 'alternate',
                'type' => Resto::$contentTypes['html'],
                'title' => $this->description['dictionary']->translate('_htmlLink', $product['identifier']),
                'href' => updateUrl($collectionUrl . $product['identifier'] . '/', array('format' => 'html'))
            ));
            
            array_push($product['links'], array(
                'rel' => 'alternate',
                'type' => Resto::$contentTypes['atom'],
                'title' => $this->description['dictionary']->translate('_atomLink', $product['identifier']),
                'href' => updateUrl($collectionUrl . $product['identifier'] . '/', array('format' => 'atom'))
            ));
            
            array_push($product['links'], array(
                'rel' => 'alternate',
                'type' => Resto::$contentTypes['json'],
                'title' => $this->description['dictionary']->translate('_jsonLink', $product['identifier']),
                'href' => updateUrl($collectionUrl . $product['identifier'] . '/', array('format' => 'json'))
            ));
            
            /*
             * Add feature array to feature collection array
             */
            array_push($features, $this->toFeature($product));

            /*
             * ...and set the total number of result without LIMIT constraint
             * (see prepareQuery() function))
             */
            $total = isset($product['totalcount']) ? $product['totalcount'] : -1;
        }

        /*
         * Close database connection
         */
        pg_close($dbh);

        /*
         * Compute links i.e. self, first, next, previous and last URLs
         */
        $count = count($features);
        $startIndex = $offset + 1;
        $lastIndex = $startIndex + $count - 1;
        $total = $total ? $total : $count;
        
        /*
         * Query is made from request parameters
         */
        $query = array();
        $exclude = array(
            'count',
            'startIndex',
            'startPage'
        );
        foreach ($this->request['params'] as $key => $value) {
            if (in_array($key, $exclude)) {
                continue;
            }
            $query[$key] = $key === 'searchTerms' ? stripslashes($value) : $value;
        }

        /*
         * Real query is the query defined by queryAnalyzer
         */
        $real = array();
        foreach ($this->request['realParams'] as $key => $value) {
            if (in_array($key, $exclude)) {
                continue;
            }
            $real[$key] = $value;
        }

        /*
         * Request stop time
         */
        $requestStopTime = microtime(true);

        /*
         * Create GeoJSON
         */
        ksort($query);
        $this->response = array(
            'type' => 'FeatureCollection',
            'title' => $query['searchTerms'],
            'id' => UUIDv5(Resto::UUID, $this->request['collection'] . ':' . implode($query)),
            'totalResults' => $total,
            'startIndex' => $startIndex,
            'lastIndex' => $lastIndex,
            'query' => array(
                'original' => $query,
                'real' => $real,
                'queryAnalyzeProcessingTime' => $this->request['queryAnalyzeProcessingTime'],
                'searchProcessingTime' => $requestStopTime - $requestStartTime
            ),
            'links' => array(
                array(
                    'rel' => 'self',
                    'type' => Resto::$contentTypes['json'],
                    'title' => $this->description['dictionary']->translate('_selfCollectionLink'),
                    'href' => updateUrl($baseUrl, $this->writeRequestParams())
                ),
                array(
                    'rel' => 'alternate',
                    'type' => Resto::$contentTypes['html'],
                    'title' => $this->description['dictionary']->translate('_alternateCollectionLink'),
                    'href' => updateUrl(updateUrl($baseUrl, $this->writeRequestParams()), array('format' => 'html'))
                )
            ),
            'features' => $features
        );

        /*
         * Previous URL is the previous URL from the self URL
         * startIndex cannot be lower than 1
         */
        if ($startIndex > 1) {
            array_push($this->response['links'], array(
                'rel' => 'previous',
                'type' => Resto::$contentTypes['json'],
                'title' => $this->description['dictionary']->translate('_previousCollectionLink'),
                'href' => updateUrl($baseUrl, $this->writeRequestParams(array(
                            'startIndex' => max($startIndex - $limit, 1),
                            'count' => $limit)))
                )
            );
            // First URL is the first search URL i.e. with startIndex = 1
            array_push($this->response['links'], array(
                'rel' => 'first',
                'type' => Resto::$contentTypes['json'],
                'title' => $this->description['dictionary']->translate('_firstCollectionLink'),
                'href' => updateUrl($baseUrl, $this->writeRequestParams(array(
                            'startIndex' => 1,
                            'count' => $limit)))
                )
            );
        }

        /*
         * Next URL is the next search URL from the self URL
         * startIndex cannot be greater than the one from lastURL 
         */
        if ($lastIndex < $total) {
            array_push($this->response['links'], array(
                'rel' => 'next',
                'type' => Resto::$contentTypes['json'],
                'title' => $this->description['dictionary']->translate('_nextCollectionLink'),
                'href' => updateUrl($baseUrl, $this->writeRequestParams(array(
                            'startIndex' => min($startIndex + $limit, $total - $limit + 1),
                            'count' => $limit)))
                )
            );
            // Last URL has the highest startIndex
            array_push($this->response['links'], array(
                'rel' => 'last',
                'type' => Resto::$contentTypes['json'],
                'title' => $this->description['dictionary']->translate('_lastCollectionLink'),
                'href' => updateUrl($baseUrl, $this->writeRequestParams(array(
                            'startIndex' => max($total - $limit + 1, 1),
                            'count' => $limit)))
                )
            );
        }
        
        /*
         * If total = -1 then it means that total number of resources is unknown
         * (i.e. realCount is set to false in resto.ini)
         * 
         * The last index cannot be displayed
         */
        if ($total === -1 && $count >= $limit) {
            array_push($this->response['links'], array(
                'rel' => 'next',
                'type' => Resto::$contentTypes['json'],
                'title' => $this->description['dictionary']->translate('_nextCollectionLink'),
                'href' => updateUrl($baseUrl, $this->writeRequestParams(array(
                            'startIndex' => $startIndex + $limit,
                            'count' => $limit)))
                )
            );
        }
        
        /*
         * Store query
         */
        if (class_exists('QueryStorage')) {
            $storage = new QueryStorage($this->R);
            $storage->store(array(
                'service' => 'search',
                'collection' => $this->request['collection'],
                'resource' => $query['searchTerms'],
                'realquery' => $real,
                'url' => updateUrl($baseUrl, $this->writeRequestParams())
            ));
        }

        $this->responseStatus = 200;
    }

    /**
     * Get resource metadata
     * 
     * @param {array} $fields - list of fields to retrieve
     * @param {string} $identifier - identifier of product to search
     */
    final protected function describeResource($fields, $identifier) {

        /*
         * Retrieve products from database
         */
        try {
            $dbh = $this->dbConnector->getConnection();
            if (!$dbh) {
                throw new Exception('Database connection error', 500);
            }
            $products = pg_query($dbh, 'SELECT ' . superImplode(',', $fields) . ($this->R->realCount ? ',count(*) OVER() AS totalcount' : '') . ' FROM ' . $this->dbConnector->getSchema() . '.' . $this->dbConnector->getTable() . ' WHERE ' . $this->getModelName('identifier') . "='" . pg_escape_string($identifier) . "'");
            if (!$products) {
                pg_close($dbh);
                throw new Exception('Database connection error', 500);
            }
        } catch (Exception $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }

        /*
         * BaseUrl can be modified if language is forced !
         */
        $collectionUrl = $this->request['restoUrl'] . $this->request['collection'] . '/';
        $resourceUrl = $collectionUrl . $identifier . '/';
        $mods = array($this->description['searchFiltersDescription']['language']['osKey'] => $this->description['dictionary']->language, 'format' => $this->request['format']);

        /*
         * Retrieve product
         */
        $product = pg_fetch_assoc($products);

        /*
         * Close database connection
         */
        pg_close($dbh);

        /*
         * No result - return empty product
         */
        if (!$product) {

            $this->response = array(
                'type' => 'FeatureCollection',
                'totalResults' => 0,
                'links' => array(
                    array(
                        'rel' => 'self',
                        'type' => Resto::$contentTypes['json'],
                        'title' => $this->description['dictionary']->translate('_selfCollectionLink'),
                        'href' => updateUrl($resourceUrl, $mods)
                    ),
                    array(
                        'rel' => 'alternate',
                        'type' => Resto::$contentTypes['html'],
                        'title' => $this->description['dictionary']->translate('_alternateCollectionLink'),
                        'href' => updateUrl($resourceUrl, array($this->description['searchFiltersDescription']['language']['osKey'] => $this->description['dictionary']->language, 'format' => 'html'))
                    )
                ),
                'features' => array()
            );
        } else {

            /*
             * Prepare keyword urls
             */
            $product['keywords'] = $this->getKeywords($product, updateUrl($collectionUrl, $mods));
            
            /*
             * Set self url
             */
            if (!is_array($product['links'])) {
                $product['links'] = array();
            }
            array_push($product['links'], array(
                'rel' => 'alternate',
                'type' => Resto::$contentTypes['html'],
                'title' => $this->description['dictionary']->translate('_htmlLink', $product['identifier']),
                'href' => updateUrl($resourceUrl, updateUrl($resourceUrl, array($this->description['searchFiltersDescription']['language']['osKey'] => $this->description['dictionary']->language, 'format' => 'html')))
            ));
            array_push($product['links'], array(
                'rel' => 'alternate',
                'type' => Resto::$contentTypes['json'],
                'title' => $this->description['dictionary']->translate('_jsonLink', $product['identifier']),
                'href' => updateUrl($resourceUrl, updateUrl($resourceUrl, array($this->description['searchFiltersDescription']['language']['osKey'] => $this->description['dictionary']->language, 'format' => 'json')))
            ));
            array_push($product['links'], array(
                'rel' => 'alternate',
                'type' => Resto::$contentTypes['atom'],
                'title' => $this->description['dictionary']->translate('_atomLink', $product['identifier']),
                'href' => updateUrl($resourceUrl, updateUrl($resourceUrl, array($this->description['searchFiltersDescription']['language']['osKey'] => $this->description['dictionary']->language, 'format' => 'atom')))
            ));
            $this->response = array(
                'type' => 'FeatureCollection',
                'totalResults' => 1,
                'links' => $product['links'],
                'features' => array($this->toFeature($product))
            );
        }
        
        /*
         * Store query
         */
        if (class_exists('QueryStorage')) {
            $storage = new QueryStorage($this->R);
            $storage->store(array(
                'service' => 'describe',
                'collection' => $this->request['collection'],
                'resource' => $identifier,
                'url' => $resourceUrl
            ));
        }

        $this->responseStatus = 200;
    }

    /**
     * Get resource i.e. download resource
     * 
     * @param {string} $identifier - identifier of product to download
     */
    protected function getResource($identifier) {
        
        /*
         * Check authorization
         */
        $rights = $this->R->getUser()->getRights($this->description['name'], 'get', 'download');
        if (!$rights['enabled']) {
            return $this->error('Forbidden', 403);
        }
        
        $product = null;

        /*
         * Retrieve product url from database
         */
        if ($this->getModelName('archive')) {

            try {
                $dbh = $this->dbConnector->getConnection();
                if (!$dbh) {
                    throw new Exception('Database connection error', 500);
                }
                $products = pg_query($dbh, 'SELECT ' . $this->getModelName('archive') . ' AS archive' . ($this->getModelName('mimetype') ? ',' . $this->getModelName('mimetype') . ' AS mimetype  ' : '') . ' FROM ' . $this->dbConnector->getSchema() . '.' . $this->dbConnector->getTable() . ' WHERE ' . $this->getModelName('identifier') . "='" . pg_escape_string($identifier) . "'");
                if (!$products) {
                    pg_close($dbh);
                    throw new Exception('Database connection error', 500);
                }
            } catch (Exception $e) {
                return $this->error($e->getMessage(), $e->getCode());
            }

            /*
             * Retrieve product
             */
            $product = pg_fetch_assoc($products);

            /*
             * Close database connection
             */
            pg_close($dbh);
            
        }
        else {
            $product = $this->getModelValue('archive');
        }

        /*
         * No result - Not found
         */
        if (!$product) {
            return $this->error('Not Found', 404);
        }
        
        /*
         * Consolidate archive url
         */
        $product['archive'] = $this->getModelValue('archive', $product['archive']);

        /*
         * This is a stream - this will bypass RESTo::send() function
         */
        $this->description['forceStream'] = true;

        /*
         * Response should not be empty
         */
        $this->response = 'Download';
        $this->responseStatus = 200;
        
        /*
         * Store query
         */
        if (class_exists('QueryStorage')) {
            $storage = new QueryStorage($this->R);
            $storage->store(array(
                'service' => 'download',
                'collection' => $this->request['collection'],
                'resource' => $identifier,
                'url' => $product['archive']
            ));
        }
        
        /*
         * Stream file in 1024*1024 chunk tiles
         */
        header('HTTP/1.1 200 OK');
        header('Access-Control-Allow-Origin: *');
        header('Content-Disposition: attachment; filename="' . basename($product['archive']) . '"');
        header('Content-Type: ' . $product['mimetype'] ? $product['mimetype'] : 'application/unknown');
        readfile_chunked($product['archive'], 1024 * 1024);
    }

    /**
     * Get resource tags
     * Tags are string starting with a dash (i.e. #) - e.g. #school
     * 
     * @param {string} $identifier - identifier of product to get tags from
     */
    protected function getTags($identifier) {
        throw new Exception('Not Implemented', 501);
    }
    
    /**
     * Return the model parameter name equivalent to $inputName 
     *
     * @param string $inputName
     */
    final private function modelName($inputName) {

        foreach ($this->description['searchFiltersDescription'] as $searchFiltersDescriptionName => $properties) {
            if ($properties['osKey'] === $inputName) {
                return $searchFiltersDescriptionName;
            }
        }

        return null;
    }

    /**
     * Return the model parameter name equivalent to $inputName 
     *
     * @param string $inputName
     */
    final private function modelNameFromRestoKey($inputName) {

        foreach ($this->description['searchFiltersDescription'] as $searchFiltersDescriptionName => $properties) {
            if ($properties['key'] === $inputName) {
                return $searchFiltersDescriptionName;
            }
        }

        return null;
    }
    
    /**
     * Return the output parameter name equivalent to $searchFiltersDescriptionName 
     *
     * @param string $searchFiltersDescriptionName
     */
    final private function outputName($searchFiltersDescriptionName) {
        return $this->description['searchFiltersDescription'][$searchFiltersDescriptionName]['osKey'];
    }

    /**
     * Return a GeoJSON feature array from a json product
     * 
     * @param {Array} $product
     */
    final private function toFeature($product) {

        $properties = array();
        
        /*
         * Populate properties array with product keys
         */
        foreach ($product as $key => $value) {
            
            /*
             * These properties are excluded since they are processed separatly
             */
            if (in_array($key, array(
                        'geometry',
                        'archive',
                        'wms',
                        'bbox3857',
                        'totalcount',
                        'identifier'
                    ))) {
                continue;
            /*
             * Arrays property case
             */
            }
            else if ($key === 'links') {
                $properties[$key] = $value;
            }
            /*
             * Every other properties are properties from the RESTo model
             * (see getModelType() function in $RESTO_HOME/lib/functions.php)
             */
            else {
                $properties[$key] = getRESToType($this->getModelType($key)) === 'numeric' ? floatval($this->getModelValue($key, $value)) : $this->getModelValue($key, $value);
            }
        }

        /*
         * Services array
         */
        $properties['services'] = array();

        /*
         * WMS url (product full resolution visualization)
         * 
         * Notes :
         * 
         *          bbox3857 format is BOX(xmin xmax,ymin ymax)
         * 
         *      GetMap Url format is
         * 
         *          http://spirit.cnes.fr/cgi-bin/mapserv
         *                  ?map=/mount/take5/wms//map.map
         *                  &LAYERS=take5
         *                  &FORMAT=image%2Fpng
         *                  &TRANSITIONEFFECT=resize
         *                  &TRANSPARENT=true
         *                  &VERSION=1.1.1
         *                  &SERVICE=WMS
         *                  &REQUEST=GetMap
         *                  &STYLES=
         *                  &SRS=EPSG%3A3857
         *                  &BBOX=313086.0678125,6261721.35625,391357.58476563,6339992.8732031
         *                  &WIDTH=256
         *                  &HEIGHT=256
         */
        if (isset($product['wms'])) {
            $wms = $this->getModelValue('wms', array($product['wms'], str_replace(' ', ',', substr(substr($product['bbox3857'], 0, strlen($product['bbox3857']) - 1), 4))));
            if ($wms) {
                $properties['services']['browse'] = array(
                    'title' => 'Display full resolution product on map',
                    'layer' => array(
                        'type' => 'WMS',
                        'url' => $wms,
                        // mapshup needs layers to be set -> to be changed in mapshup
                        'layers' => ''
                    )
                );
            }

        }
        
        /*
         * Download url
         */
        $archive = $this->getModelValue('archive', $product['archive']);
        if (isset($archive)) {
            $properties['services']['download'] = array(
                'url' => $archive,
                'mimeType' => $this->getModelValue('mimetype', isset($product['mimetype']) ? $product['mimetype'] : null)
            );
        }

        return array(
            'type' => 'Feature',
            'id' => $product['identifier'],
            'geometry' => json_decode($product['geometry'], true),
            'properties' => $properties
        );
    }

    /**
     * 
     * Return array of keywords
     * Structure of output is 
     *      array(
     *          "id" => // Keyword id (optional)
     *          "type" => // Keyword type
     *          "value" => // Keyword value if it make sense
     *          "href" => // RESTo search url to get keyword
     *      )
     * 
     * @param array $product
     * @param string $baseUrl
     * @return array
     */
    final private function getKeywords($product, $baseUrl) {

        $keywords = array();

        /*
         * Add a keyword for year, month and day of acquisition
         */
        if (in_array('time:start', $this->description['searchFiltersList']) || in_array('time:start?', $this->description['searchFiltersList'])) {
            if ($product[$this->description['searchFiltersDescription']['time:start']['key']]) {
                $year = substr($product[$this->description['searchFiltersDescription']['time:start']['key']], 0, 4);
                $month = substr($product[$this->description['searchFiltersDescription']['time:start']['key']], 5, 2);
                $day = substr($product[$this->description['searchFiltersDescription']['time:start']['key']], 8, 2);
                $keywords[$year] = array(
                    'type' => 'date',
                    'href' => updateUrl($baseUrl, array('format' => $this->request['format'], $this->outputName('searchTerms') => $year))
                );
                $keywords[$year . '-' . $month] = array(
                    'type' => 'date',
                    'href' => updateUrl($baseUrl, array('format' => $this->request['format'], $this->outputName('searchTerms') => $year . '-' . $month))
                );
                $keywords[$year . '-' . $month . '-' . $day] = array(
                    'type' => 'date',
                    'href' => updateUrl($baseUrl, array('format' => $this->request['format'], $this->outputName('searchTerms') => $year . '-' . $month . '-' . $day))
                );
            }
        }
        /*
         * Add keywords from model
         */
        foreach (array_keys($this->description['searchFiltersDescription']) as $key) {
            if (isset($this->description['searchFiltersDescription'][$key]['keyword']) && isset($product[$this->description['searchFiltersDescription'][$key]['key']])) {
                $v = replace($this->description['searchFiltersDescription'][$key]['keyword']['value'], array($product[$this->description['searchFiltersDescription'][$key]['key']]));
                $keywords[$v] = array(
                    'type' => $this->description['searchFiltersDescription'][$key]['keyword']['type'],
                    'href' => updateUrl($baseUrl, array('format' => $this->request['format'], $this->outputName('searchTerms') => $v))
                );
            }
        }

        /*
         * Add keywords read from database
         * 
         * Keywords are produced from PostgreSQL which returns a string "#name" => "value" or "type:name" => "value" e.g.
         * 
         *      "disaster:flood"=>NULL, "country:canada"=>"23.5", "continent:north america"=>NULL
         *
         * Note : hstore_to_array() is only available in PostgreSQL >= 9.3
         */
        $json = json_decode('{' . str_replace('"=>"', '":"', str_replace('NULL', '""', $product[$this->description['searchFiltersDescription']['searchTerms']['key']])) . '}', true);
        
        /* 
         * Sort results by value (highest to lowest)
         */
        arsort($json);
        
        foreach ($json as $key => $value) {

            /*
             * $key format is "type:name"
             */
            $type = null;
            $name = $key;
            $splitted = explode(':', $key);
            if (count($splitted) > 1) {

                $type = $splitted[0];

                /*
                 * Do not display landuse_details
                 */
                if ($type === 'landuse_details') {
                    continue;
                }

                $name = substr($key, strlen($splitted[0]) + 1);
            }
            $translated = $this->description['dictionary']->translate($name, true);
            
            $keywords[$translated] = array();
            $keywords[$translated]['id'] = $name;
            if ($type !== null) {
                $keywords[$translated]['type'] = $type;
            }
            if ($value !== null) {
                $keywords[$translated]['value'] = $value;
            }
            $keywords[$translated]['href'] = updateUrl($baseUrl, array('format' => $this->request['format'], $this->outputName('searchTerms') => trim(str_replace(' ', '-', $translated))));
        }

        return $keywords;
    }
    
    /**
     * Return Collection database connector
     */
    final public function getDbConnector() {
        return $this->dbConnector;
    }
    
    /**
     * Return RESTo instance
     */
    final public function getParent() {
        return $this->R;
    }
    
    /**
     * Return database fields
     * 
     * @return array
     */
    final protected function getFields() {

        /*
         * Get Controller database fields
         */
        $fields = Array();
        foreach (array_keys($this->description['model']) as $key) {

            /*
             * Avoid null value
             */
            $v = $this->getModelName($key);
            if (!$v) {
                continue;
            }

            /*
             * Force geometry element to be retrieved as GeoJSON
             * Retrieve also BoundinBox in EPSG:3857
             */
            if ($key === 'geometry') {
                $postgisVersion = $this->dbConnector->postgisVersion;
                array_push($fields, 'ST_AsGeoJSON(' . $v . ') AS ' . $key);
                array_push($fields, ($postgisVersion < 2 ? 'ST_' : '') . 'Box2D(ST_Transform(' . $v . ', 3857)) AS bbox3857');
            }
            /*
             * Force keywords to be retrieved AS JSON
             */ else if ($key === 'keywords') {
                array_push($fields, $v . ' AS ' . $key);
            }
            /*
             * Other fields are retrieved normally
             */ else {
                array_push($fields, $v . ' AS "' . $key . '"');
            }
        }

        return $fields;
    }

    /*
     * Below are mandatory functions that
     * need to be defined within each extended
     * controller classes
     */
    abstract public function get();

    abstract public function post();

    abstract public function put();

    abstract public function delete();
}
