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
 * RESTo common functions
 */

/**
 * Modify $url with $mod query parameters
 * 
 * @param {string} $url
 * @param {array} $mod
 */
function updateUrl($url, $mod) {
    
    $components = explode("?", $url);
    $query = array();
    if (isset($components[1])) {
        $query = explode("&", $components[1]);
    }
    else {
        $url .= '?';
    }
    
    // modify/delete data
    foreach($query as $q){
        list($key, $value) = explode("=", $q);
        if(array_key_exists($key, $mod)) {
            if($mod[$key]) {
                $url = preg_replace('/'.$key.'='.$value.'/', $key.'='.$mod[$key], $url);
            }
            else {
                $url = preg_replace('/&?'.$key.'='.$value.'/', '', $url);
            }
        }
    }
    // add new data
    foreach($mod as $key => $value) {
        if($value && !preg_match('/'.$key.'=/', $url)) {
            $url .= '&'.$key.'='.$value;
        }
    }
    return $url;
} 


/**
 * 
 * Return true if input date string is ISO 8601 formatted
 * i.e. one in the following form :
 * 
 *      YYYY
 *      YYYY-MM
 *      YYYY-MM-DD
 *      YYYY-MM-DDTHH:MM:SS
 *      YYYY-MM-DDTHH:MM:SSZ
 *      YYYY-MM-DDTHH:MM:SS.sssss
 *      YYYY-MM-DDTHH:MM:SS.sssssZ
 *      YYYY-MM-DDTHH:MM:SS+HHMM
 *      YYYY-MM-DDTHH:MM:SS-HHMM
 *      YYYY-MM-DDTHH:MM:SS.sssss+HHMM
 *      YYYY-MM-DDTHH:MM:SS.sssss-HHMM
 * 
 * @param {String} $dateStr
 *    
 */
function isISO8601($dateStr) {

    /* Pattern for matching : YYYY */
    $patternYear = '\d{4}';

    /* Pattern for matching : YYYY-MM */
    $patternMonthExtend = '\d{4}-\d{2}';

    /* Pattern for matching : YYYY-MM-DD */
    $patternDateExtend = '\d{4}-\d{2}-\d{2}';

    /* Pattern for matching : YYYY-MM-DDTHH:MM:SS */
    $patternDateAndTimeExtend = '\d{4}-\d{2}-\d{2}T\d{2}\:\d{2}\:\d{2}';

    /* Pattern for matching : +HH:MM or -HH:MM */
    $patternTimeZoneExtend = '[\+|\-]\d{2}\:\d{2}';

    /** Pattern for matching : ,n or .n 
     *  where n is the fraction of seconds to one or more digits
     */
    $patternFractionSeconds = '[,|\.]\d+';

    /* Pattern for matching : YYYYMM */
    $patternMonth = '\d{4}\d{2}';

    /* Pattern for matching : YYYYMMDD */
    $patternDate = '\d{4}\d{2}\d{2}';

    /* Pattern for matching : YYYYMMDDTHHMMSS */
    $patternDateAndTime = '\d{4}\d{2}\d{2}T\d{2}\d{2}\d{2}';

    /* Pattern for matching : +HHMM or -HHMM */
    $patternTimeZone = '[\+|\-]\d{2}\d{2}';

    /**
     * Construct the regex to match all ISO 8601 format date case
     * The regex is constructed as a combination of all pattern       
     */
    $completePattern = '/^'
            . $patternYear . '$|^'
            . $patternMonthExtend . '$|^'
            . $patternDateExtend . '$|^'
            . $patternDateAndTimeExtend . '$|^'
            . $patternDateAndTimeExtend . 'Z$|^'
            . $patternDateAndTimeExtend . '' . $patternTimeZoneExtend . '$|^'
            . $patternDateAndTimeExtend . '' . $patternFractionSeconds . '$|^'
            . $patternDateAndTimeExtend . '' . $patternFractionSeconds . 'Z$|^'
            . $patternDateAndTimeExtend . '' . $patternFractionSeconds . '' . $patternTimeZoneExtend . '$|^'
            . $patternMonth . '$|^'
            . $patternDate . '$|^'
            . $patternDateAndTime . '$|^'
            . $patternDateAndTime . 'Z$|^'
            . $patternDateAndTime . '' . $patternTimeZone . '$|^'
            . $patternDateAndTime . '' . $patternFractionSeconds . '$|^'
            . $patternDateAndTime . '' . $patternFractionSeconds . 'Z$|^'
            . $patternDateAndTime . '' . $patternFractionSeconds . '' . $patternTimeZone . '$/i';

    return preg_match($completePattern, $dateStr);
}

