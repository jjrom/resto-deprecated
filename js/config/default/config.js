(function(c) {

    /*
     * Update configuration options
     * 
     * Should be changed to match target server
     */
    c["general"].rootUrl = 'http://localhost/resto/';
    c["general"].serverRootUrl = null;
    c["general"].proxyUrl = null;
    c["general"].confirmDeletion = false;
    
    /*
     * !! DO NOT EDIT UNDER THIS LINE !!
     */
    c["general"].themePath = "/js/mapshup/theme/default";
    c["general"].displayContextualMenu = true;
    c["general"].displayCoordinates = true;
    c["general"].displayScale = false;
    c["general"].overviewMap = "closed";
    c['general'].enableHistory = false;
    c["general"].timeLine = {
        enable: false
    };
    
    c.remove("layers", "Streets");
    c.remove("layers", "Satellite");
    c.remove("layers", "Relief");
    c.remove("layers", "MapQuest OSM");
    c.remove("layers", "OpenStreetMap");
    
    c.add("layers", {
        type: "Bing",
        title: "Satellite",
        key: "AmraZAAcRFVn6Vbxk_TVhhVZNt66x4_4SV_EvlfzvRC9qZ_2y6k1aNsuuoYS0UYy",
        bingType: "Aerial"
    });
    
    c.extend("Navigation", {
        position: 'nw',
        orientation: 'h'
    });
    
    c["general"].location = {
        lon:0,
        lat:40,
        zoom:3
    };

})(window.M.Config);
