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
            <span id="logo"><a title="<?php echo $this->description['dictionary']->translate('_home'); ?>" href="<?php echo $this->request['restoUrl'] ?>">RESTo</a> | <?php echo $this->description['os']['ShortName']; ?></span>
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

        <!-- Search result -->
        <div class="row">
            <div class="large-12 columns">
                <ul class="small-block-grid-1 medium-block-grid-3 large-block-grid-4 resto-content center">
                    
                </ul>
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
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/themes/<?php echo $templateName ?>/theme.js"></script>
        <script type="text/javascript">
            $(document).ready(function() {

                /*
                 * Initialize RESTo
                 */
                R.init({
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
