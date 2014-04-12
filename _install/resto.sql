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
    userid              VARCHAR(255) DEFAULT 'anonymous'
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
