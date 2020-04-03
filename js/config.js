(function(global) {
    'use strict';

    let MY_APP = global.MY_APP = global.MY_APP || {};

    MY_APP.config = {};

    MY_APP.config.token = "d2d9938ecdabc652138eacfb78f62c00";

    MY_APP.config.whitelistDomains = ['ead.puc-rio.br'];

    MY_APP.config.vimeOpts = {
        "privacy": {
            "download": false,  
            "view": "disable",
            "embed": "whitelist"
        }                 
    }    

     
})(window);    