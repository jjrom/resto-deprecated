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
            <!-- top menu contains search form -->
            <div id="menu" class="white">
                <div class="left">
                    <a href="<?php echo $this->request['restoUrl'] ?>"><?php echo $this->R->getTitle(); ?></a>
                </div>
                <div class="search">
                    <form id="searchform" action="<?php echo $collectionUrl ?>">
                        <input type="hidden" name="format" value="html" />
                        <?php
                        if ($this->request['language']) {
                            echo '<input type="hidden" name="' . $this->description['searchFiltersDescription']['language']['osKey'] . '" value="' . $this->request['language'] . '" />';
                        }
                        ?>
                        <input type="text" id="search" name="<?php echo $this->description['searchFiltersDescription']['searchTerms']['osKey'] ?>" value="<?php echo str_replace('"', '&quot;', stripslashes($this->request['params'][$this->description['searchFiltersDescription']['searchTerms']['osKey']])); ?>" placeholder="<?php echo $this->description['dictionary']->translate('_placeHolder', $this->description['os']['Query']);?>"/>
                    </form>
                </div>
                <div class="right uppercase">
                    <ul>
                        <?php
                            for ($i = 0, $l = count($this->description['acceptedLangs']); $i < $l; $i++) {
                                echo '<li class="language" title="' . $this->description['dictionary']->translate($this->description['acceptedLangs'][$i] === $this->request['language'] ? '_inLang' : '_switchLang', $this->description['dictionary']->translate('_' . $this->description['acceptedLangs'][$i])) . '" id="language-' . $this->description['acceptedLangs'][$i] . '" href="' . updateURL($collectionUrl, array($this->description['searchFiltersDescription']['searchTerms']['osKey'] => stripslashes($this->request['params'][$this->description['searchFiltersDescription']['searchTerms']['osKey']]), $this->description['searchFiltersDescription']['language']['osKey'] => $this->description['acceptedLangs'][$i])) . '">' . $this->description['acceptedLangs'][$i] . '<li>';
                            }
                        ?>
                        <li>&nbsp;&nbsp;</li>
                        <!--<li id="_connect"><?php echo $this->description['dictionary']->translate('_connect');?></li>-->
                        <li id="_about"><?php echo $this->description['dictionary']->translate('_about');?></li>  
                    </ul>
                </div>
            </div>
            <!-- header contains title and description -->
            <div class="header left medium">
                <h2><?php echo $this->description['os']['ShortName']; ?></h2>
                <div class="description">
                    <?php echo $this->description['os']['Description']; ?>
                </div>
            </div>
            <!-- map -->
            <div id="mapshup" class="mapshup">
                <div class="content"></div>
                <div class="close large"><?php echo $this->description['dictionary']->translate('_close') ?></div>
            </div>
            <!-- query analyze result -->
            <div class="queryanalyze center medium"></div>
            <!-- list of results -->
            <div class="result left">
                <div class="pagination"></div>
                <div class="content"></div>
                <div class="pagination"></div>
                <div id="footer">
                    <div class="center">
                        Powered by <a href="http://github.com/jjrom/resto">RESTo</a>, <a href="http://github.com/jjrom/itag">iTag</a> and <a href="http://mapshup.info">mapshup</a> -  Maps &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Tiles courtesy of <a href="http://hot.openstreetmap.org/" target="_blank">Humanitarian OpenStreetMap Team</a>
                    </div>
                </div>
            </div>
        </div>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/lib/jquery.min.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/lib/jquery.fancybox.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/lib/jquery.history.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/lib/leaflet.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/resto.js"></script>
        <script type="text/javascript">
            $(document).ready(function() {
                R.init({
                    language:'<?php echo $this->request['language']; ?>',
                    data:<?php echo json_encode($this->response) ?>,
                    translation:<?php echo json_encode($this->description['dictionary']->getTranslation())?>,
                    mapshupUrl:'<?php echo $mapshup && $mapshup['url'] ? $mapshup['url'] : null; ?>',
                    restoUrl:'<?php echo $this->request['restoUrl'] ?>'
                });
            });
        </script>
    </body>
</html>