/**
 * 
 * Return an ISO 8601 formatted YYYY-MM-DDT00:00:00 from
 * a valid iso8601 string
 * 
 * @param {String} $dateStr
 *    
 */
function toISO8601($dateStr) {

    // Year
    if (preg_match('/^\d{4}$/i', $dateStr)) {
        return $dateStr . '-01-01T00:00:00';
    }
    // Month
    else if (preg_match('/^\d{4}-\d{2}$/i', $dateStr)) {
        return $dateStr . '-01T00:00:00';
    }
    // Day
    else if (preg_match('/^\d{4}-\d{2}-\d{2}$/i', $dateStr)) {
        return $dateStr . 'T00:00:00';
    }
    
    return $dateStr;
}

/**
 * Upgraded implode($glue, $arr) function that
 * do not aggregate NULL elements in result
 */
function superImplode($glue, $arr) {
    $ret_str = "";
    foreach ($arr as $a) {
        $ret_str .= (is_array($a)) ? implode_r($glue, $a) : $a === NULL ? "" : strval($a) . $glue ;
    }
    if (strrpos($ret_str, $glue) != strlen($glue))
        $ret_str = substr($ret_str, 0, -(strlen($glue)));
    return $ret_str;
}

/**
 * Replace all occurences of a string
 * 
 *  Example :
 *      
 *      replace('Hello. My name is {a:1}. I live in {a:2}', array('Jérôme', 'Toulouse'));
 * 
 *  Will return
 * 
 *      'Hello. My name is Jérôme. I live in Toulouse
 * 
 * 
 * @param string $sentence
 * @param array $args
 * 
 */
function replace($sentence, $args) {
    
    /*
     * If args is a string convert to an array
     */
    if (is_string($args)) {
        $args = array($args);
    }
    
    /*
     * Replace additional arguments
     */
    if (false !== strpos($sentence, '{a:')) {
        $replace = array();
        for ($i = 0, $max = count($args); $i < $max; $i++) {
            $replace['{a:' . ($i + 1) . '}'] = $args[$i];
        }
        return strtr($sentence, $replace);
    }
    
    return $sentence;
}


/*
 * Read a file and display its content chunk by chunk
 * (i.e. avoid memory issue with large files)
 */
function readfile_chunked($filename, $chunkSize = 1048576, $retbytes = TRUE) {

    $buffer = "";
    $cnt = 0;
    $handle = fopen($filename, "rb");
    if ($handle === false) {
        return false;
    }
    while (!feof($handle)) {
        $buffer = fread($handle, $chunkSize);
        echo $buffer;
        ob_flush();
        flush();
        if ($retbytes) {
            $cnt += strlen($buffer);
        }
    }
    $status = fclose($handle);
    if ($retbytes && $status) {
        return $cnt; // return num. bytes delivered like readfile() does.
    }
    return $status;
}

/**
 * Remove URN prefix
 * 
 * @param {string} $str
 */
function stripURN($str) {
    $stripped = explode(':', $str);
    return $stripped[count($stripped) - 1];
}

/**
 * Remove OGC URN prefix
 * 
 * @param {string} $str
 */
function stripOGCURN($str) {
    return str_replace('urn:ogc:def:EOP:', '', $str);
}

/**
 * Transform input string to 7bits ascii equivalent (i.e. remove accent on letters and so on)
 * (see http://www.php.net/manual/fr/function.iconv.php)
 * 
 * @param {string} $text
 * @param {string} $charset
 */
