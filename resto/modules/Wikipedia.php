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
 * Wikipedia module
 *
 * Return a list of wikipedia (location based) entries from
 * input bounding box
 *
 */
class Wikipedia {

    /*
     * Wikipedia database handler
     */
    private $dbh;
    private $schema;

    /**
     * Constructor
     *
     * @param array $R RESTo instance reference
     */
    public function __construct($R) {
        $config = $R->getModuleConfig('Wikipedia');
        if ($config && $config['activate']) {
            $dbConnector = $R->getDatabaseConnectorInstance();
            if (is_array($config['db'])) {
                $dbConnector->update($config['db']);
            }
            $this->dbh = $dbConnector->getConnection();
            $this->schema = $dbConnector->getSchema();
        }
    }

    /**
     * Return the first nbOfResults wikipedia articles
     * within a given bbox order by relevance
     *
     * @param String $polygon WKT Polygon to constraint search
     * @param String $lang lang
     * @param String $nbOfResults number of results
     *
     * @return array
     *
     */

    final public function getEntries($polygon, $lang = 'en', $nbOfResults = 10) {

        $result = array();

        if (!$this->dbh) {
            return $result;
        }
        
        $where = '';
        if ($polygon) {
            $where = 'WHERE ST_intersects(geom, ST_GeomFromText(\'' . pg_escape_string($polygon) . '\', 4326))';
        }
        
        /*
         * Search in input language
         */
        $entries = pg_query($this->dbh, 'SELECT title, summary FROM ' . $this->schema . '.wk WHERE lang = \'' . pg_escape_string($lang) . '\' AND wikipediaid IN (SELECT wikipediaid FROM ' . $this->schema . '.wikipedia ' . $where . ' ORDER BY relevance DESC) LIMIT ' . $nbOfResults);

        if (!$entries) {
            return $result;
        }

        /*
         * Retrieve first result
         */
        while ($entry = pg_fetch_assoc($entries)) {
            $entry['url'] = '//' . $lang . '.wikipedia.com/wiki/' . rawurlencode($entry['title']);
            $result[] = $entry;
        }

        return $result;
    }

}
