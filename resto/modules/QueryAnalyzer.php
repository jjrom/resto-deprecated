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
 * QueryAnalyzer module
 * 
 * Extract OpenSearch EO search parameters from
 * an input string (i.e. searchTerms)
 * 
 */
class QueryAnalyzer {

    private $dictionary;
    private $searchFiltersDescription;
    private $gazetteer;
    private $unProcessed = array();
    private $remaining = array();

    public function __construct($dictionary, $searchFiltersDescription, $gazetteer) {
        $this->dictionary = $dictionary;
        $this->searchFiltersDescription = $searchFiltersDescription;
        $this->gazetteer = $gazetteer;
    }

    /*
     * Return the more similar dictionary keyword from input string
     * Return null if similarity is < 90%
     * 
     * @param {String} $s
     */
    final private function getSimilar($s) {
        
        $bestPercentage = 80;
        $similar = null;
        $keywords = $this->dictionary->getKeywords();
        foreach(array_keys($keywords) as $category) {
            foreach(array_keys($keywords[$category]) as $key) {
                $p = 0.0;
                similar_text($s, $key, $p);
                if ($p >= $bestPercentage) {
                    $similar = array('keyword' => $keywords[$category][$key], 'type' => $category);
                    $bestPercentage = $p;
                }
            }
        }
        
        return $similar;
    }
    
    /*
     * Return true if the entry is a numeric value wich is
     * the case if value is really numeric or if value is
     * a string within the numbers dictionary
     * 
     * @param String $str
     */
    final private function isNumeric($str) {
        
        if (is_numeric($str)) {
            return true;
        }
        
        if ($this->dictionary->getNumber($str) !== null) {
            return true;
        }
        
        return false;
    }
    
    /*
     * Return numeric value of input $str
     * 
     * @param String $str
     */
    final private function toNumeric($str) {
        
        if (is_numeric($str)) {
            return $str;
        }
        
        return $this->dictionary->getNumber($str);
    }
    
    /*
     * Return filter name associated to $quantity
     * 
     * A valid quantity should be defined with searchFiltersDescription as
     *      'quantity' => array(
     *          'value' => // name of the quantity (i.e. an existing entry in "quantities" dictionary array)
     *          'unit' => // unit of the quantity (i.e. an existing entry in "units" dictionnary array)
     *      )
     * 
     * @param String $quantity
     */
    final private function getSearchFilter($quantity) {
        
        if (!$quantity) {
            return null;
        }
        
        foreach(array_keys($this->searchFiltersDescription) as $key) {
            if (is_array($this->searchFiltersDescription[$key]['quantity']) && $this->searchFiltersDescription[$key]['quantity']['value'] === $quantity) {
                return array('key' => $key, 'unit' => $this->searchFiltersDescription[$key]['quantity']['unit']);
            }
        }
        
        return null;
    }
    
    /**
     * Return normalized unit from $unit
     * e.g. if $unit = 'km', returned value is 
     *      array(
     *          'unit' => 'm',
     *          'factor' => 1000
     *      )
     * 
     * @param string $unit
     */
    final private function normalizedUnit($unit) {
        
        if (!$unit) {
            return null;
        }
        
        $factor = 1.0;
        switch ($unit) {
            case 'km':
                $unit = 'm';
                $factor = 1000.0;
                break;
            default:
                break;
        }
        
        return array(
            'unit' => $unit,
            'factor' => $factor
        );
    }
    
