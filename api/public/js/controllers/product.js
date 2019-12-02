/* The event Cotroller */
dashboard.controller('productCtrl', ['$scope','$rootScope','$state','$stateParams','ConductionAPI','session',  function($scope,$rootScope, $state, $stateParams, ConductionAPI,session ) {

	console.log($state.current.name);
	// Lets create an object for the add form
	$scope.resource = {};
	$scope.resource.organisation = {};
	$scope.resource.organisation.id = $stateParams.organisationId;

    // We capture the get actions in an function so that we can trigger a reload from the scop
    $scope.load = function () {
    	// Lets first see if we need a specfic event
		if($stateParams.resourceId){
			// We have an id so lets get an specific event 
			$scope.loading = true;
			if($stateParams.resource){
				$scope.resource = $stateParams.resource;
				$scope.loading = false;
				return true;
			}
			ConductionAPI.all('organisation')
			.all('resources')
			.one('resoure', $stateParams.resourceId).get().then(function successCallback(response) {
				$scope.resource = response;
				$scope.loading = false;
		    }, function errorCallback(response) {
		    	session.check();
		    	$scope.loading = false; 	
		    });
		}	
		// If we don't want a specific event then perhaps we want all the events for an organisation
		else if($state.current.name!= 'organisation-resources-edit' && $state.current.name!= 'organisation-resurces-add'){
		    // This will query /accounts and return a promise.
			$scope.loading = true;
			
			ConductionAPI
			.all('organisation')
			.all('resources')
			.getList({'organisation.id': $stateParams.organisationId}) 
			.then(function successCallback(response) {
		    	$scope.resources = response; 	      
				$scope.loading = false;
		    }, function errorCallback(response) {
		    	session.check();
		    	$scope.loading = false; 	
		    });       		        
		}
    }    		

	$rootScope.$on('reloadResources', function() { 
		$scope.load(); 
	});

	// The reload button
	 $scope.reload = function () {
		 $rootScope.$broadcast('reloadResources');
	 }

    
	// Saving an event
	 $scope.submitResource = function (isValid) {
	        $scope.submitted = true;
	        if(!isValid){
	        	return false;
	        }

			$scope.loading = true;
	        if($scope.resource.id){
	        	//$scope.tax.save()
				ConductionAPI
				.all('organisation')
				.all('resources')
				.customPUT($scope.resource, $scope.resource.id)
				.then(function successCallback(response) {
			    	//console.log(response);	   
		        	console.log(session);
			    	$scope.loading = false; 
					$state.go('beambten');	
					$rootScope.$broadcast('reloadResources');
			    }, function errorCallback(response) {
			    	$scope.loading = false; 	
			    });    
	        }
	        else{
				ConductionAPI
				.all('organisation')
				.all('resources')
				.post($scope.resource).then(function successCallback(response) {
					$state.go('beambten');	
					$rootScope.$broadcast('reloadResources');
			    	$scope.loading = false; 	
			    }, function errorCallback(response) {
			    	session.check();
			    	$scope.loading = false; 	
			    });  
	        }
	    }      		

}]);