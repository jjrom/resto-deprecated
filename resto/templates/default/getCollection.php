<!DOCTYPE html>
<?php
    $collectionUrl = $this->request['restoUrl'] . $this->request['collection'] . '/';
    $mapshup = $this->description['mapshup'];
?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><title><?php echo strip_tags($this->R->getTitle()); ?></title>
        <link rel="shortcut icon" href="<?php echo $this->request['restoUrl'] ?>/favicon.ico" />
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/css/default/style.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/js/lib/jquery.fancybox.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/js/lib/leaflet.css" type="text/css"/>
        <link rel="search" type="application/opensearchdescription+xml" href="<?php echo $collectionUrl ?>_describe" hreflang="<?php echo $this->request['language'] ?>" title="<?php echo $this->description['name']; ?>" />
    </head>
    <body>
        <div id="container">

            <div id="menu" class="white">
                <div class="left">
                    <a href="<?php echo $this->request['restoUrl'] ?>"><?php echo $this->R->getTitle(); ?></a>
                </div>
                <div class="right uppercase">
                    <ul>
                        <!--<li id="_connect">Connect</li>-->
                            <li id="_about"><?php echo $this->description['dictionary']->translate('_about');?></li>
                            <!--<li id="_help">Help</li>-->
                    </ul>
                </div>
            </div>

            <div class="header left medium">
                <h2><?php echo $this->description['os']['ShortName']; ?></h2>
                <div class="description">
                    <?php echo $this->description['os']['Description']; ?>
                </div>
                <div class="search">
                    <form id="searchform" action="<?php echo $collectionUrl ?>">
                        <input type="hidden" name="format" value="html" />
                        <?php
                        if ($this->request['language']) {
                            echo '<input type="hidden" name="language" value="' . $this->request['language'] . '" />';
                        }
                        ?>
                        <input type="text" id="search" name="<?php echo $this->description['searchFiltersDescription']['searchTerms']['osKey'] ?>" value="<?php echo str_replace('"', '&quot;', stripslashes($this->request['params'][$this->description['searchFiltersDescription']['searchTerms']['osKey']])); ?>" placeholder="<?php echo $this->description['dictionary']->translate('_placeHolder', $this->description['os']['Query']);?>"/>
                        <label>
                            <?php
                                for ($i = 0, $l = count($this->description['acceptedLangs']); $i < $l; $i++) {
                                    echo '<a class="item" title="' . $this->description['dictionary']->translate($this->description['acceptedLangs'][$i] === $this->request['language'] ? '_inLang' : '_switchLang', $this->description['dictionary']->translate('_' . $this->description['acceptedLangs'][$i])) . '" id="language-' . $this->description['acceptedLangs'][$i] . '" href="' . updateURL($collectionUrl, array($this->description['searchFiltersDescription']['searchTerms']['osKey'] => stripslashes($this->request['params'][$this->description['searchFiltersDescription']['searchTerms']['osKey']]), $this->description['searchFiltersDescription']['language']['osKey'] => $this->description['acceptedLangs'][$i])) . '">' . $this->description['acceptedLangs'][$i] . '</a>';
                                }
                            ?>
                        </label>
                    </form>
                </div>
                <?php
                if (isset($this->response['query']) && isset($this->response['query']['real'])) {
                    $items = array();
                    foreach ($this->response['query']['real'] as $key => $value) {
                        if ($value) {
                            array_push($items, '<b>' . $key . '</b> ' . $value);
                        }
                    }
                    if (count($items) > 0) {
                        echo '<div class="query">' . $this->description['dictionary']->translate('_query', join(' | ', $items)) . '</div>';
                    }
                    else {
                        echo '<div class="query"><span class="warning">' . $this->description['dictionary']->translate('_notUnderstood') . '</span></div>';
                    }
                }
                else if (isset($this->response['missing'])) {
                    //echo '<div class="query"><span class="warning">Missing mandatory search filters - ' . join(", ", $this->response['missing']) . '</span></div>';
                }
                ?>
                
            </div>

            <div class="result left">
                <div class="synthesis">
                <?php
                
                    $synthesis = "";
                    
                    if (isset($this->response['missing'])) {
                    // Do nothing
                    }
                    else if ($this->response['totalResults'] === 0) {
                        echo $this->description['dictionary']->translate('_noResult');
                    }
                    else {
                        $first = "";
                        $previous = "";
                        $next = "";
                        $last = "";
                        if (isset($this->response['links']['first'])) {
                            $first = ' <a href="' . updateURL($this->response['links']['first'], array('format' => 'html')) . '">' . $this->description['dictionary']->translate('_firstPage') . '</a> ';
                        }
                        if (isset($this->response['links']['previous'])) {
                            $previous = ' <a href="' . updateURL($this->response['links']['previous'], array('format' => 'html')) . '">' . $this->description['dictionary']->translate('_previousPage') . '</a> ';
                        }
                        if (isset($this->response['links']['next'])) {
                            $next = ' <a href="' . updateURL($this->response['links']['next'], array('format' => 'html')) . '">' . $this->description['dictionary']->translate('_nextPage') . '</a> ';
                        }
                        if (isset($this->response['links']['last'])) {
                            $last = ' <a href="' . updateURL($this->response['links']['last'], array('format' => 'html')) . '">' . $this->description['dictionary']->translate('_lastPage') . '</a>';
                        }
                        if ($mapshup && $mapshup['url']) {
                            $layer = array(
                                'title' => stripslashes($this->request['params'][$this->description['searchFiltersDescription']['searchTerms']['osKey']]),
                                'type' => 'GeoJSON',
                                'clusterized' => false,
                                // Hint - remove \ i.e. %5C
                                'url' => str_replace('%5C', '', updateUrl($this->response['links']['self'], array('format' => 'json', $this->description['searchFiltersDescription']['language']['osKey'] => $this->request['language']))),
                                'zoomOnNew' => true,
                                'unremovable' => true
                            );
                            $synthesis .= '<a class="hilite red displayMap" addLayer="' . rawurlencode(json_encode(array('command' => 'addLayer', 'options' => $layer))) . '" href="' . $mapshup['url'] . '">' . $this->description['dictionary']->translate('_viewMapshup') . '</a> | ';
                        }
                        
                        if ($this->response['totalResults'] == 1) {
                            $synthesis .= $this->description['dictionary']->translate('_oneResult', $this->response['totalResults']);
                        }
                        else if ($this->response['totalResults'] > 1){
                            $synthesis .= $this->description['dictionary']->translate('_multipleResult', $this->response['totalResults']);
                        }
                        //$synthesis .= '&nbsp;(<a href="' . updateURL($this->response['links']['self'], array('format' => 'atom')) . '">Atom</a>|<a href="' . updateUrl($this->response['links']['self'], array('format' => 'json')) . '">GeoJSON</a>)';
                        $synthesis .= isset($this->response['startIndex']) ? '&nbsp;|&nbsp;' . $first . $previous . $this->description['dictionary']->translate('_pagination', $this->response['startIndex'], $this->response['lastIndex']) . $next . $last : '';
                        
                        echo $synthesis;
                    }
                ?>
                </div>
                <div class="content">
                    <?php
                    for ($i = 0, $l = count($this->response['features']); $i < $l; $i++) {
                        $product = $this->response['features'][$i];
                    ?>
                    <div class="entry" style="clear:both;">
                    <?php
                    $thumbnail = $product['properties']['thumbnail'];
                    $quicklook = $product['properties']['quicklook'];
                    if (!$thumbnail && $quicklook) {
                        $thumbnail = $quicklook;
                        }
                        else if (!$thumbnail && !$quicklook) {
                        $thumbnail = $this->request['restoUrl'] . '/css/default/img/noimage.png';
                    }
                    if (!$quicklook) {
                        $quicklook = $thumbnail;
                    }
                    ?>
                    <span class="thumbnail"><a href="<?php echo $quicklook; ?>" class="quicklook" title="<?php echo $product['properties']['identifier'];?>"><img src="<?php echo $thumbnail; ?>"/></a></span>
                    <span class="map" id="id<?php echo $i;?>" geom='<?php echo json_encode($product['geometry'])?>'></span>
                        <div class="metadata">
                            <p>
                                <?php
                                ?>
                                <b><?php echo $product['properties']['platform']?><?php echo $product['properties']['platform'] && $product['properties']['instrument'] ? '/' : ''; ?><?php echo $product['properties']['instrument']?></b> <?php echo $this->description['dictionary']->translate('_acquiredOn', substr($product['properties']['startDate'], 0, 10)) ?>
                            </p>
                            <p class="tabbed-left small">
                                <b><?php echo $this->description['dictionary']->translate('_identifier'); ?></b> : <span title="<?php echo $product['properties']['identifier']; ?>"><?php echo stripOGCURN($product['properties']['identifier']); ?></span><br/>
                                <?php
                                    if ($product['properties']['resolution']) {
                                        echo '<b>' . $this->description['dictionary']->translate('_resolution') . '</b> : ' . $product['properties']['resolution'] .' m<br/>';
                                    }
                                    if ($product['properties']['startDate']) {
                                        echo '<b>' . $this->description['dictionary']->translate('_startDate') . '</b> : ' . $product['properties']['startDate'] .'<br/>';
                                    }
                                    if ($product['properties']['completionDate']) {
                                        echo '<b>' . $this->description['dictionary']->translate('_completionDate') . '</b> : ' . $product['properties']['completionDate'] .'<br/>';
                                    }
                                ?>
                                <?php echo $this->description['dictionary']->translate('_viewMetadata', '<a href="' . updateURL($product['properties']['self'], array('format' => 'html')) . '">HTML</a> | <a href="' . updateURL($product['properties']['self'], array('format' => 'atom')) . '">ATOM</a> | <a href="' . updateURL($product['properties']['self'], array('format' => 'json')) . '">GeoJSON</a></li>'); ?>
                            </p>
                            <p>
                            <?php if ($product['properties']['services']['download']['url']) { ?>
                                    &nbsp;&nbsp;<a href="<?php echo $product['properties']['services']['download']['url']; ?>" <?php if ($product['properties']['services']['download']['mimeType'] === 'text/html') {echo ' target="_blank"';} ?>><?php echo $this->description['dictionary']->translate('_download');?></a>
                            <?php } ?>
                            <?php
                            if ($product['properties']['services']['browse']['layer']) {
                                if ($mapshup && $mapshup['url']) {
                                    $layer = array(
                                            'title' => $product['properties']['identifier'],
                                            'type' => $product['properties']['services']['browse']['layer']['type'],
                                            'layers' => $product['properties']['services']['browse']['layer']['layers'],
                                            'url' => str_replace('%5C', '', $product['properties']['services']['browse']['layer']['url']),
                                            'zoomOnNew' => true
                                    );
                                    echo '&nbsp;&nbsp;<a class="displayMap" addLayer="' . rawurlencode(json_encode(array('command' => 'addLayer', 'options' => $layer))) . '" href="' . $tmapshup['url'] . '">' . $this->description['dictionary']->translate('_viewMapshupFullResolution') . '</a>';
                                }
                            }
                            ?>    
                            </p>
                        </div>
                        <div class="keywords">
                        <?php
                        if ($product['properties']['keywords']) {
                            $tmp = array(
                                'a' => array(),
                                'b' => array(),
                                'c' => array()
                            );
                            foreach ($product['properties']['keywords'] as $keyword => $value) {
                                $r = array(
                                    'url' => $value['url'],
                                    'type' => strtolower($value['type']),
                                    'keyword' => $keyword
                                );
                                switch ($r['type']) {
                                    case 'date' :
                                    case 'platform' :
                                    case 'instrument' :
                                        array_push($tmp['a'], $r);
                                        break;
                                    case 'continent' :
                                    case 'country' :
                                    case 'city' :
                                        array_push($tmp['b'], $r);
                                        break;
                                    default :
                                        array_push($tmp['c'], $r);
                                        break;
                                }
                            }
                            echo '<div class="keywords">';
                            foreach (array_keys($tmp) as $arr) {
                                if (count($tmp[$arr]) > 0) {
                                    for ($j = 0, $k = count($tmp[$arr]); $j < $k; $j++) {
                                        echo '<a href="' . updateURL($tmp[$arr][$j]['url'], array('format' => 'html')) . '" class="keyword keyword-' . str_replace(' ', '', $tmp[$arr][$j]['type']) . '">' . $tmp[$arr][$j]['keyword'] . '</a> ';
                                    }
                                    
                                }
                            }
                            echo '</div>';
                        }
                        ?>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <div class="synthesis">
                <?php echo $synthesis; ?>
                </div>
                <?php if (count($this->response['features']) > 0) {?>
                <div id="footer">
                    <div class="center">
                        Powered by <a href="http://github.com/jjrom/resto">RESTo</a> -  Maps &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Tiles courtesy of <a href="http://hot.openstreetmap.org/" target="_blank">Humanitarian OpenStreetMap Team</a>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
        <div id="above">
            <div class="content"></div>
            <div class="close large"><?php echo $this->description['dictionary']->translate('_close') ?></div>
        </div>

        <!--<div id="bg"><div><table cellpadding="0" cellspacing="0"><tr><td><img alt="" src="<?php echo $this->request['restoUrl'] ?>/css/default/img/bg.png" /></td></tr></table></div></div>-->
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/lib/jquery.min.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/lib/jquery.fancybox.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/lib/jquery.history.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/lib/leaflet.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/resto.js"></script>
        <script type="text/javascript">
            $(document).ready(function() {
                initResto({
                    language:'<?php echo $this->request['language']; ?>',
                    mapshupUrl:'<?php echo $mapshup && $mapshup['url'] ? $mapshup['url'] : null; ?>'
                });
            });
        </script>
    </body>
</html>