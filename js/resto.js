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

/*
 * Set to true when iFrame content is loaded
 */ 
var frameIsLoaded = false;

/*
 * Initialize RESTo
 * 
 * @param {Object} options
 */
function initResto(options) {
    
    options = options || {};
    
    if (options.language) {
        $('#language-' + options.language).addClass('red');
    }
    
    if (options.mapshupUrl) {
        
        // Preload mapshup in hidden frame
        setFrameContent(options.mapshupUrl);
        hideAbove();

        // Map is displayed within hidden frame
        $('.displayMap').click(function(e) {
            e.preventDefault();
            var data = $(this).attr('addLayer');
            if (frameIsLoaded) {
                postMessage(data);
            }
            else {
                setFrameContent($(this).attr('href'), data);
            }
            showMask();
            showAbove();
            return false;
        });
    }
                
    
    //Fancybox 
    $('a.quicklook').fancybox({
        type: "image"
    });

    // Show mask before submit request
    $("#searchform").submit(function(e) {
        e.preventDefault();
        showMask();
        $.fancybox.showActivity();
        this.submit();
        return false;
    });
    
    // Set keywords action
    $('a.keyword').click(function() {
        showMask();
        $.fancybox.showActivity();
        return true;
    });

    // Set About menu action
    $('#_about').click(function() {
        alert("Work in progress. For more info contact jerome[dot]gasperi[at]gmail[dot]com");
    });

    // Create leaflet maps
    $('.map').each(function() {
        initMap($(this).attr('id'), $.parseJSON($(this).attr('geom')));
    });

    // Set Show/Hide hidden above div
    $('#above .close').click(function(e) {
        hideMask();
        hideAbove();
    });

}

/**
 * Show mask overlay (during loading)
 */
function showMask() {
    $('<div id="mask-overlay"></div>').appendTo($('body')).css({
        'position': 'absolute',
        'z-index': '1000',
        'top': '0px',
        'left': '0px',
        'background-color': '#777',
        'opacity': 0.7,
        'width': $(document).width(),
        'height': $(document).height()
    }).show();
}

/**
 * Clear mask overlay
 */
function hideMask() {
    $('#mask-overlay').remove();
}

/**
 * Hide above div
 */
function hideAbove() {
    $('#above').css({
        visibility: "hidden",
        opacity: 0 // Chrome bug
    });
}

/**
 * Show above div
 */
function showAbove() {
    $('#above').css({
        visibility: "visible",
        opacity: 1 // Chrome bug
    });
}

/**
 * Create Leaflet map
 * 
 * @param {string} divId div identifier
 * @param {object} geometry GeoJSON geometry to plot
 */
function initMap(divId, geometry) {

    var geojson, map = L.map(divId, {
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

        geojson = L.geoJson(feature, {
            pointToLayer: function(feature, latlng) {
                return L.circleMarker(latlng, pointStyle);
            }
        }).addTo(map);

        map.setView(geojson.getBounds().getCenter(), 6);
    }
    else {

        var polygonStyle = {
            color: "#ff7800",
            weight: 5,
            opacity: 0.65
        };

        geojson = L.geoJson(feature, {style: polygonStyle}).addTo(map);
        map.fitBounds(geojson.getBounds(), {padding: new L.Point(40, 40)});

    }
}

/**
 * Set frame content of above frame
 * 
 * @param {string} url
 * @param {string} data
 */
function setFrameContent(url, data) {
    var target = $('#above .content');
    target.empty();
    $('<iframe>', {
        src: url,
        id: 'aboveFrame',
        height: '100%',
        width: '100%',
        frameborder: 0,
        scrolling: 'no'
    }).appendTo(target);

    $('#aboveFrame').load(function(e) {
        frameIsLoaded = true;
        if (data) {
            setTimeout(function() {
                postMessage(data);
            }, 500);
        }
    });
}

/**
 * Post message to mapshup
 * 
 * @param {string} data (URIEncoded JSON string conforms to mapshup API.js plugin) 
 */
function postMessage(data) {
    document.getElementById('aboveFrame').contentWindow.postMessage(data, $('#aboveFrame').attr('src'));
}
                