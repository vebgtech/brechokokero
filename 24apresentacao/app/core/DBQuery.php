<?php
/**
 * ============================================================================
 *  ZaitTiny Framework - DBQuery
 *  ----------------------------------------------------------------------------
 *  Compatível com:
 *   - DBConnection (query, execute, fetchOne, lastInsertId)
 *   - DBWhere      (buildWhereOnly() e params())
 *
 *  Ideia:
 *   - DBQuery cuida de: SELECT, FROM, JOIN, WHERE, GROUP BY, HAVING,
 *     ORDER BY, LIMIT.
 *   - DBWhere cuida apenas da parte lógica do WHERE (parênteses, AND/OR, etc.).
 *
 *  Uso típico no DAO (herdando DBQuery):
 *
 *   class UsuarioDAO extends DBQuery {
 *       public function __construct() {
 *           parent::__construct(
 *               'usuarios',
 *               'idUsuario, email, senha, idNivelUsuario, nome, cpf, endereco, bairro, cidade, uf, cep, telefone, foto, ativo',
 *               'idUsuario'
 *           );
 *       }
 *
 *       public function findByEmail(string $email): ?array {
 *           $this->addCondition('AND', 'email', '=', $email)
 *                ->addLimit(1);
 *
 *           $rows = $this->select(); // estado interno é resetado após o SELECT
 *           return $rows[0] ?? null;
 *       }
 *   }
 * ============================================================================
 */
declare ( strict_types = 1 );

namespace app\core;

use InvalidArgumentException;
use Exception;

class DBQuery {
	private DBConnection $dbConnection;
	private string $tableName;

	/** @var string[] */
	private array $fieldsName;

	/** @var string[] */
	private array $primaryKeys = [ ];

	/**
	 * WHERE interno reutilizável
	 */
	private DBWhere $where;

	/**
	 * JOINs
	 */
	private array $joins = [ ]; // ex.: ["INNER JOIN t2 ON t2.id = t1.t2_id"]
	private array $outerJoins = [ ]; // ex.: ["LEFT JOIN t3 ON ..."]

	/**
	 * GROUP BY / HAVING / ORDER / LIMIT
	 */
	private array $groupBy = [ ];
	private array $havingRaw = [ ]; // HAVING em string crua (ex.: "COUNT(*) > 10")
	private array $orderBy = [ ];
	private ?int $limit = null;
	private ?int $offset = null;

	/**
	 * SELECT extra / agregações
	 */
	private array $selectRaw = [ ]; // ex.: ["DATE(dataCadastro) AS dia"]
	private array $aggregates = [ ]; // ex.: ["COUNT(*) AS total"]
	private array $aggregateFields = [ ]; // campos que entram em função (para ajudar no GROUP BY auto)

	/**
	 * metadados livres (para validações futuras, etc.)
	 */
	private array $foreignKeys = [ ];
	private array $uniqueKeys = [ ];
	public function __construct(string $tableName, string $fieldsNames, $primaryKeys) {
		$this->dbConnection = new DBConnection ();
		$this->tableName = $tableName;
		$this->fieldsName = array_map ( 'trim', explode ( ',', $fieldsNames ) );
		$this->primaryKeys = is_array ( $primaryKeys ) ? $primaryKeys : [ 
				$primaryKeys
		];

		$this->where = new DBWhere ();
	}

	// =====================================================================
	// SELECT
	// =====================================================================

	/**
	 * SELECT genérico:
	 * - Usa FROM, JOIN, WHERE (DBWhere interno), GROUP BY, HAVING, ORDER BY, LIMIT.
	 * - Após a execução, o estado interno de filtros/joins/ordenação é resetado.
	 */
	public function select(): array {
		$fieldList = $this->buildFieldList ();

		$sql = "SELECT {$fieldList} FROM {$this->tableName}";
		$params = [ ];

		// JOINs
		foreach ( $this->joins as $joinSql ) {
			$sql .= " {$joinSql}";
		}
		foreach ( $this->outerJoins as $joinSql ) {
			$sql .= " {$joinSql}";
		}

		// WHERE
		$whereSql = $this->where->buildWhereOnly ();
		$whereParams = $this->where->params ();
		$sql .= $whereSql;
		$params = array_merge ( $params, $whereParams );

		// GROUP BY (automático se houver agregação e não houver groupBy manual)
		$sql .= $this->buildGroupByClause ();

		// HAVING (apenas RAW, sem parâmetros por simplicidade)
		if (! empty ( $this->havingRaw )) {
			$sql .= ' HAVING ' . implode ( ' AND ', $this->havingRaw );
		}

		// ORDER BY
		if (! empty ( $this->orderBy )) {
			$sql .= ' ORDER BY ' . implode ( ', ', $this->orderBy );
		}

		// LIMIT / OFFSET
		if ($this->limit !== null) {
			$sql .= ' LIMIT ' . $this->limit;
			if ($this->offset !== null) {
				$sql .= ' OFFSET ' . $this->offset;
			}
		}

		$rows = $this->dbConnection->query ( $sql, $params );

		// Limpamos tudo para não “vazar” estado entre chamadas
		$this->resetQueryState ();

		return $rows;
	}