function asciify($text, $charset = 'utf-8') {
    
    /*
     * Includes combinations of characters that present as a single glyph
     */
    if (is_string($text)) {
        $text = preg_replace_callback('/\X/u', __FUNCTION__, $text);
    }
    else if (is_array($text) && count($text) == 1 && is_string($text[0])) {
        
        /*
         * IGNORE characters that can't be TRANSLITerated to ASCII
         */
        $text = iconv("UTF-8", "ASCII//IGNORE//TRANSLIT", $text[0]);
        
        /*
         * The documentation says that iconv() returns false on failure but it returns ''
         */
        if ($text === '' || !is_string($text)) {
            $text = '?';
        }
        /*
         * If the text contains any letters...then remove all non-letters
         */
        else if (preg_match('/\w/', $text)) {
            $text = preg_replace('/\W+/', '', $text);
        }
    }
    /*
     * Text is not a string
     */
    else {
        $text = '';
    }
    
    return $text;
    
}

/**
 * Return database column name for RESTo model $key or null if there is none
 * 
 * Considering $model[$key] as "value"
 * 
 *  - IF "value" is a string prefixed with 'db:' THEN "value" without 'db:' prefixed is returned
 *  - IF "value" is an array THEN element 'dbKey' is returned without 'db:' prefix
 *  - Otherwise null is returned
 * 
 * @param array $model
 * @param string $key - RESTo model key name
 */
function getModelName($model, $key) {
    
    if (!key || !$model[$key]) {
        return null;
    }

    if (is_array($model[$key])) {
        if (isset($model[$key]['dbKey'])) {
            return substr($model[$key]['dbKey'], 3, strlen($model[$key]['dbKey']) - 1);
        }
        return null;
    }

    if (substr($model[$key], 0, 3) === 'db:') {
        return substr($model[$key], 3, strlen($model[$key]) - 1);
    }

    return null;
}

/**
 * Return database column value for $key 
 * 
 * @param array $model
 * @param string $key - RESTo model key name
 * @param array/string $value - Value returned by the database
 */
function getModelValue($model, $key, $value) {
   
    if (!key || !$model[$key]) {
        return !is_array($value) ? $value : null;
    }
 
    /*
     * PostgreSQL returns date as YYYY-MM-DD HH:MM:SS => space should be replaced by 'T'
     * to make a valid ISO8601 date
     */
    if (is_string($value) && getModelType($model, $key) === 'date') {
        $value = str_replace(' ', 'T', $value);
    }
 
    /*
     * Array case 
     *  - "column" entry should be the database column name (see getModelName($key) function)
     *  - "template" entry should be a template (e.g. http://blablab/{a:1}). If not set returns value
     */
    if (is_array($model[$key]) && isset($model[$key]['template'])) {
        return replace($model[$key]['template'], $value);
    }

    /*
     * Value is not set - returned constant
     */
    if (!$value && !is_array($model[$key]) && !getModelName($model, $key)) {
        return $model[$key];
    }

    return $value;
}

/**
 * Return database column type for $key 
 * 
 * @param array $model
 * @param string $key - RESTo model key name
 */
function getModelType($model, $key) {

    /*
     * Default SQL types for RESTo model properties
     */
    $sqlTypes = array(
        'identifier' => 'VARCHAR(250)',
        'parentIdentifier' => 'VARCHAR(250)',
        'title' => 'VARCHAR(250)',
        'description' => 'TEXT',
        'authority' => 'VARCHAR(50)',
        'startDate' => 'TIMESTAMP',
        'completionDate' => 'TIMESTAMP',
        'productType' => 'VARCHAR(50)',
        'processingLevel' => 'VARCHAR(50)',
        'platform' => 'VARCHAR(10)',
        'instrument' => 'VARCHAR(10)',
        'resolution' => 'NUMERIC(8,2)',
        'sensorMode' => 'VARCHAR(20)',
        'orbitNumber' => 'INTEGER',
        'quicklook' => 'VARCHAR(250)',
        'thumbnail' => 'VARCHAR(250)',
        'metadata' => 'VARCHAR(250)',
        'archive' => 'VARCHAR(250)',
        'mimetype' => 'VARCHAR(20)',
        'wms' => 'VARCHAR(250)',
        'updated' => 'TIMESTAMP',
        'published' => 'TIMESTAMP',
        'geometry' => 'POLYGON',
         // Add DEFAULT '' to avoid strange behavior in some versions of postgres
        'keywords' => 'hstore DEFAULT \'\''
    );

    if (!key || !$model[$key]) {
        return null;
    }

    if (is_array($model[$key])) {
        if (isset($model[$key]['type'])) {
            return $model[$key]['type'];
        }
    }
    
    return $sqlTypes[$key];
}

