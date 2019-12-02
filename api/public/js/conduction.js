/*
 *  Trouwplanner Angular JS libary
 * 
 * Rules
 * 1. The libbary provides all basic interactions with the connduction API
 * 2. The app using the libary should handle follow up. E.g. the libary wil log in but the app should decide what to do afther succesfull login
 * 
 * 
 * 
 */

// Declaring the module as a module
var conduction = angular.module('conduction', ['ngResource','conductionConfiguration','restangular']);

conduction.config( function(RestangularProvider,publicKey, apiLocation) {
	RestangularProvider.setBaseUrl(apiLocation);
	RestangularProvider.setDefaultHttpFields({cache: false}); // For dashboard purposes we always want true date, for a website you might consider setting this to true
	RestangularProvider.setDefaultHeaders({'Content-Type': 'application/json' }); 	
});	

conduction.factory('ConductionAPI', function( publicKey, Restangular) {

  	
	return Restangular.withConfig(function(RestangularConfigurer) {	    
		    // Setting a public-key and optional security token
			RestangularConfigurer.setDefaultHttpFields({cache: false}); // For dashboard purposes we always want true date, for a website you might consider setting this to true

	        // JSON-LD @id support
			RestangularConfigurer.setRestangularFields({
	            id: '@id',
	            selfLink: '@id'
	        });
			RestangularConfigurer.setSelfLinkAbsoluteUrl(false);
			
			//RestangularConfigurer.setParentless(['platforms']);

	        // Hydra collections support
			RestangularConfigurer.addResponseInterceptor(function(data, operation) {
	            // Remove trailing slash to make Restangular working
	            function populateHref(data) {
	                if (data['@id']) {
	                    data.href = data['@id'].substring(1);
	                }
	            }

	            // Populate href property for the collection
	            populateHref(data);

	            if ('getList' === operation) {
	                var collectionResponse = data['hydra:member'];
	                collectionResponse.metadata = {};

	                // Put metadata in a property of the collection
	                angular.forEach(data, function(value, key) {
	                    if ('hydra:member' !== key) {
	                        collectionResponse.metadata[key] = value;
	                    }
	                });

	                // Populate href property for all elements of the collection
	                angular.forEach(collectionResponse, function(value) {
	                    populateHref(value);
	                });

	                return collectionResponse;
	            }
	            else{
	            	return data; 
	            }
			});
	  });
});
