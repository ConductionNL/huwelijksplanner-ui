//import angular from 'angular';
//import uiDate from 'angular-ui-date';

//require('jquery-ui/themes/base/minified/jquery-ui.min.css');

// Declaring the module a module
var larping = angular.module('larping', ['conduction','ui.router','pascalprecht.translate','ngAnimate','ngTouch','ngSanitize','ui.bootstrap','summernote']);

// We want to do some basic checks when running the application
larping.run(function($rootScope, session, $state) {
	// Let get the sesion from memory
	session = session.get();
	
	// Listen to '$locationChangeSuccess', not '$stateChangeStart'
	$rootScope.$on('$locationChangeSuccess', function() {

		//console.log($state.$current );
		// Lets check is this is a logged in session, if not we need to do stuff	
		if(!session.token && $state.$current.name != 'register' && $state.$current.name != 'reset' && $state.$current.name != 'login'){
    	
		// log-in promise failed. Redirect to log-in page.
		$state.go('login');       
		}      
	})
})

larping.factory('lazyService', [ '$http', function($http) {
    var jsPath = 'js/${ name }.js';
    var promisesCache = {};

    return {
        loadScript: function(name) {
            var path = jsPath.replace('${ name }', name);
            var promise = promisesCache[name];
            if (!promise) {
                promise = $http.get(path);
                promisesCache[name] = promise;

                return promise.then(function(result) {
                    eval(result.data);
                    console.info('Loaded: ' + path);
                });
            }

            return promise;
        }
    }
}]);

/*@tdo depraticed? */
larping.factory('api', [ 'ConductionApi', function(ConductionApi) {
    return ConductionApi;
}]);


