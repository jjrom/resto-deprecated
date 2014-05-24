<!DOCTYPE html>
<?php
$collectionUrl = $this->request['restoUrl'] . $this->request['collection'] . '/';
$templateName = 'default';
?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><title><?php echo strip_tags($this->R->getTitle()); ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1" />
        <link rel="shortcut icon" href="<?php echo $this->request['restoUrl'] ?>/favicon.ico" />
        <!-- mapshup : start -->
        <link rel="stylesheet" type="text/css" href="<?php echo $this->request['restoUrl'] ?>/js/externals/mol/theme/default/style.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo $this->request['restoUrl'] ?>/js/externals/mjquery/mjquery.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo $this->request['restoUrl'] ?>/js/externals/mapshup/theme/default/mapshup.css" />
        <!-- mapshup : end -->
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/js/externals/foundation/foundation.min.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/js/externals/swipebox/css/swipebox.min.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/js/externals/fontawesome/css/font-awesome.min.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/themes/<?php echo $templateName ?>/style.css" type="text/css" />
        <link rel="search" type="application/opensearchdescription+xml" href="<?php echo $collectionUrl ?>$describe" hreflang="<?php echo $this->request['language'] ?>" title="<?php echo $this->description['name']; ?>" />
        <!--[if lt IE 9]>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/modernizr/modernizr.min.js"></script>
        <![endif]-->
    </head>
    <?php flush(); ?>
    <body>

        <header>
            <span id="logo"><a title="<?php echo $this->description['dictionary']->translate('_home'); ?>" href="<?php echo $this->request['restoUrl'] ?>">RESTo</a> | <?php echo $this->description['os']['ShortName']; ?></span>
            <nav>
                <ul class="no-bullet">
                    <li title="<?php echo $this->description['dictionary']->translate('_shareOn', 'Facebook'); ?>" class="fa fa-facebook link shareOnFacebook"></li>
                    <li title="<?php echo $this->description['dictionary']->translate('_shareOn', 'Twitter'); ?>" class="fa fa-twitter link shareOnTwitter"></li>
                    <!--
                    <li title="<?php echo $this->description['dictionary']->translate('_viewCart'); ?>" class="fa fa-shopping-cart link"></li>
                    -->
                    <li></li>
                    <li class="link gravatar center bgorange viewUserPanel"></li>
                </ul>
            </nav>
        </header>

        <div class="row" style="height:50px;">
            <div class="large-12 columns"></div>
        </div>

        <!-- Collection title and description -->
        <div class="row">
            <div class="large-6 columns">
                <h1 class="right"><?php echo $this->description['os']['ShortName']; ?></h1>
            </div>
            <div class="large-6 columns">
                <p>
                    <?php echo $this->description['os']['Description']; ?>
                </p>
            </div>
        </div>

        <div class="row">
            <div class="large-12 columns resto-search">
                <form id="resto-searchform" action="<?php echo $collectionUrl ?>">
                    <input type="hidden" name="format" value="html" />
                    <?php
                    if ($this->request['language']) {
                        echo '<input type="hidden" name="' . $this->description['searchFiltersDescription']['language']['osKey'] . '" value="' . $this->request['language'] . '" />';
                    }
                    ?>
                    <input type="search" id="search" name="<?php echo $this->description['searchFiltersDescription']['searchTerms']['osKey'] ?>" value="<?php echo str_replace('"', '&quot;', stripslashes($this->request['params'][$this->description['searchFiltersDescription']['searchTerms']['osKey']])); ?>" placeholder="<?php echo $this->description['dictionary']->translate('_placeHolder', $this->description['os']['Query']); ?>"/>
                </form>
            </div>
        </div>
        <!-- mapshup display -->
        <div id="mapshup" class="noResizeHeight"></div>

        <!-- mapshup display -->
        <!--<div id="mapshup" class="noResizeHeight fixed"></div>
        <div id="mapshup-tools" class="fixed"></div>
        <div class="row mapshup-block-fixed">
            <div class="large-12 columns"></div>
        </div>
        <div class="row mobile-block-fixed">
            <div class="large-12 columns"></div>
        </div>-->


        <!--
         <div class="resto-search">
            <form id="resto-searchform" action="<?php echo $collectionUrl ?>">
                <input type="hidden" name="format" value="html" />
        <?php
        if ($this->request['language']) {
            echo '<input type="hidden" name="' . $this->description['searchFiltersDescription']['language']['osKey'] . '" value="' . $this->request['language'] . '" />';
        }
        ?>
                <input type="search" id="search" name="<?php echo $this->description['searchFiltersDescription']['searchTerms']['osKey'] ?>" value="<?php echo str_replace('"', '&quot;', stripslashes($this->request['params'][$this->description['searchFiltersDescription']['searchTerms']['osKey']])); ?>" placeholder="<?php echo $this->description['dictionary']->translate('_placeHolder', $this->description['os']['Query']); ?>"/>
            </form>
        </div>
        -->
        <?php if ($this->R->getUser()->canPost($this->request['collection'])) { ?>
            <div class="row fullWidth resto-admin">
                <div class="large-12 columns center">
                    <div id="dropZone"><h1><?php echo $this->description['dictionary']->translate('_addResource'); ?></h1><span class="fa fa-arrow-down"></span> <?php echo $this->description['dictionary']->translate('_dropResource'); ?> <span class="fa fa-arrow-down"></span></div>
                </div>
            </div>
        <?php } ?>

        <!-- query analyze result -->
        <?php if ($this->request['special']['_showQuery']) { ?>
            <div class="resto-queryanalyze fixed"></div>
        <?php } ?>

        <!-- Collection title and description -->
        <!--
        <div class="row">
            <div class="large-12 columns">
                <h1><?php echo $this->description['os']['ShortName']; ?></h1>
                <div class="resto-description">
        <?php echo $this->description['os']['Description']; ?>
                </div>
            </div>
        </div>
        -->

        <div class="row">
            <div class="large-12 columns">
                <ul class="small-block-grid-1 medium-block-grid-3 large-block-grid-4 resto-pagination center"></ul>
            </div>
        </div>

        <!-- Search result -->
        <div class="row">
            <div class="large-12 columns">
                <ul class="small-block-grid-1 medium-block-grid-3 large-block-grid-4 resto-content center"></ul>
            </div>
        </div>

        <div class="row">
            <div class="large-12 columns">
                <ul class="small-block-grid-1 medium-block-grid-3 large-block-grid-4 resto-pagination center"></ul>
            </div>
        </div>

        <!-- Footer -->
        <div class="row">
            <div class="small-12 columns">
                <div class="footer">
                    Powered by <a href="http://github.com/jjrom/resto">RESTo</a>, <a href="http://github.com/jjrom/itag">iTag</a> and <a href="http://mapshup.info">mapshup</a>
                </div>
            </div>
        </div>

        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/mjquery/mjquery.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/mjquery/mjquery.ui.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/swipebox/js/jquery.swipebox.min.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/history/jquery.history.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/visible/jquery.visible.min.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/resto.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/themes/<?php echo $templateName ?>/collection.js"></script>
        <!-- mapshup : start -->
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/mol/OpenLayers.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/mapshup/mapshup.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/mapshup/config/default.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/themes/<?php echo $templateName ?>/config.js"></script>
        <!-- mapshup : end -->
        <script type="text/javascript">
            $(document).ready(function() {

                var data = <?php echo json_encode($this->response) ?>;

                /*
                 * Initialize mapshup
                 */
                if (M) {
                    M.load();
                }

                /*
                 * Initialize RESTo
                 */
                R.init({
                    language: '<?php echo $this->request['language']; ?>',
                    data: data,
                    translation:<?php echo json_encode($this->description['dictionary']->getTranslation()) ?>,
                    restoUrl: '<?php echo $this->request['restoUrl'] ?>',
                    collection: '<?php echo $this->request['collection'] ?>',
                    ssoServices:<?php echo json_encode($this->R->ssoServices) ?>
                });

                /*
                 * Bind history change with update collection action
                 */
                R.onHistoryChange(R.updateGetCollection);

                /*
                 * Initialize page with no mapshup refresh
                 */
                R.updateGetCollection(data, {
                    updateMap: false,
                    centerMap: data && data.query
                });

            });
        </script>
    </body>
</html>
