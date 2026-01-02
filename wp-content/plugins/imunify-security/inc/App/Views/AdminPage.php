<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App\Views;

use CloudLinux\Imunify\App\AccessManager;
use CloudLinux\Imunify\App\Api\AjaxHandler;
use CloudLinux\Imunify\App\View;
use CloudLinux\Imunify\App\DataStore;

/**
 * Admin page view for Imunify Security.
 */
class AdminPage extends View {

	/**
	 * Page slug for the admin menu.
	 */
	const PAGE_SLUG = 'imunify-security';

	/**
	 * Action name for rendering the iframe.
	 */
	const IFRAME_ACTION = 'render_imunify_iframe';

	/**
	 * Data store instance.
	 *
	 * @var DataStore
	 */
	private $dataStore;

	/**
	 * Access manager instance.
	 *
	 * @var AccessManager
	 */
	private $accessManager;

	/**
	 * Constructor.
	 *
	 * @param AccessManager $accessManager Access manager instance.
	 * @param DataStore     $dataStore     Data store instance.
	 */
	public function __construct( AccessManager $accessManager, DataStore $dataStore ) {
		$this->dataStore     = $dataStore;
		$this->accessManager = $accessManager;

		add_action( 'admin_menu', array( $this, 'addAdminMenu' ) );
		add_action( 'admin_post_' . self::IFRAME_ACTION, array( $this, 'renderIframeTemplate' ) );
	}

	/**
	 * Add admin menu item for Imunify Security.
	 *
	 * @return void
	 */
	public function addAdminMenu() {
		if ( ! $this->dataStore->isDataAvailable() ) {
			return;
		}

		add_menu_page(
			esc_html__( 'Imunify Security', 'imunify-security' ),
			esc_html__( 'Imunify Security', 'imunify-security' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'renderPage' ),
			'dashicons-shield-alt',
			80 // Position after "Settings".
		);
	}

	/**
	 * Render the admin page.
	 *
	 * @return void
	 */
	public function renderPage() {

		echo '<style type="text/css">';
		echo '#wpcontent { padding-top: 0; padding-left: 0; }';
		echo '#footer-upgrade { display: none; }';
		echo '</style>';

		$iframe_url = admin_url( 'admin-post.php?action=' . self::IFRAME_ACTION );
		echo '<iframe 
			id="imunify-angular-iframe"
			style="width: 100%; border: none; min-height: 450px;"
			src="' . esc_url( $iframe_url ) . '"
			allowtransparency="true">
		</iframe>';

		echo '<script>
				window.addEventListener("message", function(event) {
					if (event?.data?.type === "iframe-resize") {
						const wpFooterHeight = document.getElementById("wpfooter")?.scrollHeight || 0;
						document.getElementById("wpbody-content").style.paddingBottom = wpFooterHeight + "px";

						const wpAdminBarHeight = document.getElementById("wpadminbar")?.scrollHeight || 0;
						const availableHeight = window.innerHeight - wpAdminBarHeight - wpFooterHeight - 5;
						const newHeight = Math.max(450, availableHeight, event.data.height) + "px";
						const iframe = document.getElementById("imunify-angular-iframe");

						if (iframe && iframe.style.height !== newHeight) {
							iframe.style.height = newHeight;
						}
					} else if (event?.data?.type === "imunify-url-hash-changed") {
						if (window.location.hash !== event.data.hash) {
							window.location.hash = event.data.hash;
						}
					} else if (event?.data?.type === "imunify-session-expired") {
						// Reload only the iframe to avoid navigating the parent to site homepage
						if (window.__i360IframeReloadTs && Date.now() - window.__i360IframeReloadTs < 5000) {
							return;
						}
						window.__i360IframeReloadTs = Date.now();
						const iframe = document.getElementById("imunify-angular-iframe");
						if (iframe) {
							var baseSrc = iframe.src.split("#")[0];
							iframe.src = window.location.hash ? (baseSrc + window.location.hash) : baseSrc;
						} else {
							// Fallback: reload the whole page if iframe not found
							window.location.reload();
						}
					}
				});
				document.addEventListener("DOMContentLoaded", function() {
					const iframe = document.getElementById("imunify-angular-iframe");
					if (window.location.hash) {
						iframe.src = iframe.src + window.location.hash;
					}
				});
		</script>';
	}

