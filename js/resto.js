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
(function(window) {

    window.R = window.R || {};

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
         * Result layer
         */
        
        layer: null,
        
        /*
         * Initialize RESTo
         * 
         * @param {Object} options
         */
        init: function(options) {

            var timer, self = this;

            options = options || {};

            self.translation = options.translation || {};
            self.restoUrl = options.restoUrl;

            /*
             * mapshup is defined
             */
            if (window.M) {

                /*
                 * Set mapshup-tools
                 */
                self.updateMapshupToolbar();

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
                            self.initSearchLayer(options.data, options.data.query && options.data.query.hasLocation ? true : false);
                            clearInterval(fct);
                        }
                    }, 500);
                }
            }

            /*
             * State change - Ajax call to RESTo backend server
             */
            window.History.Adapter.bind(window, 'statechange', function() {

                // Be sure that json is called !
                var state = window.History.getState(), url = self.updateURL(state.cleanUrl, {format: 'json'});

                self.showMask();

                $.ajax({
                    url: url,
                    async: true,
                    dataType: 'json',
                    success: function(json) {
                        self.hideMask();
                        self.updatePage(json, {
                            updateMap:true,
                            centerMap:(state.data && state.data.centerMap) || (json.query && json.query.hasLocation) ? true : false
                        });
                    },
                    error: function(e) {
                        self.hideMask();
                        self.message("Connection error");
                    }
                });
            });

            /*
             * Update bbox parameter in href attributes of all element with 'resto-updatebbox' class
             */
            if (window.M) {
                var uFct = setInterval(function() {
                    if (window.M.Map.map && window.M.isLoaded) {
                        window.M.Map.events.register("moveend", self, function(map, scope) {
                            scope.updateBBOX();
                        });
                        self.updateBBOX();
                        clearInterval(uFct);
                    }
                }, 500);
            }

            /*
             * Update searchForm input
             */
            $("#resto-searchform").submit(function(e) {
                e.preventDefault();
                /*
                 * Bound search to map view
                 */
                window.History.pushState({randomize: window.Math.random()}, null, '?' + $(this).serialize() + (window.M ? '&box=' + window.M.Map.Util.p2d(M.Map.map.getExtent()).toBBOX() : ''));
            });

            /*
             * Clear button on search bar
             */
            $(document).on('input', '.clearable', function() {
                $(this)[this.value ? 'addClass' : 'removeClass']('x');
            }).on('mousemove', '.x', function(e) {
                $(this)[this.offsetWidth - 18 < e.clientX - this.getBoundingClientRect().left ? 'addClass' : 'removeClass']('onX');
            }).on('click', '.onX', function() {
                $(this).removeClass('x onX').val('');
            });

            /*
             * Set the toolbar actions
             */
            self.updateRestoToolbar();

            /*
             * Initialize page with no mapshup refresh
             */
            self.updatePage(options.data, {
                updateMap:false,
                centerMap:options.data && options.data.query 
            });

            /*
             * Set the admin actions
             */
            self.Admin.updateAdminActions();

            /*
             * Force focus on search input form
             */
            $('#search').focus();

        },
        
        /**
         * Set the RESTo toolbar actions
         */
        updateRestoToolbar: function() {

            var self = this;

            /*
             * Share on facebook
             */
            $('.shareOnFacebook').click(function() {
                window.open('https://www.facebook.com/sharer.php?u=' + encodeURIComponent(window.History.getState().cleanUrl) + '&t=' + encodeURIComponent($('#search').val()));
                return false;
            });

            /*
             * Share to twitter
             */
            $('.shareOnTwitter').click(function() {
                window.open('http://twitter.com/intent/tweet?status=' + encodeURIComponent($('#search').val() + " - " + window.History.getState().cleanUrl));
                /*
                 * TODO use url shortener supporting CORS
                 * 
                 self.showMask();
                 self.ajax({
                 url:'http://tinyurl.com/api-create.php?url=' + encodeURIComponent(window.History.getState().cleanUrl),
                 success: function(txt) {
                 self.hideMask();
                 window.open('http://twitter.com/intent/tweet?status='+encodeURIComponent($('#search').val() + " - " + txt));
                 },
                 error: function(e) {
                 self.hideMask();
                 self.message('Error - cannot share on twitter');
                 }
                 });
                 */
                return false;
            });

            /*
             * Display Atom feed
             */
            $('.displayRSS').click(function() {
                window.location = self.updateURL(window.History.getState().cleanUrl, {format: 'json'});
                return false;
            });

            /*
             * Display profile or login action
             * depending if connected or not 
             */
            self.showMask();
            self.ajax({
                url: self.restoUrl + 'auth.php',
                data:{
                    a:'profile'
                },
                dataType:'json',
                success: function(json) {
                    
                    /*
                     * User is connected from previous session
                     */
                    if (json && json.userid && json.userid !== 'anonymous') {
                        self.showConnected(json);
                    }
                    else {
                        self.hideConnected();
                    }
                    self.hideMask();
                },
                error: function(e) {
                    self.hideMask();
                }
            });
            
            /*
             * Sign in
             */
            $('.signIn').click(function() {
                self.showMask();
                self.ajax({
                    url: self.restoUrl + 'auth.php',
                    dataType:'json',
                    success: function(json) {
                        self.hideMask();
                        window.location.reload();
                    },
                    error: function(e) {
                        self.hideMask();
                        self.message('Error - cannot sign in');
                    }
                });
                return false;
            });
            
            /*
             * Sign in
             */
            $('.signOut').click(function() {
                self.showMask();
                self.ajax({
                    url: self.restoUrl + 'auth.php',
                    data:{
                        a:'disconnect'
                    },
                    dataType:'json',
                    success: function(json) {
                        self.hideMask();
                        window.location.reload();
                    },
                    error: function(e) {
                        self.hideMask();
                        self.message('Error : cannot disconnect');
                    }
                });
                return false;
            });
            
        },
        
        /**
         * Show connection info in toolbar
         * 
         * @param {object} profile
         */
        showConnected: function(profile) {
            $('.signIn').hide();
            $('.signOut').show();
        },
        
        /**
         * Hide connection info in toolbar
         * 
         * @param {object} profile
         */
        hideConnected: function(profile) {
            $('.signIn').show();
            $('.signOut').hide();
        },
        
        /**
         * Show mask overlay (during loading)
         */
        showMask: function() {
            $('<div id="resto-mask-overlay"><span class="fa fa-3x fa-refresh fa-spin"></span></div>').appendTo($('body')).css({
                'position': 'fixed',
                'z-index': '40000',
                'top': '0px',
                'left': '0px',
                'background-color': '#777',
                'opacity': 0.7,
                'color': 'white',
                'text-align': 'center',
                'width': $(window).width(),
                'height': $(window).height(),
                'line-height': $(window).height() + 'px'
            }).show();
        },
        
        /**
         * Clear mask overlay
         */
        hideMask: function() {
            $('#resto-mask-overlay').remove();
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
         * @param {object} params (e.g. {format:'html'})
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
         * Post to mapshup
         * 
         * @param {string/object} json
         */
        addLayer: function(json) {

            if (!window.M) {
                return null;
            }

            if (typeof json === 'string') {
                json = JSON.parse(decodeURI(json));
            }

            return window.M.Map.addLayer(json, {
                noDeletionCheck: true
            });

        },
        /**
         * Initialize search result layer
         */
        initSearchLayer: function(json, centerMap) {
            this.layer = this.addLayer({
                type: 'GeoJSON',
                clusterized: false,
                data: json,
                zoomOnNew: centerMap ? 'always' : false,
                MID: '__resto__',
                color: '#FFF1FB',
                featureInfo: {
                    noMenu: true,
                    onSelect: function(f) {
                        if (f && f.fid) {

                            /*
                             * Unhilite all features before scrolling
                             * to the right one
                             */
                            window.M.Map.featureInfo.unhilite(window.M.Map.featureInfo.hilited);

                            /*
                             * Remove the height of the map to scroll
                             * to the element
                             */
                            var delta = 0;
                            if ($('#mapshup-tools').length > 0) {
                                delta = $('#mapshup-tools').position().top + $('#mapshup-tools').height();
                            }

                            /*
                             * Search for feature in result entries
                             */
                            $('.resto-entry').each(function() {

                                if ($(this).attr('fid') === f.fid) {
                                    $(this).addClass('selected');
                                    $('html, body').animate({
                                        scrollTop: ($(this).offset().top - delta)
                                    }, 500);
                                    return false;
                                }

                            });

                        }
                    },
                    onUnselect: function(f) {
                        $('.resto-entry').each(function() {
                            $(this).removeClass('selected');
                        });
                    }
                }
            });

        },
        /**
         * Return type from mimeType
         * 
         * @param {string} mimeType
         */
        mimeToType: function(mimeType) {
            switch (mimeType) {
                case 'application/json':
                    return 'GeoJSON';
                    break;
                case 'application/atom+xml':
                    return 'ATOM';
                    break;
                case 'text/html':
                    return 'HTML';
                    break;
                default:
                    return mimeType;
            }
        },
        /**
         * Add map bounding box in EPSG:4326 to all element with a 'resto-updatebbox' class
         */
        updateBBOX: function() {
            var box;
            if (window.M && window.M.Map.map) {
                box = window.M.Map.Util.p2d(window.M.Map.map.getExtent()).toBBOX();
                $('.resto-updatebbox').each(function() {
                    $(this).attr('href', window.M.Util.extendUrl($(this).attr('href'), {
                        box: box
                    }));
                });
            }
        },
        /**
         * Return textual resolution from value in meters
         * 
         * @param {integer} value
         */
        getResolution: function(value) {

            if (!$.isNumeric(value)) {
                return null;
            }

            if (value <= 2.5) {
                return 'THR';
            }

            if (value > 2.5 && value <= 30) {
                return 'HR';
            }

            if (value > 30 && value <= 500) {
                return 'MR';
            }

            return 'LR';

        },
        
        /**
         * Update getCollection page
         * 
         * @param {array} json
         * @param {boolean} options 
         *          {
         *              updateMap: // true to update map content
         *              centerMap: // true to center map on content
         *          }
         * 
         */
        updatePage: function(json, options) {

            var foundFilters, key, self = this;

            json = json || {};
            options = options || {};
            
            /*
             * Update mapshup view
             */
            if (window.M && options.updateMap) {

                /*
                 * Layer already exist => reload content
                 * i.e. remove old features and insert new ones
                 */
                if (self.layer) {
                    self.layer.destroyFeatures();
                    window.M.Map.layerTypes['GeoJSON'].load({
                        data: json,
                        layerDescription: self.layer['_M'].layerDescription,
                        layer: self.layer,
                        zoomOnNew: options.centerMap ? 'always' : false
                    });
                }
                /*
                 * Layer does not exist => create it
                 */
                else {
                    self.initSearchLayer(json, options.centerMap);
                }
            }

            /*
             * Update search input form
             */
            if ($('#search').length > 0) {
                $('#search').val(json.query ? json.query.original.searchTerms : '');
            }

            /*
             * Update query analysis result
             */
            if (json.query && json.query.real) {
                foundFilters = "";
                for (key in json.query.real) {
                    if (json.query.real[key]) {
                        if (key !== 'language') {
                            foundFilters += '<b>' + key + '</b> ' + json.query.real[key] + '</br>';
                        }
                    }
                }
                if (foundFilters) {
                    $('.resto-queryanalyze').html('<div class="resto-query">' + foundFilters + '</div>');
                }
                else {
                    $('.resto-queryanalyze').html('<div class="resto-query"><span class="resto-warning">' + self.translate('_notUnderstood') + '</span></div>');
                }
            }
            else if (json.missing) {
                $('.resto-queryanalyze').html('<div class="resto-query"><span class="resto-warning">Missing mandatory search filters - ' + json.missing.concat() + '</span></div>');
            }

            /*
             * Update result
             */
            self.updateResultEntries(json);

            /*
             * Constraint search to map extent
             */
            self.updateBBOX();

            /*
             * Set swipebox for quicklooks
             */
            $('a.resto-quicklook').swipebox();

            /*
             * Click on ajaxified element call href url through Ajax
             */
            $('.resto-ajaxified').each(function() {
                $(this).click(function(e) {
                    e.preventDefault();
                    window.History.pushState({
                        randomize: window.Math.random(),
                        centerMap:$(this).hasClass('centerMap')
                    }, null, $(this).attr('href'));
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
         * Set mapshup toolbar
         */
        updateMapshupToolbar: function() {

            var self = this, $tools = $('#mapshup-tools');

            $('ul', $tools.html('<ul></ul>'))
                    .append('<li class="zoom fa fa-search-plus" title="' + self.translate('_zoom') + '"></li>')
                    .append('<li class="unZoom fa fa-search-minus" title="' + self.translate('_unZoom') + '"></li>')
                    .append('<li class="centerOnLayer fa fa-bullseye" title="' + self.translate('_centerOnLayer') + '"></li>')
                    .append('<li class="globalView fa fa-globe" title="' + self.translate('_globalMapView') + '"></li>')
                    .append('<li class="hideLayer fa fa-eye" title="' + self.translate('_hideLayer') + '"></li>');

            /*
             * Zoom
             */
            $('.zoom', $tools).click(function(e) {
                e.preventDefault();
                window.M.Map.map.setCenter(window.M.Map.map.getCenter(), window.M.Map.map.getZoom() + 1);
            });

            /*
             * unZoom
             */
            $('.unZoom', $tools).click(function(e) {
                e.preventDefault();
                window.M.Map.map.setCenter(window.M.Map.map.getCenter(), Math.max(window.M.Map.map.getZoom() - 1, window.M.Map.lowestZoomLevel));
            });

            /*
             * Center on layer
             */
            $('.centerOnLayer', $tools).click(function(e) {
                e.preventDefault();
                if (self.layer && self.layer.features && self.layer.features.length > 0) {
                    window.M.Map.zoomTo(self.layer.getDataExtent(), false);
                }
                else {
                    window.M.Map.setCenter(window.M.Map.Util.d2p(new OpenLayers.LonLat(0, 40)), 1, true);
                }
            });

            /*
             * Map global view
             */
            $('.globalView', $tools).click(function(e) {
                e.preventDefault();
                window.M.Map.setCenter(window.M.Map.Util.d2p(new OpenLayers.LonLat(0, 40)), 1, true);
            });

            /*
             * Hide / Show layer
             */
            $('.hideLayer', $tools).click(function(e) {
                e.preventDefault();
                if (self.layer) {
                    if (self.layer.getVisibility()) {
                        window.M.Map.Util.setVisibility(self.layer, false);
                        $('.hideLayer', $tools)
                                .attr('title', self.translate('_showLayer'))
                                .addClass('black');
                    }
                    else {
                        window.M.Map.Util.setVisibility(self.layer, true);
                        $('.hideLayer', $tools)
                                .attr('title', self.translate('_hideLayer'))
                                .removeClass('black');
                    }
                }
            });
        },
        updateResultEntries: function(json) {
            // Should be defined in theme.js
        },
        /**
         * Launch an ajax call
         * This function relies on jquery $.ajax function
         * 
         * @param {Object} obj
         * @param {boolean} showMask
         */
        ajax: function(obj, showMask) {

            var self = this;

            /*
             * Paranoid mode
             */
            if (typeof obj !== "object") {
                return null;
            }

            /*
             * Ask for a Mask
             */
            if (showMask) {
                obj['complete'] = function(c) {
                    self.hideMask();
                };
                self.showMask();
            }

            return $.ajax(obj);

        },
        /**
         * Display non intrusive message to user
         * 
         * @param {string} message
         * @param {integer} duration
         */
        message: function(message, duration) {
            var $container = $('body'), $d;
            $container.append('<div class="adminMessage"><div class="content">' + message + '</div></div>');
            $d = $('.adminMessage', $container);
            $d.fadeIn('slow').delay(duration || 2000).fadeOut('slow', function() {
                $d.remove();
            }).css({
                'left': ($container.width() - $d.width()) / 2,
                'top': 30
            });
            return $d;

        }
    };

    /**
     * Collection/Resources management
     */
    window.R.Admin = {
        
        /**
         * Update admin actions
         */
        updateAdminActions: function() {

            var self = this;

            /*
             * Actions
             */
            $('.addCollection').click(function(e) {
                e.stopPropagation();
                try {
                    self.addCollection($.parseJSON($('#collectionDescription').val()));
                }
                catch (e) {
                    window.R.message('Error : collection description is not valid JSON');
                }
                return false;
            });

            $('.deactiveCollection').each(function() {
                $(this).click(function(e) {
                    e.stopPropagation();
                    self.removeCollection($(this).attr('collection'), false);
                    return false;
                });
            });
            $('.removeCollection').each(function() {
                $(this).click(function(e) {
                    e.stopPropagation();
                    self.removeCollection($(this).attr('collection'), true);
                    return false;
                });
            });
            $('.updateCollection').each(function() {
                $(this).click(function(e) {
                    e.stopPropagation();
                    self.updateCollection($(this).attr('collection'));
                    return false;
                });
            });

        },
        
        /**
         * Add a collection
         * 
         * @param {object} description
         */
        addCollection: function(description) {

            if (window.confirm('Add collection ' + description.name + ' ?')) {
                window.R.ajax({
                    url: window.R.restoUrl,
                    async: true,
                    type: 'POST',
                    dataType: "json",
                    data: {
                        data: encodeURIComponent(JSON.stringify(description))
                    },
                    success: function(obj, textStatus, XMLHttpRequest) {
                        if (XMLHttpRequest.status === 200) {
                            window.location = window.R.restoUrl;
                        }
                        else {
                            window.R.message(textStatus);
                        }
                    },
                    error: function(e) {
                        window.R.message(e.responseJSON['ErrorMessage']);
                    }
                }, true);
            }
        },
        
        /**
         * Logically remove a collection
         * 
         * @param {type} collection
         * @param {boolean} physical - true to physically delete the collection
         *                             (otherwise collection is logically delete)
         */
        removeCollection: function(collection, physical) {

            if (window.confirm('Remove collection ' + collection + ' ?')) {
                window.R.ajax({
                    url: window.R.restoUrl + collection + (physical ? '?physical=true' : ''),
                    async: true,
                    type: 'DELETE',
                    dataType: "json",
                    success: function(obj, textStatus, XMLHttpRequest) {
                        if (XMLHttpRequest.status === 200) {
                            window.R.message(obj['Message']);
                            $('#_' + collection).fadeOut(300, function() {
                                $(this).remove();
                            });
                        }
                        else {
                            alert(textStatus);
                        }
                    },
                    error: function(e) {
                        alert(e.responseJSON['ErrorMessage']);
                    }
                }, true);
            }

        },
        
        /**
         * Update a collection
         * 
         * @param {type} collection
         */
        updateCollection: function(collection) {

        }

    };



})(window);
