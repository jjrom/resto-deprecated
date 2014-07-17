resto
=====

RESTo - REstful Semantic search Tool for geOspatial

You can try the [RESTo demo] (http://mapshup.info/resto)

Installation
============

In the following, we suppose that $RESTO_HOME is the directory where resto sources will be installed

        export RESTO_HOME=/wherever/you/want/resto

If not already done, download RESTo to $RESTO_HOME

        git clone https://github.com/jjrom/resto.git $RESTO_HOME

Prerequesites
-------------

* Apache (v2.0+) with **mod_rewrite support**
* PHP (v5.3+) with **curl, XMLWriter and PGConnect extension**
* PostgreSQL (v9.0+) with **hstore extension**
* PostGIS (v1.5.1+)

Note: RESTo could work with lower version of the specified requirements.
However there is no guaranty of success and unwanted result may occured !


Install RESTo database
----------------------

RESTo installs a PostgreSQL database named 'resto'. 

The 'resto' database is created with PostGIS and hstore extension enabled within the 'public' schema.

During the installation, two additional schemas are created :
* 'admin' schema - among others, it stores the table containing the collections description
* 'gazetteer' schema - it contains the gazetteer table (see below)

Two users 'resto' and 'sresto' are automatically created within this database :
* 'resto' user has READ ONLY access to collections database, to collection description and to gazetteer table
* 'sresto' user has same privileges as 'resto' user plus WRITE access to 'admin' schema and to all collection databases

It is very important to specify strong passwords for these two users.

To install RESTo database, launch the following script

        $RESTO_HOME/_install/installDB.sh -F -d <PostGIS directory> -p <resto user password> -P <sresto user password>

Note1 : <PostGIS directory> should be replaced by the directory containing both postgis.sql and spatial_ref_sys.sql (e.g. /usr/local/share/postgis/)

Note2 : installation script supposed that the PostgreSQL superuser name is 'postgres' (otherwise add '-s <superusername>' to the above command)
and that it has access to psql on localhost without password.


Install Gazetteer
-----------------

RESTo provides a Gazetteer service based on geonames data (http://geonames.org). This service is optional but if 
you want to add location based search on toponyms (and i'm sure you want :) you should follow the next steps.

First you need to download geonames data in $GEONAMES_DIR directory

        export GEONAMES_DIR=/a/temporary/directory
        cd $GEONAMES_DIR
        wget http://download.geonames.org/export/dump/allCountries.zip
        wget http://download.geonames.org/export/dump/alternateNames.zip
        wget http://download.geonames.org/export/dump/countryInfo.txt
        wget http://download.geonames.org/export/dump/iso-languagecodes.txt
        unzip allCountries.zip
        unzip alternateNames.zip
        
        # Remove unwanted comment from countryInfo.txt
        grep -v "#" countryInfo.txt > tmp.txt
        mv tmp.txt countryInfo.txt

Next install the gazetteer within RESTo
        
        # 
        # Note : Read this if you are using Fedora, Red Hat Enterprise Linux, CentOS,
        # Scientific Linux, or one of the other distros that enable SELinux by default.
        #
        # SELinux policies for PostgreSQL do not permit the server to read files outside
        # the PostgreSQL data directory, or the file was created by a service covered by
        # a targeted policy so it has a label that PostgreSQL isn't allowed to read from.
        #
        # To make the itagPopulateDB.sh, run the following command as root
        #
        #   setenforce 0
        # 
        # Then after a successful itagPopulateDB.sh relaunch the command
        #
        #   setenforce 1
        #
        $RESTO_HOME/_install/Gazetteer/installGazetteerDB.sh -F -D $GEONAMES_DIR

Note : gazetteer contains more than 9 000 000 of toponyms. Depending on your server performance, the above step can
take a long time (about one hour)


Install wikipedia data (Gazetteer module) - OPTIONAL
----------------------------------------------------

This step is optional and can only be performed if you have the geolocated wikipedia data (which probably you don't have :)
In case of, these are the steps to follow in order to install this database within RESTo

Put the geolocated wikipedia data in $GEONAMES_DIR/wikipedia directory, then run the command

        $RESTO_HOME/_install/Gazetteer/installWikipediaDB.sh -D $GEONAMES_DIR/wikipedia

Deploy application
------------------

Last step is to install application to the target directory. This directory will be accessed
by the web server so it could be either directly under the DocumentRoot web server directory
or in whatever directory accessed through web server Alias configuration. The latter case is prefered
(see Apache configuration part below for Alias configuration)

To install RESTo launch the following script

        # Note : RESTO_TARGET should not exist - it will be created by deploy.sh script
        export RESTO_TARGET=/your/installation/directory
        $RESTO_HOME/_install/deploy.sh -s $RESTO_HOME -t $RESTO_TARGET


Install iTag (optional)
-----------------------

[iTag] (http://github.com/jjrom/itag) is an application to automatically tag geospatial metadata
with geographical information (such as location, landuse, etc.)

RESTo extensively use iTag during the ressource ingestion process. 

If you want to use iTag with RESTo, you should install it (follow the [instructions] (http://github.com/jjrom/itag/)) or use
the [online version of iTag] (http://mapshup.info/itag/?) - see RESTo configuration below


Configuration
=============

Apache Configuration
--------------------

The first thing to do is to configure Apache (or wathever is your web server) to support URL rewriting.

Basically, with URLs rewriting every request sent to RESTo application will end up to index.php. For example,
http://localhost/resto/whatever/youwant/to/access will be rewrite as http://localhost/resto/index.php?RESToURL=/whatever/youwant/to/access


### Check that mod_rewrite is installed

For instance on MacOS X, looks for something like this in /etc/apache2/httpd.conf

        LoadModule rewrite_module libexec/apache2/mod_rewrite.so 

### Configure target directory

Set an alias to the resto directory. To make mod_rewrite works, you need to verify that both 'FollowSymLinks'
and 'AllowOverride All' are set in the apache directory configuration

For instance to access resto at http://localhost/resto (change "/directory/to/resto" by $RESTO_TARGET below):

        Alias /resto/ "/directory/to/resto/"
        <Directory "/directory/to/resto/">
            Options FollowSymLinks
            AllowOverride All
            Order allow,deny
            Allow from all
        </Directory>

### Check "RewriteBase" value within $RESTO_TARGET/.htaccess

Edit this value so it matches your alias name. If you use the same alias as in 2. (i.e. '/resto/')
there is no need to edit $RESTO_TARGET/.htaccess file

### Configure apache to support https (optional)

RESTo can be accessed either in http or https. For security reason, https is prefered when
dealing with authenticated request (e.g. creation of a collection, insertion of a resource in the collection, etc.)

Thus, turning https in apache is optional to make RESTo work.

This document does not explain how to turn https on - but your system administrator should know how to do it !

Note: a step by step guide for installing https on Mac OS X is provided in the FAQ section below

### Restart apache

        apachectl restart


PostgreSQL configuration
------------------------

Note: the following configuration is optional but it is safer from a security point of view to do it like this.

Edit the PostgreSQL pg_hba.conf file and add the following rules :

        # Configuration for RESTo framework
        local  all     resto,sresto                                    password
        host   resto   resto,sresto            127.0.0.1/32            md5
        host   resto   resto,sresto            ::1/128                 md5

Edit the PostreSQL postgresql.conf and be sure that postgres accept tcp_ip connection.

        # Uncomment these two lines within postgesql.conf
        listen_adresses = 'localhost'
        port = 5432

Then restart postgresql (e.g. "pg_ctl restart")

Note : **Read the following if you are using Fedora, Red Hat Enterprise Linux, CentOS, Scientific Linux,
or one of the other distros that enable SELinux by default**

        # 
        #  Enable the specific permission to allow Apache to issue HTTP connections.
        #
        service httpd stop
        service postgresql stop

        setsebool -P httpd_can_network_connect 1

        service httpd start
        service postgresql start

RESTo configuration
-------------------

All configuration parameters are defined within $RESTO_TARGET/resto/resto.ini file

The configuration file is self explanatory. For a standard installation you should only check that :
* The restoUrl points to your RESTo installation webpage
* **db.password** value is **the same as the 'resto' user password set during database installation**
* **db.spassword** value is **the same as the 'sresto' user password set during database installation**
If you do not want to see products on map comment this line.
* (optional) ResourceManager.iTag value is changed accordingly to your configuration. If this line
is commented, then iTag will not be used (i.e. products will not be tagged)

Create an admin user within the database
        
        # Change password !!!
        $PASSWORD=nimda

        psql -d resto << EOF
        INSERT INTO admin.users (email,groups,username,password,activationcode,activated,registrationdate) VALUES ('admin','admin','admin',md5('$PASSWORD'),md5('$PASSWORD' || now()), TRUE, now());
        EOF


masphup configuration
---------------------

Edit $RESTO_TARGET/themes/default/config.js and set c["general"].rootUrl value to $RESTO_TARGET url  

Quick Start
===========

Create a collection
-------------------
        
        $RESTO_HOME/_scripts/createCollection.sh -f $RESTO_HOME/_examples/collections/Example.json -u admin:nimda

Access OpenSearch Description for a collection
----------------------------------------------
Only works for an existing collection (so create a collection first !)

        Open you browser to http://localhost/resto/Example/$describe

Delete a collection
-------------------
WARNING ! This will also destroy all the resources within the collection

        $RESTO_HOME/_scripts/deleteCollection.sh -p -c Example -u admin:nimda

List all collections
--------------------

        Open your browser to http://localhost/resto/

Insert a resource
-----------------
Only works for an existing collection (so create a collection first !)

        $RESTO_HOME/_scripts/postResource.sh -c Example -f $RESTO_HOME/_examples/resources/resource_Example.json -u admin:nimda


Search for resources
--------------------
Only works for an existing collection (so create a collection first !)

        Open your browser to http://localhost/resto/Example/


See resource metadata in Atom
-----------------------------
Only works on an existing resource (so insert resource first !)

        curl "http://localhost/resto/Example/DS_SPOT6_201211101947221_FR1_FR1_FR1_FR1_W152S17_01809/?&format=atom"


See resource metadata in GeoJSON
--------------------------------
Only works on an existing resource (so insert resource first !)

        curl "http://localhost/resto/Example/DS_SPOT6_201211101947221_FR1_FR1_FR1_FR1_W152S17_01809/?&format=json"


Tag a resource
--------------
Only works for an existing resource

        $RESTO_HOME/_scripts/tagResource.sh -c Example -f $RESTO_HOME/_examples/tags/tagging_Example.json -i DS_SPOT6_201211101947221_FR1_FR1_FR1_FR1_W152S17_01809 -u admin:nimda

Tag a collection
----------------
Only works for an existing collection

        $RESTO_HOME/_scripts/tagCollection.sh -c Example -f $RESTO_HOME/_examples/tags/tagging_Example.json -u admin:nimda


Frequently Asked Questions
==========================

How to configure Apache for https ?
-----------------------------------

For [Mac OS X] (http://blog.andyhunt.info/2011/11/26/apache-ssl-on-max-osx-lion-10-7/)

(Warning http://stackoverflow.com/questions/18251128/why-am-i-suddenly-getting-a-blocked-loading-mixed-active-content-issue-in-fire)


For security reasons i cannot POST file through PHP
---------------------------------------------------

You can POST collections descriptions using a "key=value" mechanism instead of file upload.

To do so, you need to encode the json file (using javascript encodeURIComponent for instance) - see $RESTO_HOME/_examples/collections/Example.txt - and run the following command

        curl -X POST -d @$RESTO_HOME/_examples/collections/Example.txt http://admin:nimda@localhost/resto/
