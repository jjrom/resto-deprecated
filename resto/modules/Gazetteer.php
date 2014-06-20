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
 * Gazetteer module
 * 
 * Return a list of geographical locations
 * from a string
 * 
 */
class Gazetteer {
    
    /*
     * Gazetteer database handler
     */
    private $dbh;
    private $schema;

    /*
     * List of countries extract from Gazetteer database
     */
    private $countries = array(
        'afghanistan' => 'AF',
        'aland islands' => 'AX',
        'albania' => 'AL',
        'algeria' => 'DZ',
        'american samoa' => 'AS',
        'andorra' => 'AD',
        'angola' => 'AO',
        'anguilla' => 'AI',
        'antarctica' => 'AQ',
        'antigua and barbuda' => 'AG',
        'argentina' => 'AR',
        'armenia' => 'AM',
        'aruba' => 'AW',
        'australia' => 'AU',
        'austria' => 'AT',
        'azerbaijan' => 'AZ',
        'bahamas' => 'BS',
        'bahrain' => 'BH',
        'bangladesh' => 'BD',
        'barbados' => 'BB',
        'belarus' => 'BY',
        'belgium' => 'BE',
        'belize' => 'BZ',
        'benin' => 'BJ',
        'bermuda' => 'BM',
        'bhutan' => 'BT',
        'bolivia' => 'BO',
        'bonaire, saint eustatius and saba ' => 'BQ',
        'bosnia and herzegovina' => 'BA',
        'botswana' => 'BW',
        'bouvet island' => 'BV',
        'brazil' => 'BR',
        'british indian ocean territory' => 'IO',
        'british virgin islands' => 'VG',
        'brunei' => 'BN',
        'bulgaria' => 'BG',
        'burkina faso' => 'BF',
        'burundi' => 'BI',
        'cambodia' => 'KH',
        'cameroon' => 'CM',
        'canada' => 'CA',
        'cape verde' => 'CV',
        'cayman islands' => 'KY',
        'central african republic' => 'CF',
        'chad' => 'TD',
        'chile' => 'CL',
        'china' => 'CN',
        'christmas island' => 'CX',
        'cocos islands' => 'CC',
        'colombia' => 'CO',
        'comoros' => 'KM',
        'cook islands' => 'CK',
        'costa rica' => 'CR',
        'croatia' => 'HR',
        'cuba' => 'CU',
        'curacao' => 'CW',
        'cyprus' => 'CY',
        'czech republic' => 'CZ',
        'democratic republic of the congo' => 'CD',
        'denmark' => 'DK',
        'djibouti' => 'DJ',
        'dominica' => 'DM',
        'dominican republic' => 'DO',
        'east timor' => 'TL',
        'ecuador' => 'EC',
        'egypt' => 'EG',
        'el salvador' => 'SV',
        'equatorial guinea' => 'GQ',
        'eritrea' => 'ER',
        'estonia' => 'EE',
        'ethiopia' => 'ET',
        'falkland islands' => 'FK',
        'faroe islands' => 'FO',
        'fiji' => 'FJ',
        'finland' => 'FI',
        'france' => 'FR',
        'french guiana' => 'GF',
        'french polynesia' => 'PF',
        'french southern territories' => 'TF',
        'gabon' => 'GA',
        'gambia' => 'GM',
        'georgia' => 'GE',
        'germany' => 'DE',
        'ghana' => 'GH',
        'gibraltar' => 'GI',
        'greece' => 'GR',
        'greenland' => 'GL',
        'grenada' => 'GD',
        'guadeloupe' => 'GP',
        'guam' => 'GU',
        'guatemala' => 'GT',
        'guernsey' => 'GG',
        'guinea' => 'GN',
        'guinea-bissau' => 'GW',
        'guyana' => 'GY',
        'haiti' => 'HT',
        'heard island and mcdonald islands' => 'HM',
        'honduras' => 'HN',
        'hong kong' => 'HK',
        'hungary' => 'HU',
        'iceland' => 'IS',
        'india' => 'IN',
        'indonesia' => 'ID',
        'iran' => 'IR',
        'iraq' => 'IQ',
        'ireland' => 'IE',
        'isle of man' => 'IM',
        'israel' => 'IL',
        'italy' => 'IT',
        'ivory coast' => 'CI',
        'jamaica' => 'JM',
        'japan' => 'JP',
        'jersey' => 'JE',
        'jordan' => 'JO',
        'kazakhstan' => 'KZ',
        'kenya' => 'KE',
        'kiribati' => 'KI',
        'kosovo' => 'XK',
        'kuwait' => 'KW',
        'kyrgyzstan' => 'KG',
        'laos' => 'LA',
        'latvia' => 'LV',
        'lebanon' => 'LB',
        'lesotho' => 'LS',
        'liberia' => 'LR',
        'libya' => 'LY',
        'liechtenstein' => 'LI',
        'lithuania' => 'LT',
        'luxembourg' => 'LU',
        'macao' => 'MO',
        'macedonia' => 'MK',
        'madagascar' => 'MG',
        'malawi' => 'MW',
        'malaysia' => 'MY',
        'maldives' => 'MV',
        'mali' => 'ML',
        'malta' => 'MT',
        'marshall islands' => 'MH',
        'martinique' => 'MQ',
        'mauritania' => 'MR',
        'mauritius' => 'MU',
        'mayotte' => 'YT',
        'mexico' => 'MX',
        'micronesia' => 'FM',
        'moldova' => 'MD',
        'monaco' => 'MC',
        'mongolia' => 'MN',
        'montenegro' => 'ME',
        'montserrat' => 'MS',
        'morocco' => 'MA',
        'mozambique' => 'MZ',
        'myanmar' => 'MM',
        'namibia' => 'NA',
        'nauru' => 'NR',
        'nepal' => 'NP',
        'netherlands' => 'NL',
        'netherlands antilles' => 'AN',
        'new caledonia' => 'NC',
        'new zealand' => 'NZ',
        'nicaragua' => 'NI',
        'niger' => 'NE',
        'nigeria' => 'NG',
        'niue' => 'NU',
        'norfolk island' => 'NF',
        'north korea' => 'KP',
        'northern mariana islands' => 'MP',
        'norway' => 'NO',
        'oman' => 'OM',
        'pakistan' => 'PK',
        'palau' => 'PW',
        'palestinian territory' => 'PS',
        'panama' => 'PA',
        'papua new guinea' => 'PG',
        'paraguay' => 'PY',
        'peru' => 'PE',
        'philippines' => 'PH',
        'pitcairn' => 'PN',
        'poland' => 'PL',
        'portugal' => 'PT',
        'puerto rico' => 'PR',
        'qatar' => 'QA',
        'republic of the congo' => 'CG',
        'reunion' => 'RE',
        'romania' => 'RO',
        'russia' => 'RU',
        'rwanda' => 'RW',
        'saint barthelemy' => 'BL',
        'saint helena' => 'SH',
        'saint kitts and nevis' => 'KN',
        'saint lucia' => 'LC',
        'saint martin' => 'MF',
        'saint pierre and miquelon' => 'PM',
        'saint vincent and the grenadines' => 'VC',
        'samoa' => 'WS',
        'san marino' => 'SM',
        'sao tome and principe' => 'ST',
        'saudi arabia' => 'SA',
        'senegal' => 'SN',
        'serbia' => 'RS',
        'serbia and montenegro' => 'CS',
        'seychelles' => 'SC',
        'sierra leone' => 'SL',
        'singapore' => 'SG',
        'sint maarten' => 'SX',
        'slovakia' => 'SK',
        'slovenia' => 'SI',
        'solomon islands' => 'SB',
        'somalia' => 'SO',
        'south africa' => 'ZA',
        'south georgia and the south sandwich islands' => 'GS',
        'south korea' => 'KR',
        'south sudan' => 'SS',
        'spain' => 'ES',
        'sri lanka' => 'LK',
        'sudan' => 'SD',
        'suriname' => 'SR',
        'svalbard and jan mayen' => 'SJ',
        'swaziland' => 'SZ',
        'sweden' => 'SE',
        'switzerland' => 'CH',
        'syria' => 'SY',
        'taiwan' => 'TW',
        'tajikistan' => 'TJ',
        'tanzania' => 'TZ',
        'thailand' => 'TH',
        'togo' => 'TG',
        'tokelau' => 'TK',
        'tonga' => 'TO',
        'trinidad and tobago' => 'TT',
        'tunisia' => 'TN',
        'turkey' => 'TR',
        'turkmenistan' => 'TM',
        'turks and caicos islands' => 'TC',
        'tuvalu' => 'TV',
        'u.s. virgin islands' => 'VI',
        'uganda' => 'UG',
        'ukraine' => 'UA',
        'united arab emirates' => 'AE',
        'united kingdom' => 'GB',
        'united states' => 'US',
        'united states minor outlying islands' => 'UM',
        'uruguay' => 'UY',
        'uzbekistan' => 'UZ',
        'vanuatu' => 'VU',
        'vatican' => 'VA',
        'venezuela' => 'VE',
        'vietnam' => 'VN',
        'wallis and futuna' => 'WF',
        'western sahara' => 'EH',
        'yemen' => 'YE',
        'zambia' => 'ZM',
        'zimbabwe' => 'ZW'
    );

