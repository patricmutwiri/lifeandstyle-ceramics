<?php
/**
 * Third Party Plugins class – CartFlows
 *
 * Modifies CartFlows funnel settings UI and prevents it from outputting robots meta.
 *
 * @package SureRank\Inc\ThirdPartyIntegrations
 */

namespace SureRank\Inc\ThirdPartyIntegrations;

use SureRank\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class CartFlows
 *
 * Modifies CartFlows funnel settings UI and prevents it from outputting robots meta.
 *
 * @since 1.0.0
 */
class CartFlows {

	use Get_Instance;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( ! defined( 'CARTFLOWS_VER' ) ) {
			return;
		}
		add_filter( 'cartflows_admin_flow_settings', [ $this, 'disable_and_change_tooltip_noindex' ], 10, 2 );
		add_filter( 'cartflows_step_add_noindex_meta', '__return_false', 10 );
		add_filter( 'cartflows_admin_global_settings_data', [ $this, 'disable_cartflows_global_settings' ], 10, 1 );
	}

	/**
	 * Disable and change tooltip for the 'Disallow Indexing' field in CartFlows funnel settings.
	 *
	 * @param array<string, array<string, mixed>> $settings The CartFlows flow settings array.
	 * @param int                                 $flow_id The ID of the current flow.
	 * @return array<string, array<string, mixed>> Modified settings array.
	 */
	public function disable_and_change_tooltip_noindex( $settings, $flow_id ) {
		if (
			isset( $settings['general'] ) &&
			isset( $settings['general']['fields'] ) &&
			isset( $settings['general']['fields']['flow_indexing'] )
		) {
			$settings['general']['fields']['flow_indexing']['tooltip']    = __( 'This indexing setting is managed by the SureRank plugin.', 'surerank' );
			$settings['general']['fields']['flow_indexing']['isDisabled'] = true;
		}
		return $settings;
	}

	/**
	 * Disable CartFlows global settings.
	 *
	 * @param array<string, mixed> $settings The CartFlows global settings array.
	 * @return array<string, mixed> Modified settings array.
	 */
	public function disable_cartflows_global_settings( $settings ) {
		if (
			isset( $settings['general']['fields']['search_engine'] )
		) {
			$settings['general']['fields']['search_engine']['desc']        = __( 'This setting is managed by the SureRank plugin, so you don’t need to configure indexing rules for funnels here.', 'surerank' );
			$settings['general']['fields']['search_engine']['is_disabled'] = true;

		}

		return $settings;
	}
}
