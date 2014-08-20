<?php

/*
 * RESTo
 * 
 * REST OpenSearch - Very Lightweigt PHP REST Library for OpenSearch EO
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
 * RESTo default Controller 
 */
class DefaultController extends RestoController {
   
    /*
     * Database model description - MANDATORY
     *
     * left column = RESTo keys
     * right column = Default database columns
     */
    public static $model = array(
        'identifier' => 'db:identifier',
        'parentIdentifier' => 'db:parentidentifier',
        'title' => 'db:title',
        'description' => 'db:description',
        'authority' => 'db:authority',
        'startDate' => 'db:startdate',
        'completionDate' => 'db:completiondate',
        'productType' => 'db:producttype',
        'processingLevel' => 'db:processinglevel',
        'platform' => 'db:platformname',
        'instrument' => 'db:instrumentname',
        'resolution' => 'db:resolution',
        'orbitNumber' => 'db:orbitnumber',
        'sensorMode' => 'db:sensorMode',
        'quicklook' => 'db:quicklook',
        'thumbnail' => 'db:thumbnail',
        'metadata' => 'db:metadata',
        'archive' => 'db:archive',
        'mimeType' => 'db:mimetype',
        'wms' => 'db:wms',
        'updated' => 'db:modifieddate',
        'published' => 'db:creationdate',
        'keywords' => 'db:keywords',
        'geometry' => 'db:geometry',
        'cultivatedCover' => 'db:lu_cultivated',
        'desertCover' => 'db:lu_desert',
        'floodedCover' => 'db:lu_flooded',
        'forestCover' => 'db:lu_forest',
        'herbaceousCover' => 'db:lu_herbaceous',
        'snowCover' => 'db:lu_snow',
        'urbanCover' => 'db:lu_urban',
        'waterCover' => 'db:lu_water',
        'continents' => 'db:lo_continents',
        'countries' => 'db:lo_countries'
    );
    
    /*
     * Search filters list
     */
    public static $searchFiltersList = array(
        'searchTerms?',
        'count?',
        'startIndex?',
        'language?',
        'geo:uid?', // Identifier
        'geo:geometry?', // Geometry in WKT
        'geo:box?', // Bounding Box
        'geo:name?', // Location name
        'geo:lon?', // Longitude
        'geo:lat?', // Latitude
        'geo:radius?', // Radius in meters
        'time:start?', // Start of acquisition
        'time:end?', // End of acquisition
        'ptsc:modifiedDate?', // Modified date (for harvesting)
        'eo:instrument?', // Instrument
        'eo:platformShortName?', // Platform
        'eo:productType?', // Product type
        'eo:parentIdentifier?', // Project name
        'eo:organisationName?', // Organisation name (authority)
        'eo:resolution?' // Resolution
    );
    
    /**
     * Process HTTP GET Requests
     */
    public function get() {
       $this->defaultGet();
    }

    /**
     * Process HTTP POST requests
     */
    public function post() {
        $this->defaultPost();
    }

    /**
     * Process HTTP PUT requests
     */
    public function put() {
        $this->defaultPut();
    }
    
    /**
     * Process HTTP PUT request
     */
    public function delete() {
        $this->defaultDelete();
    }

}
