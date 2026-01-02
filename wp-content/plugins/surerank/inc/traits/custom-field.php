<?php
/**
 * Custom Field Trait
 *
 * @package surerank
 * @since 1.6.0
 */

namespace SureRank\Inc\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Trait for handling custom fields in Post and Term classes.
 *
 * @since 1.6.0
 */
trait Custom_Field {

	/**
	 * Get all custom field values for the current post or term
	 *
	 * @since 1.6.0
	 * @return array<string, mixed>
	 */
	public function get_all_custom_fields() {
		$id = $this->get_ID();
		if ( ! $id ) {
			return [];
		}

		$metas = get_metadata( $this->get_meta_type(), $id );

		if ( empty( $metas ) || ! is_array( $metas ) ) {
			return [];
		}

		$all_values = [];
		foreach ( $metas as $field_name => $meta_value ) {
			// Filter out internal WordPress fields and SureRank fields.
			if ( strpos( $field_name, '_' ) === 0 || strpos( $field_name, 'surerank_' ) === 0 ) {
				continue;
			}

			$key   = 'custom_field.' . $field_name;
			$value = $meta_value[0] ?? '';

			// Check if the value is an image attachment ID and convert to URL.
			if ( is_numeric( $value ) && $this->is_image_field( $field_name, $value ) ) {
				$image_url = wp_get_attachment_url( (int) $value );
				if ( $image_url ) {
					$value = $image_url;
				}
			}

			/* translators: %s is replaced with the custom field label. */
			$description = sprintf( __( 'Custom field: %s', 'surerank' ), $field_name );
			if ( ! empty( $value ) ) {
				$all_values[ $key ] = [
					'label'       => $field_name,
					'description' => $description,
					'value'       => $value,
				];
			}
		}

		return $all_values;
	}

	/**
	 * Get custom field value by field name
	 *
	 * @param string $field_name The custom field name.
	 *
	 * @since 1.6.0
	 * @return mixed
	 */
	public function get_custom_field( $field_name ) {
		$id = $this->get_ID();
		if ( ! $id || empty( $field_name ) ) {
			return false;
		}

		$meta_type = $this->get_meta_type();
		$value     = get_metadata( $meta_type, $id, $field_name, true );

		if ( empty( $value ) ) {
			return false;
		}

		if ( is_numeric( $value ) && $this->is_image_field( $field_name, $value ) ) {
			$image_url = wp_get_attachment_url( (int) $value );
			if ( $image_url ) {
				return $image_url;
			}
		}

		return $value;
	}

	/**
	 * Get the meta type for this instance ('post' or 'term').
	 * Must be implemented by the class using this trait.
	 *
	 * @since 1.6.0
	 * @return string Either 'post' or 'term'.
	 */
	abstract protected function get_meta_type();

	/**
	 * Check if a custom field is an image field
	 *
	 * @param string $field_name The custom field name.
	 * @param mixed  $value The field value.
	 *
	 * @since 1.6.0
	 * @return bool Whether the field is an image field.
	 */
	private function is_image_field( $field_name, $value ) {
		if ( ! is_numeric( $value ) ) {
			return false;
		}

		if ( ! wp_attachment_is_image( (int) $value ) ) {
			return false;
		}
		$id = $this->get_ID();

		if ( ! is_int( $id ) ) {
			return false;
		}

		$is_image  = null;
		$meta_type = $this->get_meta_type();

		/**
		 * For ACF
		 */
		if ( function_exists( 'get_field_object' ) ) {
			$field_object = get_field_object( $field_name, $id );
			if ( $field_object && isset( $field_object['type'] ) ) {
				$is_image = $field_object['type'] === 'image';
			}
		}

		/**
		 * For Pods
		 */
		if ( is_null( $is_image ) && function_exists( 'pods_api' ) ) {
			$pods_api = pods_api();
			$pod      = $pods_api->load_pod( [ 'name' => get_post_type( $id ) ] );
			if ( $pod && isset( $pod['fields'][ $field_name ] ) ) {
				$field_type = $pod['fields'][ $field_name ]['type'];
				$is_image   = in_array( $field_type, [ 'file', 'avatar' ], true );
			}
		}

		/**
		 * For Secure Custom Fields (SCF)
		 */
		if ( is_null( $is_image ) && function_exists( 'scf_get_field_object' ) ) {
			$field_object = scf_get_field_object( $field_name, $id );
			if ( $field_object && isset( $field_object['type'] ) ) {
				$is_image = in_array( $field_object['type'], [ 'image', 'file' ], true );
			}
		}

		/**
		 * For Meta Box
		 */
		if ( is_null( $is_image ) && function_exists( 'rwmb_get_field_settings' ) ) {
			$object_type  = $meta_type === 'term' ? 'term' : 'post';
			$field_object = rwmb_get_field_settings( $field_name, [ 'object_type' => $object_type ], $id );
			if ( $field_object && isset( $field_object['type'] ) ) {
				$is_image = in_array(
					$field_object['type'],
					[ 'image', 'image_advanced', 'image_upload', 'single_image', 'file', 'file_input', 'file_upload' ],
					true
				);
			}
		}

		/**
		 * Filters whether a custom field is an image field.
		 *
		 * Allows other plugins to provide compatibility for their custom field types.
		 *
		 * @since 1.6.0
		 *
		 * @param bool|null $is_image    Whether the field is an image field. Null if not determined yet.
		 * @param string    $field_name  The custom field name.
		 * @param mixed     $value       The field value.
		 * @param int       $id          The post or term ID.
		 * @param string    $meta_type   The meta type ('post' or 'term').
		 */
		$is_image = apply_filters( 'surerank_is_image_field', $is_image, $field_name, $value, $id, $meta_type );

		// If still null, default to false.
		return ! is_null( $is_image ) ? (bool) $is_image : false;
	}
}
