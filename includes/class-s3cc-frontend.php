<?php
defined( 'ABSPATH' ) || exit;

/**
 * Prints the consent bootstrap, banner and scripts. Output never varies by visitor, so pages stay cacheable.
 */
class S3CC_Frontend {

	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'print_bootstrap' ), 1 );
		add_action( 'wp_head', array( __CLASS__, 'print_head_scripts' ), 99 );
		add_action( 'wp_body_open', array( __CLASS__, 'print_body_open' ), 1 );
		add_action( 'wp_footer', array( __CLASS__, 'print_footer' ), 99 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_style' ) );
	}

	/**
	 * Inline script tag excluded from WP Rocket, LiteSpeed and Cloudflare Rocket Loader deferral.
	 */
	private static function inline_script( $js ) {
		echo '<script data-no-optimize="1" data-no-defer="1" data-cfasync="false" nowprocket>' . $js . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	private static function asset( $path ) {
		$file = S3CC_DIR . 'assets/' . $path;
		return is_readable( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}

	public static function enqueue_style() {
		$s = S3CC_Settings::get();
		if ( $s['enabled'] ) {
			wp_enqueue_style( 's3-cookie-consent', S3CC_URL . 'assets/css/consent.css', array(), S3CC_VERSION );
		}
	}

	public static function print_bootstrap() {
		$s = S3CC_Settings::get();
		if ( ! $s['enabled'] && ! S3CC_Settings::has_builtin_tags( $s ) ) {
			return;
		}
		self::inline_script(
			'window.s3CookieConsentConfig=' . S3CC_Settings::json( S3CC_Settings::js_config( $s ) ) . ";\n" . self::asset( 'js/consent-head.js' )
		);
	}

	public static function print_head_scripts() {
		self::print_scripts( 'head', S3CC_Settings::get() );
	}

	public static function print_body_open() {
		$s = S3CC_Settings::get();
		if ( $s['enabled'] ) {
			$prefs = S3CC_Settings::preferences( $s );
			include S3CC_DIR . 'templates/banner.php';
		}
		self::print_scripts( 'body', $s );
	}

	public static function print_footer() {
		$s = S3CC_Settings::get();
		if ( ! did_action( 'wp_body_open' ) ) {
			self::print_body_open();
		}
		self::print_scripts( 'footer', $s );
		if ( $s['enabled'] ) {
			self::inline_script( self::asset( 'js/consent-banner.js' ) );
		}
	}

	private static function print_scripts( $location, $s ) {
		foreach ( $s['scripts'] as $script ) {
			if ( ! $script['active'] || $location !== $script['location'] || '' === $script['code'] ) {
				continue;
			}
			if ( ! $s['enabled'] || $script['essential'] ) {
				echo "\n" . $script['code'] . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				continue;
			}
			self::inline_script(
				sprintf(
					'window.s3ConsentCustomScripts.register(%s,%s,%s,%s);',
					S3CC_Settings::json( 'script-' . $script['id'] ),
					S3CC_Settings::json( 'head' === $location ? 'head' : 'body' ),
					S3CC_Settings::json( $script['code'] ),
					S3CC_Settings::json( 'S3 Cookie Consent: loaded ' . $script['name'] )
				)
			);
		}
	}
}
