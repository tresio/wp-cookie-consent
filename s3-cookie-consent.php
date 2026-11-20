<?php
/**
 * Plugin Name:       S3 Cookie Consent
 * Description:       Cookie consent banner and script gating, ported from the Tresio CMS consent widget.
 * Version:           0.1.0
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Studio 3 Marketing
 * Text Domain:       s3-cookie-consent
 */

defined( 'ABSPATH' ) || exit;

define( 'S3CC_VERSION', '0.1.0' );
define( 'S3CC_FILE', __FILE__ );
define( 'S3CC_DIR', plugin_dir_path( __FILE__ ) );
define( 'S3CC_URL', plugin_dir_url( __FILE__ ) );

require_once S3CC_DIR . 'includes/class-s3cc-settings.php';
require_once S3CC_DIR . 'includes/class-s3cc-frontend.php';
require_once S3CC_DIR . 'includes/class-s3cc-policy.php';

S3CC_Frontend::init();
S3CC_Policy::init();

if ( is_admin() ) {
	require_once S3CC_DIR . 'includes/class-s3cc-admin.php';
	S3CC_Admin::init();
}
