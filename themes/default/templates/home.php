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
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/js/css/dependencies.min.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/themes/<?php echo $templateName ?>/style.css" type="text/css" />
        <!--[if lt IE 9]>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/modernizr/modernizr.min.js"></script>
        <![endif]-->
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/dependencies.min.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/resto.min.js"></script>
    </head>
    <?php flush();?>
    <body>
        <header>
            <span id="logo"><a title="<?php echo $this->description['dictionary']->translate('_home'); ?>" href="<?php echo $this->request['restoUrl'] ?>">RESTo</a></span>
            <nav>
                <ul class="no-bullet">
                    <!--
                    <li title="<?php echo $this->description['dictionary']->translate('_viewCart'); ?>" class="fa fa-shopping-cart link"></li>
                    -->
                    <li class="link gravatar center bgorange viewUserPanel"></li>
                </ul>
            </nav>
	</header>
        <div class="row" style="height:35px;">
            <div class="large-12 columns"></div>
        </div>
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
                            <a class="fa fa-search" href="<?php echo $this->request['restoUrl'] . $key ?>">  <?php echo $collection['os']['ShortName']; ?></a><br/>
                            <?php if ($user->canPut($key)) { ?><a class="fa fa-edit button bggreen updateCollection admin" href="#" collection="<?php echo $key; ?>" title="<?php echo $this->description['dictionary']->translate('_update'); ?>"></a><?php } ?>
                            <?php if ($user->canDelete($key)) { ?><a class="fa fa-moon-o button bgorange deactivateCollection admin" href="#" collection="<?php echo $key; ?>" title="<?php echo $this->description['dictionary']->translate('_deactivate'); ?>"></a><?php } ?>
                            <?php if ($user->canDelete($key)) { ?><a class="fa fa-trash-o button bgred removeCollection admin" href="#" collection="<?php echo $key; ?>" title="<?php echo $this->description['dictionary']->translate('_remove'); ?>"></a><?php } ?>
                        </h1>
                        <p><?php echo $collection['os']['Description']; ?></p>
                    </div>
                </div>
            <?php } ?>
            <?php if ($user->canPost()) { ?>
            <div class="row fullWidth resto-admin">
                <div class="large-12 columns center">
                    <div id="dropZone" class="_dropCollection"><h1><?php echo $this->description['dictionary']->translate('_addCollection'); ?></h1><span class="fa fa-arrow-down"></span> <?php echo $this->description['dictionary']->translate('_dropCollection'); ?> <span class="fa fa-arrow-down"></span></div>
                </div>
            </div>
            <?php } ?>       
        </div>
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