/**
 * Return RESTo type for $sqlType 
 * 
 * @param array $sqlType
 */
function getRESToType($sqlType) {
    
    if (!$sqlType) {
        return null;
    }
    
    $sqlType = strtolower($sqlType);
    
    if ($sqlType === 'integer' || $sqlType === 'float' || substr($sqlType, 0, 7) === 'numeric') {
        return 'numeric';
    }
    
    if ($sqlType === 'timestamp' || $sqlType === 'date') {
        return 'date';
    }
    
    return 'string';
}

/**
 * Return WKT from geometry
 * @param array $geometry - GeoJSON geometry
 */
function geoJSONGeometryToWKT($geometry) {
    
    $type = strtoupper($geometry['type']);
    
    if ($type === 'POINT') {
        $wkt =  $type . '(' . join(' ', $geometry['coordinates']) . ')';
    }
    else if ($type === 'LINESTRING') {
        $pairs = array();
        for ($i = 0, $l = count($geometry['coordinates']); $i < $l; $i++) {
            array_push($pairs, join(' ', $geometry['coordinates'][$i]));
        }
        $wkt = $type . '(' . join(',', $pairs) . ')';
    }
    else if ($type === 'POLYGON') {
        $rings = array();
        for ($i = 0, $l = count($geometry['coordinates']); $i < $l; $i++) {
            $pairs = array();
            for ($j = 0, $k = count($geometry['coordinates'][$i]); $j < $k; $j++) {
                array_push($pairs, join(' ', $geometry['coordinates'][$i][$j]));
            }
            array_push($rings, '(' . join(',', $pairs) . ')');
        }
        $wkt = $type . '(' . join(',', $rings) . ')';
    }
    
    return $wkt;
    
}


/**
 * Return RESTo $response array as an atom feed 
 * 
 * @param array $response - RESTo response array
 * @param object $dictionary - RESTo dictionary object
 * @param string $version
 * @param string $encoding
 * @return string
 */
