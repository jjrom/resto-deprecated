<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->request['language'] ?>">
    <head>
        <title>RESTo</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1" />
        <link rel="shortcut icon" href="<?php echo $this->request['restoUrl'] ?>/favicon.ico" />
        <link rel="stylesheet" type="text/css" href="<?php echo $this->request['restoUrl'] ?>/js/css/dependencies.min.css" />
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/themes/<?php echo $templateName ?>/style.css" type="text/css" />
    </head>
    <body>
        <header>
            <span id="logo"><a title="<?php echo $this->description['dictionary']->translate('_home'); ?>" href="<?php echo $this->request['restoUrl'] ?>"><?php echo $this->R->getTitle(); ?></a> | <?php echo $this->description['os']['ShortName']; ?></span>
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
        
        <!-- Not found -->
        <div class="row center">
            <div class="large-12 columns">
                <h1>Oh no! Page not found</h1>
                <p><a href="<?php echo $this->request['restoUrl'] ?>">Go back to home page</a></p>
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
    </body>
</html>