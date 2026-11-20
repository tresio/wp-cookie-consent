<?php
defined( 'ABSPATH' ) || exit;

/**
 * [s3_cookie_policy] renders the policy text; [s3_cookie_settings] renders a link that reopens the banner.
 */
class S3CC_Policy {

	public static function init() {
		add_shortcode( 's3_cookie_policy', array( __CLASS__, 'policy' ) );
		add_shortcode( 's3_cookie_settings', array( __CLASS__, 'settings_link' ) );
	}

	public static function settings_link( $atts ) {
		$atts = shortcode_atts( array( 'text' => 'Cookie Settings' ), $atts, 's3_cookie_settings' );
		return '<a href="#cookie-settings" role="button" data-s3-cookie-settings hidden>' . esc_html( $atts['text'] ) . '</a>';
	}

	public static function policy() {
		$s       = S3CC_Settings::get();
		$name    = $s['policy_legal_name'] ? $s['policy_legal_name'] : get_bloginfo( 'name' );
		$privacy = $s['policy_privacy_url'] ? $s['policy_privacy_url'] : home_url( '/privacy-policy/' );
		ob_start();
		include S3CC_DIR . 'templates/policy.php';
		return ob_get_clean();
	}
}
