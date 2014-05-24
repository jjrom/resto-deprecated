(function(c) {

    /*
     * !!! CHANGE THIS !!!
     */
    c["general"].rootUrl = '//localhost/resto/';
    
    /*
     * !! DO NOT EDIT UNDER THIS LINE !!
     */
    c["general"].serverRootUrl = null;
    c["general"].proxyUrl = null;
    c["general"].confirmDeletion = false;
    c["general"].themePath = "/js/css";
    c["i18n"].path = "/js/i18n";
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
    //c.remove("layers", "MapQuest OSM");
    c.remove("layers", "OpenStreetMap");
    

})(window.M.Config);
