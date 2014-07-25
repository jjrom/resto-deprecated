RESTo database tuning
=====================
    
    Author  -   Jérôme Gasperi
    Date    -   2014.07.19
Description
===========

Hardware/Software used

* MacBook Pro 2.4 GHz Intel Core i7 - 8 Go RAM - SSD Hardrive
* MacOS X 10.9.3 / PostgreSQL 9.3.4 / PostGIS 2.1.3

Table spot.products with unevenly reparted keys within hstore column **keywords**

    select count(*) from spot.products;
     count
    --------
     542256
    
    select count(*) from spot.products where keywords?'landuse:forest';
     count
    --------
     236599
     
    select count(*) from spot.products where keywords?'country:italy';
     count
    -------
      1321
     
    -- Returns keys repartition within hstore column
    SELECT key, count(*) FROM
      (SELECT (each(keywords)).key FROM spot.products) AS stat
      GROUP BY key
      ORDER BY count DESC, key;
  
  
Performance analysis
====================

SQL requests are ordered by *acquisitiondate DESC* (i.e. newer data are displayed first)

Requests with GIN index on *keywords*
------------------------------------

**Indexes** 

* B-TREE index on *acquisitiondate* column (timestamp)
* GIN (or GIST) index on *keywords*

PostgreSQL Query Planner considers that *keywords* index should be used instead of *acquisitiondate* index

**Consequences**

* queries on highly represented keywords (i.e. 'landuse:forest') are significantly slower than queries on less represented keywords (i.e. 'country:italy')

**Queries details**
    
1. Search on 'landuse:forest' - returns first 50 results

        EXPLAIN ANALYZE SELECT * FROM spot.products WHERE keywords?'landuse:forest' ORDER BY acquisitiondate DESC LIMIT 50;
                                          
        Limit  (cost=3239.99..3240.11 rows=50 width=1039) (actual time=355.850..355.859 rows=50 loops=1)
        ->  Sort  (cost=3239.99..3242.02 rows=814 width=1039) (actual time=355.848..355.852 rows=50 loops=1)
                 Sort Key: acquisitiondate
                 Sort Method: top-N heapsort  Memory: 103kB
                 ->  Bitmap Heap Scan on products  (cost=22.59..3212.95 rows=814 width=1039) (actual time=113.190..308.195 rows=236599 loops=1)
                       Recheck Cond: (keywords ? 'landuse:forest'::text)
                       Rows Removed by Index Recheck: 27023
                       ->  Bitmap Index Scan on products_keywords_idx  (cost=0.00..22.39 rows=814 width=0) (actual time=84.710..84.710 rows=263622 loops=1)
                             Index Cond: (keywords ? 'landuse:forest'::text)
         Total runtime: 356.900 ms
        (10 rows)

2. Search on 'landuse:forest' - returns first 500 results

        EXPLAIN ANALYZE SELECT * FROM spot.products WHERE keywords?'landuse:forest' ORDER BY acquisitiondate DESC LIMIT 500;

            Limit  (cost=2178.68..2179.93 rows=500 width=1039) (actual time=355.024..355.108 rows=500 loops=1)
           ->  Sort  (cost=2178.68..2180.03 rows=542 width=1039) (actual time=355.024..355.069 rows=500 loops=1)
                 Sort Key: acquisitiondate
                 Sort Method: top-N heapsort  Memory: 751kB
                 ->  Bitmap Heap Scan on products  (cost=20.49..2154.07 rows=542 width=1039) (actual time=112.049..305.853 rows=236599 loops=1)
                       Recheck Cond: (keywords ? 'landuse:forest'::text)
                       Rows Removed by Index Recheck: 27023
                       ->  Bitmap Index Scan on products_keywords_idx  (cost=0.00..20.35 rows=542 width=0) (actual time=84.019..84.019 rows=263622 loops=1)
                             Index Cond: (keywords ? 'landuse:forest'::text)
         Total runtime: 356.012 ms
        (10 rows)

3. Search on 'country:italy' - returns first 50 results

        EXPLAIN ANALYZE SELECT * FROM spot.products WHERE keywords?'country:italy' ORDER BY acquisitiondate DESC LIMIT 50;

        Limit  (cost=2172.07..2172.20 rows=50 width=1039) (actual time=73.083..73.093 rows=50 loops=1)
           ->  Sort  (cost=2172.07..2173.43 rows=542 width=1039) (actual time=73.081..73.088 rows=50 loops=1)
                 Sort Key: acquisitiondate
                 Sort Method: top-N heapsort  Memory: 103kB
                 ->  Bitmap Heap Scan on products  (cost=20.49..2154.07 rows=542 width=1039) (actual time=41.110..72.637 rows=1321 loops=1)
                       Recheck Cond: (keywords ? 'country:italy'::text)
                       Rows Removed by Index Recheck: 29260
                       ->  Bitmap Index Scan on products_keywords_idx  (cost=0.00..20.35 rows=542 width=0) (actual time=35.802..35.802 rows=30581 loops=1)
                             Index Cond: (keywords ? 'country:italy'::text)
         Total runtime: 73.190 ms
        (10 rows)