	// =====================================================================
	// INSERT
	// =====================================================================

	/**
	 * INSERT
	 * $values pode ser associativo ['col'=>'val'] ou indexado na ordem de $fieldsName.
	 * Retorna lastInsertId() (string).
	 */
	public function insert(array $values): string {
		$assoc = $this->normalizeValuesAssoc ( $values );

		$cols = implode ( ', ', $this->fieldsName );
		$placeholders = ':' . implode ( ', :', $this->fieldsName );
		$sql = "INSERT INTO {$this->tableName} ({$cols}) VALUES ({$placeholders})";

		$params = [ ];
		foreach ( $this->fieldsName as $columnName ) {
			$params [":{$columnName}"] = $assoc [$columnName] ?? null;
		}

		try {
			$this->dbConnection->execute ( $sql, $params );
			$lastId = $this->dbConnection->lastInsertId ();

			$this->resetQueryState ();

			return $lastId;
		} catch ( \RuntimeException $exception ) {
			if (( string ) $exception->getCode () === '23000') {
				throw new Exception ( 'Violação de chave única ou estrangeira.' );
			}
			throw $exception;
		}
	}

	// =====================================================================
	// UPDATE
	// =====================================================================

	/**
	 * UPDATE
	 * Usa SET preparado; WHERE é montado automaticamente pelas primary keys
	 * a partir de $values.
	 * Retorna rowCount().
	 */
	public function update(array $values): int {
		$assoc = $this->normalizeValuesAssoc ( $values );
		$sets = [ ];
		$params = [ ];

		foreach ( $this->fieldsName as $columnName ) {
			if (! array_key_exists ( $columnName, $assoc )) {
				throw new InvalidArgumentException ( "Chave não encontrada no array de valores: {$columnName}" );
			}
			$sets [] = "{$columnName} = :set_{$columnName}";
			$params [":set_{$columnName}"] = $assoc [$columnName];
		}

		$sql = "UPDATE {$this->tableName} SET " . implode ( ', ', $sets );
		$whereLocal = new DBWhere ();

		foreach ( $this->primaryKeys as $primaryKey ) {
			if (! array_key_exists ( $primaryKey, $assoc )) {
				throw new InvalidArgumentException ( "Valor da chave primária não encontrado: {$primaryKey}" );
			}
			$whereLocal->addCondition ( 'AND', $primaryKey, '=', $assoc [$primaryKey] );
		}

		$sql .= $whereLocal->buildWhereOnly ();
		$params = array_merge ( $params, $whereLocal->params () );

		try {
			$rows = $this->dbConnection->execute ( $sql, $params );
			$this->resetQueryState ();
			return $rows;
		} catch ( \RuntimeException $exception ) {
			if (( string ) $exception->getCode () === '23000') {
				throw new Exception ( 'Violação de chave única ou estrangeira.' );
			}
			throw $exception;
		}
	}

	// =====================================================================
	// DELETE
	// =====================================================================

	/**
	 * DELETE
	 * WHERE é montado automaticamente pelas primary keys a partir de $values.
	 * Retorna rowCount().
	 */
	public function delete(array $values): int {
		$assoc = $this->normalizeValuesAssoc ( $values );

		$sql = "DELETE FROM {$this->tableName}";
		$whereLocal = new DBWhere ();

		foreach ( $this->primaryKeys as $primaryKey ) {
			if (! array_key_exists ( $primaryKey, $assoc )) {
				throw new InvalidArgumentException ( "Valor da chave primária não encontrado: {$primaryKey}" );
			}
			$whereLocal->addCondition ( 'AND', $primaryKey, '=', $assoc [$primaryKey] );
		}

		$sql .= $whereLocal->buildWhereOnly ();
		$params = $whereLocal->params ();

		try {
			$rows = $this->dbConnection->execute ( $sql, $params );
			$this->resetQueryState ();
			return $rows;
		} catch ( \RuntimeException $exception ) {
			if (( string ) $exception->getCode () === '23000') {
				throw new Exception ( 'Violação de chave única ou estrangeira.' );
			}
			throw $exception;
		}
	}

