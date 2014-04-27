#!/bin/bash
#
# RESTo
# 
#  RESTo - REstful Semantic search Tool for geOspatial 
# 
#  Copyright 2013 Jérôme Gasperi <https://github.com/jjrom>
# 
#  jerome[dot]gasperi[at]gmail[dot]com
#  
#  
#  This software is governed by the CeCILL-B license under French law and
#  abiding by the rules of distribution of free software.  You can  use,
#  modify and/ or redistribute the software under the terms of the CeCILL-B
#  license as circulated by CEA, CNRS and INRIA at the following URL
#  "http://www.cecill.info".
# 
#  As a counterpart to the access to the source code and  rights to copy,
#  modify and redistribute granted by the license, users are provided only
#  with a limited warranty  and the software's author,  the holder of the
#  economic rights,  and the successive licensors  have only  limited
#  liability.
# 
#  In this respect, the user's attention is drawn to the risks associated
#  with loading,  using,  modifying and/or developing or reproducing the
#  software by the user in light of its specific status of free software,
#  that may mean  that it is complicated to manipulate,  and  that  also
#  therefore means  that it is reserved for developers  and  experienced
#  professionals having in-depth computer knowledge. Users are therefore
#  encouraged to load and test the software's suitability as regards their
#  requirements in conditions enabling the security of their systems and/or
#  data to be ensured and,  more generally, to use and operate it in the
#  same conditions as regards security.
# 
#  The fact that you are presently reading this means that you have had
#  knowledge of the CeCILL-B license and that you accept its terms.
#  

# Paths are mandatory from command line
SUPERUSER=postgres
DROPFIRST=NO
DB=resto
USER=resto
ADMIN=sresto
usage="## RESTo database installation\n\n  Usage $0 -d <PostGIS directory> -P <sresto (Read+Write database) user password> -p <resto (Read-Only database) user password> [-s <database SUPERUSER> -F]\n\n  -d : absolute path to the directory containing postgis.sql\n  -s : dabase SUPERUSER (default "postgres")\n  -F : WARNING - suppress existing admin schema within resto database\n"
while getopts "d:s:p:P:hF" options; do
    case $options in
        d ) ROOTDIR=`echo $OPTARG`;;
        s ) SUPERUSER=`echo $OPTARG`;;
        p ) USERPASSWORD=`echo $OPTARG`;;
        P ) ADMINPASSWORD=`echo $OPTARG`;;
        F ) DROPFIRST=YES;;
        h ) echo -e $usage;;
        \? ) echo -e $usage
            exit 1;;
        * ) echo -e $usage
            exit 1;;
    esac
done
if [ "$ROOTDIR" = "" ]
then
    echo -e $usage
    exit 1
fi
if [ "$USERPASSWORD" = "" ]
then
    echo -e $usage
    exit 1
fi
if [ "$ADMINPASSWORD" = "" ]
then
    echo -e $usage
    exit 1
fi

# Example : $ROOTDIR = /usr/local/share/postgis/
postgis=`echo $ROOTDIR/postgis.sql`
projections=`echo $ROOTDIR/spatial_ref_sys.sql`

# Make db POSTGIS compliant
createdb $DB -U $SUPERUSER
createlang -U $SUPERUSER plpgsql $DB
psql -d $DB -U $SUPERUSER -f $postgis
psql -d $DB -U $SUPERUSER -f $projections

###### ADMIN ACCOUNT CREATION ######
psql -U $SUPERUSER -d template1 << EOF
CREATE USER $USER WITH PASSWORD '$USERPASSWORD' NOCREATEDB;
CREATE USER $ADMIN WITH PASSWORD '$ADMINPASSWORD' NOCREATEDB;
EOF

##### DROP SCHEMA FIRST ######
if [ "$DROPFIRST" = "YES" ]
then
psql -d $DB -U $SUPERUSER << EOF
DROP SCHEMA admin CASCADE;
EOF
fi
#############CREATE DB ##############
psql -d $DB -U $SUPERUSER << EOF

-- hstore is used for collection datasources
CREATE EXTENSION hstore;

-- 
-- admin schema contains history and collections tables
--
CREATE SCHEMA admin;

--
-- history table stores all user requests
--
CREATE TABLE admin.history (
    gid                 SERIAL PRIMARY KEY,
    service             VARCHAR(10),
    collection          VARCHAR(50),
    query               TEXT DEFAULT NULL,
    realquery           TEXT DEFAULT NULL,
    querytime           TIMESTAMP,
    url                 TEXT DEFAULT NULL,
    ip                  VARCHAR(15),
    userid              INTEGER DEFAULT -1
);
CREATE INDEX idx_service_history ON admin.history (service);
CREATE INDEX idx_userid_history ON admin.history (userid);