4. Search on 'county:italy' - returns first 500 results

        EXPLAIN ANALYZE SELECT * FROM spot.products WHERE keywords?'country:italy' ORDER BY acquisitiondate DESC LIMIT 500;

        Limit  (cost=2178.68..2179.93 rows=500 width=1039) (actual time=73.183..73.288 rows=500 loops=1)
           ->  Sort  (cost=2178.68..2180.03 rows=542 width=1039) (actual time=73.182..73.245 rows=500 loops=1)
                 Sort Key: acquisitiondate
                 Sort Method: top-N heapsort  Memory: 890kB
                 ->  Bitmap Heap Scan on products  (cost=20.49..2154.07 rows=542 width=1039) (actual time=40.178..72.129 rows=1321 loops=1)
                       Recheck Cond: (keywords ? 'country:italy'::text)
                       Rows Removed by Index Recheck: 29260
                       ->  Bitmap Index Scan on products_keywords_idx  (cost=0.00..20.35 rows=542 width=0) (actual time=34.804..34.804 rows=30581 loops=1)
                             Index Cond: (keywords ? 'country:italy'::text)
         Total runtime: 73.376 ms
        (10 rows)

Requests without index on *keywords*
------------------------------------

**Indexes** 

* B-TREE index on *acquisitiondate* column (timestamp)

In this case, PostgreSQL Query Planner uses *acquisitiondate* index for low LIMIT queries and a sequential scan for high LIMIT queries

**Consequences**

* **for highly represented keywords (i.e. 'landuse:forest')**, queries with low LIMIT are **much more** faster than equivalent queries with **keywords** index

* **for less represented keywords (i.e. 'country:italy')**, queries with low LIMIT are a bit faster than equivalent queries with **keywords** index

* queries with high LIMIT are **much much slower** than equivalent queries with ** keywords** index

**Queries details**

1. Search on 'landuse:forest' - returns first 50 results

        EXPLAIN ANALYZE SELECT * FROM spot.products WHERE keywords?'landuse:forest' ORDER BY acquisitiondate DESC LIMIT 50;

         Limit  (cost=0.42..177932.52 rows=50 width=1039) (actual time=0.023..0.275 rows=50 loops=1)
           ->  Index Scan Backward using products_acquisitiondate_idx on products  (cost=0.42..2896734.91 rows=814 width=1039) (actual time=0.022..0.267 rows=50 loops=1)
                 Filter: (keywords ? 'landuse:forest'::text)
                 Rows Removed by Filter: 125
         Total runtime: 0.306 ms
        (5 rows)
        
2. Search on 'landuse:forest' - returns first 500 results
        
        EXPLAIN ANALYZE SELECT * FROM spot.products WHERE keywords?'landuse:forest' ORDER BY acquisitiondate DESC LIMIT 500;

        Limit  (cost=851217.81..851219.06 rows=500 width=1039) (actual time=26241.741..26241.844 rows=500 loops=1)
           ->  Sort  (cost=851217.81..851219.17 rows=542 width=1039) (actual time=26241.739..26241.796 rows=500 loops=1)
                 Sort Key: acquisitiondate
                 Sort Method: top-N heapsort  Memory: 751kB
                 ->  Seq Scan on products  (cost=0.00..851193.20 rows=542 width=1039) (actual time=145.204..26143.686 rows=236599 loops=1)
                       Filter: (keywords ? 'landuse:forest'::text)
                       Rows Removed by Filter: 305657
         Total runtime: 26241.909 ms
        (8 rows)

3. Search on 'country:italy' - returns first 50 results
        
        EXPLAIN ANALYZE SELECT * FROM spot.products WHERE keywords?'country:italy' ORDER BY acquisitiondate DESC LIMIT 50;

         Limit  (cost=0.42..181936.80 rows=50 width=1039) (actual time=0.249..23.650 rows=50 loops=1)
           ->  Index Scan Backward using products_acquisitiondate_idx on products  (cost=0.42..1972190.69 rows=542 width=1039) (actual time=0.248..23.636 rows=50 loops=1)
                 Filter: (keywords ? 'country:italy'::text)
                 Rows Removed by Filter: 20481
         Total runtime: 23.685 ms
        (5 rows)

