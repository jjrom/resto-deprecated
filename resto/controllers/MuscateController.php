<?php

/**
 * RESTo Muscate Controller 
 * 
 * This Controller is used by the Theia project to ingest products processed
 * by the CNES Muscate processing chain
 * 
 * Input metadata is an XML file with the following structure (e.g. Landsat)
 * 
 *  <HEADER>
 *      <IDENT>LANDSAT8_OLITIRS_XS_20130414_N2A_France-MetropoleD0005H0002</IDENT>
 *      <DATE_PDV>2013-04-14 10:44:14.3364788</DATE_PDV>
 *      <DATE_PROD>2014-04-10 17:19:28.996581</DATE_PROD>
 *      <PLATEFORM>LANDSAT8</PLATEFORM>
 *      <SENSOR>OLITIRS</SENSOR>
 *      <MODE>XS</MODE>
 *      <LEVEL>N2A</LEVEL>
 *      <ZONE_GEO>France-MetropoleD0005H0002</ZONE_GEO>
 *      <VERSION>2.0</VERSION>
 *  </HEADER>
 *  <FILES>
 *      <MASK_SATURATION>MASK/LANDSAT8_OLITIRS_XS_20130414_N2A_France-MetropoleD0005H0002_SAT.TIF</MASK_SATURATION>
 *      <ORTHO_SURF_CORR_ENV>LANDSAT8_OLITIRS_XS_20130414_N2A_ORTHO_SURF_CORR_ENV_France-MetropoleD0005H0002.TIF</ORTHO_SURF_CORR_ENV>
 *      <ORTHO_SURF_CORR_PENTE>LANDSAT8_OLITIRS_XS_20130414_N2A_ORTHO_SURF_CORR_PENTE_France-MetropoleD0005H0002.TIF</ORTHO_SURF_CORR_PENTE>
 *      <MASK_N2>MASK</MASK_N2>
 *      <PRIVE>PRIVE</PRIVE>
 *  </FILES>
 *  <GEOMETRY>
 *      <PROJECTION>LAMBERT93</PROJECTION>
 *      <RESOLUTION>30.0</RESOLUTION>
 *      <NB_COLS>3667</NB_COLS>
 *      <NB_ROWS>3667</NB_ROWS>
 *      <PIXEL_SIZE_X>30.0</PIXEL_SIZE_X>
 *      <PIXEL_SIZE_Y>-30.0</PIXEL_SIZE_Y>
 *      <ORIGIN_X>500100.0</ORIGIN_X>
 *      <ORIGIN_Y>6321240.0</ORIGIN_Y>
 *  </GEOMETRY>
 *  <WGS84>
 *      <HGX>0.5089306011</HGX>
 *      <HGY>43.9617816364</HGY>
 *      <HDX>1.87953221903</HDX>
 *      <HDY>43.9844220002</HDY>
 *      <BGX>0.551427364497</BGX>
 *      <BGY>42.9725402821</BGY>
 *      <BDX>1.89865686671</BDX>
 *      <BDY>42.9947784555</BDY>
 *  </WGS84>
 *  <RADIOMETRY>
 *      <BANDS>B1;B2;B3;B4;B5;B6;B7</BANDS>
 *      <THERM_BANDS>False;False;False;False;False;False;False</THERM_BANDS>
 *      <K1></K1>
 *      <K2></K2>
 *      <MIN_REFLECTANCES>-0.09998;-0.09998;-0.09998;-0.09998;-0.09998;-0.09998;-0.09998</MIN_REFLECTANCES>
 *      <MAX_REFLECTANCES>1.2107;1.2107;1.2107;1.2107;1.2107;1.2107;1.2107</MAX_REFLECTANCES>
 *      <ADD_REFLECTANCES>-0.1;-0.1;-0.1;-0.1;-0.1;-0.1;-0.1</ADD_REFLECTANCES>
 *      <MULT_REFLECTANCES>2e-05;2e-05;2e-05;2e-05;2e-05;2e-05;2e-05</MULT_REFLECTANCES>
 *      <MIN_RADIANCES>-62.37846;-63.8763;-58.86147;-49.6353;-30.37433;-7.55382;-2.54604</MIN_RADIANCES>
 *      <MAX_RADIANCES>755.36707;773.50507;712.77832;601.05481;367.81558;91.47239;30.83109</MAX_RADIANCES>
 *      <ADD_RADIANCES>-62.39094;-63.88908;-58.87324;-49.64523;-30.38041;-7.55533;-2.54655</ADD_RADIANCES>
 *      <MULT_RADIANCES>0.012478;0.012778;0.011775;0.009929;0.0060761;0.0015111;0.00050931</MULT_RADIANCES>
 *      <MIN_VALUES>1.0;1.0;1.0;1.0;1.0;1.0;1.0</MIN_VALUES>
 *      <MAX_VALUES>65535.0;65535.0;65535.0;65535.0;65535.0;65535.0;65535.0</MAX_VALUES>
 *      <ANGLES>
 *          <PHI_S>148.40233004</PHI_S>
 *          <THETA_S>37.23948878</THETA_S>
 *      </ANGLES>
 *      <QUANTIFICATION>
 *          <THERM_BAND>False</THERM_BAND>
 *          <VALUE>1000.0</VALUE>
 *      </QUANTIFICATION>
 *  </RADIOMETRY>
 * 
 */
