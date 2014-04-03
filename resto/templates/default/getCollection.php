<!DOCTYPE html>
<?php
    $collectionUrl = $this->request['restoUrl'] . $this->request['collection'] . '/';
    $templateName = 'default';
?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><title><?php echo strip_tags($this->R->getTitle()); ?></title>
        <link rel="shortcut icon" href="<?php echo $this->request['restoUrl'] ?>/favicon.ico" />
        <!-- mapshup : start -->
        <link rel="stylesheet" type="text/css" href="<?php echo $this->request['restoUrl'] ?>/js/mol/theme/default/style.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo $this->request['restoUrl'] ?>/js/mjquery/mjquery.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo $this->request['restoUrl'] ?>/js/mapshup/theme/default/mapshup.css" />
        <!-- mapshup : end -->
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/themes/<?php echo $templateName ?>/style.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/js/_addons/jquery.fancybox.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/js/_addons/leaflet.css" type="text/css"/>
        <link rel="search" type="application/opensearchdescription+xml" href="<?php echo $collectionUrl ?>$describe" hreflang="<?php echo $this->request['language'] ?>" title="<?php echo $this->description['name']; ?>" />
    </head>
    <body>
        <div id="resto-container">
            <!-- top menu contains search form -->
            <div id="resto-menu" class="resto-white">
                <div class="resto-left">
                    <a class="resto-link" href="<?php echo $this->request['restoUrl'] ?>"><?php echo $this->R->getTitle(); ?></a>
                </div>
                <div class="resto-search">
                    <form id="resto-searchform" action="<?php echo $collectionUrl ?>">
                        <input type="hidden" name="format" value="html" />
                        <?php
                        if ($this->request['language']) {
                            echo '<input type="hidden" name="' . $this->description['searchFiltersDescription']['language']['osKey'] . '" value="' . $this->request['language'] . '" />';
                        }
                        ?>
                        <input type="text" id="search" name="<?php echo $this->description['searchFiltersDescription']['searchTerms']['osKey'] ?>" value="<?php echo str_replace('"', '&quot;', stripslashes($this->request['params'][$this->description['searchFiltersDescription']['searchTerms']['osKey']])); ?>" placeholder="<?php echo $this->description['dictionary']->translate('_placeHolder', $this->description['os']['Query']);?>"/>
                    </form>
                </div>
                <div class="resto-right resto-uppercase">
                    <ul class="resto-list">
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
            <div class="resto-header resto-left resto-medium">
                <h2><?php echo $this->description['os']['ShortName']; ?></h2>
                <div class="resto-description">
                    <?php echo $this->description['os']['Description']; ?>
                </div>
            </div>
            <!-- mapshup : start -->
            <div id="mapshup" class="noResizeHeight"></div>
            <!-- mapshup : end -->
            <!-- query analyze result -->
            <div class="resto-queryanalyze resto-center resto-medium"></div>
            <!-- list of results -->
            <div class="resto-result resto-left">
                <div class="resto-pagination"></div>
                <div class="resto-content"></div>
                <div class="resto-pagination"></div>
                <div id="resto-footer">
                    <div class="resto-center">
                        Powered by <a class="resto-link" href="http://github.com/jjrom/resto">RESTo</a>, <a class="resto-link" href="http://github.com/jjrom/itag">iTag</a> and <a class="resto-link" href="http://mapshup.info">mapshup</a> -  Maps &copy; <a class="resto-link" href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a class="resto-link" href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Tiles courtesy of <a class="resto-link" href="http://hot.openstreetmap.org/" target="_blank">Humanitarian OpenStreetMap Team</a>
                    </div>
                </div>
            </div>
        </div>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/mjquery/mjquery.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/mjquery/mjquery.ui.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/_addons/jquery.fancybox.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/_addons/jquery.history.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/_addons/leaflet.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/resto.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/themes/<?php echo $templateName ?>/theme.js"></script>
        <!-- mapshup : start -->
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/mol/OpenLayers.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/mapshup/mapshup.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/mapshup/config/default.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/themes/<?php echo $templateName ?>/config.js"></script>
        <!-- mapshup : end -->
        <script type="text/javascript">
            $(document).ready(function() {
                
                /*
                 * Load mapshup
                 */
                if (M) {
                    M.load();
                }
                
                R.init({
                    language:'<?php echo $this->request['language']; ?>',
                    data:<?php echo json_encode($this->response) ?>,
                    translation:<?php echo json_encode($this->description['dictionary']->getTranslation())?>,
                    restoUrl:'<?php echo $this->request['restoUrl'] ?>'
                });
                
            });
        </script>
    </body>
</html>