--
-- collections table list all RESTo collections
--
CREATE TABLE admin.collections (
    collection          VARCHAR(50) PRIMARY KEY,
    creationdate        TIMESTAMP,
    controller          VARCHAR(50) DEFAULT 'DefaultController',
    theme               VARCHAR(50),
    status              VARCHAR(10) DEFAULT 'public',
    dbname              VARCHAR(10) DEFAULT 'resto',
    hostname            VARCHAR(50) DEFAULT 'localhost',
    port                VARCHAR(5)  DEFAULT '5432',
    schemaname          VARCHAR(20) NOT NULL,
    tablename           VARCHAR(20) DEFAULT 'products',
    configuration       TEXT
);
CREATE INDEX idx_status_collections ON admin.collections (status);
CREATE INDEX idx_creationdate_collections ON admin.collections (creationdate);

--
-- osdescriptions table describe all RESTo collections
--
CREATE TABLE admin.osdescriptions (
    collection          VARCHAR(50),
    lang                VARCHAR(2),
    shortname           VARCHAR(50),
    longname            VARCHAR(255),
    description         TEXT,
    tags                TEXT,
    developper          VARCHAR(50),
    contact             VARCHAR(50),
    query               VARCHAR(255),
    attribution         VARCHAR(255)
);
ALTER TABLE ONLY admin.osdescriptions ADD CONSTRAINT fk_collection FOREIGN KEY (collection) REFERENCES admin.collections(collection);
ALTER TABLE ONLY admin.osdescriptions ADD CONSTRAINT cl_collection UNIQUE(collection, lang);
CREATE INDEX idx_collection_osdescriptions ON admin.osdescriptions (collection);
CREATE INDEX idx_lang_osdescriptions ON admin.osdescriptions (lang);

--
-- tags table list all tags attached to data within collection
--
CREATE TABLE admin.tags (
    tag                 VARCHAR(50) PRIMARY KEY,
    creationdate        TIMESTAMP,
    updateddate         TIMESTAMP,
    occurence           INTEGER
);
CREATE INDEX idx_updated_tags ON admin.tags (updateddate);

--
-- users table list user informations
--
CREATE TABLE admin.users (
    userid              SERIAL PRIMARY KEY,
    email               VARCHAR(255) UNIQUE,  -- should be an email adress
    groups              TEXT, -- group names are comma separated (first one is the main group)
    username            VARCHAR(50) NOT NULL,
    givenname           VARCHAR(255),
    lastname            VARCHAR(255),
    password            VARCHAR(32) NOT NULL, -- stored as md5
    registrationdate    TIMESTAMP NOT NULL,
    activationcode      VARCHAR(255) NOT NULL UNIQUE, -- activation code store as md5
    activated           BOOLEAN NOT NULL DEFAULT FALSE,              
    lastsessionid       VARCHAR(255)
);
CREATE INDEX idx_email_users ON admin.users (email);

--
-- rights table list user rights on collection
--
CREATE TABLE admin.rights (
    groupid             VARCHAR(255),  -- group name as stored in admin.users groups column
    collection          VARCHAR(50) NOT NULL, -- same as collection in admin.collections
    rights              TEXT -- serialized json representation of services rights
);
EOF

# Rights
psql -U $SUPERUSER -d $DB << EOF

-- READ ONLY rights to resto user
GRANT ALL ON geometry_columns TO $USER;
GRANT ALL ON geography_columns TO $USER;
GRANT SELECT ON spatial_ref_sys TO $USER;

-- READ+WRITE rights to resto admin user
GRANT ALL ON geometry_columns TO $ADMIN;
GRANT ALL ON geography_columns TO $ADMIN;
GRANT SELECT ON spatial_ref_sys TO $ADMIN;
GRANT CREATE ON DATABASE $DB TO $ADMIN;

GRANT ALL ON SCHEMA admin TO $ADMIN;
GRANT SELECT,INSERT,UPDATE ON admin.history TO $ADMIN;
GRANT ALL ON admin.history_gid_seq TO $ADMIN;
GRANT SELECT,INSERT,UPDATE,DELETE ON admin.collections TO $ADMIN;
GRANT SELECT,INSERT,UPDATE,DELETE ON admin.osdescriptions TO $ADMIN;
GRANT SELECT,INSERT,UPDATE,DELETE ON admin.rights TO $ADMIN;
GRANT SELECT,INSERT,UPDATE,DELETE ON admin.users TO $ADMIN;
GRANT ALL ON admin.users_userid_seq TO $ADMIN;
EOF



