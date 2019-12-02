angular.module('MyApp',['ngMaterial', 'ngMessages', 'material.svgAssetsCache']).config(function($mdDateLocaleProvider) {
	$mdDateLocaleProvider.firstDayOfWeek = 1;
	$mdDateLocaleProvider.months = ['januari', 'februari', 'maart','april','mei','juni','juli','augustus','oktober','november','december'];
	$mdDateLocaleProvider.shortMonths = ['jan', 'feb', 'mar', 'apr', 'mei','jun','jul','aug','okt','nov','dec'];
	$mdDateLocaleProvider.days = ['zondag','maandag', 'dinsdag', 'woensdag','donderdag','vrijdag','zaterdag'];
	$mdDateLocaleProvider.shortDays = ['Zo','Ma', 'Di', 'Wo','Do','Vr','Za',];
    $mdDateLocaleProvider.formatDate = function(date) {
        return moment(date).format('DD-MM-YYYY');
     };

}).controller('AppCtrl', ['$mdDateLocale','$scope', function($mdDateLocale,$scope) {
  
	// Excluding dates
	$scope.availabledates = function(date) {
	    var day = date.getDay();
	    return day === 2;//day === 1 || day === 2 || day === 3|| day === 5;
	  };
	  
}]);


/**
Copyright 2016 Google Inc. All Rights Reserved. 
Use of this source code is governed by an MIT-style license that can be foundin the LICENSE file at https://material.angularjs.org/HEAD/license.
**/
  