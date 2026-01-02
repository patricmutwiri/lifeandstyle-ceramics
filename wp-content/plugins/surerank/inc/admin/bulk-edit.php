<?php
/**
 * Bulk Edit class.
 *
 * Handles bulk editing of SureRank robot settings for posts and pages.
 *
 * @package SureRank\Inc\Admin
 */

namespace SureRank\Inc\Admin;

use SureRank\Inc\Functions\Helper as FunctionsHelper;
use SureRank\Inc\Traits\Get_Instance;

defined( 'ABSPATH' ) || exit;

/**
 * SureRank Bulk Edit
 *
 * Handles bulk editing of robot settings (Index/NoIndex, Follow/NoFollow) for posts.
 */
class BulkEdit {

	use Get_Instance;

	/**
	 * Flag to check if fields have been added.
	 *
	 * @var bool
	 */
	private static $fields_added = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$post_types = array_keys( FunctionsHelper::get_public_cpts() );

		add_action( 'bulk_edit_custom_box', [ $this, 'add_bulk_edit_fields' ], 10, 2 );

		foreach ( $post_types as $post_type ) {
			add_action( "save_post_{$post_type}", [ $this, 'save_bulk_edit' ], 10, 2 );
		}
	}

	/**
	 * Add SureRank bulk edit fields to the post bulk edit form.
	 *
	 * @param string $column_name The name of the column.
	 * @param string $post_type The post type.
	 * @return void
	 */
	public function add_bulk_edit_fields( string $column_name, string $post_type ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( self::$fields_added ) {
			return;
		}

		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<div class="inline-edit-group wp-clearfix">
					<span class="title inline-edit-surerank-settings" style="font-weight: bold; margin-bottom: 5px; display: block;"><?php esc_html_e( 'SURERANK SETTINGS', 'surerank' ); ?></span>
					<div style="display: flex; flex-direction: column; gap: 10px;">
						<label>
							<span class="title"><?php esc_html_e( 'No Index -', 'surerank' ); ?></span>
							<select name="surerank_no_index">
								<option value="-1"><?php esc_html_e( '— No Change —', 'surerank' ); ?></option>
								<option value="yes"><?php esc_html_e( 'Yes', 'surerank' ); ?></option>
								<option value="no"><?php esc_html_e( 'No', 'surerank' ); ?></option>
							</select>
						</label>
						<label>
							<span class="title"><?php esc_html_e( 'No Follow -', 'surerank' ); ?></span>
							<select name="surerank_no_follow">
								<option value="-1"><?php esc_html_e( '— No Change —', 'surerank' ); ?></option>
								<option value="yes"><?php esc_html_e( 'Yes', 'surerank' ); ?></option>
								<option value="no"><?php esc_html_e( 'No', 'surerank' ); ?></option>
							</select>
						</label>
						<label>
							<span class="title"><?php esc_html_e( 'No Archive -', 'surerank' ); ?></span>
							<select name="surerank_no_archive">
								<option value="-1"><?php esc_html_e( '— No Change —', 'surerank' ); ?></option>
								<option value="yes"><?php esc_html_e( 'Yes', 'surerank' ); ?></option>
								<option value="no"><?php esc_html_e( 'No', 'surerank' ); ?></option>
							</select>
						</label>
					</div>
				</div>
			</div>
		</fieldset>
		<?php
		self::$fields_added = true;
	}

	/**
	 * Save bulk edit settings for posts.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function save_bulk_edit( int $post_id, \WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check if this is a bulk edit request.
		if ( ! isset( $_REQUEST['bulk_edit'], $_REQUEST['post'], $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'bulk-posts' ) ) {
			return;
		}

		// Ensure this post is part of the bulk edit.
		$post_ids = array_map( 'intval', (array) wp_unslash( $_REQUEST['post'] ) );
		if ( ! in_array( $post_id, $post_ids, true ) ) {
			return;
		}

		$no_index   = isset( $_REQUEST['surerank_no_index'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['surerank_no_index'] ) ) : '-1';
		$no_follow  = isset( $_REQUEST['surerank_no_follow'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['surerank_no_follow'] ) ) : '-1';
		$no_archive = isset( $_REQUEST['surerank_no_archive'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['surerank_no_archive'] ) ) : '-1';

		if ( '-1' !== $no_index ) {
			Helper::update_robot_meta( $post_id, 'post_no_index', $no_index, false );
		}

		if ( '-1' !== $no_follow ) {
			Helper::update_robot_meta( $post_id, 'post_no_follow', $no_follow, false );
		}

		if ( '-1' !== $no_archive ) {
			Helper::update_robot_meta( $post_id, 'post_no_archive', $no_archive, false );
		}
	}
}
