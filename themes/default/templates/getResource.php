<!DOCTYPE html>
<?php
    
    /*
     * Set variables
     */
    $collectionUrl = $this->request['restoUrl'] . $this->request['collection'] . '/';
    $templateName = 'default';
    $product = isset($this->response['features']) && isset($this->response['features'][0]) ? $this->response['features'][0] : array(
        'properties' => array()
    );           
    $thumbnail = $product['properties']['thumbnail'];
    $quicklook = $product['properties']['quicklook'];
    if (!isset($thumbnail) && isset($quicklook)) {
        $thumbnail = $quicklook;
    }
    else if (!isset($thumbnail) && !isset($quicklook)) {
        $thumbnail = self.restoUrl + '/css/default/img/noimage.png';
    }
    if (!isset($quicklook)) {
        $quicklook = $thumbnail;
    }
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
        <!--[if lt IE 9]>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/modernizr/modernizr.min.js"></script>
        <![endif]-->
    </head>
    <body>

        <header>
            <span id="logo"><a title="<?php echo $this->description['dictionary']->translate('_home'); ?>" href="<?php echo $this->request['restoUrl'] ?>">RESTo</a><!-- | <?php echo $this->description['os']['ShortName']; ?>--></span>
            <nav>
                <ul>
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
                <h1><?php echo $this->description['os']['ShortName']; ?></h1>
            </div>
            <div class="large-6 columns">
                <p>
                    <?php echo $this->description['os']['Description']; ?>
                </p>
            </div>
        </div>
        
        <!-- mapshup display -->
        <div id="mapshup" class="noResizeHeight"></div>
        
        <!-- Quicklook and metadata -->
        <div class="row resto-resource">
            <div class="large-6 columns">
                <span class="resto-thumbnail"><a href="<?php echo $quicklook;?>" class="resto-quicklook" title="<?php echo $product['id'];?>"><img class="resto-image" src="<?php echo $thumbnail;?>"/></a></span>
            </div>
            <div class="large-6 columns">
                <!--<?php 
                $platform = $product['properties']['platform'];
                if (isset($platform) && $product['properties']['keywords'] && $product['properties']['keywords'][$platform]) {
                    //$platform = '<a href="' + self.updateURL(feature.properties.keywords[feature.properties['platform']]['href'], {format: 'html'}) + '" class="resto-ajaxified resto-updatebbox resto-keyword resto-keyword-platform" title="' + self.translate('_thisResourceWasAcquiredBy', [feature.properties['platform']]) + '">' + feature.properties['platform'] + '</a> ';
                }
                ?>-->
            </div>
        </div>
        
        <!-- Location content (Landcover) -->
        <div class="row resto-resource fullWidth resto-resource-location">
            <div class="large-6 columns">
                <h1><span class="right"><?php echo $this->description['dictionary']->translate('_location'); ?></span></h1>
            </div>
            <div class="large-6 columns">
            <?php
                    if ($product['properties']['keywords']) {
                        foreach ($product['properties']['keywords'] as $keyword => $value) {
                            if (strtolower($value['type']) === 'continent') {
            ?>
                <?php echo $keyword; ?><br/>
            <?php }}} ?>
            <?php
                    if ($product['properties']['keywords']) {
                        foreach ($product['properties']['keywords'] as $keyword => $value) {
                            if (strtolower($value['type']) === 'country') {
            ?>
                <?php echo $keyword; ?><br/>
            <?php }}} ?>
            </div>
        </div>
        
        <!-- Thematic content (Landcover) -->
        <div class="row resto-resource fullWidth resto-resource-landuse">
            <div class="large-6 columns">
                <h1><span class="right"><?php echo $this->description['dictionary']->translate('_landUse'); ?></span></h1>
            </div>
            <div class="large-6 columns">
            <?php
                    if ($product['properties']['keywords']) {
                        foreach ($product['properties']['keywords'] as $keyword => $value) {
                            if (strtolower($value['type']) === 'landuse') {
            ?>
                <h2><?php echo round($value['value']); ?> % <?php echo $this->description['dictionary']->translate($keyword); ?></h2>
            <?php }}} ?>
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
                    data:data,
                    translation:<?php echo json_encode($this->description['dictionary']->getTranslation()) ?>,
                    restoUrl: '<?php echo $this->request['restoUrl'] ?>',
                    ssoServices:<?php echo json_encode($this->R->ssoServices) ?>
                });
                
                /*
                 * Initialize page with no mapshup refresh
                 */
                R.updateGetResource(data, {
                    updateMap: false,
                    centerMap: data && data.query
                });
                
            });
        </script>
    </body>
</html>
