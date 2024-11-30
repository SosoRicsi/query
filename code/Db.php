<?php declare(strict_types=1);

namespace SosoRicsi\Query;

use InvalidArgumentException;
use PDO;
use PDOException;

class Db
{
	/**
	 * @var string The database driver (e.g., "mysql").
	 */
	protected string $driver;

	/**
	 * @var string The username for the database connection.
	 */
	protected string $dbUser;

	/**
	 * @var string The password for the database connection.
	 */
	protected string $dbPassword;

	/**
	 * @var string The host of the database server (e.g., "localhost").
	 */
	protected string $dbHost;

	/**
	 * @var string The database name to connect to.
	 */
	public string $database;

	/**
	 * @var PDO The PDO instance for database interaction.
	 */
	protected PDO $conn;

	/**
	 * @var string The current table for database operations.
	 */
	protected string $table;

	/**
	 * @var string The fields to be selected from the table (default is "*").
	 */
	protected string $field = '*';

	/**
	 * @var array The conditions for WHERE clauses in queries.
	 */
	protected array $wheres = [];

	/**
	 * @var array The sorting conditions for the query.
	 */
	protected array $order = [];

	protected array $joins = [];

	protected const ACCEPTED_JOINS = [
		'INNER JOIN',
		'RIGHT JOIN',
		'LEFT JOIN',
		'LEFT OUTER JOIN',
	];

	/**
	 * @var int The maximum number of rows to retrieve (LIMIT).
	 */
	protected int $limit;

	/**
	 * @var int The offset for pagination in queries.
	 */
	protected int $offset;

	/**
	 * Sets up the database connection parameters.
	 *
	 * @param string $driver The database driver (e.g., "mysql").
	 * @param string $user The username for the database.
	 * @param string $password The password for the database.
	 * @param string $host The database host (e.g., "localhost").
	 * 
	 * @return void
	 */
	public function setDatabase(string $host = "", string $user = "", string $password = "", string $driver = "mysql")
	{
		$this->driver = $driver;
		$this->dbUser = $user;
		$this->dbPassword = $password;
		$this->dbHost = $host;
	}

	/**
	 * Establishes a connection to the database.
	 *
	 * @param string|null $database The name of the database to connect to.
	 * 
	 * @return void
	 */
	public function connect(string $database)
	{
		$this->database = $database;

		try {
			$this->conn = new PDO("mysql:host={$this->dbHost};dbname={$this->database}", $this->dbUser, $this->dbPassword);

			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			print $e->getMessage();
		}
	}

	/**
	 * Starts a database transaction.
	 *
	 * @return void
	 */
	public function transaction(): void
	{
		$this->conn->beginTransaction();
	}

	/**
	 * Commits the current database transaction.
	 *
	 * @return void
	 */
	public function commit(): void
	{
		$this->conn->commit();
	}

	/**
	 * Rolls back the current database transaction.
	 *
	 * @return void
	 */
	public function rollback(): void
	{
		$this->conn->rollBack();
	}

	public function columnExists(string $column, string $table = null): bool
	{
		$table = empty($table) ? $this->table : $table;
		
		
		$query = $this->conn->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = ?");
		$query->execute([$table, $column]);

		return $query->fetchColumn() > 0;
	}

	/**
	 * Specifies the table to perform queries on.
	 *
	 * @param string $table The name of the table.
	 * 
	 * @return object The current Db instance for method chaining.
	 */
	public function table(string $table): object
	{
		$this->clear();
		$this->table = $table;

		return $this;
	}

	public function join(string $type, string $table2, string $where): object
	{
		if (!in_array($type, self::ACCEPTED_JOINS)) {
			$accepted = implode(', ', self::ACCEPTED_JOINS);

			throw new InvalidArgumentException("The join type must be [{$accepted}], [{$type}] given.");
		}

		$this->joins[] = [
			"type" => $type,
			"table2" => $table2,
			"where" => $where
		];

		return $this;
	}

	/**
	 * Specifies the fields to select in the query.
	 *
	 * @param string $field The fields to select (e.g., "column1, column2").
	 * 
	 * @return object The current Db instance for method chaining.
	 */
	public function select(string $field): object
	{
		$this->field = $field;

		return $this;
	}

	/**
	 * Performs an INSERT query to insert data into the specified table.
	 *
	 * @param string $columns The column names where values will be inserted.
	 * @param array $values The values to be inserted into the columns.
	 * 
	 * @return mixed The last insert ID or an error message on failure.
	 */
	public function insert(string $columns, array $values): mixed
	{
		$placeholders = str_repeat('?,', count($values) - 1) . '?';
		$query = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

		try {
			$stmt = $this->conn->prepare($query);
			$stmt->execute($values);
			return $this->conn->lastInsertId();
		} catch (PDOException $e) {
			return $e->getMessage();
		}
	}

	/**
	 * Adds a WHERE condition to the query using an AND operator.
	 *
	 * @param string $column The column name to apply the condition on.
	 * @param string $operator The comparison operator (e.g., '=', '>', '<').
	 * @param string $value The value to compare the column against.
	 * 
	 * @return object The current Db instance for method chaining.
	 */
	public function where(string $column, string $operator, string $value): object
	{
		$this->wheres[] = [
			"type" => "AND",
			"column" => $column,
			"operator" => $operator,
			"value" => $value
		];

		return $this;
	}

