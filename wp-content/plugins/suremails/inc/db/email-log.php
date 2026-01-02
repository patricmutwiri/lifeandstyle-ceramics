<?php
/**
 * SureMails Plugin Email Log Database Handler
 *
 * Handles CRUD operations and flexible queries for the email log table.
 *
 * @package SureMails\Inc\DB
 */

namespace SureMails\Inc\DB;

use Exception;
use SureMails\Inc\Traits\Instance;
use SureMails\Inc\Utils\LogError;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class EmailLog
 *
 * Handles operations for the `suremails_email_log` database table.
 */
class EmailLog {
	use Instance;

	/**
	 * Email log table name.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Constructor to set the table name.
	 */
	protected function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'suremails_email_log';
		add_filter( 'suremails_process_get_logs', [ $this, 'process_logs' ] );
	}

	/**
	 * Get the email log table name.
	 *
	 * @return string Table name.
	 */
	public function get_table_name() {
		return $this->table_name;
	}

	/**
	 * Get all possible statuses
	 *
	 * @return array<int,string>
	 */
	public function get_statuses() {
		return [ 'failed', 'sent', 'pending', 'blocked' ];
	}

	/**
	 * Process logs
	 *
	 * @param array<int,mixed> $logs Logs.
	 * @return array<int,mixed>
	 * @since 0.0.1
	 */
	public function process_logs( $logs ) {
		return array_map(
			/**
			 * Filter the attachments.
			 *
			 * @param array<string,mixed> $log
			 * @return array<string,mixed>
			 */
			static function ( $log ) {
				if ( ! isset( $log['attachments'] ) ) {
					return $log;
				}
				if ( isset( $log['headers'] ) && ! empty( $log['headers'] ) ) {
					$log['headers'] = maybe_unserialize( $log['headers'] );
				}
				if ( isset( $log['attachments'] ) && ! empty( $log['attachments'] ) ) {
					$log['attachments'] = maybe_unserialize( $log['attachments'] );
				}
				if ( isset( $log['response'] ) && ! empty( $log['response'] ) ) {
					$log['response'] = maybe_unserialize( $log['response'] );
				}
				if ( isset( $log['meta'] ) && ! empty( $log['meta'] ) ) {
					$log['meta'] = json_decode( $log['meta'], true );
				} else {
					$log['meta'] = [];
				}

				if ( ! is_array( $log['attachments'] ) ) {
					$log['attachments'] = [];
				}

				foreach ( $log['attachments'] as $key => $att ) {
					$log['attachments'][ $key ] = basename( $att );
				}
				return $log;
			},
			$logs
		);
	}

	/**
	 * Create the email log database table.
	 *
	 * @return bool|WP_Error True on success, false on failure.
	 * @throws Exception If there is an error creating the table.
	 */
	public function create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$statuses = "'" . implode( "', '", $this->get_statuses() ) . "'";

		try {
			$sql = "CREATE TABLE `{$this->table_name}` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `email_from` varchar(100) NOT NULL,
            `email_to` longtext NOT NULL,
            `subject` varchar(255) NOT NULL,
            `body` longtext NOT NULL,
            `headers` longtext NOT NULL,
            `attachments` longtext NOT NULL,
            `status` ENUM({$statuses}) NOT NULL DEFAULT 'pending',
            `response` longtext NOT NULL,
			`meta` json NULL,
            `connection` varchar(255) NOT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (`id`),
            KEY `email_from` (`email_from`),
            KEY `status` (`status`)
        ) {$charset_collate};";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

			if ( $wpdb->last_error ) {
				// translators: %s: Database error message.
				throw new Exception( __( 'Database error: ', 'suremails' ) . $wpdb->last_error );
			}
		} catch ( Exception $e ) {
			// Log the error for debugging purposes.
			// translators: %s: Error message.
			LogError::instance()->log_error( sprintf( __( 'Error creating email log table: %s', 'suremails' ), $e->getMessage() ) );
			// translators: %s: Error message.
			return new WP_Error( 'db_error', sprintf( __( 'Error creating email log table: %s', 'suremails' ), $e->getMessage() ) );
		}

		return true;
	}

	/**
	 * Insert a new email log entry.
	 *
	 * @param array $data Associative array of data to insert.
	 * @return int|false Inserted row ID or false on failure.
	 * @throws Exception If there is an error inserting the record.
	 */
	public function insert( array $data ) {
		global $wpdb;

		// Define the default values.
		$defaults = [
			'email_from'  => '',
			'email_to'    => '',
			'subject'     => '',
			'body'        => '',
			'headers'     => '',
			'attachments' => [],
			'status'      => 'pending',
			'response'    => [],
			'meta'        => null,
			'connection'  => '',
		];

		try {
			// Merge defaults with provided data.
			$data = wp_parse_args( $data, $defaults );

			// Validate required fields.
			$required_fields = [ 'email_from', 'email_to', 'subject', 'body', 'status' ];
			foreach ( $required_fields as $field ) {
				if ( empty( $data[ $field ] ) ) {
					// translators: %s: The name of the missing required field.
					throw new Exception( sprintf( __( 'Missing required field: %s', 'suremails' ), $field ) );
				}
			}

			// Checking if valid status is passed.
			if ( ! ( isset( $data['status'] ) && in_array( $data['status'], $this->get_statuses() ) ) ) {
				unset( $data['status'] );
			}

			// Sanitize input.
			$data['email_to']    = maybe_serialize( $data['email_to'] );
			$data['headers']     = maybe_serialize( $data['headers'] );
			$data['response']    = maybe_serialize( $data['response'] );
			$data['attachments'] = maybe_serialize( $data['attachments'] );
			$data['meta']        = ! empty( $data['meta'] ) ? wp_json_encode( $data['meta'] ) : null;
			$data['updated_at']  = current_time( 'mysql' );

			// Prepare data types.
			$format = [
				'%s', // email_from.
				'%s', // email_to.
				'%s', // subject.
				'%s', // body.
				'%s', // headers.
				'%s', // attachments.
				'%s', // status.
				'%s', // response.
				'%s', // meta.
				'%s', // connection.
				'%s', // updated_at.
			];

			// Insert into the database.
			$result = $wpdb->insert(
				$this->table_name,
				$data,
				$format
			);

			if ( $result === false ) {
				// translators: %s: Database error message.
				throw new Exception( __( 'Database error: ', 'suremails' ) . $wpdb->last_error );
			}
		} catch ( Exception $e ) {
			// Log the error for debugging purposes.
			// translators: %s: Error message.
			LogError::instance()->log_error( sprintf( __( 'Error inserting email log: %s', 'suremails' ), $e->getMessage() ) );
			return false;
		}
		return $wpdb->insert_id;
	}

	/**
	 * Retrieve email log entries based on given parameters.
	 *
	 * @param array $args Query arguments including:
	 *                    - 'select'     => string (default '*')
	 *                    - 'where'      => array (field => value)
	 *                    - 'group_by'   => string
	 *                    - 'having'     => array (field => value)
	 *                    - 'order'      => array (field => 'ASC'|'DESC')
	 *                    - 'limit'      => int
	 *                    - 'offset'     => int.
	 *
	 * @return array|false Array of results or false on failure.
	 *  @throws Exception If there is an error creating the table.
	 */
	public function get( array $args = [] ) {
		global $wpdb;

		// Extract parameters with defaults.
		$select   = ! empty( $args['select'] ) ? $args['select'] : '*';
		$where    = ! empty( $args['where'] ) && is_array( $args['where'] ) ? $args['where'] : [];
		$group_by = ! empty( $args['group_by'] ) && is_string( $args['group_by'] ) ? $args['group_by'] : '';
		$having   = ! empty( $args['having'] ) && is_array( $args['having'] ) ? $args['having'] : [];
		$order_by = ! empty( $args['order'] ) && is_array( $args['order'] ) ? $args['order'] : [];

		if ( isset( $args['limit'] ) || isset( $args['offset'] ) ) {
			$limit        = isset( $args['limit'] ) ? intval( $args['limit'] ) : 0;
			$offset       = isset( $args['offset'] ) ? intval( $args['offset'] ) : 0;
			$limit_object = Db_Helper::form_limit_clause( $limit, $offset );
			$limit_clause = is_string( $limit_object['clause'] ) ? $limit_object['clause'] : '';
			$values_limit = is_array( $limit_object['values'] ) ? $limit_object['values'] : [];
		} else {
			$limit_clause = '';
			$values_limit = [];
		}

		try {
			$values_where = [];

			// Build WHERE clause.
			$where_object = Db_Helper::form_where_clause( $where );
			$where_clause = is_string( $where_object['clause'] ) ? $where_object['clause'] : '';
			$values_where = is_array( $where_object['values'] ) ? $where_object['values'] : [];

			// Build HAVING clause.
			$having_object = Db_Helper::form_where_clause( $having, true );
			$having_clause = is_string( $having_object['clause'] ) ? $having_object['clause'] : '';
			$values_having = is_array( $having_object['values'] ) ? $having_object['values'] : [];

			// Build GROUP BY clause.
			$group_by_clause = Db_Helper::form_group_by_clause( $group_by );

			// Build ORDER BY clause.
			$order_clause = Db_Helper::form_order_by_clause( $order_by );

			// Combine all parts.
			$sql = "SELECT {$select} FROM `{$this->table_name}` {$where_clause} {$group_by_clause} {$having_clause} {$order_clause} {$limit_clause}";

			// Merge all values for prepared statement.
			$all_values = array_merge( $values_where, $values_having, $values_limit );

			// Prepare the SQL query with placeholders.
			//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared on next line.
			$prepared_query = $wpdb->prepare( $sql, $all_values );

			// Execute the query.
			//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared on next line.
			$results = $wpdb->get_results( $prepared_query, ARRAY_A );

			if ( $results === false ) {
				// translators: %s: Database error message.
				throw new Exception( sprintf( __( 'Error retrieving email logs: %s', 'suremails' ), $wpdb->last_error ) );
			}

			return apply_filters( 'suremails_process_get_logs', $results );

		} catch ( Exception $e ) {
			// Log the error for debugging purposes.
			LogError::instance()->log_error( $e->getMessage() );
			return false;
		}
	}

	/**
	 * Update an email log entry.
	 *
	 * @param int   $id   The ID of the record to update.
	 * @param array $data Associative array of data to update.
	 *  @throws Exception If there is an error updating the record.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function update( int $id, array $data ) {
		global $wpdb;

		if ( empty( $id ) || empty( $data ) ) {
			// translators: %s: Error message for invalid ID or data.
			return new WP_Error( 'email_log_update_invalid', __( 'Invalid ID or data provided for update.', 'suremails' ) );
		}

		if ( isset( $data['meta'] ) ) {
			$data['meta'] = wp_json_encode( $data['meta'] );
		}

		try {
			if ( isset( $data['response'] ) ) {
				$data['response'] = maybe_serialize( $data['response'] );
			}

			// Update the database.
			$result = $wpdb->update(
				$this->table_name,
				$data,
				[ 'id' => $id ],
				'%s',
				[ '%d' ]
			);

			if ( $result === false ) {
				// translators: %d: Log ID, %s: Database error message.
				throw new Exception( sprintf( __( 'Error updating email log ID %1$d: %2$s', 'suremails' ), $id, $wpdb->last_error ) );
			}

			return $result;

		} catch ( Exception $e ) {
			// translators: %s: Exception message.
			LogError::instance()->log_error( sprintf( __( 'Exception updating email log: %s', 'suremails' ), $e->getMessage() ) );
			// translators: %s: Exception message.
			return new WP_Error( 'email_log_update_exception', sprintf( __( 'An exception occurred: %s', 'suremails' ), $e->getMessage() ) );
		}
	}

	/**
	 * Retrieve a specific email log entry by its ID.
	 *
	 * @param int $log_id The ID of the log entry to retrieve.
	 * @return array|WP_Error|false The log entry as an associative array, WP_Error on failure, or false if not found.
	 */
	public function get_log( int $log_id ) {
		if ( empty( $log_id ) ) {
			// translators: %s: Provided log ID.
			return new WP_Error( 'email_log_get_invalid_id', sprintf( __( 'Invalid log ID provided: %s', 'suremails' ), $log_id ) );
		}

		// Use the get method to retrieve the log entry.
		$logs = $this->get(
			[
				'select' => '*',
				'where'  => [ 'id = ' => $log_id ],
				'limit'  => 1,
			]
		);

		if ( $logs === false ) {
			// translators: %d: Log ID.
			LogError::instance()->log_error( sprintf( __( 'Failed to retrieve log ID %d.', 'suremails' ), $log_id ) );
			// translators: %d: Log ID.
			return new WP_Error( 'email_log_get_failed', sprintf( __( 'Failed to retrieve log ID %d.', 'suremails' ), $log_id ) );
		}

		if ( empty( $logs ) ) {
			return false; // No log entry found with the provided ID.
		}

		return $logs[0]; // Return the first (and only) log entry.
	}

	/**
	 * Delete email log entries based on given parameters.
	 *
	 * @param array $args {
	 *   ids?: int|int[],
	 *   where?: array<string, mixed>,
	 *   having?: array<string, mixed>,
	 *   limit?: int
	 * } $args The arguments for deleting email logs, including:
	 *   - 'ids' (int|int[], optional): The ID or array of IDs of the records to delete.
	 *   - 'where' (array<string, mixed>, optional): Conditions for the WHERE clause.
	 *   - 'having' (array<string, mixed>, optional): Conditions for the HAVING clause.
	 *   - 'limit' (int, optional): The maximum number of records to delete.
	 * }.
	 *
	 * @return int|false Number of rows deleted or false on failure.
	 * @throws Exception If there is an error deleting the records.
	 */
	public function delete( array $args ) {
		global $wpdb;

		try {
			$conditions   = [];
			$values       = [];
			$limit_clause = '';

			// Handle 'ids' parameter.
			if ( isset( $args['ids'] ) ) {
				$ids           = is_array( $args['ids'] ) ? $args['ids'] : [ $args['ids'] ];
				$sanitized_ids = array_map( 'intval', $ids );

				if ( ! empty( $sanitized_ids ) ) {
					$placeholders = implode( ', ', array_fill( 0, count( $sanitized_ids ), '%d' ) );
					$conditions[] = "id IN ( {$placeholders} )";
					$values       = array_merge( $values, $sanitized_ids );
				}
			}

			// Handle 'where' conditions.
			if ( isset( $args['where'] ) && is_array( $args['where'] ) ) {
				foreach ( $args['where'] as $field => $value ) {
					if ( preg_match( '/^(\w+)\s*(=|!=|<|<=|>|>=|LIKE)$/', $field, $matches ) ) {
						$field_name   = $matches[1];
						$operator     = $matches[2];
						$conditions[] = "{$field_name} {$operator} %s";
						$values[]     = $value;
					} else {
						// Default to '=' operator if not specified.
						$conditions[] = "{$field} = %s";
						$values[]     = $value;
					}
				}
			}

			// Construct WHERE clause.
			$where_clause = '';
			if ( ! empty( $conditions ) ) {
				$where_clause = 'WHERE ' . implode( ' AND ', $conditions );
			}

			// Handle 'limit'.
			if ( isset( $args['limit'] ) ) {
				$limit = intval( $args['limit'] );
				if ( $limit > 0 ) {
					$limit_clause = 'LIMIT %d';
					$values[]     = $limit;
				}
			}

			// Construct the final DELETE query with table name enclosed in backticks.
			$query = 'DELETE FROM `' . $this->table_name . '` ' . $where_clause . ' ' . $limit_clause;

			// Prepare the query with the values.
			//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared on next line.
			$prepared_query = $wpdb->prepare( $query, $values );

			// Execute the query.
			//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared on next line.
			$result = $wpdb->query( $prepared_query );

			if ( $result === false ) {
				// translators: %s: Database error message.
				throw new Exception( sprintf( __( 'Database error: %s', 'suremails' ), $wpdb->last_error ) );
			}

						return $result; // Returns the number of rows deleted.

		} catch ( Exception $e ) {
			// translators: %s: Error message.
			LogError::instance()->log_error( sprintf( __( 'Error deleting email logs: %s', 'suremails' ), $e->getMessage() ) );
			return false;
		}
	}

}