larping.config(['$urlRouterProvider', '$stateProvider','$locationProvider','$controllerProvider', '$provide','$translateProvider', function($urlRouterProvider, $stateProvider, $locationProvider,$controllerProvider,$provide,$translateProvider) {

	//translations
    $translateProvider
        .useStaticFilesLoader({
            prefix: 'js/i18n/',
            suffix: '.json'
        })
        .fallbackLanguage('nl')    
        .registerAvailableLanguageKeys(['en', 'nl'], {
            'en_*': 'en',
            'nl_*': 'nl',
            '*': 'nl' 
        })
        .determinePreferredLanguage();
        //.useSanitizeValueStrategy('sanitize');
    
    // Lazy loading
    larping.lazy = {
        controller: $controllerProvider.register,
        //directive: $compileProvider.directive,
        //filter: $filterProvider.register,
        factory: $provide.factory,
        service: $provide.service
    }
    
    // $urlRouterProvider.otherwise('/');
   
    $stateProvider
      .state('home', {
        url: '/',
        templateUrl: 'templates/home.html',
        controller: 'homeCtrl'
      })
      .state('login', {
        url: '/login',
        templateUrl: 'templates/user/login.html',
        controller: 'loginController'
      })
      .state('register', {
          url: '/register',
          templateUrl: 'templates/user/register.html',
          controller: 'registerController'
        })      
      .state('reset', {
          url: '/reset',
          templateUrl: 'templates/user/reset.html',
          controller: 'resetController'
        })     
      .state('lockout', {
          url: '/lockout',
          templateUrl: 'templates/user/lockout.html'
        }) 
      .state('user.profile', {
        url: '/profile',
        templateUrl: 'templates/user/profile.html'
      })
      .state('user.settings', {
        url: '/settings',
        templateUrl: 'templates/user/settings.html'
      })
    // Organisation menu
    .state('groups', {
        url: '/groups',
        templateUrl: 'templates/organisation/members.html',
        controller: 'groupsCtrl',
        resolve: {
        	groups: function(ConductionGroup, $transition$) {
        		return ConductionGroup.query();
        		}
        	}
    })
    .state('group', {
        url : 'edit/{id:\d*}',
        parent : 'group',
        templateUrl: 'templates/organisation/group.html',
        controller: 'groupCtrl',
        resolve: {
        	group: function(ConductionGroup, $transition$) {
        		return ConductionGroup.get({id:$transition$.params().id});
        		}
        	}
    })
    .state('events', { // Lets pay attention here players in conduction terms is a psudo for clients
        url: '/events',
        templateUrl: 'templates/organisation/events.html',
        controller: 'eventsCtrl',
        resolve: {
        	clients: function(ConductionClient, $transition$) {
        		return ConductionClient.query();
        		}
        	}
    })
    .state('event', { // Lets pay attention here players in conduction terms is a psudo for clients
        url : 'edit/{id:\d*}',
        parent : 'events',
        templateUrl: 'templates/organisation/event.html',
        controller: 'eventCtrl',
        resolve: {
        	client: function(ConductionClient, $transition$) {
        		return ConductionClient.get({id:$transition$.params().id});
        		}
        	}
    })
    .state('orders', {
        url: '/orders',
        templateUrl: 'templates/organisation/orders.html',
        controller: 'ordersCtrl',
        resolve: {
        	orders: function(ConductionOrder, $transition$) {
            	return ConductionOrder.query();
        	}
      	}
    })
    .state('order', {
        url : '/{id:\d*}',
        parent : 'orders',
        templateUrl: 'templates/organisation/order.html',
        controller: 'orderCtrl',
        resolve: {
        	order: function(ConductionOrder, $transition$) {
        		return ConductionOrder.get({id:$transition$.params().id});
        		}
        	}
    })
    .state('products', {
        url: '/products',
        templateUrl: 'templates/organisation/products.html',
        controller: 'productsCtrl',
        resolve: {
        	products: function(ConductionProduct, $transition$) {
        		return ConductionProduct.query();
        	}
        }
    })
    .state('productEdit', {
        url : '/edit/{id:\d*}',
        parent : 'products',
        templateUrl: 'templates/organisation/product/edit.html',
        controller: 'productCtrl',
        resolve: {
        	product: function(ConductionProduct, $transition$) {
        		return ConductionProduct.get({id:$transition$.params().id});
        	}
        }
    })
    // Setting menu
    .state('setting.settings', {
        url: '/settings',
        templateUrl: 'templates/settings/settings.html'
    })
    .state('organisation-members', {
    	url : '/members',
        parent : 'organisation',
        templateUrl: 'templates/organisation/members.html',
        controller: 'membersCtrl',
        resolve: {
        	organisation: function(ConductionOrganisation, $transition$) {
        		return ConductionOrganisation.get({id:$transition$.params().id});
        	}
        }
    })
    .state('organisation-events', {
    	url : '/events',
        parent : 'organisation',
        templateUrl: 'templates/organisation/events.html',
        controller: 'eventsCtrl',
        resolve: {
        	organisation: function(ConductionOrganisation, $transition$) {
        		return ConductionOrganisation.get({id:$transition$.params().id});
        	}
        }
    })
    .state('organisation-products', {
    	url : '/products',
        parent : 'organisation',
        templateUrl: 'templates/organisation/products.html',
        controller: 'productsCtrl',
        resolve: {
        	organisation: function(ConductionOrganisation, $transition$) {
        		return ConductionOrganisation.get({id:$transition$.params().id});
        	}
        }
    })
    .state('organisation-orders', {
    	url : '/orders',
        parent : 'organisation',
        templateUrl: 'templates/organisation/orders.html',
        controller: 'organisationCtrl',
        resolve: {
        	organisation: function(ConductionOrganisation, $transition$) {
        		return ConductionOrganisation.get({id:$transition$.params().id});
        	}
        }
    })
    
    // Larping menu
     
      .state('larping', {
        url: '/larping',
        //templateUrl: 'templates/larping/about.html'
      })
      .state('larping.forum', {
        url: '/forum',
        templateUrl: 'templates/larping/forum.html'
      })
      .state('larping.inspiration', {
        url: '/inspiration',
        templateUrl: 'templates/larping/inspiration.html'
      })
      .state('larping.news', {
        url: '/news',
        templateUrl: 'templates/larping/news.html'
      })
      .state('larping.about', {
        url: '/about',
        templateUrl: 'templates/larping/about.html'
      })
    
    
     // Conduction routing    
      .state('conduction', {
        url: '/conduction'//,
        //templateUrl: 'templates/conduction/about.html'
      })  
      .state('conduction.terms', {
        url: '/terms',
        templateUrl: 'templates/conduction/terms.html'
      })  
      .state('conduction.privacy', {
        url: '/privacy',
        templateUrl: 'templates/conduction/privacy.html'
      })
      .state('conduction.about', {
        url: '/about',
        templateUrl: 'templates/conduction/about.html'
      });
    
    // use the HTML5 History API to prfent # use in internal links
    $locationProvider.html5Mode(false);
        
    // loging missing translations to the console
    $translateProvider.useMissingTranslationHandlerLog();
  }]);

