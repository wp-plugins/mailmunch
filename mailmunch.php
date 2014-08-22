<?php
  /*
  Plugin Name: MailMunch
  Plugin URI: http://www.mailmunch.co
  Description: Collect email addresses from website visitors and grow your subscribers with our attention grabbing optin-forms, entry/exit intent technology, and other effective lead-generation forms.
  Version: 1.2
  Author: MailMunch
  Author URI: http://www.mailmunch.co
  License: GPL2
  */

  require_once( plugin_dir_path( __FILE__ ) . 'inc/mailmunchapi.php' );
  require_once( plugin_dir_path( __FILE__ ) . 'inc/common.php' );

  define( 'MAILMUNCH_SLUG', "mailmunch");
  define( 'MAILMUNCH_VER', "1.0.1");
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

    echo "<script type='text/javascript'>";
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
      } else if ($post_action == "unlink_account") {
        $mailmunch_data = array();
        $mailmunch_data["site_url"] = home_url();
        $mailmunch_data["site_name"] = get_bloginfo();
        update_option("mailmunch_data", serialize($mailmunch_data));
        delete_option("mailmunch_user_email", "");
        delete_option("mailmunch_user_password", "");
      }
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

    $request = $mm->widgets($mailmunch_data["site_id"]);
    $widgets = json_decode($request->body);
?>

<?php
    if (sizeof($widgets) > 0) {
?>
<div class="container">
  <div class="page-header">
    <h1 class="pull-left">Optin Forms</h1>

    <div class="pull-right action-btns">
      <form id="unlink-account" class="pull-left" action="" method="POST" onsubmit="return confirm('Are you sure you want to switch to another MailMunch account?')">
        <input type="hidden" name="action" value="unlink_account" />
        <input type="submit" value="Switch Account" class="unlink-btn" />
      </form>

      <a class="btn btn-success btn-sm new-optin-btn" href="http://<?php echo MAILMUNCH_URL; ?>/sso?email=<?php echo $mailmunch_email; ?>&password=<?php echo $mailmunch_password; ?>&next_url=<?php echo urlencode("/sites/".$mailmunch_data["site_id"]."/widgets/new?wordpress=1"); ?>" target="_mailmunch_window">New Optin Form</a>
    </div>
    <div class="clearfix"></div>
  </div>

  <table class="table table-condensed" style="margin-top: 20px;">
    <thead>
      <tr>
        <th>Name</th>
        <th>Exit Intent</th>
        <th>Loading Delay</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($widgets as $widget) { ?>
      <tr id="widget_<?php echo $widget->id ?>">
        <td><?php echo $widget->name ?></td>
        <td><?php if ($widget->exit_intent) { echo "Yes"; } else { echo "No"; } ?></td>
        <td><?php if ($widget->loading_delay) { echo $widget->loading_delay; } else { echo "0"; } ?> seconds</td>
        <td>
          <?php if (widget.enabled) { ?>
          <span class="label label-success">Active</span>
          <?php } else { ?>
          <span class="label label-danger">Inactive</span>
          <?php } ?>
        </td>
        <td class="actions">
          <div class="btn-group">
            <a href="http://<?php echo MAILMUNCH_URL; ?>/sso?email=<?php echo $mailmunch_email; ?>&password=<?php echo $mailmunch_password; ?>&next_url=<?php echo urlencode("/sites/".$mailmunch_data["site_id"]."/widgets/".$widget->id."/design?wp_layout=1"); ?>" target="_mailmunch_window" class="btn btn-info btn-sm">Edit Optin Form</a>
          </div>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</div>
<?php
    } else {
?>
<div class="container">
  <div class="page-header">
    <h1 class="pull-left">Optin Forms</h1>

    <div class="pull-right">
      <form id="unlink-account" action="" method="POST" onsubmit="return confirm('Are you sure you want to switch to another MailMunch account?')">
        <input type="hidden" name="action" value="unlink_account" />
        <input type="submit" value="Switch MailMunch Account" class="unlink-btn" />
      </form>
    </div>
    <div class="clearfix"></div>
  </div>

  <div class="alert alert-warning alert-dismissable text-center">
    <strong>Almost There!</strong> You have no Optin Forms yet. Click the button below to create your first one.
  </div>

  <div class="text-center">
    <a href="http://<?php echo MAILMUNCH_URL; ?>/sso?email=<?php echo $mailmunch_email; ?>&password=<?php echo $mailmunch_password; ?>&next_url=<?php echo urlencode("/sites/".$mailmunch_data["site_id"]."/widgets/new?wordpress=1"); ?>" target="_mailmunch_window" class="btn btn-success btn-lg">Create Your First Optin Form</a>
  </div>
</div>
<?php
    }
  }
?>