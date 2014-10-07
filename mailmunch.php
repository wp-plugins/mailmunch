<?php
  /*
  Plugin Name: MailMunch
  Plugin URI: http://www.mailmunch.co
  Description: Collect email addresses from website visitors and grow your subscribers with our attention grabbing optin-forms, entry/exit intent technology, and other effective lead-generation forms.
  Version: 1.3.4
  Author: MailMunch
  Author URI: http://www.mailmunch.co
  License: GPL2
  */

  require_once( plugin_dir_path( __FILE__ ) . 'inc/mailmunchapi.php' );
  require_once( plugin_dir_path( __FILE__ ) . 'inc/common.php' );

  define( 'MAILMUNCH_SLUG', "mailmunch");
  define( 'MAILMUNCH_VER', "1.3.4");
  define( 'MAILMUNCH_URL', "www.mailmunch.co");

  // Adding Admin Menu
  add_action( 'admin_menu', 'register_mailmunch_page' );

  function register_mailmunch_page(){
     $menu_page = add_menu_page( 'MailMunch Settings', 'MailMunch', 'manage_options', MAILMUNCH_SLUG, 'mailmunch_setup', plugins_url( 'img/icon.png', __FILE__ ), 102.786 ); 
     // If successful, load admin assets only on that page.
     if ($menu_page) add_action('load-' . $menu_page, 'load_plugin_assets');
  }

  function load_plugin_assets() {
    add_action( 'admin_enqueue_scripts', 'enqueue_admin_styles' );
    add_action( 'admin_enqueue_scripts', 'enqueue_admin_scripts'  );
  }

  function enqueue_admin_styles() {
    wp_enqueue_style(MAILMUNCH_SLUG . '-admin-styles', plugins_url( 'css/admin.css', __FILE__ ), array(), MAILMUNCH_VER );
  }

  function enqueue_admin_scripts() {
    wp_enqueue_script(MAILMUNCH_SLUG . '-admin-script', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), MAILMUNCH_VER );
  }

  // Adding MailMunch Asset Files (JS + CSS) 
  function load_mailmunch_asset_code() {
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

    echo "(function(){ setTimeout(function(){ var d = document, f = d.getElementsByTagName('script')[0], s = d.createElement('script'); s.type = 'text/javascript'; s.async = true; s.src = '".$mailmunch_data["script_src"]."'; f.parentNode.insertBefore(s, f); }, 1); })();";
    echo "</script>";
  }

  add_action('init', 'mailmunch_assets');

  function mailmunch_assets() {
    $mailmunch_data = unserialize(get_option("mailmunch_data"));
    if (count($mailmunch_data) == 0) return;

    if (function_exists('wp_footer')) {
      if (!$_POST['mailmunch_data']) {
        add_action( 'wp_footer', 'load_mailmunch_asset_code' ); 
      }
    }
    elseif (function_exists('wp_head')) {
      if (!$_POST['mailmunch_data']) {
        add_action( 'wp_head', 'load_mailmunch_asset_code' ); 
      }
    }
  }

  function add_post_containers($content) {
    if (is_single() || is_page()) {
      $content = insert_form_after_paragraph("<div class='mailmunch-forms-in-post-middle' style='display: none !important;'></div>", "middle", $content);
      $content = "<div class='mailmunch-forms-before-post' style='display: none !important;'></div>" . $content . "<div class='mailmunch-forms-after-post' style='display: none !important;'></div>";
    }

    return $content;
  }

  function insert_form_after_paragraph($insertion, $paragraph_id, $content) {
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

  add_filter( 'the_content', 'add_post_containers' );

  function shortcode_form($atts) {
    return "<div class='mailmunch-forms-short-code mailmunch-forms-widget-".$atts['id']."' style='display: none !important;'></div>";
  }

  add_shortcode('mailmunch-form', 'shortcode_form');

  function mailmunch_setup() {
    $mailmunch_data = unserialize(get_option("mailmunch_data"));
    $mailmunch_data["site_url"] = home_url();
    $mailmunch_data["site_name"] = get_bloginfo();
    update_option("mailmunch_data", serialize($mailmunch_data));

    // This is a POST request. Let's save data first.
    if ($_POST) {
      $post_data = $_POST["mailmunch_data"];
      $post_action = $_POST["action"];

      if ($post_action == "save_settings") { 

        $mailmunch_data = array_merge(unserialize(get_option('mailmunch_data')), $post_data);
        update_option("mailmunch_data", serialize($mailmunch_data));
      
      } else if ($post_action == "sign_in") {

        $mm = new MailmunchApi($_POST["email"], $_POST["password"], "http://".MAILMUNCH_URL);
        if ($mm->validPassword()) {
          update_option("mailmunch_user_email", $_POST["email"]);
          update_option("mailmunch_user_password", $_POST["password"]);
        }

      } else if ($post_action == "grant_permissions") {

        update_option("mailmunch_permissions_granted", true);
        update_option("mailmunch_user_email", $_POST["email"]);

      } else if ($post_action == "unlink_account") {

        $mailmunch_data = array();
        $mailmunch_data["site_url"] = home_url();
        $mailmunch_data["site_name"] = get_bloginfo();
        update_option("mailmunch_data", serialize($mailmunch_data));
        delete_option("mailmunch_user_email", "");
        delete_option("mailmunch_user_password", "");

      } else if ($post_action == "delete_widget") {

        if ($_POST["site_id"] && $_POST["widget_id"]) {
          $account_info = getEmailPassword();
          $mailmunch_email = $account_info['email'];
          $mailmunch_password = $account_info['password'];
          $mm = new MailmunchApi($account_info['email'], $account_info["password"], "http://".MAILMUNCH_URL);
          $request = $mm->deleteWidget($_POST["site_id"], $_POST["widget_id"]);
        }

      }
    }

    if (get_option("mailmunch_permissions_granted") == "" && get_option("mailmunch_user_email") == "") {
      $current_user = wp_get_current_user();
?>
<div class="container">
  <div class="page-header">
    <h1>Activate MailMunch</h1>
  </div>

  <div class="alert alert-warning">To activate the plugin, please make sure the following information is correct.</div>

  <div id="sign-in-form" class="form-container">
    <form action="" method="POST">
      <input type="hidden" name="action" value="grant_permissions" />

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
        <input type="submit" value="Activate &raquo;" class="btn btn-success btn-lg" />
      </div>
    </form>
  </div>
</div>
<?php
      return;
    }

    $account_info = getEmailPassword();
    $mailmunch_email = $account_info['email'];
    $mailmunch_password = $account_info['password'];

    $mm = new MailmunchApi($mailmunch_email, $mailmunch_password, "http://".MAILMUNCH_URL);
    if ($mm->isNewUser()) {
      $mm->signUp();
    } else if (!$mm->validPassword()) {
?>
<div class="container">
  <div class="page-header">
    <h1>Sign In</h1>
  </div>

  <p>You may already have a MailMunch account. Sign in using your email and password below.</p>

  <?php if ($_POST && $_POST["action"] == "sign_in") { ?>
  <div id="invalid-alert" class="alert alert-danger" role="alert">Invalid Email or Password. Please try again.</div>
  <?php } ?>

  <div id="sign-in-form" class="form-container">
    <form action="" method="POST">
      <input type="hidden" name="action" value="sign_in" />

      <div class="form-group">
        <label>Email Address</label>
        <input type="email" placeholder="Email Address" name="email" class="form-control">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" placeholder="Password" name="password" class="form-control">
      </div>

      <div class="form-group">
        <input type="submit" value="Sign In" class="btn btn-success btn-lg" />
      </div>
    </form>
  </div>

  <p>Forgot your password? <a href="http://<?php echo MAILMUNCH_URL; ?>/users/password/new" target="_blank">Click here</a> to retrieve it.</p>
</div>
<?php
      return;
    }

    $sites = createAndGetSites($mm);

    if ($mailmunch_data["site_id"]) {
      // If there's a site already chosen, we need to get and save it's script_src in WordPress
      $site = getSite($sites, $mailmunch_data["site_id"]);
      
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

    if (!$mailmunch_data["site_id"]) {
      // If there's NO chosen site yet

      if (sizeof($sites) == 1 && $sites[0]->name == get_bloginfo()) {
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
        <input type="submit" value="Save Settings" />
      </div>
    </form>
  </div>
<?php
        return;
      }
    }

    $request = $mm->getWidgetsHtml($mailmunch_data["site_id"]);
    $widgets = $request->body;
    $widgets = str_replace("{{EMAIL}}", $mailmunch_email, $widgets);
    $widgets = str_replace("{{PASSWORD}}", $mailmunch_password, $widgets);
    echo $widgets;
  }
?>