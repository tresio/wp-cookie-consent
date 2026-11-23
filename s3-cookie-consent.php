<?php
/**
 * Plugin Name:       S3 Cookie Consent
 * Plugin URI:        https://github.com/tresio/wp-cookie-consent
 * Description:       Cookie consent banner and script gating, ported from the Tresio CMS consent widget.
 * Version:           0.3.1
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Studio 3 Marketing
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       s3-cookie-consent
 * Update URI:        https://github.com/tresio/wp-cookie-consent
 */

defined( 'ABSPATH' ) || exit;

define( 'S3CC_VERSION', '0.3.1' );
define( 'S3CC_FILE', __FILE__ );
define( 'S3CC_DIR', plugin_dir_path( __FILE__ ) );
define( 'S3CC_URL', plugin_dir_url( __FILE__ ) );
define( 'S3CC_GITHUB_REPO', 'tresio/wp-cookie-consent' );

require_once S3CC_DIR . 'includes/class-s3cc-settings.php';
require_once S3CC_DIR . 'includes/class-s3cc-frontend.php';
require_once S3CC_DIR . 'includes/class-s3cc-policy.php';
require_once S3CC_DIR . 'includes/class-s3cc-updater.php';

S3CC_Frontend::init();
S3CC_Policy::init();
S3CC_Updater::init();

if ( is_admin() ) {
	require_once S3CC_DIR . 'includes/class-s3cc-admin.php';
	S3CC_Admin::init();
}
