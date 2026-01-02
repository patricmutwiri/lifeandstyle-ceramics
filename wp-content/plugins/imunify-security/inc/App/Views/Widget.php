<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App\Views;

use CloudLinux\Imunify\App\AccessManager;
use CloudLinux\Imunify\App\DataStore;
use CloudLinux\Imunify\App\View;

/**
 * Dashboard widget view.
 */
class Widget extends View {
	/**
	 * Maximum number of items to show in widget view.
	 */
	const MAX_WIDGET_ITEMS = 5;

	/**
	 * User meta key for storing widget snooze state.
	 *
	 * @var string
	 */
	const WIDGET_SNOOZED_META_KEY = 'imunify_widget_snoozed_until';

	/**
	 * Nonce name for widget snooze action.
	 *
	 * @var string
	 */
	const WIDGET_SNOOZE_NONCE_NAME = 'imunify_widget_snooze_nonce';

	/**
	 * URI fragment for the upgrade page in the admin interface.
	 *
	 * @var string
	 */
	const UPGRADE_URI_FRAGMENT = '/AV/client/upgrade';

	/**
	 * Data store instance.
	 *
	 * @var DataStore
	 */
	public $dataStore;

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
		$this->accessManager = $accessManager;
		$this->dataStore     = $dataStore;
		add_action( 'wp_dashboard_setup', array( $this, 'add' ) );
		add_action( 'wp_ajax_imunify_snooze_widget', array( $this, 'snoozeWidget' ) );
	}

	/**
	 * Add a new dashboard widget.
	 *
	 * @return void
	 */
	public function add() {
		if ( ! $this->willBeRendered() ) {
			return;
		}

		wp_add_dashboard_widget(
			'imunify_security_widget',
			esc_html__( 'Imunify Security', 'imunify-security' ),
			array(
				$this,
				'view',
			),
			null,
			null,
			'normal',
			'high'
		);
	}

	/**
	 * Output the contents of the dashboard widget.
	 */
	public function view() {
		$pluginUrl = plugin_dir_url( IMUNIFY_SECURITY_FILE_PATH );
		if ( ! $this->dataStore->isDataAvailable() ) {
			$this->render(
				'widget-not-installed',
				array(
					'installLink' => 'https://imunify360.com/getting-started-installation/',
					'pluginUrl'   => $pluginUrl,
				)
			);
		} else {
			$scanData = $this->dataStore->getScanData();
			if ( null === $scanData ) {
				// Data is not available, do not render the widget.
				return;
			}

			$malwareItems   = $scanData->getMalware();
			$malwareCount   = count( $malwareItems );
			$canUserUpgrade = $this->accessManager->canUserUpgrade( $this->dataStore );
			$showMoreButton = $malwareCount > self::MAX_WIDGET_ITEMS;

			$this->render(
				'widget',
				array(
					'scanData'          => $scanData,
					'pluginUrl'         => $pluginUrl,
					'features'          => $this->dataStore->getFeatures(),
					'malwareItems'      => array_slice( $malwareItems, 0, self::MAX_WIDGET_ITEMS ),
					'totalItemsCount'   => $malwareCount,
					'showMoreButton'    => $showMoreButton,
					'showMoreUrl'       => $showMoreButton ? $this->getAdminPageUrl() : '',
					'showUpgradeButton' => $canUserUpgrade,
					'upgradeUrl'        => $canUserUpgrade ? $this->getUpgradeUrl() : '',
					'statusTitle'       => $this->getProtectionStatusTitle(),
					'statusIcon'        => $this->getProtectionStatusIcon(),
				)
			);
		}
	}

	/**
	 * Gets the URL for the admin page.
	 *
	 * @return string
	 *
	 * @since 2.0.0
	 */
	public function getAdminPageUrl() {
		return add_query_arg(
			'page',
			AdminPage::PAGE_SLUG,
			admin_url( 'admin.php' )
		);
	}
	/**
	 * Gets the upgrade URL for the button.
	 *
	 * @return string
	 *
	 * @since 2.0.0
	 */
	public function getUpgradeUrl() {
		return $this->getAdminPageUrl() . '#' . self::UPGRADE_URI_FRAGMENT;
	}

	/**
	 * Checks if the widget will be rendered.
	 *
	 * @return bool
	 */
	public function willBeRendered() {
		if ( ! $this->accessManager->isUserAdmin() ) {
			return false;
		}

		return ! $this->isSnoozed();
	}

	/**
	 * Checks if the widget is currently snoozed.
	 *
	 * @return bool
	 */
	private function isSnoozed() {
		$user_id       = get_current_user_id();
		$snoozed_until = get_user_meta( $user_id, self::WIDGET_SNOOZED_META_KEY, true );

		return $snoozed_until && time() < $snoozed_until;
	}

	/**
	 * Snoozes the widget for the specified number of weeks.
	 *
	 * @return void
	 */
	public function snoozeWidget() {
		check_ajax_referer( self::WIDGET_SNOOZE_NONCE_NAME, 'nonce' );

		$weeks = filter_input( INPUT_POST, 'weeks', FILTER_VALIDATE_INT );
		if ( ! $weeks || $weeks < 1 || $weeks > 4 ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid snooze duration', 'imunify-security' ) ) );
		} else {
			$user_id      = get_current_user_id();
			$snooze_until = strtotime( "+{$weeks} weeks UTC" );
			update_user_meta( $user_id, self::WIDGET_SNOOZED_META_KEY, $snooze_until );
			wp_send_json_success();
		}
	}

	/**
	 * Checks if the product is ImunifyAV.
	 *
	 * @return bool
	 */
	private function isImunifyAV() {
		return AccessManager::isProductType( $this->dataStore, 'imunifyav' );
	}

	/**
	 * Gets the protection status title based on product type.
	 *
	 * @return string
	 */
	private function getProtectionStatusTitle() {
		return $this->isImunifyAV()
			? esc_html__( 'Not protected', 'imunify-security' )
			: esc_html__( 'Protected', 'imunify-security' );
	}

	/**
	 * Gets the protection status icon based on product type.
	 *
	 * @return string
	 */
	private function getProtectionStatusIcon() {
		return $this->isImunifyAV()
			? 'shield-warning.svg'
			: 'shield-check.svg';
	}
}