    /**
     * Constructor
     * 
     * @param array $R RESTo instance reference
     */
    public function __construct($R) {
        $config = $R->getModuleConfig('Gazetteer');
        if ($config && $config['activate']) {
            $dbConnector = $R->getDatabaseConnectorInstance();
            if (is_array($config['db'])) {
                $dbConnector->update($config['db']);
            }
            $this->dbh = $dbConnector->getConnection();
            $this->schema = $dbConnector->getSchema();
        }
    }

    /*
     * Return location from a $toponym
     * Use input gazetteer
     * 
     * Gazetteer tables format :
     * 
     *  CREATE TABLE geoname (
     *      geonameid   int,
     *      name varchar(200),
     *      asciiname varchar(200),
     *      alternatenames varchar(8000),
     *      latitude float,
     *      longitude float,
     *      fclass char(1),
     *      fcode varchar(10),
     *      country varchar(2),
     *      cc2 varchar(60),
     *      admin1 varchar(20),
     *      admin2 varchar(80),
     *      admin3 varchar(20),
     *      admin4 varchar(20),
     *      population bigint,
     *      elevation int,
     *      gtopo30 int,
     *      timezone varchar(40),
     *      moddate date,
     *      geom
     *  );
     * 
     * @param String $toponym toponym
     * @param String $lang language
     * @param String $countryName limit search to country name
     * @param String $bbox limit search to bbox
     * 
     * @return array
     * 
     */

