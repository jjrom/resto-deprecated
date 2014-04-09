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
 * Dictionary class
 * 
 * This class follows the Singleton pattern. It cannot be instantiated with new
 * 
 * See dictionary files under $RESTO_HOME/dictionaries
 */
class Dictionary {
    
    /*
     * Dictionary Structure
     * 
     *      excluded => array(),
     *      modifiers => array(),
     *      units => array(),
     *      months => array(),
     *      numbers => array(),
     *      quantities => array()
     *      instruments => array(),
     *      keywords => array(),
     *      translation => array()
     */
    private $dictionary = array();
    
    /*
     * Reference to the dictionary language
     */
    public $language;

    /**
     * Constructor
     * 
     * @param string $language
     * @param array $dictionary
     * @throws Exception
     */
    public function __construct($language, $dictionary = array()) {
        
        /*
         * Set dictionary language
         */
        $this->language = $language;
        
        /*
         * Retrieve dictionary in input language if exists
         * otherwise switch to english (default)
         */
        $dictionaryFile = realpath(dirname(__FILE__)) . '/../dictionaries/dictionary_' . $language . '.php';
        if (!file_exists($dictionaryFile)) {
            $this->language = 'en';
            $dictionaryFile = realpath(dirname(__FILE__)) . '/../dictionaries/dictionary_en.php';
            if (!file_exists($dictionaryFile)) {
                throw new Exception('Missing mandatory dictionary file', 500);
            }
        }
        
        $this->dictionary = require $dictionaryFile;
        
        /*
         * Get common properties (plaforms and instruments)
         */
        if (file_exists(realpath(dirname(__FILE__)) . '/../dictionaries/common.php')) {
            $tmp = require realpath(dirname(__FILE__)) . '/../dictionaries/common.php';
            $this->dictionary['platforms'] = isset($tmp['platforms']) ? $tmp['platforms'] : array();
            $this->dictionary['instruments'] = isset($tmp['instruments']) ? $tmp['instruments'] : array();
        }

        /*
         * $collectionDictionary variable should contain an array of dictionaries
         * identified by language (i.e. 'en', 'fr', etc.) - see config/collections/Charter.config.php
         * file for a valid example
         */
        $this->add($dictionary);
        
    }
    
    /**
     * Add local dictionary to Dictionary
     * 
     * Local dictionary example :
     * 
     *  'dictionary_en' => array(
     *      'keywords' => array(
     *          'other' => array(
     *              'cyclone' => 'cyclone',
     *              'huricane' => 'cyclone'
     *          )
     *      ),
     *      'translation' => array(
     *         'oil_spill' => 'Oil Spill',
     *         'volcanic_eruption' => 'Volcanic Eruption'
     *      )
     *  )
     * 
     * @param array $dictionary
     */
    final public function add($dictionary = array()) {
        
        $a = 'dictionary_' . $this->language;
        
        if (is_array($dictionary) && isset($dictionary[$a])) {
            
            /*
             * Update "quantities"
             */
            if (is_array($dictionary[$a]['quantities'])) {
                foreach ($dictionary[$a]['quantities'] as $keyword => $value) {
                    $this->dictionary['quantities'][$keyword] = $value;
                }
            }
            /*
             * Update "keywords"
             */
            if (is_array($dictionary[$a]['keywords'])) {
                foreach (array_keys($dictionary[$a]['keywords']) as $type) {
                    foreach ($dictionary[$a]['keywords'][$type] as $keyword => $value) {
                        $this->dictionary['keywords'][$type][$keyword] = $value;
                    }
                }
            }
            /*
             * Update "translation"
             */
            if (is_array($dictionary[$a]['translation'])) {
                foreach ($dictionary[$a]['translation'] as $keyword => $value) {
                    $this->dictionary['translation'][$keyword] = $value;
                }
            }
            
        }
        
    }
    
    /**
     * Return translation array
     */
    final public function getTranslation() {
        return $this->dictionary['translation'];
    }
    
