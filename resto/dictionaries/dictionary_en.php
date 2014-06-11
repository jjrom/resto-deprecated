<?php
/* 
 * Dictionary structure :
 * 
 *      array(
 *          excluded => array(),
 *          modifiers => array(),
 *          units => array(),
 *          months => array(),
 *          numbers => array(),
 *          keywords => array(),
 *          keywordsTranslation => array(),
 *          restoTranslation => array()
 *      )
 * 
 * IMPORTANT : all keys and values must be in 7bits lower case
 * (i.e. no accents) except for values from 'keywordsTranslation' and 'restoTranslation arrays
 * 
 */ 
return array(
    /*
     * List of words in the query that are
     * considered as 'noise' for the query analysis
     * and thus excluded from the analysis
     */
    'excluded' => array(
        'than',
        'over',
        'acquired',
        'image',
        'images',
        'cover',
        'area',
        'zone'
    ),
    /*
     * Modifiers
     * 
     * Valid modifiers values are
     *  - with
     *  - witout
     *  - less
     *  - greater
     *  - and
     * 
     * For each entry 
     *   - the key (left side) is what the user types 
     *   - the value (right side) is the equivalent modifier
     */
    'modifiers' => array(
        'ago' => 'ago',
        'before' => 'before',
        'after' => 'after',
        'between' => 'between',
        'containing' => 'with',
        'with' => 'with',
        'without' => 'without',
        'no' => 'without',
        'less' => 'lesser',
        'lesser' => 'lesser',
        'lower' => 'lesser',
        'greater' => 'greater',
        'equal' => 'equal',
        'and' => 'and',
        'since' => 'since',
        'last' => 'last',
        'today' => 'today',
        'yesterday' => 'yesterday'
    ),
    /*
     * Units
     * 
     * For each entry 
     *   - the key (left side) is what the user types
     *   - the value (right side) is the equivalent unit
     * 
     */
    'units' => array(
        'm' => 'm',
        'meter' => 'm',
        'meters' => 'm',
        'km' => 'km',
        'kilometer' => 'km',
        'kilometers' => 'km',
        'percent' => '%',
        'percents' => '%',
        'percentage' => '%',
        '%' => '%',
        'day' => 'days',
        'days' => 'days',
        'month' => 'months',
        'months' => 'months',
        'year' => 'years',
        'years' => 'years'
    ),
    /*
     * Numbers
     * 
     * For each entry 
     *   - the key (left side) is the textual number
     *   - the value (right side) is number
     * 
     */
    'numbers' => array(
        'one' => '1',
        'two' => '2',
        'three' => '3',
        'four' => '4',
        'five' => '5',
        'six' => '6',
        'seven' => '7',
        'eight' => '8',
        'nine' => '9',
        'ten' => '10',
        'hundred' => '100',
        'thousand' => '1000'
    ),
    /*
     * Months
     * 
     * For each entry 
     *   - the key (left side) is the month
     *   - the value (right side) is the equivalent
     *     month number (from 01 to 12)
     * 
     */
    'months' => array(
        'january' => '01',
        'february' => '02',
        'march' => '03',
        'april' => '04',
        'may' => '05',
        'june' => '06',
        'july' => '07',
        'august' => '08',
        'september' => '09',
        'october' => '10',
        'november' => '11',
        'december' => '12'
    ),
    /*
     * Quantities
     * 
     * Quantity is the entity on which apply a comparaison modifier
     * 
     *  e.g.
     *      "resolution   lesser    than 10  meters"
     *       <quantity> <modifier>           <units>
     * 
     */
    'quantities' => array(
        'resolution' => 'resolution',
        'orbit' => 'orbit',
        'cloud' => 'cloud',
        'clouds' => 'cloud',
        'snow' => 'snow'
    ),
    /*
     * Keywords
     * 
     * For each entry 
     *   - first level of array is the keyword type
     *   - second level of array
     *      - the key (left side) is what the user types
     *      - the value (right side) is the equivalent value
     *        stored within the database (keywords column)
     * 
     */
    'keywords' => array(
        // itag -x
        'continent' => array(
            'europe' => 'europe',
            'oceania' => 'oceania',
            'asia' => 'asia',
            'seven seas' => 'seven seas (open ocean)',
            'africa' => 'africa',
            'antarctica' => 'antarctica',
            'north america' => 'north america',
            'south america' => 'south america'
        ),
        // itag -c
        'country' => array(
            'afghanistan' => 'afghanistan',
            'albania' => 'albania',
            'algeria' => 'algeria',
            'angola' => 'angola',
            'antarctica' => 'antarctica',
            'argentina' => 'argentina',
            'armenia' => 'armenia',
            'australia' => 'australia',
            'austria' => 'austria',
            'azerbaijan' => 'azerbaijan',
            'bahamas' => 'bahamas',
            'bangladesh' => 'bangladesh',
            'belarus' => 'belarus',
            'belgium' => 'belgium',
            'belize' => 'belize',
            'benin' => 'benin',
            'bhutan' => 'bhutan',
            'bolivia' => 'bolivia',
            'bosnia and herzegovina' => 'bosnia and herzegovina',
            'botswana' => 'botswana',
            'brazil' => 'brazil',
            'brunei' => 'brunei',
            'bulgaria' => 'bulgaria',
            'burkina faso' => 'burkina faso',
            'burundi' => 'burundi',
            'cambodia' => 'cambodia',
            'cameroon' => 'cameroon',
            'canada' => 'canada',
            'central african republic' => 'central african republic',
            'chad' => 'chad',
            'chile' => 'chile',
            'china' => 'china',
            'colombia' => 'colombia',
            'congo' => 'congo',
            'congo' => 'congo',
            'costa rica' => 'costa rica',
            'croatia' => 'croatia',
            'cuba' => 'cuba',
            'cyprus' => 'cyprus',
            'czech republic' => 'czech republic',
            'denmark' => 'denmark',
            'djibouti' => 'djibouti',
            'dominican republic' => 'dominican republic',
            'ecuador' => 'ecuador',
            'egypt' => 'egypt',
            'el salvador' => 'el salvador',
            'equatorial guinea' => 'equatorial guinea',
            'eritrea' => 'eritrea',
            'estonia' => 'estonia',
            'ethiopia' => 'ethiopia',
            'falkland islands' => 'falkland islands',
            'fiji' => 'fiji',
            'finland' => 'finland',
            'france' => 'france',
            'french southern and antarctic lands' => 'french southern and antarctic lands',
            'gabon' => 'gabon',
            'gambia' => 'gambia',
            'georgia' => 'georgia',
            'germany' => 'germany',
            'ghana' => 'ghana',
            'greece' => 'greece',
            'greenland' => 'greenland',
            'guatemala' => 'guatemala',
            'guinea' => 'guinea',
            'guinea-bissau' => 'guinea-bissau',
            'guyana' => 'guyana',
            'haiti' => 'haiti',
            'honduras' => 'honduras',
            'hungary' => 'hungary',
            'iceland' => 'iceland',
            'india' => 'india',
            'indonesia' => 'indonesia',
            'iran' => 'iran',
            'iraq' => 'iraq',
            'ireland' => 'ireland',
            'israel' => 'israel',
            'italy' => 'italy',
            'ivory coast' => 'ivory coast',
            'jamaica' => 'jamaica',
            'japan' => 'japan',
            'jordan' => 'jordan',
            'kazakhstan' => 'kazakhstan',
            'kenya' => 'kenya',
            'korea' => 'korea',
            'kosovo' => 'kosovo',
            'kuwait' => 'kuwait',
            'kyrgyzstan' => 'kyrgyzstan',
            'laos' => 'laos',
            'latvia' => 'latvia',
            'lebanon' => 'lebanon',
            'lesotho' => 'lesotho',
            'liberia' => 'liberia',
            'libya' => 'libya',
            'lithuania' => 'lithuania',
            'luxembourg' => 'luxembourg',
            'macedonia' => 'macedonia',
            'madagascar' => 'madagascar',
            'malawi' => 'malawi',
            'malaysia' => 'malaysia',
            'mali' => 'mali',
            'mauritania' => 'mauritania',
            'mexico' => 'mexico',
            'moldova' => 'moldova',
            'mongolia' => 'mongolia',
            'montenegro' => 'montenegro',
            'morocco' => 'morocco',
            'mozambique' => 'mozambique',
            'myanmar' => 'myanmar',
            'namibia' => 'namibia',
            'nepal' => 'nepal',
            'netherlands' => 'netherlands',
            'new caledonia' => 'new caledonia',
            'new zealand' => 'new zealand',
            'nicaragua' => 'nicaragua',
            'niger' => 'niger',
            'nigeria' => 'nigeria',
            'north korea' => 'north korea',
            'northern cyprus' => 'northern cyprus',
            'norway' => 'norway',
            'oman' => 'oman',
            'pakistan' => 'pakistan',
            'palestine' => 'palestine',
            'panama' => 'panama',
            'papua new guinea' => 'papua new guinea',
            'paraguay' => 'paraguay',
            'peru' => 'peru',
            'philippines' => 'philippines',
            'poland' => 'poland',
            'portugal' => 'portugal',
            'puerto rico' => 'puerto rico',
            'qatar' => 'qatar',
            'romania' => 'romania',
            'russia' => 'russia',
            'rwanda' => 'rwanda',
            'saudi arabia' => 'saudi arabia',
            'senegal' => 'senegal',
            'serbia' => 'serbia',
            'sierra leone' => 'sierra leone',
            'slovakia' => 'slovakia',
            'slovenia' => 'slovenia',
            'solomon islands' => 'solomon islands',
            'somalia' => 'somalia',
            'somaliland' => 'somaliland',
            'south africa' => 'south africa',
            'south sudan' => 'south sudan',
            'spain' => 'spain',
            'sri lanka' => 'sri lanka',
            'sudan' => 'sudan',
            'suriname' => 'suriname',
            'swaziland' => 'swaziland',
            'sweden' => 'sweden',
            'switzerland' => 'switzerland',
            'syria' => 'syria',
            'taiwan' => 'taiwan',
            'tajikistan' => 'tajikistan',
            'tanzania' => 'tanzania',
            'thailand' => 'thailand',
            'timor-leste' => 'timor-leste',
            'togo' => 'togo',
            'trinidad and tobago' => 'trinidad and tobago',
            'tunisia' => 'tunisia',
            'turkey' => 'turkey',
            'turkmenistan' => 'turkmenistan',
            'uganda' => 'uganda',
            'ukraine' => 'ukraine',
            'united arab emirates' => 'united arab emirates',
            'united kingdom' => 'united kingdom',
            'uk' => 'united kingdom',
            'united states' => 'united states',
            'usa' => 'united states',
            'us' => 'united states',
            'uruguay' => 'uruguay',
            'uzbekistan' => 'uzbekistan',
            'vanuatu' => 'vanuatu',
            'venezuela' => 'venezuela',
            'vietnam' => 'vietnam',
            'western sahara' => 'western sahara',
            'yemen' => 'yemen',
            'zambia' => 'zambia',
            'zimbabwe' => 'zimbabwe'
        ),
        // itag -l
        'landuse' => array(
            'urban' => 'urban',
            'town' => 'urban',
            'city' => 'urban',
            'artificial' => 'urban',
            'cultivated' => 'cultivated',
            'forest' => 'forest',
            'forests' => 'forest',
            'herbaceous' => 'herbaceous',
            'desert' => 'desert',
            'snow' => 'snow',
            'flooded' => 'flooded',
            'water' => 'water'
        ),
        // Free keywords
        'other' => array()
    ),
    /*
     * Keywords Translation array
     */
    'translation' => array(
        '_selfCollectionLink' => 'self',
        '_alternateCollectionLink' => 'alternate',
        '_firstCollectionLink' => 'first',
        '_lastCollectionLink' => 'last',
        '_nextCollectionLink' => 'next',
        '_previousCollectionLink' => 'previous',
        '_selfFeatureLink' => 'self',
        '_about' => 'About',
        '_close' => 'Close',
        '_acquiredOn' => 'acquired on <b>{a:1}</b>',
        '_placeHolder' => 'Search - ex. {a:1}',
        '_query' => 'Search filters - {a:1}',
        '_notUnderstood' => 'Request not understood - no search filters applied',
        '_noResult' => 'Found no result - try another request !',
        '_oneResult' => '1 result',
        '_multipleResult' => '{a:1} results', 
        '_firstPage' => '<<',
        '_previousPage' => 'Previous',
        '_nextPage' => 'Next',
        '_lastPage' => '>>',
        '_pagination' => '{a:1} to {a:2}',
        '_identifier' => 'Identifier',
        '_resolution' => 'Resolution',
        '_startDate' => 'Start of acquisition',
        '_completionDate' => 'End of acquisition',
        '_viewMetadata' => 'View description of product {a:1}',
        '_viewMapshup' => 'View on map',
        '_viewMapshupFullResolution' => 'View on map',
        '_download' => 'Download',
        '_keywords' => 'Keywords',
        '_atomLink' => 'ATOM link for {a:1}',
        '_htmlLink' => 'HTML link for {a:1}',
        '_jsonLink' => 'GeoJSON link for {a:1}',
        '_thisResourceContainsLanduse' => 'Images that contain {a:2}',
        '_thisResourceIsLocated' => 'Images located in {a:1}',
        '_thisResourceContainsCity' => 'Images around {a:1}',
        '_thisResourceWasAcquiredBy' => 'Images acquired by {a:1} satellite',
        '_landUse' => 'Land cover',
        '_location' => 'Location',
        '_platform' => 'Satellite',
        '_tags' => 'Tags',
        '_other' => 'Other',
        // landuse
        'urban' => 'Urban area',
        'cultivated' => 'Cultivated area',
        'flooded' => 'Flooded area',
        'herbaceous' => 'Herbaceous area',
        'desert' => 'Desert',
        'water' => 'Water',
        'forest' => 'Forest',
        'THR' => 'Images with resolution lower than 2.5 m',
        'HR' => 'Images with resolution between 2.5 and 30 m',
        'MR' => 'Images with resolution between 30 and 500 m',
        'LR' => 'Images with resolution greater than 500 m',
        '_home' => 'Home',
        '_viewAtomFeed' => 'View Atom feed for this search result',
        '_shareOn' => 'Share on {a:1}',
        '_zoom' => 'Zoom map',
        '_unZoom' => 'Unzoom map',
        '_centerOnLayer' => 'Center view on search result',
        '_globalMapView' => 'Center on whole earth',
        '_showOnMap' => 'View on map',
        '_addCollection' => 'Add a collection',
        '_update' => 'Update',
        '_deactivate' => 'Deactivate',
        '_remove' => 'Remove',
        '_login' => 'Connect',
        '_logout' => 'Disconnect',
        '_dropCollection' => 'Drop a collection description file',
        '_email' => 'Email',
        '_password' => 'Password',
        '_createAccount' => 'Create an account',
        '_givenName' => 'Given name',
        '_lastName' => 'Last name',
        '_userName' => 'User name',
        '_retypePassword' => 'Retype password',
        '_back' => 'Back',
        '_signWithOauth' => 'Sign in with {a:1} account',
        '_addResource' => 'Add a resource',
        '_dropResource' => 'Drop a resource metadata file',
        '_resultFor' => 'Search results for &#34;{a:1}&#34;',
        '_resourceSummary' => '{a:1} image ({a:2} m) acquired on {a:3}'
    )
    
);