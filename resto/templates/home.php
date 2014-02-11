<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><title>RESTo framework</title>
        <link rel="shortcut icon" href="<?php echo $this->request['restoUrl'] ?>/favicon.ico" />
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/css/default/style.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/js/_addons/jquery.fancybox.css" type="text/css" />
        <!--<link rel="search" type="application/opensearchdescription+xml" href="<?php echo $this->request['restoUrl'] ?>_describe" title="Search" />-->
        <!-- IE Fallbacks -->
        <!--[if lt IE 9]>
        <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->   
    </head>
    <body>
        <div id="resto-home">

            <?php 
                $inNorth = ceil(count($this->R->getCollectionsDescription()) / 2.0);
                $inSouth = count($this->R->getCollectionsDescription()) - $inNorth;
                if ($inNorth > 0) {
                    $northWidth = 100 / $inNorth;
                }
                if ($inSouth > 0) {
                    $southWidth = 100 / $inSouth;
                }
            ?>
            <div class="resto-north">
                <div class="resto-tabled">
                    <?php
                    $count = 0;
                    foreach($this->R->getCollectionsDescription() as $key => $collection) {
                        if ($count < $inNorth) { ?>
                    <div search="<?php echo $this->request['restoUrl'] . $key . '/?q=' . urlencode($collection['os']['Query']); ;  ?>" class="resto-collection resto-bg-<?php echo $count;?>" style="width:<?php echo $northWidth ?>%;">
                        <div class="resto-medium resto-ellipsis">
                            <h2><?php echo $collection['os']['ShortName']; ?></h2>
                            <p><?php echo $collection['os']['Description']; ?></p>
                        </div>
                    </div>
                    <?php
                        }
                        $count++;
                    }
                    ?>
                </div>
            </div>
            <div class="resto-middle resto-white">
                <div class="resto-tabled resto-large">
                    <div class="resto-title">
                        <h2><?php echo $this->R->getTitle();?></h2>
                    </div>
                    <div class="resto-description">
                        <?php echo $this->R->getDescription();?>
                    </div>
                </div>
                <div id="resto-homemenu" class="resto-white">
                    <div class="resto-right resto-uppercase">
                        <ul>
                            <!--<li id="_connect">Connect</li>-->
                            <li id="_about">About</li>
                            <!--<li id="_help">Help</li>-->
                        </ul>
                    </div>
                </div>
            </div>
            <div class="resto-south">
                <div class="resto-tabled">
                    <?php
                    $count = 0;
                    foreach($this->R->getCollectionsDescription() as $key => $collection) {
                        if ($count >= $inNorth) { ?>
                    <div search="<?php echo $this->request['restoUrl'] . $key . '/?q=' . urlencode($collection['os']['Query']); ;  ?>" class="resto-collection resto-bg-<?php echo $count;?>" style="width:<?php echo $southWidth ?>%;">
                        <div class="resto-medium resto-ellipsis">
                            <h2><?php echo $collection['os']['ShortName']; ?></h2>
                            <p><?php echo $collection['os']['Description']; ?></p>
                        </div>
                    </div>
                    <?php
                        }
                        $count++;
                    }
                    ?>
                </div>
            </div>
            
        </div>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/mjquery/mjquery.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/_addons/jquery.fancybox.js"></script>
        <script type="text/javascript">
            $(document).ready(function() {
                $('a.resto-quicklook').fancybox();
                $('.resto-collection').click(function(){
                   window.location = $(this).attr('search'); 
                });
                $('#_about').click(function(){
                    alert("Work in progress. For more info contact jerome[dot]gasperi[at]gmail[dot]com");
                });
            });
        </script>
    </body>
</html>