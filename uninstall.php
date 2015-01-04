<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();

delete_option("mailmunch_data");
delete_option("mailmunch_user_email");
delete_option("mailmunch_user_password");
delete_option("mailmunch_guest_user");
?>