class MuscateController extends RestoController {
    
    /*
     * Muscate model is based on the XML file example above 
     */
    public static $model = array(
        'identifier' => 'db:identifier',
        'parentIdentifier' => 'SHOULD_BE_DEFINED_IN_COLLECTION_DEFINITION',
        'title' => null,
        'description' => null,
        'authority' => 'CNES',
        'startDate' => 'db:startDate',
        'completionDate' => 'db:completionDate',
        'productType' => 'db:productType',
        'processingLevel' => 'db:processingLevel',
        'platform' => 'db:platform',
        'instrument' => 'db:instrument',
        'resolution' => 'db:resolution',
        'sensorMode' => 'db:sensorMode',
        'orbitNumber' => null,
        'quicklook' => 'SHOULD_BE_DEFINED_IN_COLLECTION_DEFINITION',
        'thumbnail' => 'SHOULD_BE_DEFINED_IN_COLLECTION_DEFINITION',
        'metadata' => 'SHOULD_BE_DEFINED_IN_COLLECTION_DEFINITION',
        'archive' => 'SHOULD_BE_DEFINED_IN_COLLECTION_DEFINITION',
        'wms' => 'SHOULD_BE_DEFINED_IN_COLLECTION_DEFINITION',
        'mimetype' => 'application/x-gzip',
        'updated' => 'db:updated',
        'published' => 'db:published',
        'geometry' => 'db:geometry',
        'keywords' => 'db:keywords',
        'location' => array(
            'dbKey' => 'db:zonegeo',
            'type' => 'VARCHAR(50)'
        ),
        'version' => array(
            'dbKey' => 'db:version',
            'type' => 'VARCHAR(10)'
        ),
        'productionDate' => array(
            'dbKey' => 'db:dateprod',
            'type' => 'TIMESTAMP'
        ),
        'bands' => array(
            'dbKey' => 'db:bands',
            'type' => 'VARCHAR(50)'
        ),
        'thermBands' => array(
            'dbKey' => 'db:thermbands',
            'type' => 'VARCHAR(50)'
        ),
        'nb_cols' => array(
            'dbKey' => 'db:nbcols',
            'type' => 'INTEGER'
        ),
        'nb_rows' => array(
            'dbKey' => 'db:nbrows',
            'type' => 'INTEGER'
        ),
        'tileId' => array(
            'dbKey' => 'db:tileid',
            'type' => 'VARCHAR(20)'
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
        'eo:resolution?', // Resolution,
        'ptsc:tileId' // Tile identifier
    );

    /*
     * Search filters list
     */
    public static $searchFiltersDescription = array(
        'ptsc:tileId' => array(
            'key' => 'tileId',
            'osKey' => 'tileId',
            'operation' => '=',
            'keyword' => array(
                'value' => 'tileId={a:1}',
                'type' => 'other'
            )
        )
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
        $this->defaultPost(array($this->getFeatureCollection()));
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

    /**
     * Create JSON feature from xml string
     * 
     * @param {String} $xml : $xml string
     */
    private function parse($xml) {
        
        $dom = new DOMDocument();
        $dom->loadXML(rawurldecode($xml));
        
        /*
         * Initialize feature
         */
        $feature = array(
            'type' => 'Feature',
            'id' => $dom->getElementsByTagName("IDENT")->item(0)->nodeValue,
            'geometry' => array(
                'type' => 'Polygon',
                'coordinates' => array(
                    array(
                        array(
                            $dom->getElementsByTagName("HGX")->item(0)->nodeValue,
                            $dom->getElementsByTagName("HGY")->item(0)->nodeValue
                        ),
                        array(
                            $dom->getElementsByTagName("HDX")->item(0)->nodeValue,
                            $dom->getElementsByTagName("HDY")->item(0)->nodeValue
                        ),
                        array(
                            $dom->getElementsByTagName("BDX")->item(0)->nodeValue,
                            $dom->getElementsByTagName("BDY")->item(0)->nodeValue
                        ),
                        array(
                            $dom->getElementsByTagName("BGX")->item(0)->nodeValue,
                            $dom->getElementsByTagName("BGY")->item(0)->nodeValue
                        ),
                        array(
                            $dom->getElementsByTagName("HGX")->item(0)->nodeValue,
                            $dom->getElementsByTagName("HGY")->item(0)->nodeValue
                        )
                    )
                )
            ),
            'properties' => array(
                'startDate' => str_replace(' ', 'T', $dom->getElementsByTagName("DATE_PDV")->item(0)->nodeValue),
                'completionDate' => str_replace(' ', 'T', $dom->getElementsByTagName("DATE_PDV")->item(0)->nodeValue),
                'productType' => $this->getProductType($dom->getElementsByTagName("LEVEL")->item(0)->nodeValue),
                'processingLevel' => $this->getProcessingLevel($dom->getElementsByTagName("LEVEL")->item(0)->nodeValue),
                'platform' => $dom->getElementsByTagName("PLATEFORM")->item(0)->nodeValue,
                'instrument' => $dom->getElementsByTagName("SENSOR")->item(0)->nodeValue,
                'resolution' => $dom->getElementsByTagName("RESOLUTION")->item(0)->nodeValue,
                'sensorMode' => $dom->getElementsByTagName("MODE")->item(0)->nodeValue,
                'productionDate' => str_replace(' ', 'T', $dom->getElementsByTagName("DATE_PROD")->item(0)->nodeValue),
                'bands' =>  $dom->getElementsByTagName("BANDS")->item(0)->nodeValue,
                'thermBands' =>  $dom->getElementsByTagName("THERM_BANDS")->item(0)->nodeValue,
                'location' => preg_replace("/(.*)[A-Z][0-9]{4}[A-Z][0-9]{4}/", "$1", $dom->getElementsByTagName("ZONE_GEO")->item(0)->nodeValue),
                'version' => $dom->getElementsByTagName("VERSION")->item(0)->nodeValue,
                'nb_cols' => $dom->getElementsByTagName("NB_COLS")->item(0)->nodeValue,
                'nb_rows' => $dom->getElementsByTagName("NB_ROWS")->item(0)->nodeValue
            )
        );
        
        return $feature;
        
    }

    function getProductType($level) {

        $product = $level;

        if ($level === "N1_TUILE" || $level === "N1_SCENE") {
            $product = "REFLECTANCETOA";
        } else if ($level === "N2A") {
            $product = "REFLECTANCE";
        }


        return $product;
    }

    function getProcessingLevel($level) {

        $product = $level;

        if ($level === "N1_TUILE" || $level === "N1_SCENE") {
            $product = "LEVEL1C";
        } else if ($level === "N2A") {
            $product = "LEVEL2A";
        }


        return $product;
    }

    /**
     * Create the features collection associated to the file passed through POST or FILE
     */
    private function getFeatureCollection() {
        
        /*
         * Files is an array of array.
         * Second array contains XML file split line by line
         * (i.e. each entry of the array is a line of the XML file)
         */
        $files = getFiles(array(
            'mimeType' => 'text/xml'
        ));
        $features = array();
        for ($i = 0, $l = count($files); $i < $l; $i++) {
            $features[] = $this->parse(implode('', $files[$i]));
        }
        return $featureCollection = array(
            'type' => 'FeatureCollection',
            'totalResults' => count($features),
            'features' => $features
        );
    }

}
