<?php
/**
 * Widget view for case when the Imunify software is not installed.
 *
 * @var array $data Template data.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! is_array( $data ) ) {
	$data = array();
}

$pluginUrl = $data['pluginUrl'];

?>
<div class="imunify-security__widget imunify-security__widget--not-installed">
	<div class="imunify-security__widget-content">
		<div class="imunify-security__widget-icon">
			<img src="<?php echo esc_url( $pluginUrl . 'assets/images/shield-warning.svg' ); ?>" alt="Warning" width="80" height="80">
		</div>
		<div class="imunify-security__widget-status"><?php esc_html_e( 'Not protected', 'imunify-security' ); ?></div>
		<div class="imunify-security__widget-message"><?php esc_html_e( 'Imunify Software is not installed', 'imunify-security' ); ?></div>
		<a href="https://imunify360.com/getting-started-installation/" class="button button-primary" target="_blank">
			<?php esc_html_e( 'Get Imunify360', 'imunify-security' ); ?>
		</a>
	</div>
</div>
