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
                            self.addLayer({
                                type: 'GeoJSON',
                                clusterized: false,
                                data: options.data,
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
                var url = window.History.getState().cleanUrl.replace('format=html', 'format=json');

                self.showMask();

                $.ajax({
                    url: url,
                    async: true,
                    dataType: 'json',
                    success: function(json) {
                        self.hideMask();
                        self.updatePage(json, true);
                    },
                    error: function(e) {
                        self.hideMask();
                        alert("Connection error");
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
             * Responsive toolbar
             */
            $(function() {
                var pull = $('#pull'),
                        menu = $('nav ul');

                $(pull).on('click', function(e) {
                    e.preventDefault();
                    menu.slideToggle();
                });

                $(window).resize(function() {
                    if ($(window).width() > 320 && menu.is(':hidden')) {
                        menu.removeAttr('style');
                    }
                });
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
             * Initialize page with no mapshup refresh
             */
            self.updatePage(options.data, false);

        },
        
        /**
         * Show mask overlay (during loading)
         */
        showMask: function() {
            $('<div id="resto-mask-overlay"><span class="fa fa-3x fa-refresh fa-spin"></span></div>').appendTo($('body')).css({
                'position': 'absolute',
                'z-index': '1000',
                'top': '0px',
                'left': '0px',
                'background-color': '#777',
                'opacity': 0.7,
                'color':'white',
                'text-align':'center',
                'width': $(window).width(),
                'height': $(window).height(),
                'line-height':$(window).height() + 'px'
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
                return false;
            }

            if (typeof json === 'string') {
                json = JSON.parse(decodeURI(json));
            }

            window.M.Map.addLayer(json, {
                noDeletionCheck: true
            });

            return true;

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
         * @param {boolean} updateMapshup - true to update mapshup
         * 
         */
        updatePage: function(json, updateMapshup) {
            alert('updatePage function must be declared within theme.js');
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
                var layer = window.M.Map.Util.getLayerByMID('__resto__');
                if (layer && layer.features && layer.features.length > 0) {
                    window.M.Map.zoomTo(layer.getDataExtent(), false);
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
                var layer = window.M.Map.Util.getLayerByMID('__resto__');
                if (layer) {
                    if (layer.getVisibility()) {
                        window.M.Map.Util.setVisibility(layer, false);
                        $('.hideLayer', $tools)
                                .attr('title', self.translate('_showLayer'))
                                .addClass('black');
                    }
                    else {
                        window.M.Map.Util.setVisibility(layer, true);
                        $('.hideLayer', $tools)
                                .attr('title', self.translate('_hideLayer'))
                                .removeClass('black');
                    }
                }
            });
        }

    };
})(window);