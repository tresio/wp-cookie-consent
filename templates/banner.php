<?php
/**
 * Banner markup, same contract as the CMS cookie_consent.twig.
 *
 * @var array      $s
 * @var array|null $prefs
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="partial_cookie_consent disabled" role="dialog" aria-label="Cookie consent" data-nosnippet>
	<div class="row">
		<?php if ( $s['title'] ) : ?>
			<h2><?php echo wp_kses_post( $s['title'] ); ?></h2>
		<?php endif; ?>
		<?php echo wp_kses_post( wpautop( $s['content'] ) ); ?>
		<div class="btns">
			<button type="button" class="btn" data-val="all" data-s3-event="Cookie - Accept">Accept All</button>
			<button type="button" class="btn" data-val="essential" data-s3-event="Cookie - Reject">Reject Non-Essential</button>
			<?php if ( $prefs ) : ?>
				<button type="button" class="btn" data-val="manage" hidden>Manage Preferences</button>
			<?php endif; ?>
		</div>
	</div>
	<?php if ( $prefs ) : ?>
		<div class="row cookie-consent-preferences" hidden>
			<h2 tabindex="-1">Cookie Preferences</h2>
			<ul class="cookie-categories">
				<?php foreach ( $prefs['essential'] as $entry ) : ?>
					<li>
						<label>
							<input type="checkbox" checked disabled>
							<span class="name"><?php echo esc_html( $entry['label'] ); ?></span>
							<span class="state">Always on</span>
						</label>
						<p><?php echo wp_kses_post( $entry['desc'] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ul>
			<div class="cookie-toggle">
				<label>
					<input type="checkbox" data-consent-toggle>
					<span class="name">Allow non-essential cookies</span>
				</label>
				<ul class="cookie-categories">
					<?php foreach ( $prefs['nonEssential'] as $entry ) : ?>
						<li>
							<span class="name"><?php echo esc_html( $entry['label'] ); ?></span>
							<p><?php echo wp_kses_post( $entry['desc'] ); ?></p>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="btns">
				<button type="button" class="btn" data-val="save">Save Preferences</button>
				<button type="button" class="btn" data-val="all" data-s3-event="Cookie - Accept">Accept All</button>
				<button type="button" class="btn" data-val="essential" data-s3-event="Cookie - Reject">Reject Non-Essential</button>
			</div>
		</div>
	<?php endif; ?>
	<button type="button" id="cookie-close" class="close" aria-label="Close Cookie Consent"></button>
</div>