    /*
     * Query analyzer process searchTerms and modify query parameters accordingly
     * 
     * A typical searchTerms query can be anything :
     * 
     *      searchTerms = "spot5 images with forest in france between march 2012 and may 2012"
     * 
     * The query analyzer converts this string into comprehensive request.
     * 
     * For instance the previous string will be transformed as :
     *  
     *      eo:platformShortName = spot5
     *      time:start = 2012-01-03
     *      time:end = 2012-31-05
     *      geo:box = POLYGON(( ...coordinates of France country...))
     *      searchTerms = forest
     * 
     * IMPORTANT : if a word is prefixed by 'xxx=' then QueryAnalyzer considered the string as a key=value pair
     * 
     * Some notes :
     * 
     * Dictionary
     * ==========
     * The dictionary structure :
     * 
     *      array(
     *          excluded => array(),
     *          modifiers => array(),
     *          units => array(),
     *          numbers => array(),
     *          months => array(),
     *          quantities => array(),
     *          platforms => array(),
     *          instruments => array(),
     *          keywords => array()
     *      )
     * 
     * 
     * Dates
     * =====
     * Detected dates format are :
     *      
     *      ISO8601 : see isISO8601($str) in lib/functions.php (e.g 2010-10-23)
     *      <month> <year> (e.g. may 2010)
     *      <year> <month> (e.g. 2010 may)
     *      <day> <month> <year> (e.g. 10 may 2010)
     *      <year> <month> <day> (e.g. 2010 may 10)
     * 
     * Modifiers
     * =========
     * 
     * A 'modifier' is a term which modify the way following term(s) are handled.
     * Known <modifier> and expected "terms" are :
     * 
     *      <with> "keyword" 
     *      <without> "keyword"
     * 
     *      "quantity" <lesser> (than) "numeric" "unit"
     *      "quantity" <greater> (than) "numeric" "unit"
     *      "quantity" <equal> (to) "numeric" "unit"
     *      <lesser> (than) "numeric" "unit" (of) "quantity" 
     *      <greater> (than) "numeric" "unit" (of) "quantity"
     *      <equal> (to) "numeric" "unit" (of) "quantity"
     * 
     *      <today>
     *      <yesterday>
     * 
     *      <before> "date"
     *      <after> "date"
     *      
     *      <between> "date" <and> "date"
     *      "quantity" <between> "numeric" <and> "numeric" ("unit")
     *      <between> "numeric" <and> "numeric" "unit" (of) "quantity"
     * 
     *      <last> "(year|day|month)"
     *      <last> "numeric" "(year|day|month)"
     *      "numeric" <last> "(year|day|month)"
     *      "(year|day|month)" <last>
     * 
     *      <since> "numeric" "(year|day|month)"
     *      <since> "month" "year"
     *      <since> "date"
     *      <since> "numeric" <last> "(year|day|month)"
     *      <since> <last> "numeric" "(year|day|month)"
     *      <since> <last> "(year|day|month)"
     *      <since> "(year|day|month)" <last>
     * 
     *      "numeric" "units" <ago>
     * 
     */
    final public function analyze($params) {

        $startTime = microtime(true);
        
        /*
         * queryAnalyzer only apply on searchTerms filter
         */
        if (!isset($params['searchTerms']) || !$params['searchTerms']) {
            $params['searchTerms'] = "";
            return array('query' => '', 'analyze' => $params, 'queryAnalyzeProcessingTime' => microtime(true) - $startTime);
        }
        
        /*
         * Set analyze language
         */
        if (!isset($params['language']) || !$params['language']) {
            $params['language'] = $this->dictionary->language;
        }
        
        /*
         * Store input
         */
        $input = $params['searchTerms'];
        
        /*
         * Transliterate searchTerms string
         * Split each terms with (" "character)
         */
        $searchTerms = preg_split('/ /', str_replace(array('!', '?'), '', asciify($params['searchTerms'])));
        
        /*
         * First extract explicit mapping i.e. words with '=' delimiter 
         */
        $toRemove = array();
        for ($i = 0, $l = count($searchTerms); $i < $l; $i++) {
            $splitted = explode('=', $searchTerms[$i]);
            if (count($splitted) === 2) {
                foreach(array_keys($this->searchFiltersDescription) as $key) {
                    if ($this->searchFiltersDescription[$key]['osKey'] === $splitted[0]) {
                        $params[$key] = $splitted[1];
                        break;
                    }
                }
                array_push($toRemove, $searchTerms[$i]);
            }
        }
        
        /*
         * Update searchTerms passed by reference in the function
         * Split searchTerms on (" ", "'", ";") characters
         */
        $searchTerms = preg_split('/ /', str_replace(array('!', '?'), '', str_replace(array('\\', '\'', ';'), ' ', join(' ', $this->stripArray($searchTerms, $toRemove)))));
        function trimComma(&$value) {
            $value = strtolower(rtrim($value, ','));
        }
        array_walk_recursive($searchTerms, 'trimComma');

        /*
         *  - Extract Platform and Instrument
         *  - Remove words with less than 4 characters that are not in dictionary
         *  - Remove excluded words
         */
        $this->extractPlaformAndInstrument($searchTerms, $params);
        
        /*
         * At this stage remaining terms are 
         *  - numeric values
         *  - modifiers
         *  - non excluded terms with 4 or more characters in length that
         */
        $this->extractModifiers($searchTerms, $params);
        
        /*
         * Extract dates alone
         */
        $this->extractDates($searchTerms, $params);
        
        /*
         * Extract keywords
         */
        $this->extractKeywordsAndLocation($searchTerms, $params);

        return array('query' => $input, 'analyze' => $params, 'unProcessed' => $this->unProcessed, 'remaining' => implode(' ', $this->remaining), 'queryAnalyzeProcessingTime' => microtime(true) - $startTime);
        
    }

    /**
     * Extract Platform and Instrument from searchTerms array
     * then remove
     *      - terms less than 4 characters in length
     *      - excluded characters
     */
    private function extractPlaformAndInstrument(&$searchTerms, &$params) {
       
        $toRemove = array();
     
        for ($i = 0, $l = count($searchTerms); $i < $l; $i++) {

            /*
             * Platforms is an associative array
             */
            if ($this->dictionary->getPlatform($searchTerms[$i]) !== null) {
                $params['eo:platformShortName'] = $this->dictionary->getPlatform($searchTerms[$i]);
                array_push($toRemove, $searchTerms[$i]);
            }
            /*
             * Instruments is an indexed array
             */
            else if ($this->dictionary->getInstrument($searchTerms[$i]) !== null) {
                $params['eo:instrument'] = $this->dictionary->getInstrument($searchTerms[$i]);
                array_push($toRemove, $searchTerms[$i]);
            }
            /*
             * Remove non numeric terms
             *  - with less than 4 characters in length
             *  - that are not in the dictionary
             */
            else if (strlen($searchTerms[$i]) < 4
                     && !$this->isNumeric($searchTerms[$i])
                     && !$this->dictionary->getModifier($searchTerms[$i])
                     && !$this->dictionary->getMonth($searchTerms[$i])
                     && !$this->dictionary->getUnit($searchTerms[$i])
                     && !$this->dictionary->getQuantity($searchTerms[$i])
                     && !$this->dictionary->getKeyword($searchTerms[$i])
                     && !$this->dictionary->isKeywordsValue($searchTerms[$i])) {
                array_push($toRemove, $searchTerms[$i]);
                array_push($this->unProcessed, $searchTerms[$i]);
            }
            /*
             * Remove excluded terms
             */
            else if ($this->dictionary->isExcluded($searchTerms[$i])) {
                array_push($toRemove, $searchTerms[$i]);
                array_push($this->unProcessed, $searchTerms[$i]);
            }
            
        }
        
        /*
         * Update searchTerms passed by reference in the function
         */
        $searchTerms = $this->stripArray($searchTerms, $toRemove);

    }
    
