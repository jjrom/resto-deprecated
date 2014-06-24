<?php
    
    /*
     * Set variables
     */
    $collectionUrl = $this->request['restoUrl'] . $this->request['collection'] . '/';
    $templateName = 'default';
    
    /*
     * 404.html if product is not found
     */
    if (!isset($this->response['features']) || !isset($this->response['features'][0])) {
        header("HTTP/1.0 404 Not Found");
        include '404.php';
        exit;
    }
    
    $product = $this->response['features'][0];           
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
    
    if (class_exists('Wikipedia')) {
        $wikipedia = new Wikipedia($this->R);
        $wikipediaEntries = $wikipedia->getEntries(geoJSONGeometryToWKT($product['geometry']), $this->request['language'], 10);
    }
    
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->request['language'] ?>">
    <head>
        <title><?php echo strip_tags($this->R->getTitle()); ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1" />
        <link rel="shortcut icon" href="<?php echo $this->request['restoUrl'] ?>/favicon.ico" />
        <link rel="stylesheet" type="text/css" href="<?php echo $this->request['restoUrl'] ?>/js/css/dependencies.min.css" />
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/themes/<?php echo $templateName ?>/style.css" type="text/css" />
        <link rel="search" type="application/opensearchdescription+xml" href="<?php echo $collectionUrl ?>$describe" hreflang="<?php echo $this->request['language'] ?>" title="<?php echo $this->description['name']; ?>" />
        <!--[if lt IE 9]>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/modernizr.min.js"></script>
        <![endif]-->
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/dependencies.min.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/resto.min.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/themes/<?php echo $templateName ?>/config.js"></script>
    </head>
    <?php flush();?>
    <body>

        <header>
            <span id="logo"><a title="<?php echo $this->description['dictionary']->translate('_home'); ?>" href="<?php echo $collectionUrl ?>"><?php echo $this->description['os']['ShortName']; ?></a><!-- | <?php echo $this->description['os']['ShortName']; ?>--></span>
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
            <div class="large-6 columns center">
                <h1>&nbsp;</h1>
                <h2><?php echo $this->description['dictionary']->translate('_resourceSummary', $product['properties']['platform'], $product['properties']['resolution'], substr($product['properties']['startDate'],0, 10)); ?></h2>
                <h7 title="<?php echo $product['id']; ?>" style="overflow: hidden;"><?php echo $product['id']; ?></h7>
                <?php
                    if ($product['properties']['services'] && $product['properties']['services']['download'] && $product['properties']['services']['download']['url']) {
                        if ($this->R->getUser()->getRights($this->request['collection'], 'get', 'download')) {
                ?>
                <p class="center padded-top">
                    <a class="fa fa-4x fa-cloud-download" href="<?php echo $product['properties']['services']['download']['url']; ?>" <?php echo $product['properties']['services']['download']['mimeType'] === 'text/html' ? 'target="_blank"' : ''; ?> title="<?php echo $this->description['dictionary']->translate('_download'); ?>"></a> 
                </p>
                <?php
                      } 
                    }
                ?>
                <h1>&nbsp;</h1>
            </div>
            <div class="large-6 columns grey">
                <h3><?php echo $this->description['os']['ShortName']; ?></h3>
                <p>
                    <?php echo $this->description['os']['Description']; ?>
                </p>
            </div>
        </div>
        
        <!-- mapshup display -->
        <div id="mapshup" class="noResizeHeight"></div>
        
        <!-- Quicklook and metadata -->
        <div class="row resto-resource">
            <div class="large-6 columns center">
                <img title="<?php echo $product['id'];?>" class="resto-image" src="<?php echo $quicklook;?>"/>
            </div>
            <div class="large-6 columns">
                <table style="width:100%;">
                    <?php
                    $excluded = array('quicklook', 'thumbnail', 'links', 'services', 'keywords', 'updated', 'productId');
                    foreach(array_keys($product['properties']) as $key) {
                        if (!in_array($key, $excluded) && isset($product['properties'][$key])) {
                            echo '<tr><td>' . $this->description['dictionary']->translate($key) . '</td><td>' . $product['properties'][$key] . '</td></tr>';
                        }
                    }
                    ?>
                </table>
            </div>
        </div>
        
        <!-- Location content (Landcover) -->
        <div class="row resto-resource fullWidth dark">
            <div class="large-6 columns">
                <h1><span class="right"><?php echo $this->description['dictionary']->translate('_location'); ?></span></h1>
            </div>
            <div class="large-6 columns">
            <?php
                    if ($product['properties']['keywords']) {
                        foreach ($product['properties']['keywords'] as $keyword => $value) {
                            if (strtolower($value['type']) === 'continent') {
            ?>
                <h2><a title="<?php echo $this->description['dictionary']->translate('_thisResourceIsLocated', $keyword) ?>" href="<?php echo updateUrl($collectionUrl, array('format' => 'html', 'q' => $keyword)) ?>"><?php echo $keyword; ?></a></h2>
            <?php }}} ?>
            <?php
                    if ($product['properties']['keywords']) {
                        foreach ($product['properties']['keywords'] as $keyword => $value) {
                            if (strtolower($value['type']) === 'country') {
            ?>
                <h2><a title="<?php echo $this->description['dictionary']->translate('_thisResourceIsLocated', $keyword) ?>" href="<?php echo updateUrl($collectionUrl, array('format' => 'html', 'q' => $keyword)) ?>"><?php echo $keyword; ?></a></h2>
            <?php }}} ?>
            </div>
        </div>
        
        <!-- Thematic content (Landcover) -->
        <div class="row resto-resource fullWidth light">
            <div class="large-6 columns">
                <h1><span class="right"><?php echo $this->description['dictionary']->translate('_landUse'); ?></span></h1>
            </div>
            <div class="large-6 columns">
            <?php
                    if ($product['properties']['keywords']) {
                        foreach ($product['properties']['keywords'] as $keyword => $value) {
                            if (strtolower($value['type']) === 'landuse') {
            ?>
                <h2><?php echo round($value['value']); ?> % <a title="<?php echo $this->description['dictionary']->translate('_thisResourceContainsLanduse', $value['value'], $keyword) ?>" href="<?php echo updateUrl($collectionUrl, array('format' => 'html', 'q' => $keyword)) ?>"><?php echo $keyword; ?></a></h2>
            <?php }}} ?>
            </div>
        </div>
        
        <!-- Wikipedia -->
        <div class="row resto-resource fullWidth dark">
            <div class="large-6 columns">
                <h1 class="right"><?php echo $this->description['dictionary']->translate('_poi'); ?></h1>
            </div>
            <div class="large-6 columns">
                <?php
                if (is_array($wikipediaEntries) && count($wikipediaEntries) > 0) {
                    foreach ($wikipediaEntries as $wikipediaEntry) {
                ?>
                <h2><a href="<?php echo $wikipediaEntry['url']; ?>"><?php echo $wikipediaEntry['title']; ?></a></h2>
                <p><?php echo $wikipediaEntry['summary']; ?></p>
                <?php }} ?>
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
        <script type="text/javascript">
            $(document).ready(function() {
               
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
                    issuer:'getResource',
                    language: '<?php echo $this->request['language']; ?>',
                    data:<?php echo json_encode($this->response) ?>,
                    translation:<?php echo json_encode($this->description['dictionary']->getTranslation()) ?>,
                    restoUrl: '<?php echo $this->request['restoUrl'] ?>',
                    ssoServices:<?php echo json_encode($this->R->ssoServices) ?>
                });
            });
        </script>
    </body>
</html>
