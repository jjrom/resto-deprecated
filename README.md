resto
====

RESTo - REstful Semantic search Tool for geOspatial

Requirements
============

* Apache mod_rewrite support
* PHP XMLWriter extension
* PostgreSQL
* PostGIS (version > 1.5)

Installation
============

We suppose that $RESTO_HOME is the directory containing this file

Apache Configuration
--------------------

1. Check that mod_rewrite is installed

For instance on MacOS X, looks for something like this in /etc/apache2/httpd.conf

        LoadModule rewrite_module libexec/apache2/mod_rewrite.so 

2. Configure target directory

Set an alias to the resto directory. To make mod_rewrite works, you need to add a FollowSymLinks options and to AllowOverride All
For instance to access resto at http://localhost/resto :

        Alias /resto/ "/Users/jrom/Devel/resto"
        <Directory "/Users/jrom/Devel/resto">
            Options FollowSymLinks
            AllowOverride All
            Order allow,deny
            Allow from all
        </Directory>

3. Modify "RewriteBase" value within $RESTO_HOME/.htaccess

4. Configure apache to support https (optional)

See http://blog.andyhunt.info/2011/11/26/apache-ssl-on-max-osx-lion-10-7/
Warning http://stackoverflow.com/questions/18251128/why-am-i-suddenly-getting-a-blocked-loading-mixed-active-content-issue-in-fire

4. Restart apache

        apachectl restart

RESTo configuration
-------------------

Edit and modify $RESTO_HOME/resto/resto.ini

How does it works
=================

Quick Start
===========

Create a new collection
-----------------------
        
        $RESTO_HOME/_examples/createCollection.sh -f $RESTO_HOME/_examples/collections/Spot.json -u admin:nimda
