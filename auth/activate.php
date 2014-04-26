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
 *  Remove PHP Warning
 */
error_reporting(E_ERROR | E_PARSE);

/*
 * Load functions
 */
require realpath(dirname(__FILE__)) . '/../resto/core/lib/functions.php';

/*
 * Autoload controllers and modules
 */

function autoload($className) {
    foreach (array('../resto/core/') as $current_dir) {
        $path = $current_dir . sprintf('%s.php', $className);
        if (file_exists($path)) {
            include $path;
            return;
        }
    }
}

spl_autoload_register('autoload');

/*
 * Set headers
 */
function echoResult($status, $message, $body = null) {
    header('HTTP/1.1 ' . $status . ' ' . $message);
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    if (!body && $status !== 200) {
        echo json_encode(array(
            'ErrorCode' => $status,
            'ErrorMessage' => $message
        ));
    } else {
        echo json_encode($body);
    }
}

/*
 * Start session (or retrieve existing session)
 */
session_start();

/*
 * Bufferize echo
 */
ob_start();

/*
 * Initialize database connector
 */
$configFile = realpath(dirname(__FILE__)) . '/../resto/resto.ini';
if (!file_exists($configFile)) {
    echoResult(500, 'Internal Server Error', array(
        'ErrorCode' => 500,
        'ErrorMessage' => 'Missing mandatory configuration file'
    ));
    exit;
}

/*
 * Only GET method is allowed
 */
$method = strtolower($_SERVER['REQUEST_METHOD']);
if ($method !== 'get') {
    echoResult(405, 'Method Not Allowed');
    exit;
}
$params = array_merge($_POST, $_GET);
$mandatory = array(
    'userid',
    'act'
);
foreach ($mandatory as $key) {
    if (!isset($params[$key]) || empty($params[$key])) {
        echoResult(500, 'Internal Server Error', array(
            'ErrorCode' => 500,
            'ErrorMessage' => 'Missing or invalid input parameters'
        ));
        ob_end_flush();
        exit;
    }
}

try {
    $config = IniParser::read($configFile);
    $dbConnector = new DatabaseConnector($config['general']['db']);
    $dbh = $dbConnector->getConnection(true);
    if (!$dbh) {
        throw new Exception('Database connection error', 500);
    }
    $userid = pg_escape_string(trim(strtolower($params['userid'])));
    
    /*
     * Userid must be unique
     */
    $results = pg_query($dbh, 'SELECT 1 FROM admin.users WHERE userid=\'' . $userid . '\' AND activationcode=\'' . pg_escape_string(trim($params['act'])) . '\'');
    if (!$results || !pg_fetch_assoc($results)) {
        throw new Exception('Invalid activation code', 500);
    }
    pg_query($dbh, 'UPDATE admin.users SET activated = TRUE WHERE userid=\'' . $userid . '\' AND activationcode=\'' . pg_escape_string(trim($params['act'])) . '\'');
    echoResult(200, 'OK', array(
        'status' => 'OK',
        'message' => 'User ' . $userid . ' activated'
    ));
} catch (Exception $e) {
    echoResult(500, 'Internal Server Error', array(
        'ErrorCode' => $e->getCode(),
        'ErrorMessage' => $e->getMessage()
    ));
}

ob_end_flush();
