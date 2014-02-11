/*
 * RESTo
 * 
 * RESTo - REstful Semantic search Tool for geOspatial 
 * 
 * Copyright 2013 Jérôme Gasperi <https://github.com/jjrom>
 * 
 * jerome[dot]gasperi[at]gmail[dot]com
 * 
 * 
 * This software is governed by the CeCILL-B license under French law and
 * abiding by the rules of distribution of free software.  You can  use,
 * modify and/ or redistribute the software under the terms of the CeCILL-B
 * license as circulated by CEA, CNRS and INRIA at the following URL
 * "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and  rights to copy,
 * modify and redistribute granted by the license, users are provided only
 * with a limited warranty  and the software's author,  the holder of the
 * economic rights,  and the successive licensors  have only  limited
 * liability.
 *
 * In this respect, the user's attention is drawn to the risks associated
 * with loading,  using,  modifying and/or developing or reproducing the
 * software by the user in light of its specific status of free software,
 * that may mean  that it is complicated to manipulate,  and  that  also
 * therefore means  that it is reserved for developers  and  experienced
 * professionals having in-depth computer knowledge. Users are therefore
 * encouraged to load and test the software's suitability as regards their
 * requirements in conditions enabling the security of their systems and/or
 * data to be ensured and,  more generally, to use and operate it in the
 * same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL-B license and that you accept its terms.
 * 
 */