larping.controller('homeCtrl', ['$scope','$state','$filter','session','ConductionAPI',  function($scope,$state,$filter, session, ConductionAPI) {
    var $translate = $filter('translate');    
    
    $scope.session = session.get();
    $scope.page = [];
    $scope.page.title = "larping | Dashboard";     
    
 }])
 
larping.controller('larpingCtrl', ['$scope','$state','$filter','session','ConductionAPI',  function($scope,$state,$filter, session, ConductionAPI) {
    var $translate = $filter('translate');    
    
    $scope.session = session.get();
    $scope.page = [];
    $scope.page.title = "larping | Dashboard";     
    
 }])
  
larping.controller('larpingCtrl', ['$scope','$state','$filter','session','ConductionAPI',  function($scope,$state, $filter, session, ConductionAPI) {
    var $translate = $filter('translate');    
    $scope.session = session.get();
    
	// we should kinda set this on the routing level          
    $scope.alerts = [];
    $scope.page = [];
    $scope.page.title = "larping | Welkom";    	
    
	// Lets set the importan variables
    $scope.session = session.get();
    $scope.user = $scope.session.user; 
}]);


larping.controller('loginController', ['$scope','$state','$filter','session','ConductionAPI', function($scope, $state, $filter, session, ConductionAPI) {  	
    var $translate = $filter('translate');    
    $scope.submitted = false;
    $scope.submitting = false;
    $scope.error = [];
    $scope.success = [];
    $scope.info = [];

	// Let get the platforms
	var platformResource = ConductionAPI.all('platforms');	
	
	$scope.submit = function (isValid) { 

		$scope.submitted = true;
		
    	// Lets do some form handling here, we dont want empty password/email posts 
		if(!isValid){
			return false;
		}

		$scope.submitting = true;
		
    	session.login($scope.email, $scope.password).then(function(response){
    		// What happens if we have an responce but no token? That means valid login but

    		// If the responce is an authenticated user we can party, if not we need to error handle 
    		if( response.token){    			
    			$scope.success.push = $translate('LOGIN_SUCCES');
    			$scope.submitted = false; 
    			$scope.submitting = false;
    			/*@todo we relly schould make this work with routing */
    			window.location.href = '/';  
    			
    			   
           }
    		else{
    			console.log(response);
    			
    			// error handle
    			$scope.error.push($translate(response));
                if(!$translate(response)){
        			$scope.error.push($translate('ERROR_UNKNOWN'));
                 }
    			$scope.submitting = false;
    		}
    	});
    };  
    
}]);