    /**
     * Extract Modifiers from $searchTerms array
     * 
     * @param array $searchTerms
     * @param array $params
     */
    private function extractModifiers(&$searchTerms, &$params) {
        
        $toRemove = array();
        $foundSince = false;
      
        for ($i = 0, $l = count($searchTerms); $i < $l; $i++) {

            /*
             * Modifiers, aka the tricky part :)
             */
            if ($this->dictionary->getModifier($searchTerms[$i])) {

                $modifier = $this->dictionary->getModifier($searchTerms[$i]);

                /*
                 * <without> "keyword"
                 * Add a "-" character in front of the term
                 */
                if ($modifier === 'without') {
                    if ($i + 1 < $l && !$this->isNumeric($searchTerms[$i + 1])) {
                        $searchTerms[$i + 1] = '-' . $searchTerms[$i + 1];
                    }
                }
                /*
                 * <before> "date"
                 */
                else if ($modifier === 'before') {
                    if ($i + 1 < $l && isISO8601($searchTerms[$i + 1])) {
                        $params['time:end'] = toISO8601($searchTerms[$i + 1]);
                        array_push($toRemove, $searchTerms[$i + 1]);
                    }
                }
                /*
                 * <after> "date"
                 */
                else if ($modifier === 'after') {
                    if ($i + 1 < $l && isISO8601($searchTerms[$i + 1])) {
                        $params['time:start'] = toISO8601($searchTerms[$i + 1]);
                        array_push($toRemove, $searchTerms[$i + 1]);
                    }
                }
                /*
                 * <between> "date" <and> "date"
                 * "quantity" <between> "numeric" <and> "numeric" "unit"
                 * <between> "numeric" <and> "numeric" "unit" (of) "quantity"
                 */
                else if ($modifier === 'between') {
                    $this->processModifierBetween($searchTerms, $params, $toRemove, $i, $l);
                }
                /*
                 * <since> works with a date.
                 */
                else if ($modifier === 'since') {
                    $this->processModifierSince($searchTerms, $params, $toRemove, $i, $l);
                    $foundSince = true;
                }
                /*
                 * <last> works with a date
                 */
                else if ($modifier === 'last' && !$foundSince) {
                    $this->processModifierLast($searchTerms, $params, $toRemove, $i, $l);
                }
                /*
                 *  "quantity" <lesser> (than) "numeric" "unit"
                 *  "quantity" <greater> (than) "numeric" "unit"
                 *  "quantity" <equal> (to) "numeric" "unit"
                 *  <lesser> (than) "numeric" "unit" (of) "quantity" 
                 *  <greater> (than) "numeric" "unit" (of) "quantity"
                 *  <equal> (to) "numeric" "unit" (of) "quantity"
                 * 
                 */
                else if ($modifier === 'lesser' || $modifier === 'greater' || $modifier === 'equal') {
                    $this->processModifierEqualLesserOrGreater($modifier, $searchTerms, $params, $toRemove, $i, $l);
                }
                /*
                 * "numeric" "units" "ago"
                 */
                else if ($modifier === 'ago') {
                    $this->processModifierAgo($searchTerms, $params, $toRemove, $i);
                }
                /*
                 * Today - so easy :)
                 */
                else if ($modifier === 'today') {
                    $params['time:start'] = date('Y-m-d') . 'T00:00:00';
                    $params['time:end'] = date('Y-m-d') . 'T23:59:59';
                }
                /*
                 * Yesterday - also easy :)
                 */
                else if ($modifier === 'yesterday') {
                    $params['time:start'] = date('Y-m-d', strtotime(date('Y-m-d') . ' - 1 days')) . 'T00:00:00';
                    $params['time:end'] = date('Y-m-d', strtotime(date('Y-m-d') . ' - 1 days')) . 'T23:59:59';
                }
          
                array_push($toRemove, $searchTerms[$i]);
            }
            else if ($this->dictionary->isExcluded($searchTerms[$i])) {
                array_push($toRemove, $searchTerms[$i]);
            }
        }
        
        /*
         * Rewrite searchTerms query without removed element
         */
        $searchTerms = $this->stripArray($searchTerms, $toRemove);
        
    }
    