	/**
	 * Render the iframe template.
	 *
	 * @since 2.0.0
	 *
     * @phpcs:disable WordPress.WhiteSpace.PrecisionAlignment.Found
     * @phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	 * @phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
	 * @phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript
	 */
	public function renderIframeTemplate() {
		$pluginUrl       = plugin_dir_url( IMUNIFY_SECURITY_FILE_PATH );
		$uiAppAssetsPath = $pluginUrl . 'assets/ui-app/assets/';

		$endpointUrl = admin_url( 'admin-ajax.php?action=' . AjaxHandler::AJAX_ACTION );

		$scanData  = $this->dataStore->getScanData();
		$license   = is_null( $scanData ) ? array() : $scanData->getLicense();
		$licenseId = self::extractLicenseId( $license );

		$locale      = get_user_locale();
		$localeShort = substr( $locale, 0, 2 );

		$canUserUpgrade        = $this->accessManager->canUserUpgrade( $this->dataStore );
		$canUserUpgradeJsValue = $canUserUpgrade ? 'true' : 'false';

		header( 'Content-Type: text/html; charset=UTF-8' );
		?>
			<!DOCTYPE html>
			<html lang="en">
				<head>
					<meta charset="utf-8">
					<base href="/">
					<title><?php esc_html_e( 'Imunify Security', 'imunify-security' ); ?></title>

					<link href="<?php echo $uiAppAssetsPath; ?>static/container.css" rel="stylesheet">
					<link href="<?php echo $uiAppAssetsPath; ?>static/fonts/fonts.css?v1" rel="stylesheet">
	
					<script type="text/javascript">
						window.IS_WORDPRESS_PLUGIN = true;
						window.I360_PATH_TO_STATIC = "<?php echo esc_url( $uiAppAssetsPath ); ?>";
						window.clientAction = "<?php echo esc_url( $endpointUrl ); ?>";
						window.adminAction = "<?php echo esc_url( $endpointUrl ); ?>";
						window.i360userName = "<?php echo esc_js( $this->dataStore->getUsername() ); ?>";
						window.IMUNIFY_PACKAGE = "<?php echo esc_js( $licenseId ); ?>";
						window.MYIMUNIFY_DISABLED = true;
						window.I360_SHOW_UPGRADE_BUTTON_FOR_END_USER = <?php echo $canUserUpgradeJsValue; ?>;
						window.I360_SHOW_IMUNIFY_SECURITY_UPGRADE_PAGE = <?php echo $canUserUpgradeJsValue; ?>;
						localStorage.setItem('lang', "<?php echo esc_js( $localeShort ); ?>");

						document.addEventListener('DOMContentLoaded', function () {
							const sendHeight = () => {
								const height = document.body.scrollHeight;
								window.parent.postMessage({ type: 'iframe-resize', height: height }, '*');
							};

							const resizeObserver = new ResizeObserver(() => {
								sendHeight();
							});

							resizeObserver.observe(document.body);
							window.addEventListener('load', sendHeight);

							setTimeout(sendHeight, 200);
						});
					</script>
	
					<script src="<?php echo $uiAppAssetsPath; ?>static/shared-dependencies/system.min.js"></script>
					<script src="<?php echo $uiAppAssetsPath; ?>static/shared-dependencies/amd.min.js"></script>
					<script src="<?php echo $uiAppAssetsPath; ?>static/shared-dependencies/named-exports.min.js"></script>
					<script src="<?php echo $uiAppAssetsPath; ?>static/shared-dependencies/named-register.min.js"></script>
					<script src="<?php echo $uiAppAssetsPath; ?>static/shared-dependencies/zone.min.js"></script>
					<script src="<?php echo $uiAppAssetsPath; ?>static/load-scripts-after-zone.js?v2"></script>
					<style>
						body, html {
							margin: 0;
							padding: 0;
						}
						.i360-app__container {
							margin-top: 20px;
							margin-left: 20px;
							margin-right: 20px;
						}

						.i360-app__loader {
							position: absolute;
							top: 0;
							left: 0;
							right: 0;
							bottom: 0;
						}
					</style>
				</head>

				<body>
					<div id="spa_wrapper" class="display-flex-column"></div>
					<template id="single-spa-layout">
						<single-spa-router mode="hash" containerEl="#spa_wrapper">
							<div class="i360-app i360-app-outer i360-app__container">
								<application name="@imunify/nav-root"></application>
								<div class="main-content">
									<route default>
										<application name="@imunify/other-root" loader="loader"></application>
									</route>
								</div>
							</div>
							<div class="i360-app__loader">
								<div class="i360-app__loader-icon"></div>
							</div>
						</single-spa-router>
					</template>
				</body>
			</html>
			<?php
			exit();
	}

	/**
	 * Checks if the admin page should be rendered.
	 *
	 * @param string $hook The current admin page hook.
	 * @return bool
	 */
	public function willBeRendered( $hook ) {
		return 'toplevel_page_' . self::PAGE_SLUG === $hook && $this->dataStore->isDataAvailable();
	}

	/**
	 * Extract license ID from license data.
	 *
	 * @since 2.0.2
	 *
	 * @param array|null $license License data array.
	 * @return string License ID ('360' if contains '360', 'AV' otherwise, defaults to '360' if not found).
	 */
	public static function extractLicenseId( $license ) {
		if ( ! is_array( $license ) || ! isset( $license['license_type'] ) ) {
			return '360';
		}

		$processed = strtoupper( str_ireplace( 'imunify', '', $license['license_type'] ) );
		if ( strpos( $processed, '360' ) !== false ) {
			return '360';
		}

		return 'AV';
	}
}