    final public function locate($toponym, $lang = 'en', $countryName = null, $bbox = null) {
        
        $result = array();
        $where = '';
        
        /*
         * Order toponyms entry following convention
         * (see http://www.geonames.org/export/codes.html for class and code explanation)
         * 
         *      - fclass priority chain is P, A, the rest 
         *      - for 'P', fcode priority chain is PPLC, PPLA, PPLA2, PPLA3, PPLA4, PPL, the rest
         */
        $orderBy = ' ORDER BY CASE fclass WHEN \'P\' then 1 WHEN \'A\' THEN 2 ELSE 3 END, CASE fcode WHEN \'PPLC\' then 1 WHEN \'PPLA\' then 2 WHEN \'PPLA2\' then 3 WHEN \'PPLA4\' then 4 WHEN \'PPL\' then 5 ELSE 6 END';
        
        if (!$this->dbh || !$toponym) {
            return $result;
        }
        
        /*
         * Country name could be defined in toponym
         */
        $splitted = explode(',', $toponym);
        if (count($splitted) > 1) {
            $countryName = $splitted[1];
            $toponym = $splitted[0];
        }
        
        /*
         * Convert $toponym to a searchable name
         * i.e. no accents and lower case
         */
        $toponym = trim(strtolower(str_replace(array('\'', ',', ';'), '', asciify($toponym))));
        
        /*
         * Constrain search on country name
         */
        if ($countryName && $this->getCountryCode(trim($countryName))) {
            $where .= " AND country ='" . pg_escape_string($this->getCountryCode(trim($countryName))) . "'";
        }

        /*
         * Constrain search on bbox
         */
        $bboxConstraint = '';
        if ($bbox) {
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
            if ($lonmin <= -180 && $latmin <= -90 && $lonmax >= 180 && $latmax >= 90) {
                $bboxConstraint = '';
            }
            else {
                $bboxConstraint = " AND ST_intersects(geom, ST_GeomFromText('" . pg_escape_string('POLYGON((' . $lonmin . ' ' . $latmin . ',' . $lonmin . ' ' . $latmax . ',' . $lonmax . ' ' . $latmax . ',' . $lonmax . ' ' . $latmin . ',' . $lonmin . ' ' . $latmin . '))') . "', 4326))";
            }
        }
        
        /*
         * First search in native language within alternatename table
         */
        if ($lang !== 'en') {
            $toponyms = pg_query($this->dbh, 'SELECT name, country as countrycode, latitude, longitude, fclass, fcode, population FROM ' . $this->schema . '.geoname WHERE geonameid = ANY((SELECT array(SELECT geonameid FROM ' . $this->schema . '.alternatename WHERE searchname =\'' . pg_escape_string($toponym) . '\'  AND isolanguage=\'' . $lang . '\'))::integer[])' . $where . $orderBy);
            if (!$toponyms) {
                return $result;
            }
        }
        
        /*
         * No result - search in english
         */
        if ($lang === 'en' || pg_num_rows($toponyms) === 0) {
            $toponyms = pg_query($this->dbh, 'SELECT name, country as countrycode, latitude, longitude, fclass, fcode, population FROM ' . $this->schema . '.geoname WHERE searchname =\'' . pg_escape_string($toponym) . '\'' . $where . $bboxConstraint . $orderBy);
            if (!$toponyms) {
                return $result;
            }
        }
        
        if (!$toponyms) {
            return $result;
        }

        /*
         * No result - check without bbox
         */
        if (pg_num_rows($toponyms) === 0 && $bboxConstraint) {
            $toponyms = pg_query($this->dbh, 'SELECT name, country as countrycode, latitude, longitude, fclass, fcode, population FROM ' . $this->schema . '.geoname WHERE searchname =\'' . pg_escape_string($toponym) . '\'' . $where . $orderBy);
            if (!$toponyms) {
                return $result;
            }
        }
        /*
         * Retrieve first result
         */
        while ($toponym = pg_fetch_assoc($toponyms)) {
            /*$countries = pg_query($this->dbh, 'SELECT name FROM countryinfo WHERE iso_alpha2=\'' . $toponym['countrycode'] . '\'');
            if ($countries) {
                $country = pg_fetch_assoc($countries);
                $toponym['country'] = $country['name'];
            }*/
            $toponym['country'] = ucwords(array_search($toponym['countrycode'], $this->countries));
            $result[] = $toponym;
        }

        return $result;
    }
    
    /**
     * Return country code for a given country
     * 
     * @param string $countryName
     */
    final private function getCountryCode($countryName) {
        if (!$countryName) {
            return null;
        }
        return $this->countries[strtolower($countryName)];
    }

}