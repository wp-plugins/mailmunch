<?php
  require_once( plugin_dir_path( __FILE__ ) . 'Requests/library/Requests.php' );
  Requests::register_autoloader();

  class MailmunchApi {
    protected $base_url = '';
    protected $email = '';
    protected $password = '';
    protected $headers = array('Accept' => 'application/json');
    protected $requestType = 'get';

    function __construct($email, $password, $url) {
      $this->email = $email;
      $this->password = $password;
      $this->base_url = $url;
    }

    function sites() {
      $this->requestType = 'get';
      return $this->ping('/sites');
    }

    function widgets($site_id) {
      $this->requestType = 'get';
      return $this->ping('/sites/'.$site_id.'/widgets');
    }

    function hasSite() {
      $request = $this->sites();
      $sites = $request->body;
      $result = json_decode($sites);

      return (sizeof($result) > 0);
    }

    function createSite($sitename, $domain) {
      $this->requestType = 'post';
      return $this->ping('/sites', array(
        'site' => array(
          'name' => $sitename,
          'domain' => $domain
          )
      ));
    }

    function signUp() {
      $this->requestType = 'post';
      return $this->ping('/users', array(
        'user' => array(
          'email' => $this->email,
          'password' => $this->password,
          'referral' => "wordpress-plugin"
          )
      ), true);
    }

    function signIn() {
      $this->requestType = 'post';
      return $this->ping('/users/sign_in');
    }

    function validPassword() {
     $this->requestType = 'get';
     $request = $this->ping('/sites');

     if ($request->status_code == 200){
       return true;
     }
     else {
       return false;
     }
    }

    function isNewUser() {
      $this->requestType = 'get';
      return $this->ping('/users/exists?user[email]='. $this->email, array(), true)->body == 'false';
    }

    function ping($path, $options=array(), $skipAuth=false) {
      $auth = array('auth' => array($this->email, $this->password));
      $type = $this->requestType;
      if ($type == 'get') {
        $request = Requests::$type($this->base_url. $path, $this->headers, $skipAuth ? $options : array_merge($options, $auth));
      }
      else {
        $request = Requests::$type($this->base_url. $path, $this->headers, $options, $skipAuth ? array() : $auth); 
      }
      return $request;
    }
  }
?>