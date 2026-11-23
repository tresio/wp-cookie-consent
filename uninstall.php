<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 's3_cookie_consent' );
delete_option( 's3cc_policy_page_id' );
