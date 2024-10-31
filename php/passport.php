<?php

/*
Plugin Name: Passport
Plugin URI: https://github.com/inversoft/passport-wordpress-plugin
Description: A WordPress plugin that allows users to login by authenticating with Passport via OAuth 2.0.
Version: 1.0.1
Author: Derek Klatt
Author URI: http://www.inversoft.com
Copyright [2016] [Inversoft]
License: GPL2
*/


Class Passport
{

  const PLUGIN_VERSION = "1.0.1";

  protected static $instance = NULL;

  public static function get_instance()
  {
    NULL === self::$instance and self::$instance = new self;
    return self::$instance;
  }

  private $settings = array(
    'passport_login_redirect' => 'home_page',
    'passport_login_redirect_page' => 0,
    'passport_login_redirect_url' => '',
    'passport_suppress_welcome_email' => 0,
    'passport_logout_inactive_users' => 0,
    'passport_api_id' => '',
    'passport_api_secret' => '',
    'passport_api_key' => '',
    'passport_frontend_url' => '',
    'passport_backend_url' => '',
    'passport_http_util_verify_ssl' => 1,
    'passport_restore_default_settings' => 0,
    'passport_delete_settings_on_uninstall' => 0,
  );

  function __construct()
  {
    require_once 'lib/PassportClient.php';
    add_action('init', array($this, 'init'));
  }

  function init()
  {
    add_filter('query_vars', array($this, 'passport_qvar_triggers'));
    add_action('template_redirect', array($this, 'passport_qvar_handlers'));
    add_action('admin_enqueue_scripts', array($this, 'passport_init_admin_scripts_styles'));
    add_action('admin_menu', array($this, 'passport_settings_page'));
    add_action('admin_init', array($this, 'passport_register_settings'));
    $plugin = plugin_basename(__FILE__);
    add_filter("plugin_action_links_$plugin", array($this, 'passport_settings_link'));
    add_action('wp_logout', array($this, 'passport_end_logout'));
    add_action('wp_ajax_passport_logout', array($this, 'passport_logout_user'));
    add_filter('login_url',
      function () {
        $params = array('response_type' => 'code', 'client_id' => get_option('passport_api_id'), 'redirect_uri' => site_url());
        return get_option('passport_frontend_url') . "/oauth2/authorize?" . http_build_query($params);
      }); //Replaces the wordpress login URI with Passport Oauth login.  If you do not have your settings properly configured, you will not be able to log back into wordpress!
    add_filter('allowed_redirect_hosts',
      function ($content) {
        $content[] = parse_url(get_option('passport_frontend_url'), PHP_URL_HOST);
        return $content;
      }); //Allows wordpress to redirect to Passport frontend to get oauth tokens
  }

  function passport_init_admin_scripts_styles()
  {
    wp_enqueue_style('passport-style', plugin_dir_url(__FILE__) . 'passport.css', array());
  }

  function passport_settings_link($links)
  {
    $settings_link = "<a href='options-general.php?page=passport.php'>Settings</a>";
    array_unshift($links, $settings_link);
    return $links;
  }

  //Vars on the URL that will trigger certain functionality
  function passport_qvar_triggers($vars)
  {
    $vars[] = 'code';
    $vars[] = 'error_description';
    $vars[] = 'error_message';
    return $vars;
  }

  /*
   * Gets the Oauth token from Passport
   * @return ClientResponse When successful, the response will contain the access token, the expiration and the user id.  If Passport encountered an error or exception
   * the information will be stored in the response exception or error response properties but this function redirects to your 500 page so add logging before the redirect if needed.
   */
  private function passport_get_oauth_token()
  {
    $client = new PassportClient(get_option('passport_api_key'), get_option('passport_frontend_url'));
    $clientResponse = $client->start()->uri("/oauth2/token")
      ->urlParameter("grant_type", "authorization_code")
      ->urlParameter("code", $_GET["code"])
      ->urlParameter("redirect_uri", rtrim(site_url(), '/'))
      ->urlParameter("client_id", get_option("passport_api_id"))
      ->post()
      ->go();
    if (!$clientResponse->successResponse) {
      // log $clientResponse->exception or $clientResponse->errorResponse
      wp_safe_redirect(esc_url("/500"));
      die();
      return false; //this will never happen
    } else {
      $access_token = $clientResponse->successResponse->access_token;
      $expires_in = $clientResponse->successResponse->expires_in;
      $expires_at = time() + $expires_in;
      $_SESSION['PASSPORT']['ACCESS_TOKEN'] = $access_token;
      $_SESSION['PASSPORT']['USER_ID'] = $clientResponse->successResponse->userId;
      $_SESSION['PASSPORT']['EXPIRES_IN'] = $expires_in;
      $_SESSION['PASSPORT']['EXPIRES_AT'] = $expires_at;
      return true;
    }
  }

  /*
   * Gets the user information from Passport.
   * @return $oauth_identity When successful, the response will contain the user information Wordpress needs to login or register the user in Wordpress.  If Passport encountered an error or exception
   * the information will be stored in the response exception or error response properties but this function redirects to your 500 page so add logging before the redirect if needed.
   */

  private function passport_get_oauth_identity()
  {
    $client = new PassportClient(get_option('passport_api_key'), get_option('passport_backend_url'));
    $clientResponse = $client->retrieveUser($_SESSION['PASSPORT']['USER_ID']);
    //This will fail unless the API KEY has permission to hit /api/user with GET
    if (!$clientResponse->successResponse) {
      // log $clientResponse->exception or $clientResponse->errorResponse
      wp_safe_redirect(esc_url("/500"));
      die();
      return null; //this will never happen
    }

    $user = $clientResponse->successResponse->user;
    $registrations = $user->registrations;
    $oauth_identity = array();
    $oauth_identity['id'] = $user->id;
    $oauth_identity['email'] = $user->email;
    $oauth_identity['username'] = $user->username;
    $registered = false;

    //This checks to see if the user has a Wordpress registration and what their wordpress role is
    foreach ($registrations as $app) {
      if ($app->applicationId == get_option('passport_api_id')) {
        $roles = $app->roles;
        if($app->username){
          $oauth_identity['username'] = $app->username;
        }
        $registered = true;
        break;
      }
    }

    //If the user does not have a Wordpress registration and the global setting of anyone can register is false, the login process will end and redirect to the homepage
    if(!$registered && !get_option('users_can_register')){
      //If you have a way to display messages, display a message saying registration not enabled
      wp_safe_redirect(site_url());
      die();
      return null; //this will never happen
    } elseif(!$registered){  //If the user does not have a Wordpress registration and the global setting of anyone can register is true, this will hit the Passport registration API
      //This will fail unless the API KEY has permission to hit /api/user/registration with POST
      $registrationRequest = array();
      $registrationRequest["registration"] = array();
      $registrationRequest["registration"]["applicationId"] = get_option("passport_api_id");
      $registrationRequest["registration"]["username"] = $user->email;
      $registrationResponse = $client->register($user->id,json_encode($registrationRequest));
      if(!$registrationResponse->successResponse){
        // log $clientResponse->exception or $clientResponse->errorResponse
        wp_safe_redirect(esc_url("/500"));
        die();
        return null; //this will never happen
      } else {
        $roles = $registrationResponse->successResponse->roles;
      }
    }

    //If the User has a role or roles, this will set the Wordpress role to the highest or default to subscriber
    if ($roles) {
      $role_check = array_map('strtolower', $roles);
    }
    if (sizeof($role_check) > 1) {
      if (in_array("administrator", $role_check)) {
        $oauth_identity['role'] = "administrator";
      } elseif (in_array("editor", $role_check)) {
        $oauth_identity['role'] = "editor";
      } elseif (in_array("author", $role_check)) {
        $oauth_identity['role'] = "author";
      } else {
        $oauth_identity['role'] = "contributor";
      }
    } elseif (sizeof($role_check) == 1) {
      $oauth_identity['role'] = $role_check[0];
    } else {
      $oauth_identity['role'] = "subscriber";
    }
    return $oauth_identity;
  }

  //Handles what to do when specific vars are on the URL
  function passport_qvar_handlers()
  {
    if (get_query_var('code') || get_query_var('error_description') || get_query_var('error_message')) {
      if (!$_SESSION['PASSPORT']['LAST_URL']) {
        $redirect_url = esc_url($_GET['redirect_to']);
        if (!$redirect_url) {
          $redirect_url = strtok($_SERVER['HTTP_REFERER'], "?");
        }
        $_SESSION['PASSPORT']['LAST_URL'] = $redirect_url;
      }

      if (isset($_GET['error_description'])) {
        $this->passport_end_login($_GET['error_description']);
      } elseif (isset($_GET['error_message'])) {
        $this->passport_end_login($_GET['error_message']);
      } elseif (isset($_GET['code'])) {
        $this->passport_get_oauth_token();
        $oauth_identity = $this->passport_get_oauth_identity();
        $this->passport_login_user($oauth_identity);
      } else {
        $this->passport_end_login("Sorry, we couldn't log you in. The authentication flow terminated in an unexpected way. Please notify the admin or try again later.");
      }
      return null; //this will never happen
    }
  }

  /*
   * Checks to see if the User exists in the Wordpress database
   * @return user when successful, null if not found.
   */
  private function passport_match_wordpress_user($id)
  {
    global $wpdb;
    $usermeta_table = $wpdb->usermeta;
    $query_string = "SELECT $usermeta_table.user_id FROM $usermeta_table WHERE $usermeta_table.meta_key = 'passport_identity' AND $usermeta_table.meta_value = '" . $id . "'";
    $query_result = $wpdb->get_var($query_string);
    $user = get_user_by('id', $query_result);
    return $user;
  }

  /*
   * Registers the user in the wordpress database if its a users first time logging into wordpress.
   */
  private function passport_register_user($oauth_identity)
  {
    global $wpdb;

    $id = uniqid('', true);
    $password = wp_generate_password();

    if ($oauth_identity["email"]) {
      $email = $oauth_identity["email"];
      $username = $email;
    }

    if ($oauth_identity["username"]) {
      $username = $oauth_identity["username"];
    }

    //If the user already existed in the database before the installation of the plugin, this links the accounts
    $does_user_exist = get_user_by('email', $email);
    if(!$does_user_exist){
      $user_id = wp_create_user($id, $password, $email);

      if (is_wp_error($user_id)) {
        $_SESSION["PASSPORT"]["RESULT"] = $user_id->get_error_message();
        wp_safe_redirect(esc_url("/500"));
        die();
      }
    } else {
      $user_id = $does_user_exist->ID;
    }


    $update_username_result = $wpdb->update($wpdb->users, array('user_login' => $username, 'user_nicename' => $username, 'display_name' => $username), array('ID' => $user_id));
    $update_nickname_result = update_user_meta($user_id, 'nickname', $username);

    $role = $oauth_identity['role'];
    $update_role_result = wp_update_user(array('ID' => $user_id, 'role' => $role));

    if (!$update_username_result || !$update_nickname_result) {
      $_SESSION["PASSPORT"]["RESULT"] = "Could not rename the username during registration. Please contact an admin or try again later.";
      wp_safe_redirect(esc_url("/500"));
      die();
    } elseif (!$update_role_result) {
      $_SESSION["PASSPORT"]["RESULT"] = "Could not assign default user role during registration. Please contact an admin or try again later.";
      wp_safe_redirect(esc_url("/500"));
      die();
    } else {
      $this->passport_link_account($user_id);
      $creds = array();
      $creds['user_login'] = $username;
      $creds['user_password'] = $password;
      $creds['remember'] = true;
      $user = wp_signon($creds, false);
      if (!get_option('passport_suppress_welcome_email')) {
        wp_new_user_notification($user_id, $password);
      }
      wp_safe_redirect(esc_url($_GET['redirect_to']));
    }
  }

  private function passport_login_user($oauth_identity)
  {
    $matched_user = $this->passport_match_wordpress_user($oauth_identity['id']);
    $user_id = $matched_user->ID;
    if ($matched_user) {
      if($matched_user->roles[0] != $oauth_identity['role']){
        $update_role_result = wp_update_user(array('ID' => $user_id, 'role' => $oauth_identity['role']));
        if(!$update_role_result){
          wp_safe_redirect(esc_url("/500"));
          die();
        }
        $matched_user = $this->passport_match_wordpress_user($update_role_result);
      }

      $user_login = $matched_user->user_login;
      wp_set_current_user($user_id, $user_login);
      wp_set_auth_cookie($user_id);
      do_action('wp_login', $user_login, $matched_user);
      $this->passport_end_login("Logged in successfully!");
      return null; //this should never happen
    } else {
      $this->passport_register_user($oauth_identity);
    }
    $this->passport_end_login("Sorry, we couldn't log you in. The login flow terminated in an unexpected way. Please notify the admin or try again later.");
  }

  //Ends the login process and redirects to the set page
  private function passport_end_login($msg)
  {
    $last_url = $_SESSION["PASSPORT"]["LAST_URL"];
    unset($_SESSION["PASSPORT"]["LAST_URL"]);
    $_SESSION["PASSPORT"]["RESULT"] = $msg;
    $redirect_method = get_option("passport_login_redirect");
    $redirect_url = "";
    switch ($redirect_method) {
      case "home_page":
        $redirect_url = site_url();
        break;
      case "last_page":
        $redirect_url = $last_url;
        break;
      case "specific_page":
        $redirect_url = get_permalink(get_option('passport_login_redirect_page'));
        break;
      case "admin_dashboard":
        $redirect_url = admin_url();
        break;
      case "user_profile":
        $redirect_url = get_edit_user_link();
        break;
      case "custom_url":
        $redirect_url = get_option('passport_login_redirect_url');
        break;
    }

    wp_safe_redirect($redirect_url);
    die();
  }

  //Logouts of Wordpress
  function passport_logout_user()
  {
    $user = null;
    session_destroy();
    wp_logout();
  }

  //Redirects to the Passport Logoout API to kill the session
  function passport_end_logout()
  {
    $_SESSION["PASSPORT"]["RESULT"] = 'Logged out successfully.';
    unset($_SESSION["PASSPORT"]["LAST_URL"]);
    $redirect_url = get_option('passport_frontend_url') . "/oauth2/logout?client_id=" . get_option('passport_api_id');
    wp_safe_redirect($redirect_url);
    die();
  }

  //Links the account in the Wordpress database
  private function passport_link_account($user_id)
  {
    if ($_SESSION['PASSPORT']['USER_ID']) {
      add_user_meta($user_id, 'passport_identity', $_SESSION['PASSPORT']['USER_ID']);
    }
  }

  function passport_register_settings()
  {
    foreach ($this->settings as $setting_name => $default_value) {
      register_setting('passport_settings', $setting_name);
    }
  }

  function passport_settings_page()
  {
    add_options_page('Passport Options', 'Passport', 'manage_options', 'Passport', array($this, 'passport_settings_page_content'));
  }

  function passport_settings_page_content()
  {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    include 'passport-settings.php';
  }
}

PASSPORT::get_instance();
?>
