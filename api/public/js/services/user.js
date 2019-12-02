angular
  .module('frinzj')
  .factory('User', ['$http', function($http) {
      
    var _person = undefined,
        _authenticated = true, 
        _roles  = [];
      
    return {
      isAuthenticated: _authenticated,
      person: _person,
      roles: _roles,
      signIn: function(password, email) {
        //lets fake the shizle out of this  
        _person.email = email;
        _person.firstName = 'Ruben';
        _person.fullName = 'Ruben van der Linde';
        _authenticated = true;
        _roles = [1,2,3];
        
        return true;
      },
      // Iets meer uitlegen    
      register: function(data) { 
        _person.email = data.email;
        _person.firstName = data.firstName;
        _person.fullName = data.fullName;
        _authenticated = true;
        _roles = [1,2,3];
      }
        
    };
      
  }])