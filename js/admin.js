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

    window.R.Admin = {
        /*
         * RESTO URL
         */
        restoUrl: null,
        /**
         * Initialize Resto Administation module
         * 
         * @param {object} options
         */
        init: function(options) {

            var self = this;

            options = options || {};

            self.restoUrl = options.restoUrl;

            /*
             * Actions
             */
            $('.addCollection').click(function(e) {
                e.stopPropagation();
                try {
                    self.addCollection($.parseJSON($('#collectionDescription').val()));
                    /*
                     * For test
                     *
                    self.addCollection({
                        "name": "Example",
                        "controller": "SpotController",
                        "status": "public",
                        "createdb": true,
                        "osDescription": {
                            "en": {
                                "ShortName": "RESTo collection example",
                                "LongName": "RESTo collection example",
                                "Description": "A dummy collection using SPOTController",
                                "Tags": "resto example",
                                "Developper": "J\u00e9r\u00f4me Gasperi",
                                "Contact": "jerome.gasperi@gmail.com",
                                "Query": "SPOT6",
                                "Attribution": "RESTo - Copyright 2014, All Rights Reserved"
                            }
                        }
                    });
                    */
                }
                catch (e) {
                    self.message('Error : collection description is not valid JSON');
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
            
            var self = this;

            if (window.confirm('Add collection ' + description.name + ' ?')) {
                self.ajax({
                    url: self.restoUrl,
                    async: true,
                    type: 'POST',
                    dataType: "json",
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('Authorization', 'Basic ' + btoa('admin:nimda'));
                    },
                    data: {
                        data: encodeURIComponent(JSON.stringify(description))
                    },
                    success: function(obj, textStatus, XMLHttpRequest) {
                        if (XMLHttpRequest.status === 200) {
                            window.location = self.restoUrl + '$admin';
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
         * Logically remove a collection
         * 
         * @param {type} collection
         * @param {boolean} physical - true to physically delete the collection
         *                             (otherwise collection is logically delete)
         */
        removeCollection: function(collection, physical) {
            
            var self = this;
            
            if (window.confirm('Remove collection ' + collection + ' ?')) {
                this.ajax({
                    url: this.restoUrl + collection + (physical ? '?physical=true' : ''),
                    async: true,
                    type: 'DELETE',
                    dataType: "json",
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('Authorization', 'Basic ' + btoa('admin:nimda'));
                    },
                    success: function(obj, textStatus, XMLHttpRequest) {
                        if (XMLHttpRequest.status === 200) {
                            self.message(obj['Message']);
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

        },
        /**
         * Launch an ajax call
         * This function relies on jquery $.ajax function
         * 
         * @param {Object} obj
         * @param {boolean} showMask
         */
        ajax: function(obj, showMask) {

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
                    $('#resto-mask-overlay').remove();
                };
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
            $d.fadeIn('slow').delay(duration || 2000).fadeOut('slow', function(){
                $d.remove();
            }).css({
                'left': ($container.width() - $d.width()) / 2,
                'top' : 30
            });
            return $d;
          
        }

    };

})(window);