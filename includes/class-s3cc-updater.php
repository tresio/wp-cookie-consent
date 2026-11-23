<?php
defined( 'ABSPATH' ) || exit;

/**
 * Self-update from GitHub Releases, ported from tresio/wp-cf7-slack-alerts.
 * Auto-install is left to WordPress's own "Enable auto-updates" toggle on the Plugins screen.
 */
class S3CC_Updater {

	const TRANSIENT   = 's3cc_github_release';
	const CACHE_HOURS = 12;

	private static $basename;
	private static $slug;

	public static function init() {
		self::$basename = plugin_basename( S3CC_FILE );
		self::$slug     = dirname( self::$basename );

		// Priority 1 so the cache is dropped before core's own callback on this hook reads it.
		add_action( 'load-update-core.php', array( __CLASS__, 'flush_on_force_check' ), 1 );
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'inject_update' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_details' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'fix_source_directory' ), 10, 4 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'flush_cache' ), 10, 2 );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'row_meta' ), 10, 2 );
	}

	public static function inject_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$release = self::latest_release();
		if ( is_wp_error( $release ) ) {
			return $transient;
		}

		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			$transient->response = array();
		}
		if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
			$transient->no_update = array();
		}

		$item = self::build_update_object( $release );

		if ( version_compare( $release['version'], S3CC_VERSION, '>' ) && '' !== $release['package'] ) {
			$transient->response[ self::$basename ] = $item;
			unset( $transient->no_update[ self::$basename ] );
		} else {
			$transient->no_update[ self::$basename ] = $item;
			unset( $transient->response[ self::$basename ] );
		}

		return $transient;
	}

	public static function plugin_details( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || $args->slug !== self::$slug ) {
			return $result;
		}

		$release = self::latest_release();
		if ( is_wp_error( $release ) ) {
			return $result;
		}

		$data                = new stdClass();
		$data->name          = 'S3 Cookie Consent';
		$data->slug          = self::$slug;
		$data->version       = $release['version'];
		$data->author        = 'Studio 3 Marketing';
		$data->homepage      = 'https://github.com/' . S3CC_GITHUB_REPO;
		$data->download_link = $release['package'];
		$data->trunk         = $release['package'];
		$data->requires      = '5.2';
		$data->requires_php  = '7.4';
		$data->last_updated  = $release['published_at'];
		$data->sections      = array(
			'description' => wpautop( esc_html( 'Cookie consent banner and script gating, ported from the Tresio CMS consent widget.' ) ),
			'changelog'   => self::render_notes( $release['notes'] ),
		);

		return $data;
	}

	/**
	 * Renames the unpacked folder to the installed folder name, or WordPress installs a second copy beside the first.
	 */
	public static function fix_source_directory( $source, $remote_source, $upgrader = null, $hook_extra = array() ) {
		if ( is_wp_error( $source ) || empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== self::$basename ) {
			return $source;
		}

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			return $source;
		}

		$desired = trailingslashit( $remote_source ) . self::$slug;
		if ( untrailingslashit( $source ) === $desired ) {
			return $source;
		}
		if ( $wp_filesystem->exists( $desired ) ) {
			$wp_filesystem->delete( $desired, true );
		}
		if ( ! $wp_filesystem->move( untrailingslashit( $source ), $desired ) ) {
			return new WP_Error( 's3cc_rename_failed', 'Could not rename the downloaded plugin folder.' );
		}

		return trailingslashit( $desired );
	}

	/**
	 * "Check Again" on Dashboard → Updates clears core's cache but not ours, so clear ours too.
	 */
	public static function flush_on_force_check() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['force-check'] ) || ! current_user_can( 'update_plugins' ) ) {
			return;
		}
		delete_transient( self::TRANSIENT );
		delete_transient( self::TRANSIENT . '_backoff' );
	}

	public static function flush_cache( $upgrader, $extra ) {
		if ( isset( $extra['type'] ) && 'plugin' === $extra['type'] ) {
			delete_transient( self::TRANSIENT );
		}
	}

	public static function row_meta( $meta, $basename ) {
		if ( $basename === self::$basename && current_user_can( 'update_plugins' ) ) {
			$meta[] = '<a href="' . esc_url( 'https://github.com/' . S3CC_GITHUB_REPO . '/releases' ) . '" target="_blank" rel="noreferrer noopener">Releases</a>';
		}
		return $meta;
	}

	private static function build_update_object( array $release ) {
		$item                = new stdClass();
		$item->id            = 'github.com/' . S3CC_GITHUB_REPO;
		$item->slug          = self::$slug;
		$item->plugin        = self::$basename;
		$item->new_version   = $release['version'];
		$item->url           = 'https://github.com/' . S3CC_GITHUB_REPO;
		$item->package       = $release['package'];
		$item->icons         = array();
		$item->banners       = array();
		$item->banners_rtl   = array();
		$item->requires      = '5.2';
		$item->requires_php  = '7.4';
		$item->compatibility = new stdClass();
		return $item;
	}

	private static function latest_release() {
		$cached = get_transient( self::TRANSIENT );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		if ( get_transient( self::TRANSIENT . '_backoff' ) ) {
			return new WP_Error( 's3cc_release_lookup_backoff', 'Skipping the GitHub release check after a recent failure.' );
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . S3CC_GITHUB_REPO . '/releases/latest',
			array(
				'timeout'    => 10,
				'headers'    => array(
					'Accept'               => 'application/vnd.github+json',
					'X-GitHub-Api-Version' => '2022-11-28',
				),
				'user-agent' => 's3-cookie-consent/' . S3CC_VERSION,
			)
		);
		if ( is_wp_error( $response ) ) {
			set_transient( self::TRANSIENT . '_backoff', 1, HOUR_IN_SECONDS );
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( 200 !== $code || ! is_array( $body ) || empty( $body['tag_name'] ) ) {
			set_transient( self::TRANSIENT . '_backoff', 1, HOUR_IN_SECONDS );
			return new WP_Error( 's3cc_release_lookup_failed', sprintf( 'Could not read the latest release from GitHub (HTTP %d).', $code ) );
		}

		$release = array(
			'version'      => ltrim( (string) $body['tag_name'], 'vV' ),
			'package'      => self::package_url( $body ),
			'notes'        => isset( $body['body'] ) ? (string) $body['body'] : '',
			'published_at' => isset( $body['published_at'] ) ? (string) $body['published_at'] : '',
		);
		set_transient( self::TRANSIENT, $release, self::CACHE_HOURS * HOUR_IN_SECONDS );

		return $release;
	}

	/**
	 * Prefers the built .zip release asset (shipped files only); falls back to the source zipball.
	 */
	private static function package_url( array $body ) {
		if ( ! empty( $body['assets'] ) && is_array( $body['assets'] ) ) {
			foreach ( $body['assets'] as $asset ) {
				if ( ! empty( $asset['browser_download_url'] ) && ! empty( $asset['name'] ) && '.zip' === strtolower( substr( $asset['name'], -4 ) ) ) {
					return (string) $asset['browser_download_url'];
				}
			}
		}
		return isset( $body['zipball_url'] ) ? (string) $body['zipball_url'] : '';
	}

	private static function render_notes( $notes ) {
		$notes = trim( (string) $notes );
		if ( '' === $notes ) {
			return wpautop( 'No release notes were published.' );
		}

		$html = esc_html( $notes );
		$html = preg_replace( '/^### (.+)$/m', '<h4>$1</h4>', $html );
		$html = preg_replace( '/^## (.+)$/m', '<h3>$1</h3>', $html );
		$html = preg_replace( '/^# (.+)$/m', '<h3>$1</h3>', $html );
		$html = preg_replace( '/^[\*\-] (.+)$/m', '<li>$1</li>', $html );
		$html = preg_replace( '/(<li>.*<\/li>\n?)+/s', '<ul>$0</ul>', $html );
		$html = preg_replace( '/`([^`]+)`/', '<code>$1</code>', $html );

		return wpautop( $html );
	}
}
