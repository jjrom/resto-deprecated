<!DOCTYPE html>
<?php
$templateName = 'default';
$user = $this->R->getUser();
?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><title>RESTo framework</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1" />
        <link rel="shortcut icon" href="<?php echo $this->request['restoUrl'] ?>/favicon.ico" />
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/js/externals/foundation/foundation.min.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/js/externals/fontawesome/css/font-awesome.min.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/themes/<?php echo $templateName ?>/style.css" type="text/css" />
        <!--[if lt IE 9]>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/modernizr/modernizr.min.js"></script>
        <![endif]-->
    </head>
    <body>
        <header>
            <span id="logo"><a title="<?php echo $this->description['dictionary']->translate('_home'); ?>" href="<?php echo $this->request['restoUrl'] ?>">RESTo</a></span>
            <nav>
                <ul>
                    <li title="<?php echo $this->description['dictionary']->translate('_shareOn', 'Facebook'); ?>" class="fa fa-facebook link shareOnFacebook"></li>
                    <li title="<?php echo $this->description['dictionary']->translate('_shareOn', 'Twitter'); ?>" class="fa fa-twitter link shareOnTwitter"></li>
                    <li></li>
                    <li title="<?php echo $this->description['dictionary']->translate('_login'); ?>" class="fa fa-sign-in link  signIn"></li>
                    <li title="<?php echo $this->description['dictionary']->translate('_logout'); ?>" class="fa fa-sign-out link  signOut"></li>
                    <!--
                    <li title="<?php echo $this->description['dictionary']->translate('_viewCart'); ?>" class="fa fa-shopping-cart link"></li>
                    -->
                </ul>
            </nav>
	</header>
        <div class="row fullWidth resto-title">
            <div class="large-12 columns">
                <h1><a href="http://jjrom.github.io/resto/"><?php echo $this->R->getTitle(); ?></a></h1>
                <p><?php echo $this->R->getDescription(); ?></p>
            </div>
        </div>
        <div class="collections">
            <?php
            $left = false;
            foreach ($this->R->getCollectionsDescription() as $key => $collection) {
                $left = !$left;
                ?>
                <div class="row fullWidth resto-collection" id="_<?php echo $key;?>"> 
                    <div class="large-12 columns <?php echo $left ? 'left' : 'right' ?>">
                        <h1>
                            <a class="fa fa-search" href="<?php echo $this->request['restoUrl'] . $key . '/?q=' . urlencode($collection['os']['Query']); ?>">  <?php echo $collection['os']['ShortName']; ?></a><br/>
                            <?php if ($user->canPut($key)) { ?><a class="button bggreen updateCollection" href="#" collection="<?php echo $key; ?>"><?php echo $this->description['dictionary']->translate('_update'); ?></a><?php } ?>
                            <?php if ($user->canDelete($key)) { ?><a class="button bgorange deactivateCollection" href="#" collection="<?php echo $key; ?>"><?php echo $this->description['dictionary']->translate('_deactivate'); ?></a><?php } ?>
                            <?php if ($user->canDelete($key)) { ?><a class="button bgred removeCollection" href="#" collection="<?php echo $key; ?>"><?php echo $this->description['dictionary']->translate('_remove'); ?></a><?php } ?>
                        </h1>
                        <p><?php echo $collection['os']['Description']; ?></p>
                    </div>
                </div>
            <?php } ?>
            <?php if ($user->canPost()) { ?>
            <div class="row fullWidth resto-admin">
                <div class="large-12 columns center">
                    <h1><?php echo $this->description['dictionary']->translate('_addCollection'); ?></h1>
                    <textarea id='collectionDescription' placeholder="JSON description" name='collectionDescription'></textarea>
                    <p class="center">
                        <a href="#" class="fa fa-4x fa-plus-circle white addCollection"></a>
                    </p>
                </div>
            </div>
            <?php } ?>       
        </div>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/mjquery/mjquery.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/mjquery/mjquery.ui.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/swipebox/js/jquery.swipebox.min.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/history/jquery.history.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/resto.js"></script>
        <script type="text/javascript">
            $(document).ready(function() {

                /*
                 * Initialize RESTo 
                 */
                R.init({
                    language: '<?php echo $this->request['language']; ?>',
                    data:<?php echo json_encode($this->response) ?>,
                    translation:<?php echo json_encode($this->description['dictionary']->getTranslation()) ?>,
                    restoUrl: '<?php echo $this->request['restoUrl'] ?>'
                });
            });
        </script>
    </body>
</html>
