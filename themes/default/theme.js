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

    window.R = window.R || {};

    /**
     * Update getCollection page
     * 
     * @param {array} json
     * @param {boolean} updateMapshup - true to update mapshup
     * 
     */
    window.R.updatePage = function(json, updateMapshup) {

        var i, l, j, k, alternates, thumbnail, quicklook, feature, metadata, keywords, $content, key, foundFilters = [], self = this;

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
                self.addLayer({
                    type: 'GeoJSON',
                    clusterized: false,
                    data: json,
                    zoomOnNew: true,
                    MID: '__resto__',
                    color: '#FFF1FB',
                    featureInfo: {
                        noMenu: true,
                        onSelect: function(f) {
                            //console.log(f);
                        }
                    }
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

            if ($.isArray(json.links)) {
                for (i = 0, l = json.links.length; i < l; i++) {
                    if (json.links[i]['rel'] === 'first') {
                        first = ' <a class="resto-link resto-ajaxified" href="' + this.updateURL(json.links[i]['href'], {format: 'html'}) + '">' + this.translate('_firstPage') + '</a> ';
                    }
                    if (json.links[i]['rel'] === 'previous') {
                        previous = ' <a class="resto-link resto-ajaxified" href="' + this.updateURL(json.links[i]['href'], {format: 'html'}) + '">' + this.translate('_previousPage') + '</a> ';
                    }
                    if (json.links[i]['rel'] === 'next') {
                        next = ' <a class="resto-link resto-ajaxified" href="' + this.updateURL(json.links[i]['href'], {format: 'html'}) + '">' + this.translate('_nextPage') + '</a> ';
                    }
                    if (json.links[i]['rel'] === 'last') {
                        last = ' <a class="resto-link resto-ajaxified" href="' + this.updateURL(json.links[i]['href'], {format: 'html'}) + '">' + this.translate('_lastPage') + '</a> ';
                    }

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
            $content.append('<div class="resto-entry" style="clear:both;" id="rid' + i + '"><span class="resto-thumbnail"><a href="' + thumbnail + '" class="resto-link resto-quicklook" title="' + feature.id + '"><img class="resto-image" src="' + thumbnail + '"/></a></span><span class="resto-map" id="idmap' + i + '"></span><div class="resto-metadata"></div><div class="resto-keywords"></div></div>');

            /*
             * Preview map
             */
            self.createPreviewMap('idmap' + i, feature['geometry']);

            /*
             * Metadata
             */
            $('.resto-metadata', $('#rid' + i)).html('<p><b>' + feature.properties['platform'] + (feature.properties['platform'] && feature.properties['instrument'] ? "/" + feature.properties['instrument'] : "") + '</b> ' + self.translate('_acquiredOn', [feature.properties['startDate']]) + '</p>');
            metadata = '<p class="resto-tabbed-left resto-small"><b>' + self.translate('_identifier') + '</b> : <span title="' + feature.id + '">' + self.stripOGCURN(feature.id) + '</span><br/>';
            if (feature.properties['resolution']) {
                metadata += '<b>' + self.translate('_resolution') + '</b> : ' + feature.properties['resolution'] + ' m<br/>';
            }
            if (feature.properties['startDate']) {
                metadata += '<b>' + self.translate('_startDate') + '</b> : ' + feature.properties['startDate'] + '<br/>';
            }
            if (feature.properties['completionDate']) {
                metadata += '<b>' + self.translate('_completionDate') + '</b> : ' + feature.properties['completionDate'] + '<br/>';
            }
            alternates = [];
            if ($.isArray(feature.properties['links'])) {
                for (j = 0, k = feature.properties['links'].length; j < k; j++) {
                    alternates.push('<a class="resto-link" href="' + feature.properties['links'][j]['href'] + '" title="' + feature.properties['links'][j]['title'] + ' ">' + self.mimeToType(feature.properties['links'][j]['type']) + '</a>');
                }
            }
            metadata += self.translate('_viewMetadata', [alternates.join(' | ') + '</li></p><p>']);

            if (feature.properties['services']) {
                if (feature.properties['services']['download'] && feature.properties['services']['download']['url']) {
                    metadata += '&nbsp;&nbsp;<a class="resto-link" href="' + feature.properties['services']['download']['url'] + '"' + (feature.properties['services']['download']['mimeType'] === 'text/html' ? ' target="_blank"' : '') + '>' + self.translate('_download') + '</a>';
                }
                if (feature.properties['services']['browse'] && feature.properties['services']['browse']['layer']) {
                    if (window.M) {
                        message = {
                            title: feature.id,
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
                    keywords += '<a href="' + this.updateURL(feature.properties.keywords[key]['href'], {format: 'html'}) + '" class="resto-link resto-ajaxified resto-updatebbox resto-keyword' + (feature.properties.keywords[key]['type'] ? ' resto-keyword-' + feature.properties.keywords[key]['type'].replace(' ', '') : '') + '">' + key + '</a> ';
                }
                $('.resto-keywords', $('#rid' + i)).html(keywords);
            }
        }

        /*
         * Constraint search to map extent
         */
        self.updateBBOX();

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
                window.History.pushState({randomize: window.Math.random()}, null, $(this).attr('href'));
            });
        });

        /*
         * Click on postToMapshup element send request to mapshup
         */
        $('.resto-addLayer').click(function(e) {
            e.preventDefault();
            self.addLayer($(this).attr('data'));
        });

    };

})(window, navigator);
