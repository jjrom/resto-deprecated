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