    /**
     * Extract date from searchTerms array
     */
    private function extractDates(&$searchTerms, &$params) {
        
        $toRemove = array();
     
        /*
         * Date alone
         */
        for ($i = 0, $l = count($searchTerms); $i < $l; $i++) {
            
            /*
             * Year
             */
            if (preg_match('/^\d{4}$/i', $searchTerms[$i])) {
                $year = $searchTerms[$i];
                array_push($toRemove, $searchTerms[$i]);
            }
            /*
             * Textual month
             */
            else if ($this->dictionary->getMonth($searchTerms[$i]) !== null) {
                $month = $this->dictionary->getMonth($searchTerms[$i]);
                array_push($toRemove, $searchTerms[$i]);
            }
            /*
             * Day is an int value < 31
             * Month shoud specified immediately before or after
             */
            else if (is_numeric($searchTerms[$i])) {
                if (($i - 1 > 0 && $this->dictionary->getMonth($searchTerms[$i - 1])) || ($i + 1 < $l && $this->dictionary->getMonth($searchTerms[$i + 1]))) {
                    $d = intval($searchTerms[$i]);
                    if ($d > 0 && $d < 31) {
                        $day = $d < 10 ? '0' . $d : $d;
                        array_push($toRemove, $searchTerms[$i]);
                    }
                }
            }
            
            /*
             * ISO8601 date
             */
            else if (isISO8601($searchTerms[$i])) {

                $l = strlen($searchTerms[$i]);

                /*
                 * Year only
                 */
                if ($l === 4) {
                    $year = substr($searchTerms[$i], 0, 4);
                }
                /*
                 * Year and month
                 */
                else if ($l === 7) {
                    $year = substr($searchTerms[$i], 0, 4);
                    $month = substr($searchTerms[$i], 5, 2);
                }
                /*
                 * Year, month and day
                 */
                else if ($l === 10) {
                    $year = substr($searchTerms[$i], 0, 4);
                    $month = substr($searchTerms[$i], 5, 2);
                    $day = substr($searchTerms[$i], 8, 2);
                }

                array_push($toRemove, $searchTerms[$i]);
            }
            
        }
       
        /*
         * Set date
         */
        if (isset($year)) {

            /*
             * Year only
             */
            if (!isset($month)) {
                $params['time:start'] = $year . '-01-01' . 'T00:00:00';
                $params['time:end'] = $year . '-12-31' . 'T23:59:59';
            }
            /*
             * Year and month
             */
            else if (!isset($day)) {
                $params['time:start'] = $year . '-' . $month . '-01' . 'T00:00:00';
                $params['time:end'] = $year . '-' . $month . '-' . cal_days_in_month(CAL_GREGORIAN, intval($month), intval($year)) . 'T23:59:59';
            }
            /*
             * Year, month and day
             */
            else {
                $params['time:start'] = $year . '-' . $month . '-' . $day . 'T00:00:00';
                $params['time:end'] = $year . '-' . $month . '-' . $day . 'T23:59:59';
            }
        }

        /*
         * Rewrite searchTerms query without removed element
         */
        $searchTerms = $this->stripArray($searchTerms, $toRemove);
    }
    
    /**
     * Extract keywords and location
     * 
     * @param type $searchTerms
     */
    private function extractKeywordsAndLocation(&$searchTerms, &$params) {
        
        $toRemove = array();
        $countryName = null;
        
        /*
         * Keywords
         */
        $keywords = array();
        for ($i = 0, $l = count($searchTerms); $i < $l; $i++) {

            /*
             * Detect presence of '-' sign (see without)
             */
            $s = $searchTerms[$i];
            $sign = '';
            if (substr($s, 0, 1) === '-') {
                $sign = '-';
                $s = substr($s, 1);
            }
            $s = str_replace('-', ' ', $s);
            
            /*
             * Tags start with '#'
             */
            if (substr($s , 0, 1) === '#') {
                array_push($keywords, $sign . $s);
                array_push($toRemove, $searchTerms[$i]);
            }
            else {
                $keyword = $this->dictionary->getKeyword($s);
                if ($keyword) {
                    array_push($keywords, $sign . $keyword['type'] . ':' . str_replace(' ', '-', $keyword['keyword']));
                    if ($keyword['type'] === 'country') {
                        $countryName = $keyword['keyword'];
                    }
                    array_push($toRemove, $searchTerms[$i]);
                }
                else {

                    /*
                     * Check similarity
                     */
                    $similar = $this->getSimilar($s);
                    if ($similar) {
                        array_push($keywords, $sign . $similar['type'] . ':' . str_replace(' ', '-', $similar['keyword']));
                        if ($keyword['type'] === 'country') {
                            $countryName = $keyword['keyword'];
                        }
                        array_push($toRemove, $searchTerms[$i]);
                    }
                }
            }
        }
        
        /*
         * Rewrite searchTerms query without removed element
         */
        $searchTerms = $this->stripArray($searchTerms, $toRemove);
        $countryFoundInGazetteer = null;
        
        /*
         * Ultimate keywords treated as location or discarded
         * 
         * Note: remaining keywords for location detection are processed
         * in reverse order assuming that grammatically, the location
         * occurence has greater probability to be defined at the end
         * of the sentence 
         */
        for ($i = count($searchTerms); $i--;) {
            
            /*
             * Check in Gazetteer except if a toponym was already found !
             */
            if ($this->gazetteer && !$countryFoundInGazetteer) {
                $locations = $this->gazetteer->locate($searchTerms[$i], $this->dictionary->language, $countryName, isset($params['geo:box']) ? $params['geo:box'] : null);
                if (count($locations) > 0) {
                    $countryFoundInGazetteer = $locations[0]['country'];
                    $params['geo:name'] = $locations[0]['name'] . ', ' . $countryFoundInGazetteer;
                    $params['geo:lon'] = $locations[0]['longitude'];
                    $params['geo:lat'] = $locations[0]['latitude'];
                    if (!isset($params['geo:radius']) || !$params['geo:radius']) {
                        $params['geo:radius'] = 10000;
                    }
                }
                else {
                    array_push($this->unProcessed, $searchTerms[$i]);
                    array_push($this->remaining, $searchTerms[$i]);
                }
            }
            else {
                array_push($this->unProcessed, $searchTerms[$i]);
                array_push($this->remaining, $searchTerms[$i]);
            }
        }
        
        /*
         * Remove $countryFoundInGazetteer within keywords if any to avoid duplication
         */
        if ($countryFoundInGazetteer) {
            $keywords = array_diff($keywords, array(strtolower($countryFoundInGazetteer)));
        }
        
        /*
         * Rewrite searchTerms without extracted terms removing duplicates
         */
        $params['searchTerms'] = implode(' ', array_unique(explode(' ', superImplode(' ', $keywords))));
        
    }
    