larping.controller('registerController', ['$scope','$state','$filter','session', function($scope, $state, $filter, session) { 
    var $translate = $filter('translate');         
	// We use this to see if the form is being proccecced
    $scope.submitted = false;
    $scope.submitting = false;
    
    $scope.user.id = null; // Somehow,the user id and token are ending up in the user object here if a user registers a new user afther loging out
    $scope.user.token = null; // Somehow,the user id and token are ending up in the user object here if a user registers a new user afther loging out
    $scope.user = {};
    $scope.user = null;
    $scope.error = [];
    $scope.success = [];
    $scope.info = [];
    
    $scope.submit = function (isValid) { 
		$scope.submitted = true;
		
        // Lets do some form handling here, we dont want empty password/email posts 
		if(!isValid){
			return false;
		}

		$scope.submitting = true;
		
		console.log(session.register($scope.user));
		
        session.register($scope.user).then(function(response){
    		// If the responce is an authenticated user we can party, if not we need to error handle 
    		if(response){
    			// party, so update the scope and foreward to dashboard  and the easyest way to do that is to just relaod the page

    			// @todo als er een organisatie is die ook even aanmaken
    			$scope.success.push($translate('REGISTER_SUCCES'));
    			$scope.submitted = false;
    			
    			if(response.token){
    				// The user has also been loged in so proceed back to the main page
                    //window.location.href = '/';  
        			$state.go('home');
        			$scope.submitting = false;    
    			}
    			if(!response.validated){
    				// The registration was succesfull, but the user has not been loged in. 
    				console.log('User registration succesfull, but user still needs to activate itsself');
    				$scope.info.push($translate('REGISTER_ACTIVATE'));
    			}
    			if(!response.enabled){
    				// The registration was succesfull, but the user has not been loged in. 
    				console.log('User registration succesfull, but user still needs to be aproved by an administrator');
    				$scope.info.push($translate('REGISTER_AWAITING_APROVAL'));
    			}
    			
    			/*@todo we relly schould make this work with routing */
    			//$scope.$apply();    			
    		}
    		else{
    			// error handle
    			$scope.error.push($translate(response));
                if(!$translate(response)){
        			$scope.error.push($translate('ERROR_UNKNOWN'));
                 }
    			$scope.submitting = false;
    		}
    	});
    };  	
}]);

larping.controller('resetController', ['$scope','$state','$filter','session', function($scope, $state, $filter, session) { 
    var $translate = $filter('translate');    
	// We use this to see if the form is being proccecced
    $scope.submitted = false;  
    $scope.submitting = false;
    
    
    $scope.submit = function (isValid) { 
    	
		$scope.submitted = true;
		
		if(!isValid){
			return false;
		}


		$scope.submitting = true;
		
        session.register($scope.email).then(function(response){
    		// If the responce is an authenticated user we can party, if not we need to error handle 
    		if(response && response.authorization){
    			// party, so update the scope and foreward to dashboard  and the easyest way to do that is to just relaod the page

    			$scope.success = $translate('RESET_PASSWORD_SUCCES');
    			$scope.submitted = false;
    			$scope.submitting = false;
    			
    		}
    		else{
    			// error handle
    			$scope.error = response;
                if(!$scope.error){
                    $scope.error = 'ERROR_UNKNOWN'; 
                 }
                $scope.error = $translate($scope.error); 
    			$scope.submitting = false;
    		}
    	});
    };  	

}]);

