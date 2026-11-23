<?php
defined( 'ABSPATH' ) || exit;

/**
 * Settings → Cookie Consent: one option array, saved through the Settings API.
 */
class S3CC_Admin {

	const PAGE  = 's3-cookie-consent';
	const GROUP = 's3cc';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_notices', array( __CLASS__, 'hfcm_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( S3CC_FILE ), array( __CLASS__, 'action_links' ) );
	}

	public static function menu() {
		add_options_page( 'Cookie Consent', 'Cookie Consent', 'manage_options', self::PAGE, array( __CLASS__, 'render' ) );
	}

	public static function register() {
		register_setting(
			self::GROUP,
			S3CC_Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => array(),
			)
		);
	}

	public static function action_links( $links ) {
		$url = admin_url( 'options-general.php?page=' . self::PAGE );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">Settings</a>' );
		return $links;
	}

	public static function hfcm_notice() {
		if ( ! class_exists( 'NNR_HFCM' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || 'settings_page_' . self::PAGE !== $screen->id ) {
			return;
		}
		echo '<div class="notice notice-warning"><p><strong>Header Footer Code Manager is active.</strong> Its snippets are not gated by cookie consent. Move tracking snippets into the Scripts section below and deactivate them in HFCM.</p></div>';
	}

	public static function sanitize( $input ) {
		$in       = is_array( $input ) ? $input : array();
		$old      = S3CC_Settings::get();
		$can_code = current_user_can( 'unfiltered_html' );
		$text     = function ( $key ) use ( $in ) {
			return isset( $in[ $key ] ) ? sanitize_text_field( $in[ $key ] ) : '';
		};
		$html     = function ( $key ) use ( $in ) {
			return isset( $in[ $key ] ) ? wp_kses_post( $in[ $key ] ) : '';
		};

		$out = array(
			'enabled'                 => ! empty( $in['enabled'] ),
			'title'                   => $html( 'title' ),
			'content'                 => $html( 'content' ),
			'preferences'             => ! empty( $in['preferences'] ),
			'pref_essential_label'    => $text( 'pref_essential_label' ),
			'pref_essential_desc'     => $html( 'pref_essential_desc' ),
			'pref_nonessential_label' => $text( 'pref_nonessential_label' ),
			'pref_nonessential_desc'  => $html( 'pref_nonessential_desc' ),
			'ga_ids'                  => implode( ', ', S3CC_Settings::split_ids( $text( 'ga_ids' ) ) ),
			'ga_essential'            => ! empty( $in['ga_essential'] ),
			'ga_cookieless'           => ! empty( $in['ga_cookieless'] ),
			'ga_gateway_path'         => $text( 'ga_gateway_path' ),
			'wc_id'                   => preg_replace( '/[^A-Za-z0-9_-]/', '', $text( 'wc_id' ) ),
			'wc_v2'                   => ! empty( $in['wc_v2'] ),
			'wc_essential'            => ! empty( $in['wc_essential'] ),
			'meta_pixel_ids'          => implode( ', ', S3CC_Settings::split_ids( preg_replace( '/[^0-9,]/', '', $text( 'meta_pixel_ids' ) ) ) ),
			'policy_legal_name'       => $text( 'policy_legal_name' ),
			'policy_email'            => isset( $in['policy_email'] ) ? sanitize_email( $in['policy_email'] ) : '',
			'policy_privacy_url'      => isset( $in['policy_privacy_url'] ) ? esc_url_raw( $in['policy_privacy_url'] ) : '',
			'policy_address'          => $html( 'policy_address' ),
			'policy_phone'            => $text( 'policy_phone' ),
			'policy_updated'          => $text( 'policy_updated' ),
		);

		$out['scripts'] = $can_code ? self::sanitize_scripts( isset( $in['scripts'] ) ? $in['scripts'] : array() ) : $old['scripts'];

		return $out;
	}

	private static function sanitize_scripts( $rows ) {
		$out = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( ! is_array( $row ) || ! empty( $row['remove'] ) ) {
				continue;
			}
			$name = isset( $row['name'] ) ? sanitize_text_field( $row['name'] ) : '';
			$code = isset( $row['code'] ) ? trim( (string) $row['code'] ) : '';
			if ( '' === $name && '' === $code ) {
				continue;
			}
			$id    = isset( $row['id'] ) ? sanitize_key( $row['id'] ) : '';
			$out[] = array(
				'id'        => $id ? $id : strtolower( wp_generate_password( 8, false ) ),
				'name'      => $name,
				'location'  => isset( $row['location'] ) && in_array( $row['location'], S3CC_Settings::SCRIPT_LOCATIONS, true ) ? $row['location'] : 'head',
				'essential' => ! empty( $row['essential'] ),
				'active'    => ! empty( $row['active'] ),
				'code'      => $code,
			);
		}
		return $out;
	}

	private static function name( $key ) {
		return S3CC_Settings::OPTION . '[' . $key . ']';
	}

	private static function checkbox( $key, $checked, $label ) {
		printf(
			'<label><input type="checkbox" name="%s" value="1" %s> %s</label>',
			esc_attr( self::name( $key ) ),
			checked( $checked, true, false ),
			esc_html( $label )
		);
	}

	private static function text( $key, $value, $placeholder = '', $type = 'text' ) {
		printf(
			'<input type="%s" class="regular-text" name="%s" value="%s" placeholder="%s">',
			esc_attr( $type ),
			esc_attr( self::name( $key ) ),
			esc_attr( $value ),
			esc_attr( $placeholder )
		);
	}

	private static function textarea( $key, $value, $rows = 3, $placeholder = '' ) {
		printf(
			'<textarea class="large-text" rows="%d" name="%s" placeholder="%s">%s</textarea>',
			(int) $rows,
			esc_attr( self::name( $key ) ),
			esc_attr( $placeholder ),
			esc_textarea( $value )
		);
	}

	private static function row( $label, $render, $help = '' ) {
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
		$render();
		if ( $help ) {
			echo '<p class="description">' . wp_kses_post( $help ) . '</p>';
		}
		echo '</td></tr>';
	}

	private static function script_row( $index, $script, $can_code ) {
		$base = self::name( 'scripts' ) . '[' . $index . ']';
		?>
		<div class="s3cc-script">
			<input type="hidden" name="<?php echo esc_attr( $base ); ?>[id]" value="<?php echo esc_attr( $script['id'] ); ?>">
			<div class="s3cc-script-head">
				<input type="text" name="<?php echo esc_attr( $base ); ?>[name]" value="<?php echo esc_attr( $script['name'] ); ?>" placeholder="Name, e.g. Google Tag Manager">
				<select name="<?php echo esc_attr( $base ); ?>[location]">
					<?php foreach ( array( 'head' => 'Head', 'body' => 'Body open', 'footer' => 'Footer' ) as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $script['location'], $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<label><input type="checkbox" name="<?php echo esc_attr( $base ); ?>[essential]" value="1" <?php checked( $script['essential'] ); ?>> Essential</label>
				<label><input type="checkbox" name="<?php echo esc_attr( $base ); ?>[active]" value="1" <?php checked( $script['active'] ); ?>> Active</label>
				<label class="s3cc-remove"><input type="checkbox" name="<?php echo esc_attr( $base ); ?>[remove]" value="1"> Remove</label>
			</div>
			<textarea class="large-text code" rows="5" name="<?php echo esc_attr( $base ); ?>[code]" placeholder="&lt;script&gt;...&lt;/script&gt;" <?php disabled( ! $can_code ); ?>><?php echo esc_textarea( $script['code'] ); ?></textarea>
		</div>
		<?php
	}

	private static function policy_page_status( $s ) {
		$id   = S3CC_Policy::page_id();
		$page = $id ? get_post( $id ) : null;

		if ( $page && 'trash' !== $page->post_status ) {
			if ( 'publish' === $page->post_status ) {
				$status = 'Published';
			} elseif ( get_post_meta( $page->ID, S3CC_Policy::META_DRAFTED, true ) ) {
				$status = 'Draft (Consent is off; it is republished when you turn Consent back on)';
			} else {
				$status = ucfirst( $page->post_status ) . ' (not live)';
			}
			printf(
				'<p class="description">Policy page: <strong>%s</strong> · %s · <a href="%s" target="_blank">View</a> · <a href="%s">Edit</a>. It is noindexed and left out of the sitemap. Link to it from the footer.</p>',
				esc_html( get_the_title( $page ) ),
				esc_html( $status ),
				esc_url( get_permalink( $page ) ),
				esc_url( get_edit_post_link( $page ) )
			);
			return;
		}
		if ( false !== get_option( S3CC_Policy::PAGE_OPTION, false ) ) {
			echo '<p class="description"><strong>The policy page was deleted.</strong> To restore it, create a page with the slug <code>cookie-policy</code> containing <code>[s3_cookie_policy]</code>.</p>';
			return;
		}
		echo '<p class="description">A published <code>/cookie-policy/</code> page is created when you turn on Consent and save. If that page already exists, it is used as is.</p>';
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s        = S3CC_Settings::get();
		$can_code = current_user_can( 'unfiltered_html' );
		$blank    = array(
			'id'        => '',
			'name'      => '',
			'location'  => 'head',
			'essential' => false,
			'active'    => true,
			'code'      => '',
		);
		?>
		<div class="wrap s3cc-admin">
			<h1>Cookie Consent</h1>
			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>

				<h2>Banner</h2>
				<table class="form-table" role="presentation">
					<?php
					self::row(
						'Consent',
						function () use ( $s ) {
							self::checkbox( 'enabled', $s['enabled'], 'Show the banner and hold non-essential scripts until the visitor accepts' );
						},
						'Off: no banner, and every tag and script below loads on every page view.'
					);
					self::row(
						'Title',
						function () use ( $s ) {
							self::text( 'title', $s['title'], 'We use cookies' );
						}
					);
					self::row(
						'Content',
						function () use ( $s ) {
							wp_editor(
								$s['content'],
								's3cc_content',
								array(
									'textarea_name' => self::name( 'content' ),
									'media_buttons' => false,
									'teeny'         => true,
									'textarea_rows' => 5,
								)
							);
						},
						'Link to the cookie policy page here. Add a "Cookie Settings" link anywhere (footer menu, widget) with the URL <code>#cookie-settings</code> or the <code>[s3_cookie_settings]</code> shortcode.'
					);
					self::row(
						'Manage Preferences',
						function () use ( $s ) {
							self::checkbox( 'preferences', $s['preferences'], 'Add the Manage Preferences panel' );
						},
						'The panel lists categories for display only. The visitor still saves one yes/no choice for non-essential scripts.'
					);
					self::row(
						'Essential category',
						function () use ( $s ) {
							self::text( 'pref_essential_label', $s['pref_essential_label'], S3CC_Settings::DEFAULT_PREFERENCES['essential']['label'] );
							echo '<br><br>';
							self::textarea( 'pref_essential_desc', $s['pref_essential_desc'], 2, S3CC_Settings::DEFAULT_PREFERENCES['essential']['desc'] );
						}
					);
					self::row(
						'Non-essential category',
						function () use ( $s ) {
							self::text( 'pref_nonessential_label', $s['pref_nonessential_label'], S3CC_Settings::DEFAULT_PREFERENCES['nonEssential']['label'] );
							echo '<br><br>';
							self::textarea( 'pref_nonessential_desc', $s['pref_nonessential_desc'], 2, S3CC_Settings::DEFAULT_PREFERENCES['nonEssential']['desc'] );
						}
					);
					?>
				</table>

				<h2>Built-in tags</h2>
				<table class="form-table" role="presentation">
					<?php
					self::row(
						'Google tag IDs',
						function () use ( $s ) {
							self::text( 'ga_ids', $s['ga_ids'], 'G-XXXXXXX, AW-XXXXXXX' );
							echo '<br>';
							self::checkbox( 'ga_essential', $s['ga_essential'], 'Essential (loads before any choice)' );
						},
						'Comma-separated. The first ID loads gtag.js. Only loads in production (<code>WP_ENVIRONMENT_TYPE</code>) or with <code>?loadTags=1</code>. UK/EU sites: untick Essential.'
					);
					self::row(
						'WhatConverts ID',
						function () use ( $s ) {
							self::text( 'wc_id', $s['wc_id'] );
							echo '<br>';
							self::checkbox( 'wc_v2', $s['wc_v2'], 'v2 script' );
							echo '<br>';
							self::checkbox( 'wc_essential', $s['wc_essential'], 'Essential (loads before any choice)' );
						}
					);
					self::row(
						'Meta Pixel IDs',
						function () use ( $s ) {
							self::text( 'meta_pixel_ids', $s['meta_pixel_ids'] );
						},
						'Comma-separated. Always non-essential.'
					);
					self::row(
						'Google Consent Mode',
						function () use ( $s ) {
							self::checkbox( 'ga_cookieless', $s['ga_cookieless'], 'Use Consent Mode' );
						},
						'Only applies when Google is not essential. Off: Google is blocked until Accept All. On: Google loads cookieless before a choice and is upgraded on Accept All.'
					);
					self::row(
						'Google tag gateway path',
						function () use ( $s ) {
							self::text( 'ga_gateway_path', $s['ga_gateway_path'], '/metrics' );
						},
						'Optional first-party path. Falls back to googletagmanager.com if it fails to load.'
					);
					?>
				</table>

				<h2>Scripts</h2>
				<p class="description">
					Non-essential scripts are held until the visitor accepts, then injected at the end of the head or body.
					Inside a non-essential script, top-level <code>&lt;script type="application/ld+json"&gt;</code> blocks and elements with <code>data-s3-consent="essential"</code> still load straight away.
					<?php if ( ! $can_code ) : ?>
						<br><strong>Your account cannot edit script code (needs the unfiltered_html capability).</strong>
					<?php endif; ?>
				</p>
				<div id="s3cc-scripts">
					<?php
					foreach ( $s['scripts'] as $i => $script ) {
						self::script_row( $i, $script, $can_code );
					}
					?>
				</div>
				<?php if ( $can_code ) : ?>
					<p><button type="button" class="button" id="s3cc-add-script">Add script</button></p>
					<script type="text/html" id="s3cc-script-template"><?php self::script_row( '__i__', $blank, true ); ?></script>
				<?php endif; ?>

				<h2>Cookie policy page</h2>
				<?php self::policy_page_status( $s ); ?>
				<table class="form-table" role="presentation">
					<?php
					self::row(
						'Legal name',
						function () use ( $s ) {
							self::text( 'policy_legal_name', $s['policy_legal_name'], S3CC_Settings::seo_org_name() ?: get_bloginfo( 'name' ) );
						},
						'Blank uses the organisation name from Yoast or Rank Math, then the Site Title. The placeholder shows what the policy will use.'
					);
					self::row(
						'Privacy policy URL',
						function () use ( $s ) {
							self::text( 'policy_privacy_url', $s['policy_privacy_url'], '/privacy-policy/' );
						}
					);
					self::row(
						'Email',
						function () use ( $s ) {
							self::text( 'policy_email', $s['policy_email'], '', 'email' );
						},
						'Blank links to /contact/ instead.'
					);
					self::row(
						'Phone',
						function () use ( $s ) {
							self::text( 'policy_phone', $s['policy_phone'] );
						}
					);
					self::row(
						'Address',
						function () use ( $s ) {
							self::textarea( 'policy_address', $s['policy_address'], 3 );
						}
					);
					self::row(
						'Last updated',
						function () use ( $s ) {
							self::text( 'policy_updated', $s['policy_updated'] );
						}
					);
					?>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<style>
			.s3cc-script { background: #fff; border: 1px solid #dcdcde; padding: 12px; margin-bottom: 12px; max-width: 900px; }
			.s3cc-script-head { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 8px; }
			.s3cc-script-head input[type="text"] { flex: 1 1 260px; }
			.s3cc-remove { color: #b32d2e; margin-left: auto; }
		</style>
		<script>
			(function () {
				var btn = document.getElementById('s3cc-add-script');
				if (!btn) return;
				btn.addEventListener('click', function () {
					var tpl = document.getElementById('s3cc-script-template').innerHTML;
					document.getElementById('s3cc-scripts').insertAdjacentHTML('beforeend', tpl.replace(/__i__/g, 'n' + Date.now()));
				});
			})();
		</script>
		<?php
	}
}
