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
    foreach($query as $q) {
        $tmp = explode("=", $q);
        $key = isset($tmp[0]) ? $tmp[0] : null;
        $value = isset($tmp[1]) ? $tmp[1] : null;
        if (array_key_exists($key, $mod)) {
            if($mod[$key]) {
                $url = str_replace(urlencode($key) . '=' . urlencode($value), urlencode($key) . '=' . urlencode($mod[$key]), $url);
                //$url = preg_replace('/' . urlencode($key) . '=' . urlencode($value) . '/', urlencode($key) . '=' . urlencode($mod[$key]), $url);
            }
            else {
                $url = preg_replace('&?' . urlencode($key) . '=' . urlencode($value), '', $url);
                //$url = preg_replace('/&?' . urlencode($key) . '=' . urlencode($value) . '/', '', $url);
            }
        }
    }
    // add new data
    foreach($mod as $key => $value) {
        if($value && !preg_match('/'.$key.'=/', $url)) {
            $url .= '&' . urlencode($key) . '=' . urlencode($value);
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
 * Check if string starts like an url i.e. http:// or https:// or //:
 * 
 * @param {String} $str
 */
function isUrl($str) {
    if (!isset($str)) {
        return false;
    }
    if (substr(trim($str),0 , 7) === 'http://' || substr(trim($str),0 , 8) === 'https://' || substr(trim($str),0 , 2) === '//') {
        return true;
    }
    return false;
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
 * Transform input string to 7bits ascii equivalent (i.e. remove accent on letters and so on)
 * 
 * @param {string} $text
 */
function asciify($text) {
    return strtr(utf8_decode($text), utf8_decode('ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'), 'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');
}

/**
 * Transform input string to 7bits ascii equivalent (i.e. remove accent on letters and so on)
 * (see http://www.php.net/manual/fr/function.iconv.php)
 * 
 * Note : apparently this function does not work on mapshup.info server
 *  
 * @param {string} $text
 * @param {string} $charset
 */
function asciify2($text, $charset = 'UTF-8') {

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
        $text = iconv($charset, 'ASCII//IGNORE//TRANSLIT', $text[0]);

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

    if (!$key || !$model[$key]) {
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

    if (!$key || !$model[$key]) {
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
        'identifier' => 'VARCHAR(250) UNIQUE',
        'parentIdentifier' => 'VARCHAR(250)',
        'title' => 'VARCHAR(250)',
        'description' => 'TEXT',
        'authority' => 'VARCHAR(50)',
        'startDate' => 'TIMESTAMP',
        'completionDate' => 'TIMESTAMP',
        'productType' => 'VARCHAR(50)',
        'processingLevel' => 'VARCHAR(50)',
        'platform' => 'VARCHAR(50)',
        'instrument' => 'VARCHAR(50)',
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
        'keywords' => 'hstore DEFAULT \'\'',
        'cultivatedCover' => 'NUMERIC DEFAULT 0',
        'desertCover' => 'NUMERIC DEFAULT 0',
        'floodedCover' => 'NUMERIC DEFAULT 0',
        'forestCover' => 'NUMERIC DEFAULT 0',
        'herbaceousCover' => 'NUMERIC DEFAULT 0',
        'snowCover' => 'NUMERIC DEFAULT 0',
        'urbanCover' => 'NUMERIC DEFAULT 0',
        'waterCover' => 'NUMERIC DEFAULT 0',
        'continents' => 'TEXT[]',
        'countries' => 'TEXT[]'
    );

    if (!$key || !$model[$key]) {
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
    
    /*
     * Remove enventual DEFAULT stuff
     */
    $splitted = explode(' ', strtolower($sqlType));

    if ($splitted[0] === 'integer' || $splitted[0] === 'float' || substr($splitted[0], 0, 7) === 'numeric') {
        return 'numeric';
    }

    if ($splitted[0] === 'timestamp' || $splitted[0] === 'date') {
        return 'date';
    }
    
    if ($splitted[0] === 'text[]') {
        return 'array';
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
            $pairs[] = join(' ', $geometry['coordinates'][$i]);
        }
        $wkt = $type . '(' . join(',', $pairs) . ')';
    }
    else if ($type === 'POLYGON') {
        $rings = array();
        for ($i = 0, $l = count($geometry['coordinates']); $i < $l; $i++) {
            $pairs = array();
            for ($j = 0, $k = count($geometry['coordinates'][$i]); $j < $k; $j++) {
                $pairs[] = join(' ', $geometry['coordinates'][$i][$j]);
            }
            $rings[] = '(' . join(',', $pairs) . ')';
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
    $xml->writeAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');

    /*
     * Element 'title' 
     *  read from $response['title']
     */
    $xml->writeElement('title', isset($response['title']) ? $response['title'] : '');

    /*
     * Element 'subtitle' 
     *  constructed from $response['title']
     */
    if (isset($response['totalResults'])) {
        $subtitle = $dictionary->translate($response['totalResults'] === 1 ? '_oneResult' : '_multipleResult', $response['totalResults']);
    }
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
     * Element 'id' - UUID generate from RESTo::UUID and response URL
     */
    $xml->writeElement('id', $response['id']);

    /*
     * Links
     */
    if (is_array($response['links'])) {
        for ($i = 0, $l = count($response['links']); $i < $l; $i++) {
            $xml->startElement('link');
            $xml->writeAttribute('rel', $response['links'][$i]['rel']);
            $xml->writeAttribute('type', 'application/atom+xml');
            $xml->writeAttribute('title', $response['links'][$i]['title']);
            $xml->writeAttribute('href', updateURL($response['links'][$i]['href'], array('format' => 'atom')));
            $xml->endElement(); // link
        }
    }

    /*
     * Total results, startIndex and itemsPerpage
     */
    if (isset($response['totalResults'])) {
        $xml->writeElement('os:totalResults', $response['totalResults']);
    }
    if (isset($response['startIndex'])) {
        $xml->writeElement('os:startIndex', $response['startIndex']);
    }
    if (isset($response['startIndex']) && isset($response['lastIndex'])) {
        $xml->writeElement('os:itemsPerPage', $response['lastIndex'] - $response['startIndex'] + 1);
    }
    
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
         *  read from $product['id']
         * 
         * !! THIS SHOULD BE AN ABSOLUTE UNIQUE  AND PERMANENT IDENTIFIER !!
         * 
         */
        $xml->writeElement('id', $product['id']);
        
        /*
         * Local identifier - i.e. last part of uri
         */
        $xml->writeElement('dc:identifier', $product['id']);
        
        /*
         * Element 'title'
         *  read from $product['properties']['title']
         */
        $xml->writeElement('title', isset($product['properties']['title']) ? $product['properties']['title'] : '');

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
                $geometry[] = $value[1] . ' ' . $value[0];
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
         * Links
         */
        if (is_array($product['properties']['links'])) {
            for ($j = 0, $k = count($response['links']); $j < $k; $j++) {
                $xml->startElement('link');
                $xml->writeAttribute('rel', $response['links'][$j]['rel']);
                $xml->writeAttribute('type', $response['links'][$j]['rel']);
                $xml->writeAttribute('title', $response['links'][$j]['title']);
                $xml->writeAttribute('href', $response['links'][$j]['href']);
                $xml->endElement(); // link
            }
        }

        /*
         * Element 'enclosure' - download product
         *  read from $product['properties']['archive']
         */
        $xml->startElement('link');
        $xml->writeAttribute('rel', 'enclosure');
        $xml->writeAttribute('type', $product['properties']['services']['download']['mimeType']);
        //$xml->writeAttribute('length', 'TODO');
        $xml->writeAttribute('title', 'File for ' . $product['id'] . ' product');
        $xml->writeAttribute('metalink:priority', 50);
        $xml->writeAttribute('href', $product['properties']['services']['download']['url']);
        $xml->endElement(); // link
        
        /*
         * Quicklook / Thumbnail
         */
        if (isset($product['properties']['thumbnail']) || isset($product['properties']['quicklook'])) {
            
            /*
             * rel=icon
             */
            if (isset($product['properties']['quicklook'])) {
                $xml->startElement('link');
                $xml->writeAttribute('rel', 'icon');
                //$xml->writeAttribute('type', 'TODO');
                $xml->writeAttribute('title', 'Browse image URL for ' . $product['id'] . ' product');
                $xml->writeAttribute('href', $product['properties']['quicklook']);
                $xml->endElement(); // link
            }
            
            /*
             * media:group
             */
            $xml->startElement('media:group');
            if (isset($product['properties']['thumbnail'])) {
                $xml->startElement('media:content');
                $xml->writeAttribute('url', $product['properties']['thumbnail']);
                $xml->writeAttribute('medium', 'image');
                $xml->startElement('media:category');
                $xml->writeAttribute('scheme', 'http://www.opengis.net/spec/EOMPOM/1.0');
                $xml->text('THUMBNAIL');
                $xml->endElement();
                $xml->endElement();
            }
            if (isset($product['properties']['quicklook'])) {
                $xml->startElement('media:content');
                $xml->writeAttribute('url', $product['properties']['quicklook']);
                $xml->writeAttribute('medium', 'image');
                $xml->startElement('media:category');
                $xml->writeAttribute('scheme', 'http://www.opengis.net/spec/EOMPOM/1.0');
                $xml->text('QUICKLOOK');
                $xml->endElement();
                $xml->endElement();
            }
            $xml->endElement();
        }

        /*
         * Element 'summary' - HTML description
         *  construct from $product['properties'][*]
         */
        $content = '<p>' . (isset($product['properties']['platform']) ? $product['properties']['platform'] : '') . (isset($product['properties']['platform']) && isset($product['properties']['instrument']) ? '/' . $product['properties']['instrument'] : '') . ' ' . $dictionary->translate('_acquiredOn', $product['properties']['startDate']) . '</p>';
        if ($product['properties']['keywords']) {
            $keywords = array();
            foreach ($product['properties']['keywords'] as $keyword => $value) {
                $keywords[] = '<a href="' . updateURL($value['href'], array('format' => 'atom')) . '">' . $keyword . '</a>';
            }
            $content .= '<p>' . $dictionary->translate('Keywords') . ' ' . join(' | ', $keywords) . '</p>';
        }
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
 * @param float $radius
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
 * Return an array of POST files or POST parameters ('data') 
 *
 * Warning : If both POST file and POST parameters are used, the POST file is read and
 * POST parameters are skipped.
 * 
 * @param array $options - contains mimeType of expected data, and delimiter character in
 * case of texte data.
 * 		$options['mimeType'] : represents the data mimeType
 * 		$options['delimiter'] : represents the delimiter to use in php function explode
 *      $options['permissive']: if set to 'true' and mimeType is json then non GeoJSON data is allowed
 *                              otherwise an 'Invalid posted files' - HTTP 500 is sent (default false)
 * @throws Exception
 */
function getFiles($options) {

    /*
     * By default it is assumed that POST files/data is in JSON
     */
    if (!isset($options['mimeType'])) {
        $options['mimeType'] = Resto::$contentTypes['json'];
    }

    /*
     * By default if mimeType is JSON, it is mandatory to be GeoJSON
     */
    if (!isset($options['permissive'])) {
        $options['permissive'] = false;
    }

    $arr = array();

    /*
     * True by default, False if no file is posted but data posted through parameters
     */
    $isFile = true;

    /*
     * No file is posted
     */
    if (count($_FILES) == 0 || !is_array($_FILES['file'])) {
        
        /*
         * Is data posted with key/value ?
         */
        if (isset($_POST['data'])) {
            $isFile = false;
            $tmpFiles = $_POST['data'];
            if (!is_array($tmpFiles)) {
                $tmpFiles = array($tmpFiles);
            }
        }
        /*
         * Nothing posted
         */
        else {
            return $arr;
        }
    }
    /*
     * A file is posted
     */
    else {
        
        /*
         * Read file assuming this is ascii file (i.e. plain text, GeoJSON, etc.)
         */
        $tmpFiles = $_FILES['file']['tmp_name'];
        if (!is_array($tmpFiles)) {
            $tmpFiles = array($tmpFiles);
        }
        
    }

    for ($i = 0, $l = count($tmpFiles); $i < $l; $i++) {

        /*
         * The data's format is GeoJSON
         */
        if ($options['mimeType'] === Resto::$contentTypes['json']) {
            try {
                
                /*
                 * Decode json data
                 */
                if ($isFile) {
                    $json = json_decode(join('', file($tmpFiles[$i])), true);
                } else {
                    $json = json_decode(urldecode($tmpFiles[$i]), true);
                }
            } catch (Exception $e) {
                throw new Exception('Invalid posted file(s)', 500);
            }
            if ($options['permissive'] || ($json['type'] === 'FeatureCollection' && is_array($json['features']))) {
                $arr[] = $json;
            } else {
                throw new Exception('Invalid posted file(s)', 500);
            }
        }
        /*
         * The data's format is not JSON
         */
        else {
            /*
             * Push the file content in return array.
             * The file content is transformed as array by file function
             */
            if ($isFile) {
                $arr[] = file($tmpFiles[$i]);
            }
            /*
             * Explode the texte line by line to obtain an array 
             * and push it to the final array
             */
            else if (isset($options['delimiter'])) {
                $arr[] = explode($options['delimiter'], $tmpFiles[$i]);
            }
            /*
             * By default, the exploding character is "\n"
             */
            else {
                $arr[] = explode("\n", $tmpFiles[$i]);
            }
        }
    }
    
    return $arr;
    
}

/**
 * Generate v5 UUID
 * 
 * Version 5 UUIDs are named based. They require a namespace (another 
 * valid UUID) and a value (the name). Given the same namespace and 
 * name, the output is always the same.
 * 
 * @param	uuid	$namespace
 * @param	string	$name
 * 
 * @author Andrew Moore
 * @link http://www.php.net/manual/en/function.uniqid.php#94959
 */
function UUIDv5($namespace, $name) {

    if(!isValidUUID($namespace)) {
        return false;
    }

    // Get hexadecimal components of namespace
    $nhex = str_replace(array('-','{','}'), '', $namespace);

    // Binary Value
    $nstr = '';

    // Convert Namespace UUID to bits
    for($i = 0; $i < strlen($nhex); $i+=2) {
        $nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
    }

    // Calculate hash value
    $hash = sha1($nstr . $name);

    return sprintf('%08s-%04s-%04x-%04x-%12s',

                   // 32 bits for "time_low"
                   substr($hash, 0, 8),

                   // 16 bits for "time_mid"
                   substr($hash, 8, 4),

                   // 16 bits for "time_hi_and_version",
                   // four most significant bits holds version number 5
                   (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,

                   // 16 bits, 8 bits for "clk_seq_hi_res",
                   // 8 bits for "clk_seq_low",
                   // two most significant bits holds zero and one for variant DCE1.1
                   (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,

                   // 48 bits for "node"
                   substr($hash, 20, 12)
                  );
}

/**
 * Check that input $uuid has a valid uuid syntax
 * @link http://tools.ietf.org/html/rfc4122
 * 
 * @param	uuid	$uuid
 */
function isValidUUID($uuid) {
    return preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid) === 1;
}

/**
 * Return true if $identifier is not null and not start with $
 * 
 * @param String $identifier identifier as in URI /collection/identifier/modifier
 */
function isValidIdentifier($identifier) {
    if (!$identifier || substr($identifier, 0, 1) === '$') {
        return false;
    }
    return true;
}

/**
 * Transform EPSG:3857 coordinate into EPSG:4326
 * 
 * @param {array} $xy : array(x, y) 
 */
function inverseMercator($xy) {
    
    if (!is_array($xy) || count($xy) !== 2) {
        return null;
    }
    
    return array(
        180.0 * $xy[0] / 20037508.34,
        180.0 / M_PI * (2.0 * atan(exp(($xy[1] / 20037508.34) * M_PI)) - M_PI / 2.0)
    );
}

/**
 * Transform EPSG:4326 coordinate into EPSG:3857
 * 
 * @param {array} $lonlat : array(lon, lat) 
 */
function forwardMercator($lonlat) {
    
    if (!is_array($lonlat) || count($lonlat) !== 2) {
        return null;
    }
    
    /*
     * Latitude limits are -85/+85 degrees
     */
    if ($lonlat[1] > 85 || $lonlat[1] < -85) {
        return null;
    }
    
    return array(
        $lonlat[0] * 20037508.34 / 180.0,
        max(-20037508.34, min(log(tan((90.0 + $lonlat[1]) * M_PI / 360.0)) / M_PI * 20037508.34, 20037508.34))
    );
}

/**
 * Transform EPSG:4326 BBOX to EPSG:3857 bbox
 * 
 * @param {String} $bbox : bbox in EPSG:4326 (i.e. lonmin,latmin,lonmax,latmax) 
 */
function bboxToMercator($bbox) {
    
    if (!$bbox) {
        return null;
    }
    $coords = preg_split('/,/', $bbox);
    if (count($coords) !== 4) {
        return null;
    }
    
    /*
     * Lower left coordinate
     */
    $ll = forwardMercator(array(floatval($coords[0]), floatval($coords[1])));
    if (!$ll) {
        return null;
    }
    
    /*
     * Upper right coordinate
     */
    $ur = forwardMercator(array(floatval($coords[2]), floatval($coords[3])));
    if (!$ur) {
        return null;
    }
    
    return join(',', $ll) . ',' . join(',', $ur);
    
}