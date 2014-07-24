#!/usr/bin/env php
<?php
/*
 * hstore2Array - Update RESTo collection database structure
 *
 * Copyright Jérôme Gasperi, 2014
 *
 * jerome[dot]gasperi[at]gmail[dot]com
 *
 * This software is a computer program whose purpose is a webmapping application
 * to display and manipulate geographical data.
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
 */

define("DB_HOST", "localhost");
define("DB_PORT", "5432");
define("DB_USER", "sresto");
define("DB_PASSWORD", "sresto");
define("DB_NAME", "resto");

// Remove PHP NOTICE
error_reporting(E_PARSE);

// This is a shell only script
if (empty($_SERVER['SHELL'])) {
    exit;
}

// Display usage
$help = "\nMove hstore continent/country keys to dedicated columns for a RESTo given collection\n\n";
$help .= "USAGE : hstore2array.php [options] -c <collection name>\n";
$help .= "OPTIONS:\n";
$help .= "   -c [collection name] : Collection to update \n";
$help .= "   -h : Display this help\n";
$help .= "\n\n";
$options = getopt("c:h");
foreach ($options as $option => $value) {
    if ($option === "c") {
        $collection = $value;
    }
    if ($option === "h") {
        echo $help;
        exit;
    }
}
// Collection name is mandatory
if (!$collection) {
    echo $help;
    exit;
}

// Set up a RESTo superuser (i.e. sresto) database connection
try {
    $dbh = pg_connect("host=" . DB_HOST . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASSWORD);
} catch (Exception $e) {
    echo "\nFATAL : No connection to database\n\n";
    exit;
}
pg_set_client_encoding($dbh, "UTF8");
$results = pg_query($dbh, "SELECT identifier, keywords FROM " . $collection . ".products");
if (!$results) {
    echo "\nFATAL : Error performing SELECT\n\n";
    exit;
}
while ($result = pg_fetch_assoc($results)) {
    $arr = decode_hstore($result['keywords']);
    $set = array();
    $continents = array();
    $countries = array();
    foreach ($arr as $key => $value) {
        list($type, $val) = explode(':', $key);
        if ($type === 'continent') {
            $continents[] = '\'' . $val . '\'';
        }
    }
    foreach ($arr as $key => $value) {
        list($type, $val) = explode(':', $key);
        if ($type === 'country') {
            $countries[] = '\'' . $val . '\'';
        }
    }
    if (count($continents) > 0) {
        $set[] = 'lo_continents = ARRAY[' . join(',', $continents) . ']';
    }
    if (count($continents) > 0) {
        $set[] = 'lo_countries = ARRAY[' . join(',', $countries) . ']';
    }
    if (count($set) > 0) {
       echo "UPDATE ". $collection . ".products SET " . join(',', $set) . " WHERE identifier = '" . $result['identifier'] . "';\n"; 
    }
}

function encode_hstore($array) {
    if (!$array) {
        return NULL;
    }

    if (!is_array($array)) {
        return $array;
    }

    $expr = array();

    foreach ($array as $key => $val) {
        $search = array('\\', "'", '"');
        $replace = array('\\\\', "''", '\"');

        $key = str_replace($search, $replace, $key);
        $val = $val === NULL ? 'NULL' : '"' . str_replace($search, $replace, $val) . '"';

        $expr[] = sprintf('"%s"=>%s', $key, $val);
    }

    return sprintf("'%s'::hstore", implode(',', $expr));
}

function decode_hstore($hstore) {
    if (!$hstore || !preg_match_all('/"(.+)(?<!\\\)"=>(""|NULL|".+(?<!\\\)"),?/U', $hstore, $match, PREG_SET_ORDER)) {
        return array();
    }
    $array = array();

    foreach ($match as $set) {
        list(, $k, $v) = $set;

        $v = $v === 'NULL' ? NULL : substr($v, 1, -1);

        $search = array('\"', '\\\\');
        $replace = array('"', '\\');

        $k = str_replace($search, $replace, $k);
        if ($v !== NULL)
            $v = str_replace($search, $replace, $v);

        $array[$k] = $v;
    }

    return $array;
}