/* The Organisations Cotroller */
larping.controller('organisationsCtrl', ['$scope','$state','ConductionOrganisation','organisations',  function($scope, $state, ConductionOrganisation, organisations ) {
	
	/*
	 var organisations = ConductionOrganisation.query(function() {
		 	$scope.organisations = organisations;
	  }); //query() returns all the entries
	*/
	 $scope.organisations = organisations;
	 $scope.organisation = {};
	 $scope.submitted = false; 
	 $scope.fetching = true; 
	//console.log(organisation.get(1));
	 
	 
	// Lets setup the filter options
	$scope.filters = {};  
	$scope.filters.order = [];  
	$scope.filters.order['id'] = 'asc';
	$scope.filters.order['name'] = 'desc';
	
	/* @todo this schould be an directive, but lets leave it like this for testing purposes */
	$scope.orderTogle = function(model){
		if(model == 'desc' ){
			model = 'asc'
		}
		else{
			model = 'desc'
		}
	}
	 
	$scope.filter = function () { 
		 $scope.organisations = ConductionOrganisation.query($scope.filters);
	}
	
	// save for adding an organisation    
	$scope.add = function (isValid) {
		$scope.submitted = true;
	        if(!isValid){
	            return false;
	        }
	        ConductionOrganisation.save($scope.organisation).$promise.then(function(response){
	            if(response.id){
	                $state.go('editOrganisation', {id:response.id});    
	            }
	            else {
	                $scope.errors = response.errors;
	            }            
	    	});
	}
	
}]);

/* The Organisation Cotroller */
larping.controller('organisationCtrl', ['$scope','$state','ConductionOrganisation','organisation',  function($scope, $state, ConductionOrganisation, organisation ) {
	
    $scope.submitted = false;
    
    $scope.organisation = organisation;
        
    // Saving an organisation
    $scope.save = function (isValid) {
        $scope.submitted = true;
        if(!isValid){
            return false;
        }
        ConductionOrganisation.update($scope.organisation).$promise.then(function(response){
            if(response.id){
                $state.go('editOrganisation', {id:response.id});    
            }
            else {
                $scope.errors = response.errors;
            }            
    	});
    }
	
}]);

/* The Organisations Cotroller */
larping.controller('groupsCtrl', ['$scope','$state','ConductionGroup','groups',  function($scope, $state, ConductionGroup, groups ) {
	
	 $scope.groups = organisation.groups; 
	 $scope.group = {};
	 $scope.group.organisation = organisation; 
	 $scope.submitted = false; 
	//console.log(organisation.get(1));

	// save for adding an organisation    
	$scope.add = function (isValid) {
		$scope.submitted = true;
	        if(!isValid){
	            return false;
	        }
	        ConductionGroups.save($scope.member).$promise.then(function(response){
	            if(response.id){
	                $state.go('group', {id:response.id});    
	            }
	            else {
	                $scope.errors = response.errors;
	            }            
	    	});
	}	
}]);

/* The Member Cotroller */
larping.controller('groupCtrl', ['$scope','$state','ConductionGroup','group',  function($scope, $state, ConductionGroup, group ) {
	
    $scope.submitted = false;
    $scope.group = group;
        
    // Saving an organisation
    $scope.save = function (isValid) {
        $scope.submitted = true;
        if(!isValid){
            return false;
        }
        ConductionGroups.update($scope.group).$promise.then(function(response){
            if(response.id){
                $state.go('groups');    
            }
            else {
                $scope.errors = response.errors;
            }            
    	});
    }
	
}]);

/* The Clients Cotroller */
larping.controller('clientsCtrl', ['$scope','$state','ConductionClient','organisation','clients',   function($scope, $state, ConductionClient, organisation, clients) {
	
	// Passing the values to the scope
	 $scope.organisation = organisation;
	 $scope.clients = clients; 
	
	 // Setting up the object for a new client
	 $scope.client = {};
	 $scope.client.organisation = organisation; 
	 
	 /* @todo this should be an directive, couse we are going to repeat it a lot */
	 // valdidating is clients is a table
	 $scope.isArray = function (value) {
		 return angular.isArray(value);
	 }
	 
	 $scope.filter = function () {
		 $scope.clients = null; 
		 ConductionClient.query({organisation:organisation.id}).$promise.then(function(response){
			 $scope.clients = response; 
		 });
	 }
	 
	 
	// save for adding an organisation    
	$scope.submit = function (isValid) {
		$scope.submitted = true;
		console.log($scope.client);

	        if(!isValid){
	            return false;
	        }
	        ConductionClient.save($scope.client).$promise.then(function(response){
	            if(response.id){
	                $state.go('client', {id:response.id});    
	            }
	            else {
	                $scope.error = response;
	            }            
	    	});
	}
	
}]);