(function(window, navigator) {

    /**
     * Create RESTo javascript object
     */
    window.R = {
        VERSION_NUMBER: 'RESTo 1.0',
        /*
         * Translation array
         */
        translation: {},
        /*
         * RESTO URL
         */
        restoUrl: null,
        /*
         * Initialize RESTo
         * 
         * @param {Object} options
         */
        init: function(options) {

            var timer, self = this;

            options = options || {};

            this.translation = options.translation || {};
            this.restoUrl = options.restoUrl;

            if (options.language) {
                $('#language-' + options.language).addClass('resto-red');
            }

            /*
             * mapshup is defined
             */
            if (window.M) {

                /*
                 * mapshup bug ?
                 * Force map size refresh when user scroll RESTo page
                 */
                $('#resto-container').bind('scroll', function() {
                    clearTimeout(timer);
                    timer = setTimeout(function() {
                        window.M.events.trigger('resizeend');
                    }, 150);
                });

                /*
                 * Display GeoJSON data within mapshup on startup
                 * 
                 * Note : setInterval function is needed to ensure that mapshup map
                 * is loaded before sending the GeoJSON feed
                 */
                if (options.data) {
                    var fct = setInterval(function() {
                        if (window.M.Map.map && window.M.isLoaded) {
                            window.M.Map.addLayer({
                                title: options.data.query ? options.data.query.original.searchTerms : '',
                                type: 'GeoJSON',
                                clusterized: false,
                                data: options.data,
                                zoomOnNew: true,
                                MID: '__resto__'
                            },
                            {
                                noDeletionCheck: true
                            });
                            clearInterval(fct);
                        }
                    }, 500);
                }
            }

            /*
             * State change - Ajax call to RESTo backend server
             */
            window.History.Adapter.bind(window, 'statechange', function() {
                self.showMask();
                $.ajax({
                    url: window.History.getState().cleanUrl.replace('format=html', 'format=json'), // Be sure that json is called !
                    async: true,
                    dataType: 'json',
                    success: function(json) {
                        self.hideMask();
                        self.updatePage(json, true);
                    },
                    error: function(e) {
                        self.hideMask();
                        // TODO
                    }
                });
            });

            // Set About menu action
            $('#_about').click(function() {
                alert("Work in progress. For more info contact jerome[dot]gasperi[at]gmail[dot]com");
            });

            /*
             * Update searchForm input
             */
            $("#resto-searchform").submit(function(e) {
                e.preventDefault();
                window.History.pushState(null, null, '?' + $(this).serialize());
            });

            // Set language actions
            $('.language').click(function() {
                window.location = $(this).attr('href');
            });

            /*
             * Initialize page with no mapshup refresh
             */
            self.updatePage(options.data, false);

        },
        /**
         * Update getCollection page
         * 
         * @param {array} json
         * @param {boolean} updateMapshup - true to update mapshup
         * 
         */
        updatePage: function(json, updateMapshup) {

            var i, l, thumbnail, quicklook, feature, metadata, keywords, $content, key, foundFilters = [], self = this;

            json = json || {};

            /*
             * Update mapshup view
             */
            if (window.M && updateMapshup) {

                /*
                 * Layer already exist => reload content
                 */
                var layer = window.M.Map.Util.getLayerByMID('__resto__');
                if (layer) {

                    // Remove old features
                    layer.destroyFeatures();

                    // Load new features
                    window.M.Map.layerTypes['GeoJSON'].load({
                        data: json,
                        layerDescription: layer['_M'].layerDescription,
                        layer: layer,
                        zoomOnNew: true
                    });

                }
                else {
                    window.M.Map.addLayer({
                        type: 'GeoJSON',
                        clusterized: false,
                        data: json,
                        zoomOnNew: true,
                        MID: '__resto__'
                    },
                    {
                        noDeletionCheck: true
                    });
                }
            }

            /*
             * Update search input form
             */
            $('#search').val(json.query ? json.query.original.searchTerms : '');

            /*
             * Update query analysis result
             */
            if (json.query && json.query.real) {
                for (key in json.query.real) {
                    if (json.query.real[key]) {
                        if (key !== 'language') {
                            foundFilters.push('<b>' + key + '</b> ' + json.query.real[key]);
                        }
                    }
                }

                if (foundFilters.length > 0) {
                    $('.resto-queryanalyze').html('<div class="resto-query">' + this.translate('_query', [foundFilters.concat()]) + '</div>');
                }
                else {
                    $('.resto-queryanalyze').html('<div class="resto-query"><span class="resto-warning">' + this.translate('_notUnderstood') + '</span></div>');
                }
            }
            else if (json.missing) {
                $('.resto-queryanalyze').html('<div class="resto-query"><span class="resto-warning">Missing mandatory search filters - ' + json.missing.concat() + '</span></div>');
            }

            /*
             * Update pagination
             */
            var first = '', previous = '', next = '', last = '', pagination = '';

            if (json.missing) {
                pagination = '';
            }
            else if (json.totalResults === 0) {
                pagination = this.translate('_noResult');
            }
            else {

                if (json.links) {
                    if (json.links.first) {
                        first = ' <a class="resto-link resto-ajaxified" href="' + this.updateURL(json.links.first, {format: 'html'}) + '">' + this.translate('_firstPage') + '</a> ';
                    }
                    if (json.links.previous) {
                        previous = ' <a class="resto-link resto-ajaxified" href="' + this.updateURL(json.links.previous, {format: 'html'}) + '">' + this.translate('_previousPage') + '</a> ';
                    }
                    if (json.links.next) {
                        next = ' <a class="resto-link resto-ajaxified" href="' + this.updateURL(json.links.next, {format: 'html'}) + '">' + this.translate('_nextPage') + '</a> ';
                    }
                    if (json.links.last) {
                        last = ' <a class="resto-link resto-ajaxified" href="' + this.updateURL(json.links.last, {format: 'html'}) + '">' + this.translate('_lastPage') + '</a> ';
                    }

                }

                if (json.totalResults === 1) {
                    pagination += this.translate('_oneResult', [json.totalResults]);
                }
                else if (json.totalResults > 1) {
                    pagination += this.translate('_multipleResult', [json.totalResults]);
                }

                pagination += json.startIndex ? '&nbsp;|&nbsp;' + first + previous + this.translate('_pagination', [json.startIndex, json.lastIndex]) + next + last : '';

            }

            /*
             * Update each pagination element
             */
            $('.resto-pagination').each(function() {
                $(this).html(pagination);
            });

            /*
             * Iterate on features and update result container
             */
            $content = $('.resto-result .resto-content').empty();
            for (i = 0, l = json.features.length; i < l; i++) {

                feature = json.features[i];

                /*
                 * Quicklook and thumbnail
                 */
                thumbnail = feature.properties['thumbnail'];
                quicklook = feature.properties['quicklook'];
                if (!thumbnail && quicklook) {
                    thumbnail = quicklook;
                }
                else if (!thumbnail && !quicklook) {
                    thumbnail = self.restoUrl + '/css/default/img/noimage.png';
                }
                if (!quicklook) {
                    quicklook = thumbnail;
                }


                /*
                 * Display structure
                 *  
                 *  <div class="entry" id="">
                 *      <span class="thumbnail/>
                 *      <span class="map"/>
                 *      <div class="metadata">
                 *          ...
                 *      </div>
                 *      <div class="keywords">
                 *          ...
                 *      </div>
                 *  </div>
                 * 
                 */
                $content.append('<div class="resto-entry" style="clear:both;" id="rid' + i + '"><span class="resto-thumbnail"><a href="' + thumbnail + '" class="resto-link resto-quicklook" title="' + feature.properties['identifier'] + '"><img class="resto-image" src="' + thumbnail + '"/></a></span><span class="resto-map" id="idmap' + i + '"></span><div class="resto-metadata"></div><div class="resto-keywords"></div></div>');

                /*
                 * Preview map
                 */
                self.createPreviewMap('idmap' + i, feature['geometry']);

                /*
                 * Metadata
                 */
                $('.resto-metadata', $('#rid' + i)).html('<p><b>' + feature.properties['platform'] + (feature.properties['platform'] && feature.properties['instrument'] ? "/" + feature.properties['instrument'] : "") + '</b> ' + self.translate('_acquiredOn', [feature.properties['startDate']]) + '</p>');
                metadata = '<p class="resto-tabbed-left resto-small"><b>' + self.translate('_identifier') + '</b> : <span title="' + feature.properties['identifier'] + '">' + self.stripOGCURN(feature.properties['identifier']) + '</span><br/>';
                if (feature.properties['resolution']) {
                    metadata += '<b>' + self.translate('_resolution') + '</b> : ' + feature.properties['resolution'] + ' m<br/>';
                }
                if (feature.properties['startDate']) {
                    metadata += '<b>' + self.translate('_startDate') + '</b> : ' + feature.properties['startDate'] + '<br/>';
                }
                if (feature.properties['completionDate']) {
                    metadata += '<b>' + self.translate('_completionDate') + '</b> : ' + feature.properties['completionDate'] + '<br/>';
                }
                metadata += self.translate('_viewMetadata', ['<a class="resto-link" href="' + self.updateURL(feature.properties['self'], {format: 'html'}) + '">HTML</a> | <a class="resto-link" href="' + self.updateURL(feature.properties['self'], {format: 'atom'}) + '">ATOM</a> | <a class="resto-link" href="' + self.updateURL(feature.properties['self'], {format: 'json'}) + '">GeoJSON</a></li></p><p>']);

                if (feature.properties['services']) {
                    if (feature.properties['services']['download'] && feature.properties['services']['download']['url']) {
                        metadata += '&nbsp;&nbsp;<a class="resto-link" href="' + feature.properties['services']['download']['url'] + '"' + (feature.properties['services']['download']['mimeType'] === 'text/html' ? ' target="_blank"' : '') + '>' + self.translate('_download') + '</a>';
                    }
                    if (feature.properties['services']['browse'] && feature.properties['services']['browse']['layer']) {
                        if (window.M) {
                            message = {
                                title: feature.properties['identifier'],
                                type: feature.properties['services']['browse']['layer']['type'],
                                layers: feature.properties['services']['browse']['layer']['layers'],
                                url: feature.properties['services']['browse']['layer']['url'].replace('%5C', ''),
                                zoomOnNew: true
                            };
                            metadata += '&nbsp;&nbsp;<a class="resto-link resto-addLayer" data="' + encodeURI(JSON.stringify(message)) + '" href="#">' + self.translate('_viewMapshupFullResolution') + '</a>';
                        }
                    }
                }

                metadata += '</p></p>'; // End of metatada
                $('.resto-metadata', $('#rid' + i)).append(metadata);

                /*
                 * Keywords
                 */
                if (feature.properties.keywords) {
                    keywords = '';
                    for (key in feature.properties.keywords) {
                        keywords += '<a href="' + this.updateURL(feature.properties.keywords[key]['url'], {format: 'html'}) + '" class="resto-link resto-ajaxified resto-keyword resto-keyword-' + feature.properties.keywords[key]['type'].replace(' ', '') + '">' + key + '</a> ';
                    }
                    $('.resto-keywords', $('#rid' + i)).html(keywords);
                }


            }

            /*
             * Set fancybox for quicklooks
             */
            $('a.resto-quicklook').fancybox({
                type: "image"
            });

            /*
             * Click on ajaxified element call href url through Ajax
             */
            $('.resto-ajaxified').each(function() {
                $(this).click(function(e) {
                    e.preventDefault();
                    window.History.pushState(null, null, $(this).attr('href'));
                });
            });

            /*
             * Click on postToMapshup element send request to mapshup
             */
            $('.resto-addLayer').click(function(e) {
                e.preventDefault();
                self.addLayer($(this).attr('data'));
            });

        },
        /**
         * Show mask overlay (during loading)
         */
        showMask: function() {
            $('<div id="resto-mask-overlay"></div>').appendTo($('body')).css({
                'position': 'absolute',
                'z-index': '1000',
                'top': '0px',
                'left': '0px',
                'background-color': '#777',
                'opacity': 0.7,
                'width': $(document).width(),
                'height': $(document).height()
            }).show();
            $.fancybox.showActivity();
        },
        /**
         * Clear mask overlay
         */
        hideMask: function() {
            $('#resto-mask-overlay').remove();
            $.fancybox.hideActivity();
        },
        /**
         * Create a preview Leaflet map
         * 
         * @param {string} divId div identifier
         * @param {object} geometry GeoJSON geometry to plot
         */
        createPreviewMap: function(divId, geometry) {

            var json, map = L.map(divId, {
                zoomControl: false,
                attributionControl: false
            });

            map.addLayer(new L.TileLayer('http://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png'));

            var feature = {
                type: "Feature",
                properties: {},
                geometry: geometry
            };

            if (geometry.type === "Point" || geometry.type === "MultiPoint") {

                var pointStyle = {
                    radius: 8,
                    fillColor: "#ff7800",
                    color: "#000",
                    weight: 1,
                    opacity: 1,
                    fillOpacity: 0.8
                };

                json = L.geoJson(feature, {
                    pointToLayer: function(feature, latlng) {
                        return L.circleMarker(latlng, pointStyle);
                    }
                }).addTo(map);

                map.setView(json.getBounds().getCenter(), 6);
            }
            else {

                var polygonStyle = {
                    color: "#ff7800",
                    weight: 5,
                    opacity: 0.65
                };

                json = L.geoJson(feature, {style: polygonStyle}).addTo(map);
                map.fitBounds(json.getBounds(), {padding: new L.Point(40, 40)});

            }

        },
        /**
         * Replace {a:1}, {a:2}, etc within str by array values
         * 
         * @param {string} str (e.g. "My name is {a:1} {a:2}")
         * @param {array} values (e.g. ['Jérôme', 'Gasperi'])
         * 
         */
        translate: function(str, values) {

            if (!this.translation || !this.translation[str]) {
                return str;
            }

            var i, l, out = this.translation[str];

            /*
             * Replace additional arguments
             */
            if (values && out.indexOf('{a:') !== -1) {
                for (i = 0, l = values.length; i < l; i++) {
                    out = out.replace('{a:' + (i + 1) + '}', values[i]);
                }
            }

            return out;
        },
        /**
         * Update key/value parameters from url by values
         * 
         * @param {string} url (e.g. 'http://localhost/resto/?format=json)
         * @param {object} values (e.g. {format:'html'})
         * 
         */
        updateURL: function(url, params) {

            var key, value, i, l, sourceParamsList, sourceParams = {}, newParamsString = "", sourceBase = url.split("?")[0];

            try {
                sourceParamsList = url.split("?")[1].split("&");
            }
            catch (e) {
                sourceParamsList = [];
            }
            for (i = 0, l = sourceParamsList.length; i < l; i++) {
                key = sourceParamsList[i].split('=')[0];
                value = sourceParamsList[i].split('=')[1];
                if (key) {
                    sourceParams[key] = value ? value : '';
                }
            }

            for (key in params) {
                sourceParams[key] = params[key];
            }

            for (key in sourceParams) {
                newParamsString += key + "=" + sourceParams[key] + "&";
            }

            return sourceBase + "?" + newParamsString;
        },
        /**
         * Remove OGC URN prefix
         * 
         * @param {string} str
         */
        stripOGCURN: function(str) {
            if (!str) {
                return str;
            }
            return str.replace('urn:ogc:def:EOP:', '');
        },
        /**
         * Post to mapshup
         * 
         * @param {string/object} json
         */
        addLayer: function(json) {

            if (!window.M) {
                return false;
            }

            if (typeof json === 'string') {
                json = JSON.parse(decodeURI(json));
            }

            window.M.Map.addLayer(json, {
                noDeletionCheck: true
            });

            return true;

        }

    };

})(window, navigator);