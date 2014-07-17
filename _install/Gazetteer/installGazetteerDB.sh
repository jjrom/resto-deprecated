#!/bin/bash
#
#  RESTo
#  Install Gazetteer database from geonames
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
usage="## RESTo Gazetteer installation\n\n  Usage $0 -D <data directory> [-d <database name> -s <database SUPERUSER> -F]\n\n  -D : absolute path to the data directory containing geonames data (i.e. allCountries.zip, alternateNames.zip, countryInfo.txt, iso-languagecodes.txt).\n  -s : database SUPERUSER ($SUPERUSER)\n  -d : database name ($DB)\n  -F : drop schema gazetteer first\n"
while getopts "D:d:s:u:hF" options; do
    case $options in
        D ) DATADIR=`echo $OPTARG`;;
        d ) DB=`echo $OPTARG`;;
        s ) SUPERUSER=`echo $OPTARG`;;
        F ) DROPFIRST=YES;;
        h ) echo -e $usage;;
        \? ) echo -e $usage
            exit 1;;
        * ) echo -e $usage
            exit 1;;
    esac
done
if [ "$DATADIR" = "" ]
then
    echo -e $usage
    exit 1
fi

##### DROP SCHEMA FIRST ######
if [ "$DROPFIRST" = "YES" ]
then
psql -d $DB -U $SUPERUSER << EOF
DROP SCHEMA gazetteer CASCADE;
EOF
fi

# ======================================================
## Insert Cities from Geonames
# See https://github.com/colemanm/gazetteer/blob/master/docs/geonames_postgis_import.md
psql -d $DB -U $SUPERUSER << EOF
CREATE schema gazetteer;
CREATE TABLE gazetteer.geoname (
    geonameid int,
    name varchar(200),
    asciiname varchar(200),
    alternatenames varchar(10000),
    latitude float,
    longitude float,
    fclass char(1),
    fcode varchar(10),
    country varchar(2),
    cc2 varchar(60),
    admin1 varchar(20),
    admin2 varchar(80),
    admin3 varchar(20),
    admin4 varchar(20),
    population bigint,
    elevation int,
    gtopo30 int,
    timezone varchar(40),
    moddate date
);
CREATE TABLE gazetteer.alternatename (
    alternatenameId int,
    geonameid int,
    isoLanguage varchar(7),
    alternateName varchar(200),
    isPreferredName boolean,
    isShortName boolean,
    isColloquial boolean,
    isHistoric boolean
 );
CREATE TABLE gazetteer.countryinfo (
    iso_alpha2 char(2),
    iso_alpha3 char(3),
    iso_numeric integer,
    fips_code varchar(3),
    name varchar(200),
    capital varchar(200),
    areainsqkm double precision,
    population integer,
    continent varchar(2),
    tld varchar(10),
    currencycode varchar(3),
    currencyname varchar(20),
    phone varchar(20),
    postalcode varchar(100),
    postalcoderegex varchar(200),
    languages varchar(200),
    geonameId int,
    neighbors varchar(50),
    equivfipscode varchar(3)
);

-- Toponyms (english)
COPY gazetteer.geoname (geonameid,name,asciiname,alternatenames,latitude,longitude,fclass,fcode,country,cc2,admin1,admin2,admin3,admin4,population,elevation,gtopo30,timezone,moddate) FROM '$DATADIR/allCountries.txt' NULL AS '' ENCODING 'UTF8';

-- Toponyms (other languages) 
COPY gazetteer.alternatename (alternatenameid,geonameid,isolanguage,alternatename,ispreferredname,isshortname,iscolloquial,ishistoric) from '$DATADIR/alternateNames.txt' NULL AS '' ENCODING 'UTF8';

-- Countries
COPY gazetteer.countryinfo (iso_alpha2,iso_alpha3,iso_numeric,fips_code,name,capital,areainsqkm,population,continent,tld,currencycode,currencyname,phone,postalcode,postalcoderegex,languages,geonameid,neighbors,equivfipscode) from '$DATADIR/countryInfo.txt' NULL AS '' ENCODING 'UTF8';

-- PostGIS
SELECT AddGeometryColumn ('gazetteer','geoname','geom',4326,'POINT',2);
UPDATE gazetteer.geoname SET geom = ST_PointFromText('POINT(' || longitude || ' ' || latitude || ')', 4326);
CREATE INDEX idx_geoname_geom ON gazetteer.geoname USING gist(geom);

-- Text search
ALTER TABLE gazetteer.geoname ADD COLUMN searchname VARCHAR(200);
UPDATE gazetteer.geoname SET searchname = lower(replace(asciiname, ' ', '-'));
CREATE INDEX idx_geoname_searchname ON gazetteer.geoname (searchname);
CREATE INDEX idx_geoname_country ON gazetteer.geoname (country);
CREATE INDEX idx_fclass_country ON gazetteer.geoname (fclass);

CREATE INDEX idx_alternatename_isolanguage ON gazetteer.alternatename (isolanguage);
DELETE FROM gazetteer.alternatename WHERE isolanguage IS NULL;
ALTER TABLE gazetteer.alternatename ADD COLUMN searchname VARCHAR(200);
UPDATE gazetteer.alternatename SET searchname = lower(replace(alternatename, ' ', '-'));
CREATE INDEX idx_alternatename_searchname ON gazetteer.alternatename (searchname);

-- Constraints
ALTER TABLE ONLY gazetteer.alternatename ADD CONSTRAINT pk_alternatenameid PRIMARY KEY (alternatenameid);
ALTER TABLE ONLY gazetteer.geoname ADD CONSTRAINT pk_geonameid PRIMARY KEY (geonameid);
ALTER TABLE ONLY gazetteer.countryinfo ADD CONSTRAINT pk_iso_alpha2 PRIMARY KEY (iso_alpha2);

-- User rights
GRANT ALL ON SCHEMA gazetteer TO $USER;
GRANT SELECT ON gazetteer.geoname to $USER;
GRANT SELECT ON gazetteer.alternatename to $USER;
GRANT SELECT ON gazetteer.countryinfo to $USER;

EOF