/* The Client Cotroller */
larping.controller('clientCtrl', ['$scope','$state','ConductionClient','client',  function($scope, $state, ConductionClient, client ) {
	
    $scope.client = client;
        
    // Saving an organisation
    $scope.submit = function (isValid) {
        $scope.submitted = true;
        if(!isValid){
            return false;
        }
        ConductionClient.update($scope.client).$promise.then(function(response){
            if(response.id){
                $state.go('clients');    
            }
            else {
                $scope.errors = response.errors;
            }            
    	});
    }
	
}]);

/* The Product Cotroller */
larping.controller('productsCtrl', ['$scope','$state','ConductionProduct','organisation',  function($scope, $state, ConductionProduct, v ) {
	
	 $scope.products = organisation.products(); 
	 $scope.product = {}; 
	 $scope.product.organisation = organisation; 
	 $scope.submitted = false; 
	//console.log(organisation.get(1));

	// save for adding an organisation    
	$scope.add = function (isValid) {
		$scope.submitted = true;
	        if(!isValid){
	            return false;
	        }
	        ConductionProduct.save($scope.product).$promise.then(function(response){
	            if(response.id){
	                $state.go('product', {id:response.id});    
	            }
	            else {
	                $scope.errors = response.errors;
	            }            
	    	});
	}
	
}]);

/* The Product Cotroller */
larping.controller('productCtrl', ['$scope','$state','ConductionProduct','product',  function($scope, $state, ConductionProduct, product ) {
	
    $scope.submitted = false;
    $scope.product = product;
        
    // Saving an organisation
    $scope.save = function (isValid) {
        $scope.submitted = true;
        if(!isValid){
            return false;
        }
        ConductionProduct.update($scope.product).$promise.then(function(response){
            if(response.id){
                $state.go('products');    
            }
            else {
                $scope.errors = response.errors;
            }            
    	});
    }
	
}]);

/* The Order Cotroller */
larping.controller('ordersCtrl', ['$scope','$state','ConductionOrder','organisation',   function($scope, $state, ConductionOrder, organisation ) {
	
	 $scope.orders = organisation.orders(); 
	 $scope.order = {}; 
	 $scope.order.organisation = organisation; 
	 $scope.submitted = false; 
	//console.log(organisation.get(1));

	// save for adding an organisation    
	$scope.add = function (isValid) {
		$scope.submitted = true;
	        if(!isValid){
	            return false;
	        }
	        ConductionOrder.save($scope.order).$promise.then(function(response){
	            if(response.id){
	                $state.go('order', {id:response.id});    
	            }
	            else {
	                $scope.errors = response.errors;
	            }            
	    	});
	}
	
}]);

/* The Order Cotroller */
larping.controller('orderCtrl', ['$scope','$state','ConductionOrder','order',  function($scope, $state, ConductionOrder, order ) {
	
    $scope.submitted = false;
    $scope.order = order;
        
    // Saving an organisation
    $scope.save = function (isValid) {
        $scope.submitted = true;
        if(!isValid){
            return false;
        }
        ConductionOrder.update($scope.order).$promise.then(function(response){
            if(response.id){
                $state.go('orders');    
            }
            else {
                $scope.errors = response.errors;
            }            
    	});
    }	
}]);

/* The Invoices Cotroller */
larping.controller('invoicesCtrl', ['$scope','$state','ConductionInvoice','organisation',   function($scope, $state, ConductionInvoice, organisation ) {
	
	 $scope.invoices = organisation.invoices(); 
	 $scope.invoice = {}; 
	 $scope.invoice.organisation = organisation; 
	 
	 $scope.submitted = false; 
	//console.log(organisation.get(1));

	// save for adding an organisation    
	$scope.add = function (isValid) {
		$scope.submitted = true;
	        if(!isValid){
	            return false;
	        }
	        ConductionInvoice.save($scope.invoice).$promise.then(function(response){
	            if(response.id){
	                $state.go('invoice', {id:response.id});    
	            }
	            else {
	                $scope.errors = response.errors;
	            }            
	    	});
	}
	
}]);

