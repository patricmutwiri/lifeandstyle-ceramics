<?php
/**
 * Admin Notice Handler.
 *
 * @package SureRank\Inc\Admin
 * @since 1.5.0
 */

namespace SureRank\Inc\Admin;

use SureRank\Inc\Functions\Utils as Functions_Utils;
use SureRank\Inc\Modules\Nudges\Utils;
use SureRank\Inc\Traits\Enqueue;
use SureRank\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Admin_Notice class.
 *
 * Handles displaying the admin notice for non-Pro users.
 */
class Admin_Notice {
	use Get_Instance;
	use Enqueue;

	/**
	 * Store for old permalinks before they change
	 *
	 * @var array<int|string, string>
	 */
	private static $old_permalinks = [];
	/**
	 * Constructor
	 *
	 * @since 1.5.0
	 */
	private function __construct() {
		if ( ! Utils::get_instance()->is_pro_active() && get_option( 'permalink_structure' ) ) {
			$this->enqueue_scripts_admin();

			add_action( 'admin_notices', [ $this, 'render_notice' ] );
			$this->init_post_hooks();
			$this->init_term_hooks();
		}
	}

	/**
	 * Override enqueue_scripts to prevent wp_enqueue_scripts action.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function enqueue_scripts() {
	}

	/**
	 * Handle pre_post_update hook.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $data    Post data array.
	 * @return void
	 */
	public function handle_pre_post_update( $post_id, $data ) {
		$this->store_old_permalink( $post_id );
	}

	/**
	 * Handle post_updated hook.
	 *
	 * @param int      $post_id     Post ID.
	 * @param \WP_Post $post_after  Post object following the update.
	 * @param \WP_Post $post_before Post object before the update.
	 * @return void
	 */
	public function handle_post_updated( $post_id, $post_after, $post_before ) {
		$this->check_permalink_change( $post_id, $post_after );
	}

	/**
	 * Handle edit_term hook.
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return void
	 */
	public function handle_edit_term( $term_id, $tt_id, $taxonomy ) {
		$this->store_old_term_permalink( $term_id, $taxonomy );
	}

	/**
	 * Handle edited_term hook.
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return void
	 */
	public function handle_edited_term( $term_id, $tt_id, $taxonomy ) {
		$this->check_term_permalink_change( $term_id, $taxonomy );
	}

	/**
	 * Check if permalink has changed and initialize the nudge.
	 *
	 * @param int      $post_id    Post ID.
	 * @param \WP_Post $post_after Post object after update.
	 * @since 1.5.0
	 * @return void
	 */
	public function check_permalink_change( $post_id, $post_after ) {
		if ( $post_after->post_status !== 'publish' || ! isset( self::$old_permalinks[ $post_id ] ) ) {
			return;
		}

		$old_permalink = self::$old_permalinks[ $post_id ];
		$new_permalink = get_permalink( $post_id );

		unset( self::$old_permalinks[ $post_id ] );

		if ( $new_permalink === false ) {
			return;
		}

		$this->maybe_trigger_nudge( $old_permalink, $new_permalink );
	}

	/**
	 * Render the admin notice container.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function render_notice() {
		if ( ! $this->should_display_notice() ) {
			return;
		}

		echo '<div id="surerank-admin-notice" class="notice"></div>';
	}

	/**
	 * Enqueue scripts for the admin notice.
	 *
	 * @since 1.5.0
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( ! $this->should_display_notice() ) {
			return;
		}
		$this->build_assets_operations(
			'admin-notice',
		);

		$this->enqueue_vendor_and_common_assets();
	}

	/**
	 * Initialize post-related hooks.
	 *
	 * @return void
	 */
	private function init_post_hooks() {
		add_action( 'pre_post_update', [ $this, 'handle_pre_post_update' ], 10, 2 );
		add_action( 'post_updated', [ $this, 'handle_post_updated' ], 10, 3 );
	}

