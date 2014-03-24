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
     * @param String $bbox bbox to constraint search
     * @param String $countryName limit search to country name
     * @param String $bbox limit search to bbox
     *
     * @return array
     *
     */

    final public function getEntries($bbox = '-180,-90,180,90', $lang = 'en', $nbOfResults = 10) {

        $result = array();
        $where = '';

        if (!$this->dbh) {
            return $result;
        }

        /*
         * Constrain search on bbox
         */
        $lonmin = -180;
        $lonmax = 180;
        $latmin = -90;
        $latmax = 90;
        $coords = explode(',', $bbox);
        if (count($coords) === 4) {
            $lonmin = is_numeric($coords[0]) ? $coords[0] : $lonmin;
            $latmin = is_numeric($coords[1]) ? $coords[1] : $latmin;
            $lonmax = is_numeric($coords[2]) ? $coords[2] : $lonmax;
            $latmax = is_numeric($coords[3]) ? $coords[3] : $latmax;
        }
        /*
         * Search in input language
         */
        $entries = pg_query($this->dbh, 'SELECT title, summary FROM ' . $this->schema . '.wk, ' . $this->schema . '.geoname_ds WHERE lang = \'' . pg_escape_string($lang) . '\' AND wikipediaid IN (SELECT wikipediaid FROM ' . $this->schema . '.wikipedia WHERE ST_intersects(geom, ST_GeomFromText(\'' . pg_escape_string('POLYGON((' . $lonmin . ' ' . $latmin . ',' . $lonmin . ' ' . $latmax . ',' . $lonmax . ' ' . $latmax . ',' . $lonmax . ' ' . $latmin . ',' . $lonmin . ' ' . $latmin . '))') . '\', 4326)) ORDER BY relevance DESC) LIMIT ' . $nbOfResults);

        if (!$entries) {
            return $result;
        }

        /*
         * Retrieve first result
         */
        while ($entry = pg_fetch_assoc($entries)) {
            $entry['url'] = 'http://' . $lang . '.wikipedia.com/' . rawurlencode($entry['title']);
            array_push($result, $entry);
        }

        return $result;
    }

}