4. Search on 'country:italy' - returns first 500 results
    
        EXPLAIN ANALYZE SELECT * FROM spot.products WHERE keywords?'country:italy' ORDER BY acquisitiondate DESC LIMIT 500;

         Limit  (cost=851217.81..851219.06 rows=500 width=1039) (actual time=27851.612..27851.752 rows=500 loops=1)
        ->  Sort  (cost=851217.81..851219.17 rows=542 width=1039) (actual time=27851.247..27851.321 rows=500 loops=1)
         Sort Key: acquisitiondate
         Sort Method: top-N heapsort  Memory: 890kB
         ->  Seq Scan on products  (cost=0.00..851193.20 rows=542 width=1039) (actual time=9097.935..27843.859 rows=1321 loops=1)
               Filter: (keywords ? 'country:italy'::text)
               Rows Removed by Filter: 540935
        Total runtime: 27853.855 ms
        (8 rows)
        
Conclusion
----------

* GIN (or GIST) index on **keywords** is necessary for lowly represented keys
* GIN (or GIST) index should not be used for highly represented keys with low LIMIT

Solution
--------

Extract highly represented keys from **keywords** column and to add a dedicated column for each of them i.e.
    
    -- -------------------------------------------
    --    LANDUSE
    -- -------------------------------------------

    -- CREATE new columns for landuse
    ALTER TABLE spot.products ADD COLUMN lu_cultivated NUMERIC;
    ALTER TABLE spot.products ADD COLUMN lu_desert NUMERIC;
    ALTER TABLE spot.products ADD COLUMN lu_flooded NUMERIC;
    ALTER TABLE spot.products ADD COLUMN lu_forest NUMERIC;
    ALTER TABLE spot.products ADD COLUMN lu_herbaceous NUMERIC;
    ALTER TABLE spot.products ADD COLUMN lu_snow NUMERIC;
    ALTER TABLE spot.products ADD COLUMN lu_urban NUMERIC;
    ALTER TABLE spot.products ADD COLUMN lu_water NUMERIC;
    
    -- UPDATE newly created columns with hstore values
    UPDATE spot.products SET lu_cultivated = (keywords->'landuse:cultivated')::numeric,
                             lu_desert = (keywords->'landuse:desert')::numeric,
                             lu_flooded = (keywords->'landuse:flooded')::numeric,
                             lu_forest = (keywords->'landuse:forest')::numeric,
                             lu_herbaceous = (keywords->'landuse:herbaceous')::numeric,
                             lu_snow = (keywords->'landuse:snow and ice')::numeric,
                             lu_urban = (keywords->'landuse:urban')::numeric,
                             lu_water = (keywords->'landuse:water')::numeric;
    
    -- SET NULL VALUE TO 0
    UPDATE spot.products SET lu_cultivated = 0 where lu_cultivated IS NULL;
    UPDATE spot.products SET lu_desert = 0 where lu_desert IS NULL;
    UPDATE spot.products SET lu_flooded = 0 where lu_flooded IS NULL;
    UPDATE spot.products SET lu_forest = 0 where lu_forest IS NULL;
    UPDATE spot.products SET lu_herbaceous = 0 where lu_herbaceous IS NULL;
    UPDATE spot.products SET lu_snow = 0 where lu_snow IS NULL;
    UPDATE spot.products SET lu_urban = 0 where lu_urban IS NULL;
    UPDATE spot.products SET lu_water = 0 where lu_water IS NULL;

    CREATE INDEX products_lu_cultivated_idx ON spot.products USING btree(lu_cultivated);
    CREATE INDEX products_lu_desert_idx ON spot.products USING btree(lu_desert);
    CREATE INDEX products_lu_flooded_idx ON spot.products USING btree(lu_flooded);
    CREATE INDEX products_lu_forest_idx ON spot.products USING btree(lu_forest);
    CREATE INDEX products_lu_herbaceous_idx ON spot.products USING btree(lu_herbaceous);
    CREATE INDEX products_lu_snow_idx ON spot.products USING btree(lu_snow);
    CREATE INDEX products_lu_urban_idx ON spot.products USING btree(lu_urban);
    CREATE INDEX products_lu_water_idx ON spot.products USING btree(lu_water);
    
    -- -------------------------------------------
    --    CONTINENT and COUNTRY
    -- -------------------------------------------

    -- CREATE new array columns for continent and country
    ALTER TABLE spot.products ADD COLUMN lo_continents text[];
    ALTER TABLE spot.products ADD COLUMN lo_countries text[];

    CREATE INDEX products_lo_continents_idx ON spot.products USING GIN (lo_continents);
    CREATE INDEX products_lo_countries_idx ON spot.products USING GIN (lo_countries);

    # Launch _install/admin/hstore2Array.php script to populate countries and continents

**Queries details**

