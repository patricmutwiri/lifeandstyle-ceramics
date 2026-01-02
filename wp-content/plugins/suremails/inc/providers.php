<?php
/**
 * Providers.php
 *
 * Handles provider-specific email sending functionalities for the SureMails plugin.
 *
 * @package SureMails\Inc
 */

namespace SureMails\Inc;

use SureMails\Inc\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Providers
 *
 * Handles provider-specific email sending functionalities.
 */
class Providers {

	use Instance;
	/**
	 * Get the options configuration for a specific provider or all providers.
	 *
	 * If `$provider` is empty or null, returns details of all providers.
	 *
	 * @param string|null $provider The provider key (e.g., 'aws', 'smtp'). Optional.
	 * @return array|null The merged options array for a single provider or all providers, or null if no match found.
	 */
	public function get_provider_options( $provider = null ) {
		$handlers = $this->get_handler_classes();

		if ( empty( $provider ) ) {
			$all_providers = [];

			foreach ( $handlers as $key => $handler_class ) {
				$options = $handler_class::get_options();

				$options['fields'] = array_merge( $this->get_base_fields(), $options['fields'] ?? [] );

				$all_providers[ $key ] = $options;
			}

			return $all_providers;
		}

		$provider = strtoupper( $provider );
		if ( ! isset( $handlers[ $provider ] ) ) {
			return null;
		}

		$handler_class    = $handlers[ $provider ];
		$specific_options = $handler_class::get_options();

		$specific_options['fields'] = array_merge( $this->get_base_fields(), $specific_options['fields'] ?? [] );

		return $specific_options;
	}

	/**
	 * Get the base fields applicable to all providers.
	 *
	 * @return array The base fields.
	 */
	private function get_base_fields() {
		return [
			'connection_title' => [
				'required'    => true,
				'datatype'    => 'string',
				'help_text'   => '',
				'input_type'  => 'text',
				'placeholder' => __( 'Enter Connection Title', 'suremails' ),
				'label'       => __( 'Connection Title', 'suremails' ),
			],
			'from_email'       => [
				'required'    => true,
				'datatype'    => 'email',
				'help_text'   => '',
				'label'       => __( 'From Email', 'suremails' ),
				'input_type'  => 'email',
				'placeholder' => __( 'Enter the email address to send from', 'suremails' ),
			],
			'force_from_email' => [
				'default'     => true,
				'required'    => false,
				'datatype'    => 'boolean',
				'help_text'   => __( 'Enable this option to force all emails sent from your site to use the "From Email" specified in this connection, overriding any other "From Email" set by other plugins, themes, etc.', 'suremails' ),
				'label'       => __( 'Force From Email', 'suremails' ),
				'input_type'  => 'checkbox',
				'placeholder' => '',
				'depends_on'  => [ 'from_email' ],
			],
			'from_name'        => [
				'required'    => false,
				'datatype'    => 'string',
				'help_text'   => '',
				'label'       => __( 'From Name', 'suremails' ),
				'input_type'  => 'text',
				'placeholder' => __( 'Enter the name to send from', 'suremails' ),
			],
			'force_from_name'  => [
				'default'     => true,
				'required'    => false,
				'datatype'    => 'boolean',
				'help_text'   => __( 'Enable this option to ensure all emails sent from your site use the "From Name" specified in this connection, overriding any other "From Name" set by other plugins, themes, etc.', 'suremails' ),
				'label'       => __( 'Force From Name', 'suremails' ),
				'input_type'  => 'checkbox',
				'placeholder' => '',
				'depends_on'  => [ 'from_name' ],
			],
			'priority'         => [
				'required'    => true,
				'datatype'    => 'int',
				'help_text'   => sprintf(       // translators: %s: https://suremails.com/docs/multiple-backup-connections?utm_campaign=suremails&utm_medium=suremails-dashboard URL.
					__( 'Set the order in which connections should be used to send emails. The connection for the "From Email" specified above and the lowest sequence number will be used first. If that connection fails, the next connection with the same "From Email" and the following lowest sequence number will be used. %1$sMore Information here%2$s', 'suremails' ),
					'<a href="' . esc_url( 'https://suremails.com/docs/multiple-backup-connections?utm_campaign=suremails&utm_medium=suremails-dashboard' ) . '" target="_blank">',
					'</a>'
				),
				'label'       => __( 'Connection Sequence for From Email', 'suremails' ),
				'input_type'  => 'number',
				'placeholder' => __( 'Enter priority', 'suremails' ),
				'min'         => 1,
			],
		];
	}

	/**
	 * Mapping of provider keys to handler classes.
	 *
	 * @return array
	 */
	private function get_handler_classes() {
		$handlers = [];

		$providers_dir = SUREMAILS_DIR . 'inc/emails/providers';
		$providers     = [];

		if ( is_dir( $providers_dir ) ) {
			$dirs = scandir( $providers_dir );
			if ( is_array( $dirs ) ) {
				foreach ( $dirs as $dir ) {
					if ( $dir !== '.' && $dir !== '..' && is_dir( $providers_dir . '/' . $dir ) ) {
						$providers[] = $dir;
					}
				}
			}
		}

		foreach ( $providers as $provider ) {
			$class_name = '\\SureMails\\Inc\\Emails\\Providers\\' . strtoupper( $provider ) . '\\' . ucfirst( $provider ) . 'Handler';

			if ( class_exists( $class_name ) ) {
				$handlers[ strtoupper( $provider ) ] = $class_name;
			}
		}

		/**
		 * Filter the handler classes for email providers.
		 *
		 * @since 0.0.1
		 *
		 * @param array $handlers Array of handler classes mapped by provider keys.
		 */
		return apply_filters( 'suremails_provider_list', $handlers );
	}
}
