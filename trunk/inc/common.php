<?php

function getEmailPassword() {
  $mailmunch_email = get_option("mailmunch_user_email");
  $mailmunch_password = get_option("mailmunch_user_password");

  if (empty($mailmunch_email) || empty($mailmunch_password)) {
    $current_user = wp_get_current_user();
    update_option("mailmunch_user_email", $current_user->user_email);
    update_option("mailmunch_user_password", uniqid());
    $mailmunch_email = get_option("mailmunch_user_email");
    $mailmunch_password = get_option("mailmunch_user_password");
  }

  return array('email' => $mailmunch_email, 'password' => $mailmunch_password);
}

function getSite($sites, $site_id) {
  foreach ($sites as $s) {
    if ($s->id == intval($site_id)) {
      $site = $s;
      break;
    }
  }

  return $site;
}

function createAndGetSites($mm) {
  $site_url = home_url();
  $site_name = get_bloginfo();

  if (!$mm->hasSite()) {
    $mm->createSite($site_name, $site_url);
  }
  $request = $mm->sites();
  if ($request->status_code == 200){
    $sites = $request->body;

    return json_decode($sites);
  }
  else {
    return array();
  }
}

?>