    /**
     * Return $property entry in dictionary identified by $name
     * 
     * @param string $property
     * @param string $name
     */
    final public function get($property, $name) {
        if (!is_array($this->dictionary[$property])) {
            return null;
        }
        return $this->dictionary[$property][$name];
    }
    
    /**
     * Return modifier entry in dictionary identified by $name
     * 
     * @param string $name
     */
    final public function getModifier($name) {
        return $this->get('modifiers', $name);
    }
    
    /**
     * Return unit entry in dictionary identified by $name
     * 
     * @param string $name
     */
    final public function getUnit($name) {
        return $this->get('units', $name);
    }
    
    /**
     * Return month entry in dictionary identified by $name
     * 
     * @param string $name
     */
    final public function getMonth($name) {
        return $this->get('months', $name);
    }
    
    /**
     * Return number entry in dictionary identified by $name
     * 
     * @param string $name
     */
    final public function getNumber($name) {
        return $this->get('numbers', $name);
    }
    
    /**
     * Return quantity entry in dictionary identified by $name
     * 
     * @param string $name
     */
    final public function getQuantity($name) {
        return $this->get('quantities', $name);
    }
    
    /**
     * Return instrument entry in dictionary identified by $name
     * 
     * @param string $name
     */
    final public function getInstrument($name) {
        return $this->get('instruments', $name);
    }
 
    /**
     * Return platform entry in dictionary identified by $name
     * 
     * @param string $name
     */
    final public function getPlatform($name) {
        return $this->get('platforms', $name);
    }
    
    /**
     * Return keyword entry in dictionary identified by $name
     * 
     * @param string $name
     * @return array ('keywords', 'type')
     */
    final public function getKeyword($name) {
        
        if (!is_array($this->dictionary['keywords'])) {
            return null;
        }
        
        /*
         * keywords entry is an array of array
         */
        foreach(array_keys($this->dictionary['keywords']) as $type) {
            if ($this->dictionary['keywords'][$type][$name]) {
                return array('keyword' => $this->dictionary['keywords'][$type][$name], 'type' => $type); 
            }
        }
        
        return null;
    }
    
    /**
     * Return all keywords entry in dictionary
     * 
     */
    final public function getKeywords() {
        return $this->dictionary['keywords'];
    }
    
    /**
     * Return true if $name value is present in
     * keywords array
     * 
     * @param string $name
     */
    final public function isKeywordsValue($value) {
        return in_array($value, $this->dictionary['keywords']);
    }
    
    /**
     * Return true if $name is an excluded word
     * 
     * @param string $name
     */
    final public function isExcluded($name) {
        if (!is_array($this->dictionary['excluded'])) {
            return false;
        }
        return in_array($name, $this->dictionary['excluded']);
    }
       
    /**
     * Return $keyword translation
     * 
     * Example :
     *      
     *      translation: array(
     *          'presentation' => 'Hello. My name is {a:1}. I live in {a:2}'
     *      }
     *  
     *      Call to dictionary->translate('presentation', 'Jérôme', 'Toulouse');
     *      Will return
     * 
     *           'Hello. My name is Jérôme. I live in Toulouse
     * 
     * 
     * @param string $name
     * @param boolean $capitalize // true to capitalize first letter of each word
     *                               if $name is not found in the dictionary
     * @param string any number of optional arguments
     */
    final public function translate($sentence, $capitalize) {
        
        if (!isset($this->dictionary['translation'])) {
            return $capitalize ? ucwords($sentence) : $sentence;
        }
        
        /*
         * Replace additional arguments
         */
        if (false !== strpos($this->dictionary['translation'][$sentence], '{a:')) {
            $replace = array();
            $args = func_get_args();
            for ($i = 1, $max = count($args); $i < $max; $i++) {
                $replace['{a:' . $i . '}'] = $args[$i];
            }

            return strtr($this->dictionary['translation'][$sentence], $replace);
        }
        
        return isset($this->dictionary['translation'][$sentence]) ? $this->dictionary['translation'][$sentence] : $capitalize ? ucwords($sentence) : $sentence;
    }

}