	// =====================================================================
	// JOIN helpers
	// =====================================================================
	public function addJoin(string $joinSql): self {
		$this->joins [] = $joinSql;
		return $this;
	}
	public function addLeftJoin(string $joinSql): self {
		$this->outerJoins [] = $joinSql;
		return $this;
	}
	public function clearJoins(): self {
		$this->joins = [ ];
		$this->outerJoins = [ ];
		return $this;
	}

	// =====================================================================
	// Facade para o WHERE interno (DAO não precisa conhecer DBWhere)
	// =====================================================================

	/**
	 * Adiciona condição ao WHERE interno
	 */
	public function addCondition(string $logic, string $field, string $operator, mixed $value = null): self {
		$this->where->addCondition ( $logic, $field, $operator, $value );
		return $this;
	}

	/**
	 * Abre bloco lógico com parênteses no WHERE interno
	 */
	public function openParenthesis(string $logic = 'AND'): self {
		$this->where->openParenthesis ( $logic );
		return $this;
	}

	/**
	 * Fecha bloco lógico com parênteses no WHERE interno
	 */
	public function closeParenthesis(): self {
		$this->where->closeParenthesis ();
		return $this;
	}

	/**
	 * Adiciona trecho cru ao WHERE interno
	 */
	public function addRawCondition(string $logic, string $rawSql): self {
		$this->where->addRaw ( $logic, $rawSql );
		return $this;
	}

	// =====================================================================
	// GROUP BY / HAVING / ORDER BY / LIMIT
	// =====================================================================

	/**
	 * Define colunas para GROUP BY (manual).
	 * Pode ser string ou array.
	 */
	public function addGroupBy(string|array $group): self {
		if (is_array ( $group )) {
			foreach ( $group as $g ) {
				$g = trim ( ( string ) $g );
				if ($g !== '') {
					$this->groupBy [] = $g;
				}
			}
		} else {
			$g = trim ( $group );
			if ($g !== '') {
				$this->groupBy [] = $g;
			}
		}
		return $this;
	}

	/**
	 * Adiciona expressão crua ao HAVING.
	 * Ex.: "COUNT(*) > 10", "SUM(valor) >= 1000"
	 */
	public function addHaving(string $rawExpression): self {
		$expr = trim ( $rawExpression );
		if ($expr !== '') {
			$this->havingRaw [] = $expr;
		}
		return $this;
	}

	/**
	 * ORDER BY (string ou array)
	 */
	public function addOrderBy(string|array $order): self {
		if (is_array ( $order )) {
			foreach ( $order as $o ) {
				$o = trim ( ( string ) $o );
				if ($o !== '') {
					$this->orderBy [] = $o;
				}
			}
		} else {
			$o = trim ( $order );
			if ($o !== '') {
				$this->orderBy [] = $o;
			}
		}
		return $this;
	}

	/**
	 * LIMIT / OFFSET
	 */
	public function addLimit(int $limit, ?int $offset = null): self {
		if ($limit < 0) {
			$limit = 0;
		}
		$this->limit = $limit;
		$this->offset = $offset !== null ? max ( 0, $offset ) : null;
		return $this;
	}

	// =====================================================================
	// SELECT extra / agregações
	// =====================================================================

	/**
	 * Adiciona expressão crua ao SELECT.
	 * Ex.: "DATE(dataCadastro) AS dia"
	 */
	public function addSelectRaw(string $rawExpression): self {
		$expr = trim ( $rawExpression );
		if ($expr !== '') {
			$this->selectRaw [] = $expr;
		}
		return $this;
	}

	/**
	 * Adiciona função de agregação ao SELECT.
	 * Ex.:
	 * addAggregate('COUNT', '*', 'total');
	 * addAggregate('SUM', 'valor', 'soma_valor');
	 *
	 * Se não for informado alias, será gerado automaticamente (ex.: SUM_valor).
	 * Se field != '*', ele é marcado para não ser incluído no GROUP BY automático.
	 */
	public function addAggregate(string $funcName, string $field, ?string $alias = null): self {
		$funcName = strtoupper ( trim ( $funcName ) ); // COUNT, SUM, AVG...
		$field = trim ( $field );

		if ($field !== '*') {
			$this->aggregateFields [] = $field;
		}

		if ($alias === null || $alias === '') {
			$alias = strtolower ( $funcName . '_' . str_replace ( '.', '_', $field ) );
		}

		$this->aggregates [] = "{$funcName}({$field}) AS {$alias}";
		return $this;
	}