    /**
     * 
     * Process modifier 'between'
     * 
     *      <between> "date" <and> "date"
     *      "quantity" <between> "numeric" <and> "numeric" "unit"
     *      <between> "numeric" <and> "numeric" "unit" (of) "quantity"
     * 
     * @param array $searchTerms
     * @param array $params
     * @param array $toRemove
     * @param integer $i position of word in the list
     * @param integer $l number of words of the list
     */
    private final function processModifierBetween(&$searchTerms, &$params, &$toRemove, $i, $l) {
        
        /*
         * <between> "date" <and> "date"
         */
        if ($i + 3 < $l && isISO8601($searchTerms[$i + 1]) && $this->dictionary->getModifier($searchTerms[$i + 2]) === 'and' && isISO8601($searchTerms[$i + 3])) {
            $params['time:start'] = toISO8601($searchTerms[$i + 1]);
            $params['time:end'] = toISO8601($searchTerms[$i + 3]);
            array_push($toRemove, $searchTerms[$i + 1]);
            array_push($toRemove, $searchTerms[$i + 2]);
            array_push($toRemove, $searchTerms[$i + 3]);
        }
        /*
         * <between> "numeric" <and> "numeric" ("unit")
         */
        else if ($i + 3 < $l && $this->isNumeric($searchTerms[$i + 1]) && $this->dictionary->getModifier($searchTerms[$i + 2]) === 'and' && $this->isNumeric($searchTerms[$i + 3])) {
            
            /*
             * Unit is specified in request
             */
            $unit = $i + 4 < $l ? $this->normalizedUnit($this->dictionary->getUnit($searchTerms[$i + 4])) : null;
            
            $c = $unit ? 1 : 0;
            
            /*
             * "quantity" <between> ...
             */
            if ($i - 1 >= 0 && $this->dictionary->getQuantity($searchTerms[$i - 1])) {
                $searchFilter = $this->getSearchFilter($this->dictionary->getQuantity($searchTerms[$i - 1]));
                array_push($toRemove, $searchTerms[$i - 1]);
            }
            /*
             * <between> ... "quantity" 
             */
            else if ($i + 4 + $c < $l && $this->dictionary->getQuantity($searchTerms[$i + 4 + $c])) {
                $searchFilter = $this->getSearchFilter($this->dictionary->getQuantity($searchTerms[$i + 4 + $c]));
                array_push($toRemove, $searchTerms[$i + 4 + $c]);
            }
            /*
             * <between> ... of "quantity" 
             */
            else if ($i + 5 + $c < $l && $this->dictionary->getModifier($searchTerms[$i + 4 + $c]) && $this->dictionary->getQuantity($searchTerms[$i + 5 + $c])) {
                $searchFilter = $this->getSearchFilter($this->dictionary->getQuantity($searchTerms[$i + 5 + $c]));
                array_push($toRemove, $searchTerms[$i + 4 + $c]);
                array_push($toRemove, $searchTerms[$i + 5 + $c]);
            }
           
            /*
             * Search filter associated to quantity
             */
            if ($searchFilter) {

                /*
                 * Unit is set and coherent with quantity unit
                 */
                if ($unit && ($unit['unit'] === $searchFilter['unit'])) {
                    $params[$searchFilter['key']] = ']' . (floatval($this->toNumeric($searchTerms[$i + 1])) * $unit['factor']) . ',' . (floatval($this->toNumeric($searchTerms[$i + 3])) * $unit['factor']) . '[';
                }
                else if (!$unit && !$searchFilter['unit']) {
                    $params[$searchFilter['key']] = ']' . $this->toNumeric($searchTerms[$i + 1]) . ',' . $this->toNumeric($searchTerms[$i + 3]) . '[';
                }
            }
            
            if ($unit) {
                array_push($toRemove, $searchTerms[$i + 4]);
            }
            
            array_push($toRemove, $searchTerms[$i + 1]);
            array_push($toRemove, $searchTerms[$i + 2]);
            array_push($toRemove, $searchTerms[$i + 3]);
        }
        
    }
    
