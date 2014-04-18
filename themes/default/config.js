(function(c) {

    /*
     * !!! CHANGE THIS !!!
     */
    c["general"].rootUrl = 'http://localhost/resto/';
    
    /*
     * !! DO NOT EDIT UNDER THIS LINE !!
     */
    c["general"].serverRootUrl = null;
    c["general"].proxyUrl = null;
    c["general"].confirmDeletion = false;
    c["general"].themePath = "/js/externals/mapshup/theme/default";
    c["i18n"].path = "/js/externals/mapshup/i18n";
    c["general"].displayContextualMenu = true;
    c["general"].displayCoordinates = true;
    c["general"].displayScale = false;
    c["general"].overviewMap = "none";
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
        type:"Google",
        title:"Hybrid",
        MID:"GoogleHybrid",
        googleType:"hybrid",
        numZoomLevels:22,
        unremovable:true
    });
    

})(window.M.Config);