	/**
	 * Adds a WHERE condition to the query using an OR operator.
	 *
	 * @param string $column The column name to apply the condition on.
	 * @param string $operator The comparison operator.
	 * @param string $value The value to compare the column against.
	 * 
	 * @return object The current Db instance for method chaining.
	 */
	public function orWhere(string $column, string $operator, string $value): object
	{
		$this->wheres[] = [
			"type" => "OR",
			"column" => $column,
			"operator" => $operator,
			"value" => $value
		];

		return $this;
	}

	/**
	 * Adds a WHERE condition to the query using a NOT operator.
	 *
	 * @param string $column The column name to apply the condition on.
	 * @param string $operator The comparison operator.
	 * @param string $value The value to compare the column against.
	 * 
	 * @return object The current Db instance for method chaining.
	 */
	public function notWhere(string $column, string $operator, string $value): object
	{
		$this->wheres[] = [
			"type" => "NOT",
			"column" => $column,
			"operator" => $operator,
			"value" => $value
		];

		return $this;
	}

	/**
	 * Specifies the sorting order for the query results.
	 *
	 * @param string $columns The column(s) to order by.
	 * @param string $type The sort direction ("ASC" or "DESC").
	 * 
	 * @return object The current Db instance for method chaining.
	 */
	public function order(string $columns, string $type): object
	{
		$this->order = [
			"type" => $type,
			"columns" => $columns
		];

		return $this;
	}

	/**
	 * Specifies the LIMIT for the number of rows to retrieve.
	 *
	 * @param int $limit The number of rows to retrieve.
	 * 
	 * @return object The current Db instance for method chaining.
	 */
	public function limit(int $limit): object
	{
		$this->limit = $limit;

		return $this;
	}

	/**
	 * Specifies the OFFSET for the query for pagination purposes.
	 *
	 * @param int $offset The offset value.
	 * 
	 * @return object The current Db instance for method chaining.
	 */
	public function offset(int $offset): object
	{
		$this->offset = $offset;

		return $this;
	}

	/**
	 * Executes the SELECT query with the defined conditions and returns the results.
	 *
	 * @return array|object The collection of fetched results.
	 */
	public function get(): array
	{
		$query = "SELECT {$this->field} FROM {$this->table}";

		if (!empty($this->joins)) {
			foreach ($this->joins as $join) {
				$query .= " {$join['type']} {$join['table2']} ON {$join['where']}";
			}
		}

		if (!empty($this->wheres)) {
			$query .= " WHERE ";
			foreach ($this->wheres as $index => $where) {
				if ($index > 0) {
					$query .= " {$where['type']} ";
				}
				if ($where['type'] === "NOT") {
					$query .= "{$where['type']} ";
				}
				$query .= "{$where['column']} {$where['operator']} ?";
			}
		}

		if (!empty($this->order)) {
			$query .= " ORDER BY {$this->order['columns']} {$this->order['type']}";
		}

		if (!empty($this->limit)) {
			$query .= " LIMIT {$this->limit}";

			if (!empty($this->offset)) {
				$query .= " OFFSET {$this->offset}";
			}
		}

		$stmt = $this->conn->prepare($query);
		$stmt->execute(array_column($this->wheres, 'value'));

		return $stmt->fetchAll(PDO::FETCH_OBJ);
	}

	/**
	 * Executes a DELETE query based on the defined conditions.
	 *
	 * @return void
	 */
	public function delete()
	{
		$query = "DELETE FROM {$this->table}";

		if (!empty($this->wheres)) {
			$query .= " WHERE ";

			foreach ($this->wheres as $index => $where) {
				if ($where['type'] === "NOT") {
					$query .= "{$where['type']} ";
				}
				$query .= "{$where['column']} {$where['operator']} ?";
			}
		}

		if (!empty($this->limit)) {
			$query .= " LIMIT {$this->limit}";
		}

		try {
			$stmt = $this->conn->prepare($query);
			$stmt->execute(array_column($this->wheres, 'value'));
		} catch (PDOException $e) {
			return $e->getMessage();
		}
	}

	/**
	 * Executes a raw SQL query and returns the results.
	 *
	 * @param string $query The raw SQL query to execute.
	 * @param array $params The parameters to bind to the query.
	 * 
	 * @return array|object The collection of fetched results.
	 */
	public function raw(string $query, array $params = []): array|object
	{
		$stmt = $this->conn->prepare($query);
		$stmt->execute($params);

		return $stmt->fetchAll(PDO::FETCH_OBJ);
	}

	/**
	 * Clears the current query state, resetting table, fields, conditions, etc.
	 *
	 * @return void
	 */
	protected function clear()
	{
		$this->table = '';
		$this->field = '*';
		$this->wheres = [];
		$this->order = [];
		$this->joins = [];
		unset($this->limit);
		unset($this->offset);
	}

	/**
	 * Closes the database connection and resets the state.
	 *
	 * @return void
	 */
	public function close()
	{
		$this->clear();
		$this->conn = null;
	}

}