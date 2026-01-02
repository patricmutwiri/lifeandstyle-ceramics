<?php
/**
 * Database Helper
 *
 * Provides functionality to help the DB classes methods.
 *
 * @package SureMails\Inc\DB
 */

namespace SureMails\Inc\DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Settings
 *
 * Handles fetching specific settings from the connections option.
 */
class Db_Helper {

	/**
	 * Where Pattern.
	 *
	 * @var string
	 * @since 0.0.1
	 */
	private static string $where_pattern = '/^(?:(AND|OR)\s+)?(\w+)\s*(=|!=|<|<=|>|>=|LIKE|IN|NOT IN)$/i';

	/**
	 * Form the where clause string from given array conditions
	 *
	 * @param array<string,mixed> $where Where Array.
	 * @param bool                $having_flag Having or Where flag.
	 * @return array<string, array<int<0, max>, array|string>|string>
	 */
	public static function form_where_clause( $where = null, $having_flag = false ) {

		if ( empty( $where ) ) {
			return [
				'clause' => '',
				'values' => [],
			];
		}

		$pattern      = self::$where_pattern;
		$conditions   = [];
		$values_where = [];

		// Build an array of condition strings with their connectors.
		foreach ( $where as $field => $value ) {
			if ( preg_match( $pattern, $field, $matches ) ) {
				$connector  = isset( $matches[1] ) ? strtoupper( $matches[1] ) : '';
				$field_name = $matches[2];
				$operator   = strtoupper( $matches[3] );

				if ( in_array( $operator, [ 'IN', 'NOT IN' ], true ) && is_array( $value ) ) {
					$placeholders = implode( ', ', array_fill( 0, count( $value ), '%s' ) );
					$clause_part  = "{$field_name} {$operator} ({$placeholders})";
					foreach ( $value as $v ) {
						$values_where[] = $v;
					}
				} else {
					$clause_part    = "{$field_name} {$operator} %s";
					$values_where[] = esc_sql( $value );
				}

				if ( empty( $conditions ) ) {
					$conditions[] = $clause_part;
				} else {
					// Prepend the connector (default to "AND" if none provided).
					$conditions[] = ( $connector ? $connector : 'AND' ) . ' ' . $clause_part;
				}
			} else {
				// Default to '=' operator.
				$default_clause = "{$field} = %s";
				$values_where[] = $value;
				if ( empty( $conditions ) ) {
					$conditions[] = $default_clause;
				} else {
					$conditions[] = 'AND ' . $default_clause;
				}
			}
		}

		// Group consecutive OR conditions together.
		$final_conditions = [];
		$i                = 0;
		$cnt              = count( $conditions );
		while ( $i < $cnt ) {
			$current = $conditions[ $i ];
			// If the next condition(s) begin with "OR ", group them with the current.
			if ( $i < $cnt - 1 && strpos( $conditions[ $i + 1 ], 'OR ' ) === 0 ) {
				$group = [];
				// Remove a leading "AND " or "OR " from the current condition.
				if ( strpos( $current, 'AND ' ) === 0 ) {
					$group[] = substr( $current, 4 );
				} elseif ( strpos( $current, 'OR ' ) === 0 ) {
					$group[] = substr( $current, 3 );
				} else {
					$group[] = $current;
				}
				$i++;
				// Group all consecutive conditions that start with "OR ".
				while ( $i < $cnt && strpos( $conditions[ $i ], 'OR ' ) === 0 ) {
					$group[] = substr( $conditions[ $i ], 3 );
					$i++;
				}
				$final_conditions[] = '(' . implode( ' OR ', $group ) . ')';
			} else {
				// Remove any leading connector from non-grouped conditions.
				if ( strpos( $current, 'AND ' ) === 0 ) {
					$current = substr( $current, 4 );
				} elseif ( strpos( $current, 'OR ' ) === 0 ) {
					$current = substr( $current, 3 );
				}
				$final_conditions[] = $current;
				$i++;
			}
		}

		// Join final conditions with " AND " to form the complete clause.
		$connector_keyword = $having_flag ? 'HAVING' : 'WHERE';
		$where_clause      = $connector_keyword . ' ' . implode( ' AND ', $final_conditions );
		return [
			'clause' => $where_clause,
			'values' => $values_where,
		];
	}

	/**
	 * Form the GROUP BY clause string from given array conditions.
	 *
	 * @param string $group_by Group By Field.
	 * @return string
	 */
	public static function form_group_by_clause( $group_by = null ) {

		if ( ! is_string( $group_by ) || empty( $group_by ) ) {
			return '';
		}

		$group_by_safe = esc_sql( $group_by );
		return "GROUP BY {$group_by_safe}";
	}

	/**
	 * Form the ORDER BY clause string from given array conditions
	 *
	 * @param array<string,string> $order_by Order By Array.
	 * @return string
	 */
	public static function form_order_by_clause( $order_by = null ) {

		if ( empty( $order_by ) ) {
			return '';
		}

		$order_clauses = [];
		foreach ( $order_by as $field => $direction ) {
			$direction       = strtoupper( $direction );
			$direction       = in_array( $direction, [ 'ASC', 'DESC' ], true ) ? $direction : 'ASC';
			$field_safe      = esc_sql( $field );
			$order_clauses[] = "{$field_safe} {$direction}";
		}
		return 'ORDER BY ' . implode( ', ', $order_clauses );
	}

	/**
	 * Form the LIMIY clause string from given array conditions
	 *
	 * @param int $limit Limit value.
	 * @param int $offset Offset value.
	 * @return array<string,string|array<int,int>>
	 */
	public static function form_limit_clause( $limit = 0, $offset = 0 ) {
		$values_limit = [];
		$limit_clause = '';

		if ( $limit ) {
			$limit_clause   = 'LIMIT %d';
			$values_limit[] = $limit;
		}

		if ( $offset ) {
			$limit_clause  .= ' OFFSET %d';
			$values_limit[] = $offset;
		}

		return [
			'clause' => $limit_clause,
			'values' => $values_limit,
		];
	}

}