function toAtom($response, $dictionary, $version = '1.0', $encoding = 'UTF-8') {

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
     *  read from $response['title']
     */
    $xml->writeElement('title', isset($response['title']) ? $response['title'] : '');

    /*
     * Element 'subtitle' 
     *  constructed from $response['title']
     */
    $subtitle = $dictionary->translate($response['totalResults'] === 1 ? '_oneResult' : '_multipleResult', $response['totalResults']);
    $previous = isset($response['links']['previous']) ? '<a href="' . updateURL($response['links']['previous'], array('format' => 'atom')) . '">' . $dictionary->translate('_previousPage') . '</a>&nbsp;' : '';
    $next = isset($response['links']['next']) ? '&nbsp;<a href="' . updateURL($response['links']['next'], array('format' => 'atom')) . '">' . $dictionary->translate('_nextPage') . '</a>' : '';
    $subtitle .= isset($response['startIndex']) ? '&nbsp;|&nbsp;' . $previous . $dictionary->translate('_pagination', $response['startIndex'], $response['lastIndex']) . $next : '';

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
     *  read from $response['??']
     */
    $xml->writeElement('id', 'TODO');

    /*
     * Self link
     */
    $xml->startElement('link');
    $xml->writeAttribute('rel', 'self');
    $xml->writeAttribute('type', 'application/atom+xml');
    $xml->writeAttribute('href', updateURL($response['links']['self'], array('format' => 'atom')));
    $xml->endElement(); // link

    /*
     * First link
     */
    if (isset($response['links']['first'])) {
        $xml->startElement('link');
        $xml->writeAttribute('rel', 'first');
        $xml->writeAttribute('type', 'application/atom+xml');
        $xml->writeAttribute('href', updateURL($response['links']['first'], array('format' => 'atom')));
        $xml->endElement(); // link
    }

    /*
     * Next link
     */
    if (isset($response['links']['next'])) {
        $xml->startElement('link');
        $xml->writeAttribute('rel', 'next');
        $xml->writeAttribute('type', 'application/atom+xml');
        $xml->writeAttribute('href', updateURL($response['links']['next'], array('format' => 'atom')));
        $xml->endElement(); // link
    }

    /*
     * Previous link
     */
    if (isset($response['links']['previous'])) {
        $xml->startElement('link');
        $xml->writeAttribute('rel', 'previous');
        $xml->writeAttribute('type', 'application/atom+xml');
        $xml->writeAttribute('href', updateURL($response['links']['previous'], array('format' => 'atom')));
        $xml->endElement(); // link
    }
    /*
     * Last link
     */
    if (isset($response['links']['last'])) {
        $xml->startElement('link');
        $xml->writeAttribute('rel', 'last');
        $xml->writeAttribute('type', 'application/atom+xml');
        $xml->writeAttribute('href', updateURL($response['links']['last'], array('format' => 'atom')));
        $xml->endElement(); // link
    }

    /*
     * Total results, startIndex and itemsPerpage
     */
    $xml->writeElement('os:totalResults', $response['totalResults']);
    $xml->writeElement('os:startIndex', $response['startIndex']);
    $xml->writeElement('os:itemsPerPage', $response['lastIndex'] - $response['startIndex'] + 1);

    /*
     * Query is made from request parameters
     */
    $xml->startElement('os:Query');
    $xml->writeAttribute('role', 'request');
    if (isset($response['query'])) {
        foreach ($response['query']['original'] as $key => $value) {
            $xml->writeAttribute($key, $value);
        }
    }
    $xml->endElement(); // os:Query

    /*
     * Loop over all products
     */
    for ($i = 0, $l = count($response['features']); $i < $l; $i++) {

        $product = $response['features'][$i];

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
         *  read from $product['properties']['startDate'] and $response['properties']['completionDate']
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
        $xml->writeAttribute('title', $dictionary->translate('_atomLink', $product['properties']['identifier']));
        $xml->writeAttribute('href', $atomUrl);
        $xml->endElement(); // link

        $htmlUrl = updateURL($product['properties']['self'], array('format' => 'html'));
        $xml->startElement('link');
        $xml->writeAttribute('rel', 'alternate');
        $xml->writeAttribute('type', 'text/html');
        $xml->writeAttribute('title', $dictionary->translate('_htmlLink', $product['properties']['identifier']));
        $xml->writeAttribute('href', $htmlUrl);
        $xml->endElement(); // link

        $jsonUrl = updateURL($product['properties']['self'], array('format' => 'json'));
        $xml->startElement('link');
        $xml->writeAttribute('rel', 'alternate');
        $xml->writeAttribute('type', 'application/json');
        $xml->writeAttribute('title', $dictionary->translate('_geojsonLink', $product['properties']['identifier']));
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
        $content = '<p>' . $product['properties']['platform'] . ($product['properties']['platform'] && $product['properties']['instrument'] ? '/' : '') . $product['properties']['instrument'] . ' ' . $dictionary->translate('_acquiredOn', $product['properties']['startDate']) . '</p>';
        if ($product['properties']['keywords']) {
            $keywords = array();
            foreach ($product['properties']['keywords'] as $keyword => $value) {
                array_push($keywords, '<a href="' . updateURL($value['url'], array('format' => 'atom')) . '">' . $keyword . '</a>');
            }
            $content .= '<p>' . $dictionary->translate('Keywords') . ' ' . join(' | ', $keywords) . '</p>';
        }
        $content .= '<p>' . $dictionary->translate('_viewMetadata', '<a href="' . updateURL($product['properties']['self'], array('format' => 'html')) . '">HTML</a>&nbsp;|&nbsp;<a href="' . updateURL($product['properties']['self'], array('format' => 'atom')) . '">ATOM</a>&nbsp;|&nbsp;<a href="' . updateURL($product['properties']['self'], array('format' => 'json')) . '">GeoJSON</a>') . '</p>';
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
 * Return radius length in degrees for a radius in meters
 * at a given latitude
 * 
 * @param float $r
 * @param float $lat
 */
function radiusInDegrees($radius, $lat) {
    return ($radius * cos(deg2rad($lat))) / 111110.0;
}

/**
 * Format a flat JSON string to make it more human-readable
 *
 * Code modified from https://github.com/GerHobbelt/nicejson-php
 * 
 * @param string $json The original JSON string to process
 *        When the input is not a string it is assumed the input is RAW
 *        and should be converted to JSON first of all.
 * @return string Indented version of the original JSON string
 */
function json_format($json, $pretty) {
    
    /*
     * No pretty print - easy part
     */
    if (!$pretty) {
        if (!is_string($json)) {
            return json_encode($json);
        }
        return $json;
    }
    
    if (!is_string($json)) {
        if (phpversion() && phpversion() >= 5.4) {
            return json_encode($json, JSON_PRETTY_PRINT);
        }
        $json = json_encode($json);
    }
    $result = '';
    $pos = 0;               // indentation level
    $strLen = strlen($json);
    $indentStr = "\t";
    $newLine = "\n";
    $prevChar = '';
    $outOfQuotes = true;

    for ($i = 0; $i < $strLen; $i++) {
        // Grab the next character in the string
        $char = substr($json, $i, 1);

        // Are we inside a quoted string?
        if ($char == '"' && $prevChar != '\\') {
            $outOfQuotes = !$outOfQuotes;
        }
        // If this character is the end of an element,
        // output a new line and indent the next line
        else if (($char == '}' || $char == ']') && $outOfQuotes) {
            $result .= $newLine;
            $pos--;
            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }
        // eat all non-essential whitespace in the input as we do our own here and it would only mess up our process
        else if ($outOfQuotes && false !== strpos(" \t\r\n", $char)) {
            continue;
        }

        // Add the character to the result string
        $result .= $char;
        // always add a space after a field colon:
        if ($char == ':' && $outOfQuotes) {
            $result .= ' ';
        }

        // If the last character was the beginning of an element,
        // output a new line and indent the next line
        if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
            $result .= $newLine;
            if ($char == '{' || $char == '[') {
                $pos++;
            }
            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }
        $prevChar = $char;
    }

    return $result;
}

/**
 * Return true if $str value is true, 1 or yes
 * Return false otherwise
 * 
 * @param string $str
 */
function trueOrFalse($str) {
    
    if (!$str) {
        return false;
    }
    
    if (strtolower($str) === 'true' || strtolower($str) === 'yes') {
        return true;
    }
    
    return false;
    
}

/**
 * Return an array of POST files
 * 
 * @param boolean $isGeoJSON - true if input files are GeoJSON (default is true)
 * @throws Exception
 */
function getFiles($isGeoJSON = true) {
    
    $arr = array();
    
   /*
    * Nothing posted
    */
    if (count($_FILES) == 0 || !is_array($_FILES['file'])) {
        return $arr;
    }

   /*
    * Read file assuming this is ascii file (i.e. plain text, GeoJSON, etc.)
    */        
    $tmpFiles = $_FILES['file']['tmp_name'];
    if (!is_array($tmpFiles)) {
        $tmpFiles = array($tmpFiles);
    }
    for ($i = 0, $l = count($tmpFiles); $i < $l; $i++) {
        if (is_uploaded_file($tmpFiles[$i])) {
            
            /*
             * This is GeoJSON
             */
            if ($isGeoJSON) {
                try {
                    $json = json_decode(join('', file($tmpFiles[$i])), true);
                } catch (Exception $e) {
                    throw new Exception('Invalid posted file(s)', 500);
                }
                if ($json['type'] === 'FeatureCollection' && is_array($json['features'])) {
                    array_push($arr, $json);
                }
                else {
                    throw new Exception('Invalid posted file(s)', 500);
                }
            }
            else {
                array_push($arr, file($tmpFiles[$i]));
            }
        }
    }
    
    return $arr;
}