/* The Invoice Cotroller */
larping.controller('invoiceCtrl', ['$scope','$state','ConductionInvoice','invoice',  function($scope, $state, ConductionInvoice, invoice ) {
	
    $scope.submitted = false;
    $scope.invoice = invoice;
        
    // Saving an organisation
    $scope.save = function (isValid) {
        $scope.submitted = true;
        if(!isValid){
            return false;
        }
        ConductionInvoice.update($scope.invoice).$promise.then(function(response){
            if(response.id){
                $state.go('invoices');    
            }
            else {
                $scope.errors = response.errors;
            }            
    	});
    }	
}]);


/* The Invoices Cotroller */
larping.controller('eventsCtrl', ['$scope','$state','ConductionInvoice','organisation',   function($scope, $state, ConductionInvoice, organisation ) {
	
	 $scope.events = organisation.events(); 
	 $scope.event = {}; 
	 $scope.event.organisation = organisation; 
	 
	 $scope.submitted = false; 
	//console.log(organisation.get(1));

	// save for adding an organisation    
	$scope.add = function (isValid) {
		$scope.submitted = true;
	        if(!isValid){
	            return false;
	        }
	        ConductionEvent.save($scope.invoice).$promise.then(function(response){
	            if(response.id){
	                $state.go('event', {id:response.id});    
	            }
	            else {
	                $scope.errors = response.errors;
	            }            
	    	});
	}
	
}]);

/* The Invoice Cotroller */
larping.controller('eventCtrl', ['$scope','$state','ConductionInvoice','event',  function($scope, $state, ConductionInvoice, event ) {
	
    $scope.submitted = false;
    $scope.event = event;
        
    // Saving an organisation
    $scope.save = function (isValid) {
        $scope.submitted = true;
        if(!isValid){
            return false;
        }
        ConductionEvent.update($scope.event).$promise.then(function(response){
            if(response.id){
                $state.go('events');    
            }
            else {
                $scope.errors = response.errors;
            }            
    	});
    }	
}]);
	/*
larping.directive("ngFilterTogle", function() {

	return {
    require: "ngModel",
    link: function postLink(scope,elem,attrs,ngModel) {
	      elem.on("click", function(e) {
	    	  
	      }    
	}
}
	*/
larping.directive("ngFiles", function() {
		var result = [];
			
		return {
	    require: "ngModel",
	    link: function postLink(scope,elem,attrs,ngModel) {
	      elem.on("change", function(e) {
	        var files = elem[0].files;
			
	        angular.forEach(files, function(value, key) {
				/* @todo becouse we only hava one instance of file reader here its going to trow an  "alread in progress" error when handling multiple files */
				var reader = new FileReader();
				
	        	// Okey we want to do a litle more then usual and actually return raw and text data on files
				
	        	file = [];
	        	file['name'] = value['name'];
	        	file['size'] = value['size'];
	        	file['type'] = value['type']; 
	        	file['lastModified'] = value['lastModified'];
	        	file['lastModifiedDate'] = value['lastModifiedDate']; 
	        	file['lastModified'] = value['lastModified'];	        	
	        	//file['text'] = reader.readAsText(value);
		        // Lets add the file to the results
	        	reader.readAsDataURL(value);	
		        reader.onload = function(e) {
			        file['raw'] = e.target.result;
			        result.push(file);	
			        console.log(result);
			        ngModel.$setViewValue(result); // lets update the model
		        }	
	        });	        
	      })
	    }
	  } // end return
	});
  