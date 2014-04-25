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
 * RESTo Spotimage Controller 
 */
class SpotController extends RestoController {
    /*
     * Database model description 
     * 
     * "properties": {
      "acquisitionDate": "2012-11-10T19:47:31Z",
      "archivingStation": "FR1",
      "cloudCoverPercentage": 2,
      "extendedData": null,
      "identifier": "DS_SPOT6_201211101947221_FR1_FR1_FR1_FR1_W152S17_01809",
      "imageName": "",
      "imageUrl": "http:\/\/ql.astrium-geo.com\/catalog\/img\/getfeatureimage.aspx?ID=DS_SPOT6_201211101947221_FR1_FR1_FR1_FR1_W152S17_01809",
      "incidenceAngle": 24.209953308105,
      "maxShift": null,
      "metadataUrl": "http:\/\/api.astrium-geo.com\/catalog\/data\/features.svc\/Features\/DS_SPOT6_201211101947221_FR1_FR1_FR1_FR1_W152S17_01809?sk=nTTe3gvSZ7-ntG3Cx8qoAU5z2xubPLXEdWAydziJPS4:",
      "minShift": null,
      "orientationAngle": 180.00489807129,
      "productId": "DS_SPOT6_201211101947221_FR1_FR1_FR1_FR1_W152S17_01809",
      "productType": "PX",
      "qualityQuotes": "E",
      "receivingStation": "FR1",
      "resolution": 1.5,
      "satellite": "SPOT6",
      "sensorFamily": "Multispectral",
      "shift": null,
      "snowCoverPercentage": 0,
      "sunAzimuth": 95.197525024414,
      "sunElevation": 60.588047027588,
      "wktBounds": "POLYGON((-151.94440577844645 -16.250438048434496,-151.27338568830842 -16.288706470573882,-151.27705117976558 -16.845826458873454,-151.94002056020608 -16.811713604959639))",
      "wktCenter": "POINT (-151.60234844839167 -16.549982658384916)",
      "wktFootprint": "POLYGON((-151.94440577844645 -16.250438048434496,-151.27338568830842 -16.288706470573882,-151.27705117976558 -16.845826458873454,-151.94002056020608 -16.811713604959639,-151.94440577844645 -16.250438048434496))"
      },
     * 
     */

    public static $model = array(
        'identifier' => 'db:identifier',
        'parentIdentifier' => 'urn:ogc:def:EOP:SPOT:',
        'authority' => 'Airbus Defence and Space,',
        'startDate' => 'db:acquisitiondate',
        'completionDate' => 'db:acquisitiondate',
        'productType' => 'db:producttype',
        'processingLevel' => 'LEVEL1C',
        'platform' => 'db:satellite',
        'resolution' => 'db:resolution',
        'sensorMode' => 'db:sensorfamily',
        'quicklook' => 'db:imageurl',
        'thumbnail' => 'db:imageurl',
        'archive' => array(
            'dbKey' => 'db:identifier',
            'template' => ' http://www.astrium-geo.com/satellite-image/{a:1}'
        ),
        'mimetype' => 'text/html',
        'updated' => 'db:modifieddate',
        'published' => 'db:creationdate',
        'keywords' => 'db:keywords',
        'geometry' => 'db:geometry',
        'archivingCenter' => array(
            'dbKey' => 'db:archivingstation',
            'type' => 'VARCHAR(3)'
        ),
        'acquisitionStation' => array(
            'dbKey' => 'db:receivingstation',
            'type' => 'VARCHAR(3)'
        ),
        'cloudCover' => array(
            'dbKey' => 'db:cloudcoverpercentage',
            'type' => 'INTEGER'
        ),
        'snowCover' => array(
            'dbKey' => 'db:snowcoverpercentage',
            'type' => 'INTEGER'
        ),
        'qualityQuotes' => array(
            'dbKey' => 'db:qualityquotes',
            'type' => 'VARCHAR(4)'
        ),
        'incidenceAngle' => array(
            'dbKey' => 'db:incidenceangle',
            'type' => 'NUMERIC(15,12)'
        ),
        'orientationAngle' => array(
            'dbKey' => 'db:orientationangle',
            'type' => 'NUMERIC(15,12)'
        ),
        'sunAzimuth' => array(
            'dbKey' => 'db:sunazimuth',
            'type' => 'NUMERIC(15,12)'
        ),
        'sunElevation' => array(
            'dbKey' => 'db:sunelevation',
            'type' => 'NUMERIC(15,12)'
        ),
        'productId' => array(
            'dbKey' => 'db:productid',
            'type' => 'VARCHAR(250)'
        )
    );

    /*
     * Search filters list
     */
    public static $searchFiltersList = array(
        'searchTerms?',
        'count?',
        'startIndex?',
        'language?',
        'geo:box?',
        'geo:name?',
        'geo:lon?',
        'geo:lat?',
        'geo:radius?',
        'time:start?',
        'time:end?',
        'eo:platformShortName?',
        'eo:productType?',
        'eo:resolution?',
        'eo:cloudCover?'
    );

    /*
     * Search filters list
     */
    public static $searchFiltersDescription = array(
        'eo:cloudCover' => array(
            'key' => 'cloudCover',
            'osKey' => 'cloudCover',
            'operation' => 'interval',
            'quantity' => array(
                'value' => 'cloud',
                'unit' => '%'
            )
        )
    );
    
    /*
     * Input properties mapping
     * Used to read resource POST GeoJSON files
     */
    public static $inputPropertiesMapping = array(
        'acquisitionDate' => 'startDate',
        'satellite' => 'platform',
        'sensorfamily' => 'sensorMode',
        'imageUrl' => 'quicklook',
        'archivingStation' => 'archivingCenter',
        'receivingStation' => 'acquisitionStation',
        'cloudCoverPercentage' => 'cloudCover',
        'snowCoverPercentage' => 'snowCover'
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