    /**
     * Process modifier 'since'
     * 
     * Understood structures are :
     *  
     *      <since> "numeric" "(year|day|month)"
     *      <since> "month" "year"
     *      <since> "date"
     *      <since> "numeric" <last> "(year|day|month)"
     *      <since> <last> "numeric" "(year|day|month)"
     *      <since> <last> "(year|day|month)"
     *      <since> "(year|day|month)" <last>
     *      
     * 
     * Example :
     *           
     *      If current date is November 2013 (i.e. 2013-11) then
     *      the "<last> 2 months" are October and December 2013
     * 
     * @param array $searchTerms
     * @param array $params
     * @param array $toRemove
     * @param integer $i position of word in the list
     * @param integer $l number of words of the list
     */
    private final function processModifierSince(&$searchTerms, &$params, &$toRemove, $i, $l) {
       
        $unit = null;
        $duration = 1;

        /*
         * <since> "numeric" "(year|day|month)"
         */
        if ($i + 2 < $l && $this->isNumeric($searchTerms[$i + 1]) && $this->dictionary->getUnit($searchTerms[$i + 2])) {
            $unit = $this->dictionary->getUnit($searchTerms[$i + 2]);
            $duration = $this->toNumeric($searchTerms[$i + 1]);
            array_push($toRemove, $searchTerms[$i + 2]);
            array_push($toRemove, $searchTerms[$i + 1]);
        }
        /*
         * <since> "date"
         */
        else if ($i + 1 < $l && isISO8601($searchTerms[$i + 1])) {
            $params['time:start'] = toISO8601($searchTerms[$i + 1]);
            array_push($toRemove, $searchTerms[$i + 1]);
        }
        /*
         * <since> "month" "year"
         */
        else if ($i + 2 < $l && $this->dictionary->getMonth($searchTerms[$i + 1]) && preg_match('\d{4}$', $searchTerms[$i + 2])) {
            $params['time:start'] = $searchTerms[$i + 2] . '-' . $this->dictionary->getMonth($searchTerms[$i + 1]) . '-01' . 'T00:00:00';
            array_push($toRemove, $searchTerms[$i + 1]);
            array_push($toRemove, $searchTerms[$i + 2]);
        }
        /*
         * <since> "month"
         */
        else if ($i + 1 < $l && $this->dictionary->getMonth($searchTerms[$i + 1])) {
            $params['time:start'] = date('Y') . '-' . $this->dictionary->getMonth($searchTerms[$i + 1]) . '-01' . 'T00:00:00';
            array_push($toRemove, $searchTerms[$i + 1]);
        }
        /*
         * <since> "numeric" <last> "(year|day|month)"
         */
        else if ($i + 3 < $l && $this->dictionary->getNumber($searchTerms[$i + 1]) && $this->dictionary->getModifier($searchTerms[$i + 2]) === 'last' && $this->dictionary->getUnit($searchTerms[$i + 3])) {
            $unit = $this->dictionary->getUnit($searchTerms[$i + 3]);
            $duration = $this->toNumeric($searchTerms[$i + 1]);
            array_push($toRemove, $searchTerms[$i + 3]);
            array_push($toRemove, $searchTerms[$i + 1]);
        }
        /*
         * <since> <last> "numeric" "(year|day|month)"
         */
        else if ($i + 3 < $l && $this->dictionary->getModifier($searchTerms[$i + 1]) === 'last' && $this->isNumeric($searchTerms[$i + 2]) && $this->dictionary->getUnit($searchTerms[$i + 3])) {
            $unit = $this->dictionary->getUnit($searchTerms[$i + 3]);
            $duration = $this->toNumeric($searchTerms[$i + 2]);
            array_push($toRemove, $searchTerms[$i + 3]);
            array_push($toRemove, $searchTerms[$i + 2]);
        }
        /*
         * <since> <last> "(year|day|month)"
         */
        else if ($i + 2 < $l && $this->dictionary->getModifier($searchTerms[$i + 1]) === 'last' && $this->dictionary->getUnit($searchTerms[$i + 2])) {
            $unit = $this->dictionary->getUnit($searchTerms[$i + 2]);
            array_push($toRemove, $searchTerms[$i + 2]);
        }
        /*
         * <since> "(year|day|month)" <last>
         */
        else if ($i + 2 < $l && $this->dictionary->getModifier($searchTerms[$i + 2]) === 'last' && $this->dictionary->getUnit($searchTerms[$i + 1])) {
            $unit = $this->dictionary->getUnit($searchTerms[$i + 1]);
            array_push($toRemove, $searchTerms[$i + 1]);
        }

        /*
         * Known duration unit
         */
        if ($unit === 'days' || $unit === 'months' || $unit === 'years') {
            $params['time:start'] = date('Y-m-d', strtotime(date('Y-m-d') . ' - ' . $duration . $unit));
        }
                 
    }
    
