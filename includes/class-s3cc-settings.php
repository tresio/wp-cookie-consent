<?php
defined( 'ABSPATH' ) || exit;

/**
 * Reads the plugin option and fills in defaults, the WordPress side of normalizeCookiesConfig.js.
 */
class S3CC_Settings {

	const OPTION           = 's3_cookie_consent';
	const GA_MODES         = array( 'essential', 'optional', 'cookieless' );
	const SCRIPT_LOCATIONS = array( 'head', 'body', 'footer' );

	const DEFAULT_PREFERENCES = array(
		'essential'    => array(
			'label' => 'Essential',
			'desc'  => 'Required for the website to work, including security, forms, call and enquiry tracking, and remembering your cookie choice.',
		),
		'nonEssential' => array(
			'label' => 'Non-Essential',
			'desc'  => 'Analytics, advertising and other third-party tools that help us measure and improve the website.',
		),
	);

	public static function defaults() {
		return array(
			'enabled'                 => false,
			'title'                   => 'Your Privacy Choices',
			'content'                 => '<p>We use essential cookies to make this website work. With your permission, we would also like to use non-essential cookies to understand how you use our website and to measure our advertising. You can accept or reject non-essential cookies, and change your choice at any time using "Cookie Settings" in the website footer. For more information, see our <a href="/cookie-policy/">Cookie Policy</a>.</p>',
			'preferences'             => false,
			'pref_essential_label'    => '',
			'pref_essential_desc'     => '',
			'pref_nonessential_label' => '',
			'pref_nonessential_desc'  => '',
			'ga_ids'                  => '',
			'ga_mode'                 => 'essential',
			'ga_gateway_path'         => '',
			'wc_id'                   => '',
			'wc_v2'                   => false,
			'wc_essential'            => true,
			'meta_pixel_ids'          => '',
			'scripts'                 => array(),
			'policy_legal_name'       => '',
			'policy_email'            => '',
			'policy_privacy_url'      => '',
			'policy_address'          => '',
			'policy_phone'            => '',
			'policy_updated'          => 'November 2, 2026',
		);
	}

	public static function get() {
		$saved = get_option( self::OPTION, array() );
		$s     = array_merge( self::defaults(), is_array( $saved ) ? $saved : array() );

		if ( ! in_array( $s['ga_mode'], self::GA_MODES, true ) ) {
			$s['ga_mode'] = 'essential';
		}
		$s['scripts'] = self::normalize_scripts( $s['scripts'] );

		return $s;
	}

	public static function normalize_scripts( $scripts ) {
		$out = array();
		foreach ( is_array( $scripts ) ? $scripts : array() as $row ) {
			if ( ! is_array( $row ) || empty( $row['id'] ) ) {
				continue;
			}
			$out[] = array(
				'id'        => (string) $row['id'],
				'name'      => isset( $row['name'] ) ? (string) $row['name'] : '',
				'location'  => isset( $row['location'] ) && in_array( $row['location'], self::SCRIPT_LOCATIONS, true ) ? $row['location'] : 'head',
				'essential' => ! empty( $row['essential'] ),
				'active'    => ! empty( $row['active'] ),
				'code'      => isset( $row['code'] ) ? (string) $row['code'] : '',
			);
		}
		return $out;
	}

	/**
	 * Preferences panel entries, or null when the panel is off. Blank labels and descriptions fall back to the defaults.
	 */
	public static function preferences( $s ) {
		if ( empty( $s['preferences'] ) ) {
			return null;
		}
		$d = self::DEFAULT_PREFERENCES;
		return array(
			'essential'    => array(
				array(
					'label' => $s['pref_essential_label'] ? $s['pref_essential_label'] : $d['essential']['label'],
					'desc'  => $s['pref_essential_desc'] ? $s['pref_essential_desc'] : $d['essential']['desc'],
				),
			),
			'nonEssential' => array(
				array(
					'label' => $s['pref_nonessential_label'] ? $s['pref_nonessential_label'] : $d['nonEssential']['label'],
					'desc'  => $s['pref_nonessential_desc'] ? $s['pref_nonessential_desc'] : $d['nonEssential']['desc'],
				),
			),
		);
	}

	/**
	 * Legal name setting, then the Yoast or Rank Math organisation name, then the Site Title.
	 */
	public static function legal_name( $s ) {
		if ( '' !== $s['policy_legal_name'] ) {
			return $s['policy_legal_name'];
		}
		return self::seo_org_name() ?: get_bloginfo( 'name' );
	}

	public static function seo_org_name() {
		$yoast = get_option( 'wpseo_titles' );
		if ( is_array( $yoast ) && ! empty( $yoast['company_name'] ) ) {
			return trim( (string) $yoast['company_name'] );
		}
		$rank_math = get_option( 'rank-math-options-titles' );
		if ( is_array( $rank_math ) && ! empty( $rank_math['knowledgegraph_name'] ) ) {
			return trim( (string) $rank_math['knowledgegraph_name'] );
		}
		return '';
	}

	public static function split_ids( $value ) {
		return array_values( array_filter( array_map( 'trim', explode( ',', (string) $value ) ), 'strlen' ) );
	}

	/**
	 * Google tags only load in production, or anywhere with ?loadTags=1 (same switch as the CMS).
	 */
	public static function is_live() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$override = isset( $_GET['loadTags'] ) && '1' === $_GET['loadTags'];
		return $override || 'production' === wp_get_environment_type();
	}

	public static function has_builtin_tags( $s ) {
		return '' !== $s['ga_ids'] || '' !== $s['wc_id'] || '' !== $s['meta_pixel_ids'];
	}

	public static function js_config( $s ) {
		$live = self::is_live();
		return array(
			'enabled'   => (bool) $s['enabled'],
			'ga'        => array(
				'ids'         => self::split_ids( $s['ga_ids'] ),
				'mode'        => $s['ga_mode'],
				'live'        => $live,
				'gatewayPath' => $live ? $s['ga_gateway_path'] : '',
			),
			'wc'        => array(
				'id'        => $s['wc_id'],
				'v2'        => (bool) $s['wc_v2'],
				'essential' => (bool) $s['wc_essential'],
			),
			'metaPixel' => array(
				'ids' => self::split_ids( $s['meta_pixel_ids'] ),
			),
		);
	}

	/**
	 * JSON that is safe to print inside an inline <script>.
	 */
	public static function json( $value ) {
		return wp_json_encode( $value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}
}
