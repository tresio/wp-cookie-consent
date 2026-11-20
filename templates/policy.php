<?php
/**
 * Cookie policy text, ported from the CMS cookie_policy.twig.
 *
 * @var array  $s
 * @var string $name
 * @var string $privacy
 */
defined( 'ABSPATH' ) || exit;
$s3cc_site = home_url( '/' );
?>
<div class="s3-cookie-policy">
	<?php if ( $s['policy_updated'] ) : ?>
		<p><em>Last updated: <?php echo esc_html( $s['policy_updated'] ); ?></em></p>
	<?php endif; ?>
	<p>This Cookie Policy explains how <?php echo esc_html( $name ); ?> ("we," "us," or "our") uses cookies and similar technologies on the following website:</p>
	<ul>
		<li><a href="<?php echo esc_url( $s3cc_site ); ?>"><?php echo esc_html( untrailingslashit( $s3cc_site ) ); ?></a></li>
	</ul>
	<p>This Cookie Policy should be read alongside our <a href="<?php echo esc_url( $privacy ); ?>">Website Privacy Policy</a>.</p>

	<h2>1. What Are Cookies?</h2>
	<p>Cookies are small text files placed on your device when you visit a website. We also use similar technologies, such as browser local storage, tags, and scripts, that store or read information on your device. In this policy, "cookies" refers to all of these technologies.</p>
	<p>Cookies help a website function properly, remember your settings, show us how the website is used, and, with your consent, measure and support our marketing.</p>
	<p>Some cookies are essential for the website to operate. All other cookies are optional and are used only if you choose to accept them.</p>

	<h2>2. Types of Cookies We Use</h2>
	<h3>Strictly Necessary Cookies</h3>
	<p>These cookies are required for the website to function and cannot be switched off. They support functions such as security, page navigation, form submission, session management, and remembering your cookie preferences.</p>
	<p>Strictly necessary cookies do not require your consent.</p>
	<h3>Non-Essential Cookies</h3>
	<p>Non-essential cookies are used only if you select "Accept All." They include:</p>
	<ul>
		<li><strong>Analytics and performance:</strong> help us understand how visitors use the website, such as which pages are visited most often and whether visitors encounter errors.</li>
		<li><strong>Functionality:</strong> remember choices you make and provide enhanced features, such as embedded content or interactive tools.</li>
		<li><strong>Advertising and marketing:</strong> help us understand which advertisements or marketing sources lead visitors to contact us, and measure the effectiveness of our advertising.</li>
	</ul>
	<p>Where required, these cookies will only be placed after you have given consent.</p>

	<h2>3. Third-Party Cookies</h2>
	<p>Some cookies are set by third-party services used on our website, such as analytics providers, embedded content, advertising platforms, or other technology suppliers. These third parties process information according to their own privacy and cookie policies. Third-party non-essential cookies are activated only if you select "Accept All."</p>

	<h2>4. Managing Your Cookie Preferences</h2>
	<p>When you first visit our website, you will be asked to choose how cookies are used. You can:</p>
	<ul>
		<li>Accept All cookies; or</li>
		<li>Reject Non-Essential cookies.</li>
	</ul>
	<p>You can change your choice at any time using <a href="#cookie-settings" role="button" data-s3-cookie-settings>Cookie Settings</a>. You can also control or delete cookies through your browser settings. Blocking certain cookies may affect how the website functions.</p>

	<h2>5. When We Use Cookies</h2>
	<p>Strictly necessary cookies are used because they are required to provide the website and the services you request, and to keep the website secure.</p>
	<p>Non-essential cookies are used only after you have given your consent by selecting "Accept All."</p>

	<h2>6. Personal Information</h2>
	<p>Some cookies may collect information such as online identifiers, IP addresses, device and browser information, or information about how you use our website.</p>
	<p>For more information about how we handle personal information collected through our website, please see our <a href="<?php echo esc_url( $privacy ); ?>">Website Privacy Policy</a>.</p>
	<p>This Cookie Policy does not govern your medical records or protected health information.</p>

	<h2>7. Changes to This Cookie Policy</h2>
	<p>We may update this Cookie Policy from time to time to reflect changes to our website, the technologies we use, or legal requirements. The current version will always be available on our website, and the "Last updated" date above will reflect the most recent revision.</p>

	<h2>8. Contact Us</h2>
	<p>If you have questions about our use of cookies or this Cookie Policy, please contact:</p>
	<div class="contact">
		<p><strong><?php echo esc_html( $name ); ?></strong></p>
		<?php if ( $s['policy_address'] ) : ?>
			<div class="address"><?php echo wp_kses_post( wpautop( $s['policy_address'] ) ); ?></div>
		<?php endif; ?>
		<?php if ( $s['policy_phone'] ) : ?>
			<p>Phone: <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $s['policy_phone'] ) ); ?>"><?php echo esc_html( $s['policy_phone'] ); ?></a></p>
		<?php endif; ?>
		<?php if ( $s['policy_email'] ) : ?>
			<p>Email: <a href="mailto:<?php echo esc_attr( $s['policy_email'] ); ?>"><?php echo esc_html( $s['policy_email'] ); ?></a></p>
		<?php else : ?>
			<p>Or reach us through our <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">contact page</a>.</p>
		<?php endif; ?>
	</div>
</div>
