<?php
  /*
  Plugin Name: MailMunch - Increase your Email Subscribers by over 500%
  Plugin URI: http://www.mailmunch.co
  Description: Collect email addresses from website visitors and grow your subscribers with our attention grabbing optin-forms, entry/exit intent technology, and other effective lead-generation forms.
  Version: 1.4.4
  Author: MailMunch
  Author URI: http://www.mailmunch.co
  License: GPL2
  */

  require_once( plugin_dir_path( __FILE__ ) . 'inc/mailmunchapi.php' );
  require_once( plugin_dir_path( __FILE__ ) . 'inc/common.php' );
  require_once( plugin_dir_path( __FILE__ ) . 'inc/sidebar_widget.php' );

  define( 'MAILMUNCH_SLUG', "mailmunch");
  define( 'MAILMUNCH_VER', "1.4.4");
  define( 'MAILMUNCH_URL', "www.mailmunch.co");

  // Create unique WordPress instance ID
  if (get_option("mailmunch_wordpress_instance_id") == "") {
    update_option("mailmunch_wordpress_instance_id", uniqid());
  }

  // Adding Admin Menu
  add_action( 'admin_menu', 'mailmunch_register_page' );

  function mailmunch_register_page(){
    add_options_page('MailMunch', 'MailMunch', 'manage_options', MAILMUNCH_SLUG, 'mailmunch_setup');
    $menu_page = add_menu_page( 'MailMunch Settings', 'MailMunch', 'manage_options', MAILMUNCH_SLUG, 'mailmunch_setup', plugins_url( 'img/icon.png', __FILE__ ), 102.786 ); 
    // If successful, load admin assets only on that page.
    if ($menu_page) add_action('load-' . $menu_page, 'mailmunch_load_plugin_assets');
  }

  function mailmunch_plugin_settings_link($links) {
    $settings_link = '<a href="options-general.php?page='.MAILMUNCH_SLUG.'">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
  }

  $plugin = plugin_basename(__FILE__);
  add_filter('plugin_action_links_'.$plugin, 'mailmunch_plugin_settings_link');

  function mailmunch_load_plugin_assets() {
    add_action( 'admin_enqueue_scripts', 'mailmunch_enqueue_admin_styles' );
    add_action( 'admin_enqueue_scripts', 'mailmunch_enqueue_admin_scripts'  );
  }

  function mailmunch_enqueue_admin_styles() {
    wp_enqueue_style(MAILMUNCH_SLUG . '-admin-styles', plugins_url( 'css/admin.css', __FILE__ ), array(), MAILMUNCH_VER );
  }

  function mailmunch_enqueue_admin_scripts() {
    wp_enqueue_script(MAILMUNCH_SLUG . '-admin-script', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), MAILMUNCH_VER );
  }

  // Adding MailMunch Asset Files (JS + CSS) 
  function mailmunch_load_asset_code() {
    $mailmunch_data = unserialize(get_option("mailmunch_data"));
    if (!$mailmunch_data["script_src"]) return;

    if (is_single() || is_page()) {
      $post = get_post();
      $post_data = array("ID" => $post->ID, "post_name" => $post->post_name, "post_title" => $post->post_title, "post_type" => $post->post_type, "post_author" => $post->post_author, "post_status" => $post->post_status);
    }

    echo "<script type='text/javascript'>";
    echo "var _mmunch = {'front': false, 'page': false, 'post': false, 'category': false, 'author': false, 'search': false, 'attachment': false, 'tag': false};";
    if (is_front_page() || is_home()) { echo "_mmunch['front'] = true;"; }
    if (is_page()) { echo "_mmunch['page'] = true; _mmunch['pageData'] = ".json_encode($post_data).";"; }
    if (is_single()) { echo "_mmunch['post'] = true; _mmunch['postData'] = ".json_encode($post_data)."; _mmunch['postCategories'] = ".json_encode(get_the_category())."; _mmunch['postTags'] = ".json_encode(get_the_tags())."; _mmunch['postAuthor'] = ".json_encode(array("name" => get_the_author_meta("display_name"), "ID" => get_the_author_meta("ID"))).";"; }
    if (is_category()) { echo "_mmunch['category'] = true; _mmunch['categoryData'] = ".json_encode(get_category(get_query_var('cat'))).";"; }
    if (is_search()) { echo "_mmunch['search'] = true;"; }
    if (is_author()) { echo "_mmunch['author'] = true;"; }
    if (is_tag()) { echo "_mmunch['tag'] = true;"; }
    if (is_attachment()) { echo "_mmunch['attachment'] = true;"; }
    echo "</script>";
    echo('<script data-cfasync="false" src="//s3.amazonaws.com/mailmunch/static/site.js" id="mailmunch-script" data-mailmunch-site-id="'.$mailmunch_data["site_id"].'" async></script>');
  }

  add_action('init', 'mailmunch_assets');

  function mailmunch_assets() {
    $mailmunch_data = unserialize(get_option("mailmunch_data"));
    if (count($mailmunch_data) == 0) return;

    if (function_exists('wp_head')) {
      add_action( 'wp_head', 'mailmunch_load_asset_code' ); 
    }
    elseif (function_exists('wp_footer')) {
      add_action( 'wp_footer', 'mailmunch_load_asset_code' ); 
    }
  }

  function mailmunch_add_post_containers($content) {
    if (is_single() || is_page()) {
      $content = mailmunch_insert_form_after_paragraph("<div class='mailmunch-forms-in-post-middle' style='display: none !important;'></div>", "middle", $content);
      $content = "<div class='mailmunch-forms-before-post' style='display: none !important;'></div>" . $content . "<div class='mailmunch-forms-after-post' style='display: none !important;'></div>";
    }

    return $content;
  }

  function mailmunch_register_sidebar_widget() {
      register_widget( 'Mailmunch_Sidebar_Widget' );
  }
  add_action( 'widgets_init', 'mailmunch_register_sidebar_widget' );

  function mailmunch_insert_form_after_paragraph($insertion, $paragraph_id, $content) {
    $closing_p = '</p>';
    $paragraphs = explode($closing_p, $content);
    if ($paragraph_id == "middle") {
      $paragraph_id = round(sizeof($paragraphs)/2);
    }

    foreach ($paragraphs as $index => $paragraph) {
      if (trim($paragraph)) {
        $paragraphs[$index] .= $closing_p;
      }

      if ($paragraph_id == $index + 1) {
        $paragraphs[$index] .= $insertion;
      }
    }
    return implode('', $paragraphs);
  }

  add_filter( 'the_content', 'mailmunch_add_post_containers' );

  function mailmunch_shortcode_form($atts) {
    return "<div class='mailmunch-forms-short-code mailmunch-forms-widget-".$atts['id']."' style='display: none !important;'></div>";
  }

  add_shortcode('mailmunch-form', 'mailmunch_shortcode_form');

  function mailmunch_setup() {
    $mm_helpers = new MailmunchHelpers();
    $mailmunch_data = unserialize(get_option("mailmunch_data"));
    $mailmunch_data["site_url"] = home_url();
    $mailmunch_data["site_name"] = get_bloginfo();
    update_option("mailmunch_data", serialize($mailmunch_data));

    // This is a POST request. Let's save data first.
    if ($_POST) {
      $post_data = (isset($_POST["mailmunch_data"]) ? $_POST["mailmunch_data"] : array());
      $post_action = $_POST["action"];

      if ($post_action == "save_settings") { 

        $mailmunch_data = array_merge(unserialize(get_option('mailmunch_data')), $post_data);
        update_option("mailmunch_data", serialize($mailmunch_data));
      
      } else if ($post_action == "sign_in") {

        $mm = new MailmunchApi($_POST["email"], $_POST["password"], "http://".MAILMUNCH_URL);
        if ($mm->validPassword()) {
          update_option("mailmunch_user_email", $_POST["email"]);
          update_option("mailmunch_user_password", base64_encode($_POST["password"]));
          delete_option("mailmunch_guest_user");
        }

      } else if ($post_action == "sign_up") {

        if (empty($_POST["email"]) || empty($_POST["password"])) {
          $invalid_email_password = true;
        } else {
          $account_info = $mm_helpers->getEmailPassword();
          $mailmunch_email = $account_info['email'];
          $mailmunch_password = $account_info['password'];

          $mm = new MailmunchApi($mailmunch_email, $mailmunch_password, "http://".MAILMUNCH_URL);
          if ($mm->isNewUser($_POST['email'])) {
            $update_result = $mm->updateGuest($_POST['email'], $_POST['password']);
            $result = json_decode($update_result['body']);
            update_option("mailmunch_user_email", $result->email);
            update_option("mailmunch_user_password", base64_encode($_POST['password']));
            if (!$result->guest_user) { delete_option("mailmunch_guest_user"); }
            $mailmunch_email = $result->email;
            $mailmunch_password = $_POST['password'];

            // We have update the guest with real email address, let's create a site now
            $mm = new MailmunchApi($mailmunch_email, $mailmunch_password, "http://".MAILMUNCH_URL);

            $update_result = $mm->updateSite($mailmunch_data["site_name"], $mailmunch_data["site_url"]);
            $result = json_decode($update_result['body']);
            $mailmunch_data = unserialize(get_option("mailmunch_data"));
            $mailmunch_data["site_url"] = $result->domain;
            $mailmunch_data["site_name"] = $result->name;
            update_option("mailmunch_data", serialize($mailmunch_data));
          } else {
            $user_exists = true;
          }
        }

      } else if ($post_action == "unlink_account") {

        $mailmunch_data = array();
        $mailmunch_data["site_url"] = home_url();
        $mailmunch_data["site_name"] = get_bloginfo();
        update_option("mailmunch_data", serialize($mailmunch_data));
        delete_option("mailmunch_user_email");
        delete_option("mailmunch_user_password");

      } else if ($post_action == "delete_widget") {

        if ($_POST["site_id"] && $_POST["widget_id"]) {
          $account_info = $mm_helpers->getEmailPassword();
          $mailmunch_email = $account_info['email'];
          $mailmunch_password = $account_info['password'];
          $mm = new MailmunchApi($account_info['email'], $account_info["password"], "http://".MAILMUNCH_URL);
          $request = $mm->deleteWidget($_POST["site_id"], $_POST["widget_id"]);
        }

      } else if ($post_action == "create_site") { 
        $site_url = (empty($_POST["site_url"]) ? get_bloginfo() : $_POST["site_url"]);
        $site_name = (empty($_POST["site_name"]) ? home_url() : $_POST["site_name"]);

        $account_info = $mm_helpers->getEmailPassword();
        $mm = new MailmunchApi($account_info['email'], $account_info["password"], "http://".MAILMUNCH_URL);
        $request = $mm->createSite($site_name, $site_url);
        $site = json_decode($request['body']);

        if (!empty($site->id)) {
          $mailmunch_data = unserialize(get_option("mailmunch_data"));
          $mailmunch_data["site_id"] = $site->id;
          $mailmunch_data["script_src"] = $site->javascript_url;
          update_option("mailmunch_data", serialize($mailmunch_data));
        }
      }
    }

    // If the user does not exists, create a GUEST user
    if (get_option("mailmunch_user_email") == "") {
      $mailmunch_email = "guest_".uniqid()."@mailmunch.co";
      $mailmunch_password = uniqid();
      $mm = new MailmunchApi($mailmunch_email, $mailmunch_password, "http://".MAILMUNCH_URL);
      $mm->createGuestUser();
      update_option("mailmunch_user_email", $mailmunch_email);
      update_option("mailmunch_user_password", base64_encode($mailmunch_password));
      update_option("mailmunch_guest_user", true);
    }

    // If we already have the user's email stored, let's create the API instance
    // If we don't have it yet, make sure NOT to phone home any user data
    if (get_option("mailmunch_user_email") != "") {
      $account_info = $mm_helpers->getEmailPassword();
      $mailmunch_email = $account_info['email'];
      $mailmunch_password = $account_info['password'];

      $mm = new MailmunchApi($mailmunch_email, $mailmunch_password, "http://".MAILMUNCH_URL);
      $pass_check = $mm->validPassword();

      if( is_wp_error( $pass_check ) ) {
        echo $pass_check->get_error_message();
        return;
      }

      if (!$pass_check) {
        // Invalid user, create a GUEST user
        $mailmunch_email = "guest_".uniqid()."@mailmunch.co";
        $mailmunch_password = uniqid();
        $mm = new MailmunchApi($mailmunch_email, $mailmunch_password, "http://".MAILMUNCH_URL);
        $mm->createGuestUser();
        update_option("mailmunch_user_email", $mailmunch_email);
        update_option("mailmunch_user_password", base64_encode($mailmunch_password));
        update_option("mailmunch_guest_user", true);
      }
    }

    $mailmunch_guest_user = get_option("mailmunch_guest_user");


    if ($mailmunch_guest_user) {
      // This is a Guest USER. Do not collect any user data.
      $sites = $mm_helpers->createAndGetGuestSites($mm);
    } else {
      $sites = $mm_helpers->createAndGetSites($mm);
    }

    if (isset($mailmunch_data["site_id"])) {
      // If there's a site already chosen, we need to get and save it's script_src in WordPress
      $site = $mm_helpers->getSite($sites, $mailmunch_data["site_id"]);
      
      if ($site) {
        $mailmunch_data = array_merge(unserialize(get_option('mailmunch_data')), array("script_src" => $site->javascript_url));
        update_option("mailmunch_data", serialize($mailmunch_data));
      } else {
        // The chosen site does not exist in the mailmunch account any more, remove it locally
        $site_not_found = true;
        $mailmunch_data = unserialize(get_option('mailmunch_data'));
        unset($mailmunch_data["site_id"]);
        unset($mailmunch_data["script_src"]);
        update_option("mailmunch_data", serialize($mailmunch_data));
      }
    }

    if (!isset($mailmunch_data["site_id"])) {
      // If there's NO chosen site yet

      if (sizeof($sites) == 1 && ($sites[0]->name == get_bloginfo() || $sites[0]->name == "WordPress")) {
        // If this mailmunch account only has 1 site and its name matches this WordPress blogs

        $site = $sites[0];

        if ($site) {
          $mailmunch_data = array_merge(unserialize(get_option('mailmunch_data')), array("site_id" => $site->id, "script_src" => $site->javascript_url));
          update_option("mailmunch_data", serialize($mailmunch_data));
        }
      } else if (sizeof($sites) > 0) {
        // If this mailmunch account has one or more sites, let the user choose one
?>
  <div class="container">
    <div class="page-header">
      <h1>Choose Your Site</h1>
    </div>

    <p>Choose the site that you would like to link with your WordPress.</p>

    <div id="choose-site-form">
      <form action="" method="POST">
        <div class="form-group">
          <input type="hidden" name="action" value="save_settings" />

          <select name="mailmunch_data[site_id]">
            <?php foreach ($sites as $site) { ?>
            <option value="<?php echo $site->id ?>"><?php echo $site->name ?></option>
            <?php } ?>
          </select>
        </div>

        <div class="form-group">
          <input type="submit" value="Save Settings" class="button-primary" />
        </div>
      </form>

      <p>
        Don't see this site above? <a href="" onclick="document.getElementById('create-site-form').style.display = 'block'; document.getElementById('choose-site-form').style.display = 'none'; return false;">Create New Site</a>
      </p>
    </div>

    <div id="create-site-form" style="display: none;">
      <form action="" method="POST">
        <input type="hidden" name="action" value="create_site" />

        <div class="form-group">
          <label>Site Name</label>
          <input type="text" name="site_name" value="<?php echo get_bloginfo() ?>" />
        </div>

        <div class="form-group">
          <label>Site URL</label>
          <input type="text" name="site_url" value="<?php echo home_url() ?>" />
        </div>

        <div class="form-group">
          <input type="submit" value="Create Site" class="button-primary" />
        </div>
      </form>

      <p>
        Already have a site in your MailMunch account? <a href="" onclick="document.getElementById('create-site-form').style.display = 'none'; document.getElementById('choose-site-form').style.display = 'block'; return false;">Choose Site</a>
      </p>
    </div>
  </div>
<?php
        return;
      }
    }

    $request = $mm->getWidgetsHtml($mailmunch_data["site_id"]);
    $widgets = $request['body'];
    $widgets = str_replace("{{EMAIL}}", $mailmunch_email, $widgets);
    $widgets = str_replace("{{PASSWORD}}", $mailmunch_password, $widgets);
    echo $widgets;

    if ($mailmunch_guest_user) {
      $current_user = wp_get_current_user();
?>

<div id="signup-signin-box-overlay" onclick="hideSignupBox();" style="display: none;"></div>

<div id="signup-signin-box" style="display:none;">
  <a id="signup-signin-close" onclick="hideSignupBox();">
    <img src="<?php echo plugins_url( 'img/close.png', __FILE__ ) ?>" />
  </a>

  <div id="sign-up-form" class="<?php if (!$_POST || ($_POST["action"] != "sign_in" && $_POST["action"] != "unlink_account")) { ?> active<?php } ?>">
    <div class="form-container">
      <h2 class="modal-header">Sign Up</h2>
      <p>To activate your MailMunch forms, we will now create your account on MailMunch (<a onclick="showWhyAccount();" id="why-account-btn">Why?</a>).</p>

      <div id="why-account" class="alert alert-warning" style="display: none;">
        <h4>Why do I need a MailMunch account?</h4>

        <p>
          MailMunch is a not just a WordPress plugin but a standalone service. An account is required to identify your WordPress and serve your MailMunch forms.
        </p>
      </div>

      <?php if (isset($user_exists)) { ?>
      <div id="invalid-alert" class="alert alert-danger signup-alert" role="alert">Account with this email already exists. Please sign in using your password.</div>
      <?php } else if (isset($invalid_email_password)) { ?>
      <div id="invalid-alert" class="alert alert-danger signup-alert" role="alert">Invalid email or password. Please enter a valid email and password below.</div>
      <?php } ?>

      <form action="" method="POST">
        <input type="hidden" name="action" value="sign_up" />

        <div class="form-group">
          <label>Wordpress Name</label>
          <input type="text" placeholder="Site Name" name="site_name" value="<?php echo $mailmunch_data["site_name"] ?>" class="form-control">
        </div>

        <div class="form-group">
          <label>Wordpress URL</label>
          <input type="text" placeholder="Site URL" name="site_url" value="<?php echo $mailmunch_data["site_url"] ?>" class="form-control">
        </div>

        <div class="form-group">
          <label>Email Address</label>
          <input type="email" placeholder="Email Address" name="email" value="<?php echo $current_user->user_email ?>" class="form-control">
        </div>

        <div class="form-group">
          <label>Password</label>
          <input type="password" placeholder="Password" name="password" class="form-control" />
        </div>

        <div class="form-group">
          <input type="submit" value="Sign Up &raquo;" class="btn btn-success btn-lg" />
        </div>
      </form>
    </div>

    <p>Already have an account? <a id="show-sign-in" onclick="showSignInForm();">Sign In</a></p>
  </div>

  <div id="sign-in-form" class="<?php if ($_POST && ($_POST["action"] == "sign_in" || $_POST["action"] == "unlink_account")) { ?> active<?php } ?>">
    <h2 class="modal-header">Sign In</h2>
    <p>Sign in using your email and password below.</p>

    <?php if ($_POST && $_POST["action"] == "sign_in") { ?>
    <div id="invalid-alert" class="alert alert-danger signin-alert" role="alert">Invalid Email or Password. Please try again.</div>
    <?php } ?>

    <div class="form-container">
      <form action="" method="POST">
        <input type="hidden" name="action" value="sign_in" />

        <div class="form-group">
          <label>Email Address</label>
          <input type="email" placeholder="Email Address" name="email" class="form-control" value="<?php if (isset($_POST["email"])) { echo $_POST["email"]; } ?>" />
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" placeholder="Password" name="password" class="form-control" />
        </div>

        <div class="form-group">
          <input type="submit" value="Sign In &raquo;" class="btn btn-success btn-lg" />
        </div>
      </form>
    </div>

    <p>Forgot your password? <a href="http://<?php echo MAILMUNCH_URL; ?>/users/password/new" target="_blank">Click here</a> to retrieve it.</p>
    <p>Don't have an account? <a id="show-sign-up" onclick="showSignUpForm();">Sign Up</a></p>
  </div>
</div>

<?php
      if ($_POST) { 
?>
<script>
jQuery(window).load(function() {
  <?php if ($_POST && ($_POST["action"] == "sign_in" || $_POST["action"] == "unlink_account")) { ?>
  showSignInForm();
  <?php } else { ?>
  showSignUpForm();
  <?php } ?>
});
</script>
<?php
      }
    }
  }
