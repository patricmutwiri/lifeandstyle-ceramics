<?php
/**
 * RobotsTxt class
 *
 * Handles installed products related REST API endpoints for the SureRank plugin.
 *
 * @package SureRank\Inc\API
 */

namespace SureRank\Inc\API;

use SureRank\Inc\Functions\Send_Json;
use SureRank\Inc\Traits\Get_Instance;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class RobotsTxt
 *
 * Handles robots.txt related REST API endpoints.
 */
class RobotsTxt extends Api_Base {

	use Get_Instance;

	/**
	 * Route Get Robots.txt
	 */
	protected const ROBOTS_TXT = '/robots-txt';

	/**
	 * Register API routes.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->get_api_namespace(),
			self::ROBOTS_TXT,
			[
				'methods'             => WP_REST_Server::CREATABLE, // POST method.
				'callback'            => [ $this, 'update_robots_txt' ],
				'permission_callback' => [ $this, 'validate_permission' ],
				'args'                => [
					'robots_txt_content' => [
						'required' => true,
						'type'     => 'string',
					],
				],
			]
		);
	}

	/**
	 * Update the robots.txt content.
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Request object.
	 * @return void
	 */
	public function update_robots_txt( $request ) {

		$robots_content = $request->get_param( 'robots_txt_content' );

		$robots_content = sanitize_textarea_field( $robots_content );

		update_option( SURERANK_ROBOTS_TXT_CONTENT, $robots_content );

		Send_Json::success(
			[
				'success' => true,
				'data'    => [
					'robots_txt_content' => $robots_content,
				],
			]
		);
	}

}
