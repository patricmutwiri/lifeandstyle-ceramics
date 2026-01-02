<?php
/**
 * Bulk Edit class.
 *
 * Handles bulk editing of SureRank robot settings for posts, pages, and taxonomies.
 *
 * @package SureRank\Inc\Admin
 */

namespace SureRank\Inc\Admin;

use SureRank\Inc\Functions\Helper as FunctionsHelper;
use SureRank\Inc\Schema\Helper as SchemaHelper;
use SureRank\Inc\Traits\Get_Instance;

/**
 * SureRank Bulk Edit
 *
 * Handles bulk editing of robot settings (Index/NoIndex, Follow/NoFollow) for posts and taxonomies.
 */
class BulkActions {

	use Get_Instance;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', [ $this, 'setup_bulk_actions' ] );
		add_action( 'admin_notices', [ $this, 'admin_notices' ] );
		add_action( 'admin_footer', [ $this, 'disable_surerank_options' ] );
	}

	/**
	 * Setup bulk actions for posts and taxonomies.
	 *
	 * @return void
	 */
	public function setup_bulk_actions(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$post_types      = array_keys( FunctionsHelper::get_public_cpts() );
		$taxonomies_data = SchemaHelper::get_instance()->get_taxonomies( [ 'public' => true ] );
		$taxonomies      = [];
		foreach ( $taxonomies_data as $taxonomy ) {
			if ( isset( $taxonomy['slug'] ) && is_string( $taxonomy['slug'] ) ) {
				$taxonomies[] = $taxonomy['slug'];
			}
		}
		$screens = array_merge( $post_types, $taxonomies );

		foreach ( $screens as $screen ) {
			add_filter( "bulk_actions-edit-{$screen}", [ $this, 'add_bulk_actions' ] );
			add_filter( "handle_bulk_actions-edit-{$screen}", [ $this, 'handle_bulk_actions' ], 10, 3 );
		}
	}

	/**
	 * Add SureRank bulk actions to the dropdown.
	 *
	 * @param array<string, string> $actions Existing bulk actions.
	 * @return array<string, string> Modified bulk actions.
	 */
	public function add_bulk_actions( array $actions ): array {
		return array_merge( $actions, $this->get_bulk_action_labels() );
	}

	/**
	 * Disable the 'surerank-settings' bulk action in the dropdown.
	 *
	 * @return void
	 */
	public function disable_surerank_options(): void {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->base, [ 'edit', 'edit-tags' ], true ) ) {
			return;
		}
		?>
		<script>
			jQuery(document).ready(function($) {
				$('select[name^="action"] option[value="surerank-settings"]').prop('disabled', true);
			});
		</script>
		<?php
	}

	/**
	 * Handle bulk actions.
	 *
	 * @param string            $redirect_to Redirect URL.
	 * @param string            $action Action being performed.
	 * @param array<int|string> $ids Array of post/term IDs.
	 * @return string Modified redirect URL.
	 */
	public function handle_bulk_actions( string $redirect_to, string $action, array $ids ): string {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $redirect_to;
		}

		$is_taxonomy = Helper::is_taxonomy_screen();

		if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
			return $redirect_to;
		}

		$nonce        = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );
		$nonce_action = $is_taxonomy ? 'bulk-tags' : 'bulk-posts';

		if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			return $redirect_to;
		}

		$surerank_actions = apply_filters( 'surerank_allowed_bulk_actions', [ 'surerank_index', 'surerank_noindex', 'surerank_follow', 'surerank_nofollow', 'surerank_archive', 'surerank_noarchive' ] );

		if ( ! in_array( $action, $surerank_actions, true ) || empty( $ids ) ) {
			return $redirect_to;
		}

		do_action( 'surerank_bulk_action_performed', $action, $ids );

		$is_taxonomy = Helper::is_taxonomy_screen();

		foreach ( $ids as $id ) {
			$id = (int) $id;
			if ( $id > 0 ) {
				$this->update_robot_settings( $id, $action, $is_taxonomy );
			}
		}

		$query_args = [
			'surerank_bulk_action' => $action,
			'surerank_updated'     => count( $ids ),
			'surerank_nonce'       => wp_create_nonce( 'surerank_bulk_action_nonce' ),
		];

		// If we're on a taxonomy screen, ensure the taxonomy parameter is preserved.
		if ( $is_taxonomy && isset( $_REQUEST['taxonomy'] ) ) {
			$query_args['taxonomy'] = sanitize_text_field( wp_unslash( $_REQUEST['taxonomy'] ) );
		}

		$redirect_url = add_query_arg( $query_args, $redirect_to );

		return apply_filters( 'surerank_bulk_action_redirect_url', $redirect_url, $action, count( $ids ) );
	}

	/**
	 * Display admin notices for bulk actions.
	 *
	 * @return void
	 */
	public function admin_notices(): void {
		if (
			! isset( $_GET['surerank_bulk_action'], $_GET['surerank_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['surerank_nonce'] ) ), 'surerank_bulk_action_nonce' )
		) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_GET['surerank_bulk_action'] ) );

		// Check for error message first.
		if ( isset( $_GET['surerank_error'] ) ) {
			$error_message = sanitize_text_field( wp_unslash( $_GET['surerank_error'] ) );
			printf(
				'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
				esc_html( rawurldecode( $error_message ) )
			);

			$this->clear_bulk_action_url_parameters();
			return;
		}

		$updated_count = isset( $_GET['surerank_updated'] ) ? absint( $_GET['surerank_updated'] ) : 0;
		if ( ! $updated_count ) {
			return;
		}

		$action_labels = $this->get_bulk_action_labels();

		if ( ! isset( $action_labels[ $action ] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-success"><p>%s</p></div>',
			sprintf(
				/* translators: %1$d: number of items updated, %2$s: action performed */
				esc_html( _n( '%1$d item %2$s.', '%1$d items %2$s.', $updated_count, 'surerank' ) ),
				esc_html( (string) $updated_count ),
				esc_html( $action_labels[ $action ] )
			)
		);

		// Clear the URL parameters after displaying the notice.
		$this->clear_bulk_action_url_parameters();
	}

	/**
	 * Clear SureRank bulk action URL parameters
	 *
	 * @return void
	 */
	public function clear_bulk_action_url_parameters() {
		?>
		<script>
			jQuery(document).ready(function() {
				if ( window.history.replaceState ) {
					var url = new URL(window.location.href);
					url.searchParams.delete('surerank_bulk_action');
					url.searchParams.delete('surerank_updated');
					url.searchParams.delete('surerank_error');
					url.searchParams.delete('surerank_nonce');
					window.history.replaceState({}, document.title, url.toString());
				}
			});
		</script>
		<?php
	}

	/**
	 * Get SureRank bulk action labels.
	 *
	 * @return array<string, string> Bulk action labels.
	 */
	private function get_bulk_action_labels(): array {
		return apply_filters(
			'surerank_bulk_actions',
			[
				'surerank-settings'  => __( 'SureRank Settings', 'surerank' ),
				'surerank_index'     => __( 'Set to index', 'surerank' ),
				'surerank_noindex'   => __( 'Set to noindex', 'surerank' ),
				'surerank_follow'    => __( 'Set to follow', 'surerank' ),
				'surerank_nofollow'  => __( 'Set to nofollow', 'surerank' ),
				'surerank_archive'   => __( 'Set to archive', 'surerank' ),
				'surerank_noarchive' => __( 'Set to noarchive', 'surerank' ),
			]
		);
	}

	/**
	 * Update robot settings for a post or term.
	 *
	 * @param int    $id Post or term ID.
	 * @param string $action Action being performed.
	 * @param bool   $is_taxonomy Whether this is a taxonomy.
	 * @return bool Success status.
	 */
	private function update_robot_settings( int $id, string $action, bool $is_taxonomy ): bool {
		$actions = [
			'surerank_index'     => [
				'key'   => 'post_no_index',
				'value' => 'no',
			],
			'surerank_noindex'   => [
				'key'   => 'post_no_index',
				'value' => 'yes',
			],
			'surerank_follow'    => [
				'key'   => 'post_no_follow',
				'value' => 'no',
			],
			'surerank_nofollow'  => [
				'key'   => 'post_no_follow',
				'value' => 'yes',
			],
			'surerank_archive'   => [
				'key'   => 'post_no_archive',
				'value' => 'no',
			],
			'surerank_noarchive' => [
				'key'   => 'post_no_archive',
				'value' => 'yes',
			],
		];

		if ( ! isset( $actions[ $action ] ) ) {
			return false;
		}

		$meta = $actions[ $action ];
		return Helper::update_robot_meta( $id, $meta['key'], $meta['value'], $is_taxonomy );
	}
}