	// =====================================================================
	// Getters / Setters básicos
	// =====================================================================
	public function getDBConnection(): DBConnection {
		return $this->dbConnection;
	}
	public function setDBConnection(DBConnection $dbConnection): void {
		$this->dbConnection = $dbConnection;
	}
	public function getTableName(): string {
		return $this->tableName;
	}
	public function setTableName(string $tableName): void {
		$this->tableName = $tableName;
	}

	/**
	 *
	 * @return string[]
	 */
	public function getFieldsName(): array {
		return $this->fieldsName;
	}
	public function setFieldsName(string $fieldsNames): void {
		$this->fieldsName = array_map ( 'trim', explode ( ',', $fieldsNames ) );
	}

	/**
	 *
	 * @return string[]
	 */
	public function getPrimaryKeys(): array {
		return $this->primaryKeys;
	}

	/**
	 *
	 * @param string[] $primaryKeys
	 */
	public function setPrimaryKeys(array $primaryKeys): void {
		$this->primaryKeys = $primaryKeys;
	}
	public function getForeignKeys(): array {
		return $this->foreignKeys;
	}
	public function setForeignKeys(array $foreignKeys): void {
		$this->foreignKeys = $foreignKeys;
	}
	public function getUniqueKeys(): array {
		return $this->uniqueKeys;
	}
	public function setUniqueKeys(array $uniqueKeys): void {
		$this->uniqueKeys = $uniqueKeys;
	}

	// =====================================================================
	// Helpers internos
	// =====================================================================

	/**
	 * Monta a lista de campos do SELECT (campos normais + agregações + raw)
	 */
	private function buildFieldList(): string {
		$fieldList = implode ( ', ', $this->fieldsName );

		if (! empty ( $this->aggregates )) {
			$fieldList .= ', ' . implode ( ', ', $this->aggregates );
		}

		if (! empty ( $this->selectRaw )) {
			$fieldList .= ', ' . implode ( ', ', $this->selectRaw );
		}

		return $fieldList;
	}

	/**
	 * Monta a cláusula GROUP BY (manual ou automática quando há agregações)
	 */
	private function buildGroupByClause(): string {
		// Se o dev definir GROUP BY manualmente, respeita
		if (! empty ( $this->groupBy )) {
			return ' GROUP BY ' . implode ( ', ', $this->groupBy );
		}

		// Se não houver agregações, não precisa de GROUP BY
		if (empty ( $this->aggregates )) {
			return '';
		}

		// GROUP BY automático:
		// todos os campos "normais" que não aparecem como campo de agregação
		$autoGroup = array_diff ( $this->fieldsName, $this->aggregateFields );

		if (empty ( $autoGroup )) {
			// Ex.: SELECT COUNT(*) FROM tabela
			return '';
		}

		return ' GROUP BY ' . implode ( ', ', $autoGroup );
	}

	/**
	 * Normaliza valores para array associativo alinhado à lista de campos.
	 * - Se vier associativo, garante todas as chaves da tabela (faltantes viram null).
	 * - Se vier indexado, mapeia pela ordem de $fieldsName (valida contagem).
	 */
	private function normalizeValuesAssoc(array $values): array {
		$isAssoc = count ( array_filter ( array_keys ( $values ), 'is_string' ) ) > 0;

		if ($isAssoc) {
			$assoc = [ ];
			foreach ( $this->fieldsName as $columnName ) {
				$assoc [$columnName] = $values [$columnName] ?? null;
			}
			return $assoc;
		}

		if (count ( $values ) !== count ( $this->fieldsName )) {
			throw new InvalidArgumentException ( 'O número de valores informados não é equivalente aos campos da tabela!' );
		}

		$assoc = [ ];
		foreach ( $this->fieldsName as $index => $columnName ) {
			$assoc [$columnName] = $values [$index];
		}
		return $assoc;
	}

	/**
	 * Reseta WHERE, GROUP BY, HAVING, ORDER BY, LIMIT, SELECT extra, agregações
	 */
	private function resetQueryState(): void {
		$this->where->reset ();

		$this->joins = [ ];
		$this->outerJoins = [ ];
		$this->groupBy = [ ];
		$this->havingRaw = [ ];
		$this->orderBy = [ ];
		$this->limit = null;
		$this->offset = null;
		$this->selectRaw = [ ];
		$this->aggregates = [ ];
		$this->aggregateFields = [ ];
	}
}