	/**
	 * Initialize term-related hooks.
	 *
	 * @return void
	 */
	private function init_term_hooks() {
		add_action( 'edit_term', [ $this, 'handle_edit_term' ], 10, 3 );
		add_action( 'edited_term', [ $this, 'handle_edited_term' ], 10, 3 );
	}

	/**
	 * Store old permalink before post update.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function store_old_permalink( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post || $post->post_status !== 'publish' ) {
			return;
		}

		// Store the old permalink before update.
		$permalink = get_permalink( $post_id );
		if ( $permalink !== false ) {
			self::$old_permalinks[ $post_id ] = $permalink;
		}
	}

	/**
	 * Store old term permalink before term update.
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return void
	 */
	private function store_old_term_permalink( $term_id, $taxonomy ) {
		$term = get_term( $term_id, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) {
			return;
		}

		$old_permalink = get_term_link( $term_id, $taxonomy );

		if ( is_wp_error( $old_permalink ) ) {
			return;
		}

		// Store old permalink for later comparison using term_ prefix.
		self::$old_permalinks[ "term_{$term_id}" ] = $old_permalink;
	}

	/**
	 * Check if term permalink has changed and initialize the nudge.
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return void
	 */
	private function check_term_permalink_change( $term_id, $taxonomy ) {
		$term_key = "term_{$term_id}";

		if ( ! isset( self::$old_permalinks[ $term_key ] ) ) {
			return;
		}

		$old_permalink = self::$old_permalinks[ $term_key ];
		$new_permalink = get_term_link( $term_id, $taxonomy );

		unset( self::$old_permalinks[ $term_key ] );

		if ( is_wp_error( $new_permalink ) ) {
			return;
		}

		$this->maybe_trigger_nudge( $old_permalink, $new_permalink );
	}

	/**
	 * Compare permalinks and trigger nudge if they changed.
	 *
	 * @param string $old_permalink Old permalink URL.
	 * @param string $new_permalink New permalink URL.
	 * @return void
	 */
	private function maybe_trigger_nudge( $old_permalink, $new_permalink ) {
		if ( $old_permalink === $new_permalink ) {
			return;
		}

		// Normalize URLs by removing home URL and trailing slashes for comparison.
		$old_relative = Functions_Utils::get_relative_url( $old_permalink );
		$new_relative = Functions_Utils::get_relative_url( $new_permalink );

		if ( $old_relative !== $new_relative ) {
			$this->initialize_permalink_redirect_nudge();
		}
	}

	/**
	 * Initialize the permalink redirect nudge.
	 *
	 * @return void
	 */
	private function initialize_permalink_redirect_nudge() {
		$nudges = (array) get_option( SURERANK_NUDGES, [] );

		$nudges['permalink_redirect'] = [
			'count'                => 0,
			'next_time_to_display' => 0,
			'display'              => true,
		];

		update_option( SURERANK_NUDGES, $nudges );
	}

	/**
	 * Check if the notice should be displayed.
	 *
	 * @since 1.5.0
	 * @return bool
	 */
	private function should_display_notice() {
		// Don't show if user doesn't have permission.
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		// Get current screen early to avoid unnecessary processing.
		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}

		// Define allowed screen IDs where the notice should appear.
		$allowed_screens = [
			'dashboard',                    // Dashboard.
			'dashboard-network',            // Network dashboard.
			'edit-post',                    // Posts list.
			'post',                         // Edit post.
			'edit-page',                    // Pages list.
			'page',                         // Edit page.
			'plugins',                      // Plugins page.
		];

		if ( ! in_array( $screen->id, $allowed_screens, true ) && ! in_array( $screen->base, $allowed_screens, true ) ) {
			return false;
		}

		// Check if notice should display based on nudges settings.
		$nudges = Utils::get_instance()->get_nudges();

		// Don't show if permalink_redirect nudge is not initialized (no permalink change yet).
		if ( ! isset( $nudges['permalink_redirect'] ) ) {
			return false;
		}

		// Don't show if the nudge has been dismissed.
		if ( isset( $nudges['permalink_redirect']['display'] ) && ! $nudges['permalink_redirect']['display'] ) {
			return false;
		}

		return true;
	}
}
