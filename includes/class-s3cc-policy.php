<?php
defined( 'ABSPATH' ) || exit;

/**
 * [s3_cookie_policy] renders the policy text; [s3_cookie_settings] renders a link that reopens the banner.
 * Also creates the /cookie-policy/ page once consent is enabled, and keeps it out of search.
 */
class S3CC_Policy {

	const SLUG          = 'cookie-policy';
	const PAGE_OPTION   = 's3cc_policy_page_id';
	const META_CREATED  = '_s3cc_created';
	const META_DRAFTED  = '_s3cc_auto_drafted';

	public static function init() {
		add_shortcode( 's3_cookie_policy', array( __CLASS__, 'policy' ) );
		add_shortcode( 's3_cookie_settings', array( __CLASS__, 'settings_link' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_create_page' ) );
		add_action( 'update_option_' . S3CC_Settings::OPTION, array( __CLASS__, 'sync_page_status' ), 10, 2 );
		add_filter( 'wp_robots', array( __CLASS__, 'noindex' ) );
		add_filter( 'wp_sitemaps_posts_query_args', array( __CLASS__, 'exclude_from_sitemap' ), 10, 2 );
	}

	public static function settings_link( $atts ) {
		$atts = shortcode_atts( array( 'text' => 'Cookie Settings' ), $atts, 's3_cookie_settings' );
		return '<a href="#cookie-settings" role="button" data-s3-cookie-settings hidden>' . esc_html( $atts['text'] ) . '</a>';
	}

	public static function policy() {
		$s       = S3CC_Settings::get();
		$name    = S3CC_Settings::legal_name( $s );
		$privacy = $s['policy_privacy_url'] ? $s['policy_privacy_url'] : home_url( '/privacy-policy/' );
		ob_start();
		include S3CC_DIR . 'templates/policy.php';
		return ob_get_clean();
	}

	public static function page_id() {
		return (int) get_option( self::PAGE_OPTION, 0 );
	}

	/**
	 * Runs once: adopts an existing cookie-policy page, or creates and publishes one. A deleted page is not recreated.
	 */
	public static function maybe_create_page() {
		if ( false !== get_option( self::PAGE_OPTION, false ) || ! current_user_can( 'publish_pages' ) ) {
			return;
		}
		$s = S3CC_Settings::get();
		if ( ! $s['enabled'] ) {
			return;
		}

		$existing = get_page_by_path( self::SLUG );
		if ( $existing ) {
			update_option( self::PAGE_OPTION, (int) $existing->ID, false );
			return;
		}

		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Cookie Policy',
				'post_name'    => self::SLUG,
				'post_content' => "<!-- wp:shortcode -->\n[s3_cookie_policy]\n<!-- /wp:shortcode -->",
				'meta_input'   => array(
					self::META_CREATED                 => '1',
					'_yoast_wpseo_meta-robots-noindex' => '1',
					'rank_math_robots'                 => array( 'noindex' ),
				),
			),
			true
		);
		if ( ! is_wp_error( $id ) ) {
			update_option( self::PAGE_OPTION, (int) $id, false );
		}
	}

	/**
	 * Consent off drafts the page the plugin created; consent on republishes it, but only if the plugin drafted it.
	 */
	public static function sync_page_status( $old, $new ) {
		$was = is_array( $old ) && ! empty( $old['enabled'] );
		$now = is_array( $new ) && ! empty( $new['enabled'] );
		$id  = self::page_id();
		if ( $was === $now || ! $id || ! get_post_meta( $id, self::META_CREATED, true ) ) {
			return;
		}
		$status = get_post_status( $id );

		if ( ! $now && 'publish' === $status ) {
			wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );
			update_post_meta( $id, self::META_DRAFTED, '1' );
			return;
		}
		if ( $now ) {
			if ( 'draft' === $status && get_post_meta( $id, self::META_DRAFTED, true ) ) {
				wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
			}
			delete_post_meta( $id, self::META_DRAFTED );
		}
	}

	public static function noindex( $robots ) {
		$id = self::page_id();
		if ( $id && is_page( $id ) ) {
			$robots['noindex'] = true;
			unset( $robots['max-image-preview'] );
		}
		return $robots;
	}

	public static function exclude_from_sitemap( $args, $post_type ) {
		$id = self::page_id();
		if ( $id && 'page' === $post_type ) {
			$args['post__not_in'] = array_merge( isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array(), array( $id ) );
		}
		return $args;
	}
}