1. Search on 'landuse:forest' - returns first 50 results

        EXPLAIN ANALYZE SELECT * FROM spot.products WHERE lu_forest > 0 ORDER BY acquisitiondate DESC LIMIT 50;
        
        Limit  (cost=0.42..420.62 rows=50 width=1037) (actual time=0.028..0.257 rows=50 loops=1)
           ->  Index Scan Backward using products_acquisitiondate_idx on products  (cost=0.42..1969666.45 rows=234375 width=1037) (actual time=0.024..0.248 rows=50 loops=1)
                 Filter: (lu_forest > 0::numeric)
                 Rows Removed by Filter: 125
         Total runtime: 0.901 ms
        (5 rows)

2. Search on 'landuse:forest' - returns first 500 results

        EXPLAIN ANALYZE SELECT * FROM spot.products WHERE lu_forest > 0 ORDER BY acquisitiondate DESC LIMIT 500;

        Limit  (cost=0.42..4202.38 rows=500 width=1037) (actual time=0.015..1.640 rows=500 loops=1)
           ->  Index Scan Backward using products_acquisitiondate_idx on products  (cost=0.42..1969666.45 rows=234375 width=1037) (actual time=0.014..1.569 rows=500 loops=1)
                 Filter: (lu_forest > 0::numeric)
                 Rows Removed by Filter: 710
         Total runtime: 1.700 ms
        (5 rows)

3. Search on 'country:italy' - returns first 50 results

        EXPLAIN ANALYZE SELECT * FROM spot.products WHERE lo_countries @> '{italy}' ORDER BY acquisitiondate DESC LIMIT 50;

        Limit  (cost=8432.56..8432.68 rows=50 width=1110) (actual time=4.773..4.785 rows=50 loops=1)
           ->  Sort  (cost=8432.56..8437.95 rows=2158 width=1110) (actual time=4.773..4.777 rows=50 loops=1)
                 Sort Key: acquisitiondate
                 Sort Method: top-N heapsort  Memory: 114kB
                 ->  Bitmap Heap Scan on products  (cost=36.72..8360.87 rows=2158 width=1110) (actual time=0.725..3.064 rows=1321 loops=1)
                       Recheck Cond: (lo_countries @> '{italy}'::text[])
                       ->  Bitmap Index Scan on products_lo_countries_idx  (cost=0.00..36.18 rows=2158 width=0) (actual time=0.529..0.529 rows=1321 loops=1)
                             Index Cond: (lo_countries @> '{italy}'::text[])
         Total runtime: 4.858 ms
        (9 rows)

4. Search on 'country:italy' - returns first 500 results

        EXPLAIN ANALYZE SELECT * FROM spot.products WHERE lo_countries @> '{italy}' ORDER BY acquisitiondate DESC LIMIT 500;
        
        Limit  (cost=8468.40..8469.65 rows=500 width=1110) (actual time=4.818..4.926 rows=500 loops=1)
           ->  Sort  (cost=8468.40..8473.80 rows=2158 width=1110) (actual time=4.817..4.870 rows=500 loops=1)
                 Sort Key: acquisitiondate
                 Sort Method: top-N heapsort  Memory: 969kB
                 ->  Bitmap Heap Scan on products  (cost=36.72..8360.87 rows=2158 width=1110) (actual time=0.540..2.703 rows=1321 loops=1)
                       Recheck Cond: (lo_countries @> '{italy}'::text[])
                       ->  Bitmap Index Scan on products_lo_countries_idx  (cost=0.00..36.18 rows=2158 width=0) (actual time=0.344..0.344 rows=1321 loops=1)
                             Index Cond: (lo_countries @> '{italy}'::text[])
         Total runtime: 5.288 ms
        (9 rows)
        
5. Search on 'continent:asia' - returns first 50 results

        EXPLAIN ANALYZE SELECT * FROM spot.products WHERE lo_continents @> '{asia}' ORDER BY acquisitiondate DESC LIMIT 50;

        Limit  (cost=0.42..773.45 rows=50 width=1107) (actual time=1.331..1.675 rows=50 loops=1)
        ->  Index Scan Backward using products_acquisitiondate_idx on products  (cost=0.42..2933769.03 rows=189758 width=1107) (actual time=1.330..1.668 rows=50 loops=1)
              Filter: (lo_continents @> '{asia}'::text[])
              Rows Removed by Filter: 1087
        Total runtime: 1.731 ms
        (5 rows)

6. Search on 'continent:asia' - returns first 500 results

        Limit  (cost=0.42..7730.71 rows=500 width=1107) (actual time=1.306..4.729 rows=500 loops=1)
          ->  Index Scan Backward using products_acquisitiondate_idx on products  (cost=0.42..2933769.03 rows=189758 width=1107) (actual time=1.304..4.663 rows=500 loops=1)
                Filter: (lo_continents @> '{asia}'::text[])
                Rows Removed by Filter: 2362
        Total runtime: 4.802 ms
        (5 rows)

