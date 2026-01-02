<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App\Model;

/**
 * Feature model class.
 */
class Feature {
	/**
	 * Feature name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Feature URL.
	 *
	 * @var string
	 */
	private $url;

	/**
	 * Feature status.
	 *
	 * @var string
	 */
	private $status;

	/**
	 * Feature type.
	 *
	 * @var string
	 */
	private $type;

	/**
	 * Create a feature from an array.
	 *
	 * @param array $data Feature data.
	 *
	 * @return \CloudLinux\Imunify\App\Model\Feature
	 */
	public static function fromArray( $data ) {
		$feature         = new self();
		$feature->name   = $data['name'];
		$feature->url    = $data['url'];
		$feature->status = $data['status'];
		$feature->type   = $data['type'];

		return $feature;
	}

	/**
	 * Create a feature from a type.
	 *
	 * @param string $type   Feature type.
	 *
	 * @param array  $config Configuration data.
	 *
	 * @return \CloudLinux\Imunify\App\Model\Feature
	 */
	public static function fromType( $type, array $config = array() ) {
		$feature         = new self();
		$feature->name   = FeatureType::getDisplayName( $type );
		$feature->url    = FeatureType::getUrl( $type );
		$feature->status = FeatureType::getStatus( $type, $config );
		$feature->type   = $type;

		return $feature;
	}

	/**
	 * Convert feature to array.
	 *
	 * @return array
	 */
	public function toArray() {
		return array(
			'name'   => $this->name,
			'url'    => $this->url,
			'status' => $this->status,
			'type'   => $this->type,
		);
	}

	/**
	 * Get feature name.
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Get feature URL.
	 *
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * Get feature status.
	 *
	 * @return string
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * Get feature status label (translated for UI).
	 *
	 * @return string
	 */
	public function getStatusLabel() {
		return FeatureStatus::getLabel( $this->status );
	}

	/**
	 * Get feature type.
	 *
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}
}
