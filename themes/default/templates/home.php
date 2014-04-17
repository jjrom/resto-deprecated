<!DOCTYPE html>
<?php
    $templateName = 'default';
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
        <div class="row fullWidth resto-title">
            <div class="large-12 columns">
                <h1><a href="http://jjrom.github.io/resto/"><?php echo $this->R->getTitle(); ?></a></h1>
                <p><?php echo $this->R->getDescription(); ?></p>
            </div>
        </div>
        <?php
            $left = false;
            foreach ($this->R->getCollectionsDescription() as $key => $collection) {
                $left = !$left;
        ?>
            <div class="row fullWidth resto-collection">
                <div class="large-12 columns <?php echo $left ? 'left' : 'right' ?>">
                    <h1><a class="fa fa-search" href="<?php echo $this->request['restoUrl'] . $key . '/?q=' . urlencode($collection['os']['Query']);?>">  <?php echo $collection['os']['ShortName']; ?></a></h1>
                    <p><?php echo $collection['os']['Description']; ?></p>
                </div>
            </div>
        <?php } ?>
    </body>
</html>