    /**
     * Process modifier 'last'
     * 
     * Understood structures are :
     *  
     *      <last> "(year|day|month)"
     *      <last> "numeric" "(year|day|month)"
     *      "numeric" <last> "(year|day|month)"
     *      "(year|day|month)" <last>
     * 
     * Example :
     *           
     *      If current date is November 2013 (i.e. 2013-11) then
     *      the "<last> 2 months" are October and December 2013
     * 
     * @param array $searchTerms
     * @param array $params
     * @param array $toRemove
     * @param integer $i position of word in the list
     * @param integer $l number of words of the list
     */
    private final function processModifierLast(&$searchTerms, &$params, &$toRemove, $i, $l) {
       
        $unit = null;
        $duration = 1;

        /*
         * <last> "numeric" "(year|day|month)"
         */
        if ($i + 2 < $l && $this->isNumeric($searchTerms[$i + 1]) && $this->dictionary->getUnit($searchTerms[$i + 2])) {
            $unit = $this->dictionary->getUnit($searchTerms[$i + 2]);
            $duration = $this->toNumeric($searchTerms[$i + 1]);
            array_push($toRemove, $searchTerms[$i + 2]);
            array_push($toRemove, $searchTerms[$i + 1]);
        }
        /*
         * "numeric" <last> "(year|day|month)"
         */
        else if ($i - 1 >= 0 && $i + 1 < $l && $this->isNumeric($searchTerms[$i - 1]) && $this->dictionary->getUnit($searchTerms[$i + 1])) {
            $unit = $this->dictionary->getUnit($searchTerms[$i + 1]);
            $duration = $this->toNumeric($searchTerms[$i - 1]);
            array_push($toRemove, $searchTerms[$i - 1]);
            array_push($toRemove, $searchTerms[$i + 1]);
        }
        /*
         * "(year|day|month)" <last>
         */
        else if ($i - 1 >= 0 && $this->dictionary->getUnit($searchTerms[$i - 1])) {
            $unit = $this->dictionary->getUnit($searchTerms[$i - 1]);
            array_push($toRemove, $searchTerms[$i - 1]);
        }
        /*
         * <last> "(year|day|month)"
         */
        else if ($i + 1 < $l && $this->dictionary->getUnit($searchTerms[$i + 1])) {
            $unit = $this->dictionary->getUnit($searchTerms[$i + 1]);
            array_push($toRemove, $searchTerms[$i + 1]);
        }

        /*
         * Known duration unit
         * <last> excludes the current day/month/year
         */
        if ($unit === 'days' || $unit === 'months' || $unit === 'years') {
            $year = date('Y', strtotime(date('Y-m-d') . ' - 1 ' . $unit));
            $pYear = date('Y', strtotime(date('Y-m-d') . ' - ' . $duration . $unit));
            $month = date('m', strtotime(date('Y-m-d') . ' - 1 ' . $unit));
            $pMonth = date('m', strtotime(date('Y-m-d') . ' - ' . $duration . $unit));
            $day = date('d', strtotime(date('Y-m-d') . ' - 1 ' . $unit));
            $pDay = date('d', strtotime(date('Y-m-d') . ' - ' . $duration . $unit));

            if ($unit === 'years') {
                $params['time:start'] = $pYear . '-01-01' . 'T00:00:00';
                $params['time:end'] = $year . '-12-31' . 'T23:59:59';
            } else if ($unit === 'months') {
                $params['time:start'] = $pYear . '-' . $pMonth . '-01' . 'T00:00:00';
                $params['time:end'] = $year . '-' . $month . '-' . cal_days_in_month(CAL_GREGORIAN, intval($month), intval($year)) . 'T23:59:59';
            } else if ($unit === 'days') {
                $params['time:start'] = $pYear . '-' . $pMonth . '-' . $pDay . 'T00:00:00';
                $params['time:end'] = $year . '-' . $month . '-' . $day . 'T23:59:59';
            }

        }
    }
    
