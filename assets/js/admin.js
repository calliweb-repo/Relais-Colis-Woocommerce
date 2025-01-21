
    
jQuery(document).ready(function() {
    
    console.log('JS init');

});


var AdminJS = new function() {
    'use strict';
    
    /*
     * Private variables.
     */
    var $ = jQuery.noConflict();
    
    /*
     * Constructor
     */
    this.AdminJS = function() {
        
        $(document).ready(function() {

            console.log('AdminJS JS init');
            $(function(){
                //...
            });
        });
    };
    
    this.method = function() {
    	
    };

    return this.AdminJS();
};