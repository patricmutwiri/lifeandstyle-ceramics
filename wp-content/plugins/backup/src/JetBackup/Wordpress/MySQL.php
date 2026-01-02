<?php

namespace JetBackup\Wordpress;

use Exception;
use JetBackup\Exception\DBException;
use wpdb;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

/**
 * JetDB Class
 * Wrapper for WordPress database operations using wpdb.
 */
class MySQL {

    private wpdb $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Get all tables with the WordPress table prefix.
     *
     * @return array List of table names.
     * @throws DBException
     */
	public function listTables(): array {
		try {
			$like_pattern = $this->wpdb->esc_like($this->getPrefix()) . '%';
			$tables = $this->query("SHOW TABLES LIKE %s", [$like_pattern], ARRAY_N);

			$tableList = [];
			foreach ($tables as $table) {
				$tableList[] = $table[0];
			}

			return $tableList;
		} catch (Exception $e) {
			throw new DBException($e->getMessage());
		}
	}



	/**
	 * @return string|null
	 * Check if the current database user has the required privileges to restore the database.
	 */
	public function checkPrivileges(): ?string {
		try {
			$this->execRaw("CREATE TEMPORARY TABLE jetbackup_priv_test (id INT);");
			$this->execRaw("DROP TEMPORARY TABLE jetbackup_priv_test;");
			return null;
		} catch (Exception $e) {
			return 'Database permissions error: ' . $e->getMessage();
		}
	}


	/**
     * Get the WordPress table prefix.
     *
     * @return string Table prefix.
     */
    public function getPrefix(): string {
        return $this->wpdb->prefix;
    }

    /**
     * Execute a query and return the results.
     *
     * @param string $query SQL query string.
     * @param array $params Parameters for SQL query.
     * @param string $resultType Type of result (OBJECT, ARRAY_A, ARRAY_N).
     *
     * @throws DBException
     */
	public function query(string $query, array $params = [], string $resultType = OBJECT) {
		try {
			if (!empty($params)) {
				$query = $this->wpdb->prepare($query, $params);
			}
			return $this->wpdb->get_results($query, $resultType);
		} catch (Exception $e) {
			throw new DBException($e->getMessage());
		}
	}

    /**
     * Execute an SQL query and return the number of rows affected.
     *
     * @param string $query SQL query string.
     *
     * @return int Number of rows affected.
     * @throws DBException
     */
    public function exec( string $query): int {
        try {
            return $this->wpdb->query($this->escapeSql($query));
        } catch (Exception $e) {
            throw new DBException ($e->getMessage());
        }
    }

    /**
     * Execute a raw SQL query without preparation.
     *
     * @param string $query Raw SQL query string.
     *
     * @throws DBException
     */
    public function execRaw( string $query)
    {
        try {
            return $this->wpdb->get_results($this->escapeSql($query));
        } catch (Exception $e) {
            throw new DBException ($e->getMessage());
        }
    }

    /**
     * Fetch a single row from the result set.
     *
     * @param string $query SQL query string.
     * @param array $params Parameters for SQL query.
     * @param string $resultType Type of result (OBJECT, ARRAY_A, ARRAY_N).
     *
     * @throws DBException
     */
    public function fetch( string $query, array $params = [], string $resultType = OBJECT)
    {
        try {
            $preparedQuery = $this->wpdb->prepare($query, $params);
            return $this->wpdb->get_row($preparedQuery, $resultType);
        } catch (Exception $e) {
            throw new DBException ($e->getMessage());
        }
    }

    /**
     * Get the ID of the last inserted row.
     *
     * @return int Last insert ID.
     */
    public function lastInsertId(): int {
        return $this->wpdb->insert_id;
    }

    /**
     * Get the last error occurred in database operations.
     *
     * @return string Last error message.
     */
    public function getLastError(): string {
        return $this->wpdb->last_error;
    }

    /**
     * Flush the cached results.
     */
    public function flush()
    {
        $this->wpdb->flush();
    }

    /**
     * Escape a SQL string for safe use in queries.
     *
     * @param mixed $value The value to be escaped.
     * @return array|string The escaped value.
     */
    public function escapeSql($value)
    {
        if (function_exists('esc_sql')) {
            return esc_sql($value);
        } else {
            return $this->wpdb->_escape($value);
        }
    }

    /**
     * Clears all post revisions from the WordPress database.
     *
     * @return int The number of rows affected.
     * @throws DBException
     */
	public function clearPostRevisions(): int {
		try {
			$query = "DELETE FROM {$this->wpdb->posts} WHERE post_type = %s";
			return $this->exec($this->wpdb->prepare($query, ['revision']));
		} catch (Exception $e) {
			throw new DBException($e->getMessage());
		}
	}

}