    /**
     * Process modifiers 'lesser' or 'greater'
     * 
     *      "quantity" <lesser> (than) "numeric" "unit"
     *      "quantity" <greater> (than) "numeric" "unit"
     *      "quantity" <equal> (to) "numeric" "unit"
     *      <lesser> (than) "numeric" "unit" (of) "quantity"
     *      <greater> (than) "numeric" "unit" (of) "quantity"
     *      <equal> (to) "numeric" "unit" (of) "quantity"
     * 
     * @param array $searchTerms
     * @param array $params
     * @param array $toRemove
     * @param integer $i position of word in the list
     * @param integer $l number of words of the list
     */
    private final function processModifierEqualLesserOrGreater($modifier, &$searchTerms, &$params, &$toRemove, $i, $l) {
    
        $c = -1;

        /*
         * <...> "numeric" "unit"
         */
        if ($i + 2 < $l && $this->isNumeric($searchTerms[$i + 1]) && $this->dictionary->getUnit($searchTerms[$i + 2])) {
            $c = 1;
        }
        /*
         * <...> than "numeric" "unit"
         */
        else if ($i + 3 < $l && $this->isNumeric($searchTerms[$i + 2]) && $this->dictionary->getUnit($searchTerms[$i + 3])) {
            $c = 2;
        }
        
        if ($c !== -1) {

            $unit = $this->normalizedUnit($this->dictionary->getUnit($searchTerms[$i + $c + 1]));
            $searchFilter = null;
            
            /*
             * Get search filter associated to quantity
             */
            /*
             * "quantity" <...> "numeric" "unit"
             */
            if ($i - 1 >= 0 && $this->dictionary->getQuantity($searchTerms[$i - 1])) {
                $searchFilter = $this->getSearchFilter($this->dictionary->getQuantity($searchTerms[$i - 1]));
                array_push($toRemove, $searchTerms[$i - 1]);
            }
            /*
             * <...> "numeric" "unit" "quantity" 
             */
            else if ($i + 2 + $c < $l && $this->dictionary->getQuantity($searchTerms[$i + 2 + $c])) {
                $searchFilter = $this->getSearchFilter($this->dictionary->getQuantity($searchTerms[$i + 2 + $c]));
                array_push($toRemove, $searchTerms[$i + 2 + $c]);
            }
            /*
             * <...> "numeric" "unit" of "quantity" 
             */
            else if ($i + 3 + $c < $l && $this->dictionary->getModifier($searchTerms[$i + 2 + $c]) && $this->dictionary->getQuantity($searchTerms[$i + 3 + $c])) {
                $searchFilter = $this->getSearchFilter($this->dictionary->getQuantity($searchTerms[$i + 3 + $c]));
                array_push($toRemove, $searchTerms[$i + 2 + $c]);
                array_push($toRemove, $searchTerms[$i + 3 + $c]);
            }
            if ($searchFilter) {

                /*
                 * Search filter associated unit should be coherent with user request
                 */
                if ($unit && ($unit['unit'] === $searchFilter['unit'])) {

                    $min = $unit['unit'] === '%' ? 0 : -1000000000;
                    $max = $unit['unit'] === '%' ? 100 : 1000000000;
                    if ($modifier === 'lesser') {
                        $params[$searchFilter['key']] = '[' . $min . ',' . (floatval($this->toNumeric($searchTerms[$i + $c])) * $unit['factor']) . '[';
                    }
                    else if ($modifier === 'greater') {
                        $params[$searchFilter['key']] = ']' . (floatval($this->toNumeric($searchTerms[$i + $c])) * $unit['factor']) . ',' . $max . '[';
                    }
                    else {
                        $params[$searchFilter['key']] = (floatval($this->toNumeric($searchTerms[$i + $c])) * $unit['factor']);
                    }

                }

            }
                
            /*
             * Date
             */
            if ($unit['unit'] === 'days' || $unit['unit'] === 'months' || $unit['unit'] === 'years') {
                if ($modifier === 'lesser') {
                    $params['time:start'] = date('Y-m-d', strtotime(date('Y-m-d') . ' - ' . $this->toNumeric($searchTerms[$i + $c]) . ' ' . $unit['unit']));
                }
                else {
                    $params['time:end'] = date('Y-m-d', strtotime(date('Y-m-d') . ' - ' . $this->toNumeric($searchTerms[$i + $c]) . ' ' . $unit['unit']));
                }
            }
            if ($c === 2) {
                array_push($toRemove, $searchTerms[$i + 1]);
            }
            array_push($toRemove, $searchTerms[$i + $c]);
            array_push($toRemove, $searchTerms[$i + $c + 1]);
        }
    }
    
    /**
     * Process modifier 'ago'
     *       
     *      "numeric" "units" <ago>
     * 
     * @param array $searchTerms
     * @param array $params
     * @param array $toRemove
     * @param integer $i position of word in the list
     * @param integer $l number of words of the list
     */
    private final function processModifierAgo(&$searchTerms, &$params, &$toRemove, $i) {
    
        if ($i - 2 >= 0 && $this->isNumeric($searchTerms[$i - 2]) && $this->dictionary->getUnit($searchTerms[$i - 1])) {
            $unit = $this->dictionary->getUnit($searchTerms[$i - 1]);
            $duration = $this->toNumeric($searchTerms[$i - 2]);
            array_push($toRemove, $searchTerms[$i - 1]);
            array_push($toRemove, $searchTerms[$i - 2]);

            /*
             * Known duration unit
             */
            if ($unit === 'days' || $unit === 'months' || $unit === 'years') {
                $params['time:start'] = date('Y-m-d', strtotime(date('Y-m-d') . ' - ' . $duration . $unit)) . 'T00:00:00';
                $params['time:end'] = date('Y-m-d', strtotime(date('Y-m-d') . ' - ' . $duration . $unit)) . 'T23:59:59';
            }
        }
    }
    
    /*
     * Remove $toRemove elements from $input array
     * 
     * @param {array} $input
     * @param {array} $toRemove
     */
    private final function stripArray($input, $toRemove) {

        $output = array();

        for ($i = 0, $l = count($input); $i < $l; $i++) {
            $add = true;
            for ($j = 0, $k = count($toRemove); $j < $k; $j++) {
                if ($input[$i] === $toRemove[$j]) {
                    $add = false;
                    break;
                }
            }
            if ($add) {
                array_push($output, $input[$i]);
            }
        }

        return $output;
    }

}