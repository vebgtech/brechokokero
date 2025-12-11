<?php
declare ( strict_types = 1 );

namespace app\core;

/**
 * DBWhere
 * ----------------------------------------------------------------------------
 * Responsável apenas por montar o predicado lógico (WHERE) e parâmetros.
 *
 * Recursos:
 * - addCondition(AND|OR, campo, operador, valor)
 * - IN / NOT IN (valor = array)
 * - BETWEEN (valor = [min, max])
 * - IS NULL / IS NOT NULL (sem valor)
 * - Parênteses (openParenthesis/closeParenthesis) para agrupamento lógico
 * - addRaw() para trechos customizados (use com cuidado)
 * - params() para obter os parâmetros nomeados
 * - reset() para limpar o estado interno
 */
class DBWhere {

	/** @var array<int, array<string,mixed>> */
	private array $tokens = [ ];

	/** @var array<string,mixed> */
	private array $params = [ ];
	private int $counter = 0;

	/**
	 * Adiciona uma condição ao WHERE.
	 * Exemplos:
	 * - addCondition('AND', 'id', '=', 10)
	 * - addCondition('OR', 'nome', 'LIKE', '%ana%')
	 * - addCondition('AND', 'status', 'IN', ['A','B'])
	 * - addCondition('AND', 'created_at', 'BETWEEN', ['2025-01-01','2025-12-31'])
	 * - addCondition('AND', 'deleted_at', 'IS NULL')
	 */
	public function addCondition(string $logic, string $field, string $operator, mixed $value = null): self {
		$logic = strtoupper ( trim ( $logic ) );
		if ($logic !== 'AND' && $logic !== 'OR') {
			$logic = 'AND';
		}

		$operator = strtoupper ( trim ( $operator ) );

		// Operadores sem valor
		if ($operator === 'IS NULL' || $operator === 'IS NOT NULL') {
			$sql = "{$field} {$operator}";
			$this->tokens [] = [ 
					'type' => 'cond',
					'logic' => $logic,
					'sql' => $sql
			];
			return $this;
		}

		// IN / NOT IN (array)
		if ($operator === 'IN' || $operator === 'NOT IN') {
			if (! is_array ( $value ) || empty ( $value )) {
				// expressão "impossível" ou "sempre verdadeira" para manter compatibilidade
				$sql = $operator === 'IN' ? '1 = 0' : '1 = 1';
				$this->tokens [] = [ 
						'type' => 'cond',
						'logic' => $logic,
						'sql' => $sql
				];
				return $this;
			}

			$placeholders = [ ];
			foreach ( $value as $singleValue ) {
				$paramName = $this->nextParam ();
				$this->params [$paramName] = $singleValue;
				$placeholders [] = $paramName;
			}

			$sql = "{$field} {$operator} (" . implode ( ', ', $placeholders ) . ")";
			$this->tokens [] = [ 
					'type' => 'cond',
					'logic' => $logic,
					'sql' => $sql
			];
			return $this;
		}

		// BETWEEN (array [min, max])
		if ($operator === 'BETWEEN') {
			if (! is_array ( $value ) || count ( $value ) !== 2) {
				throw new \InvalidArgumentException ( "BETWEEN requer array com [min, max]." );
			}
			[ 
					$minValue,
					$maxValue
			] = $value;

			$paramMin = $this->nextParam ();
			$paramMax = $this->nextParam ();
			$this->params [$paramMin] = $minValue;
			$this->params [$paramMax] = $maxValue;

			$sql = "{$field} BETWEEN {$paramMin} AND {$paramMax}";
			$this->tokens [] = [ 
					'type' => 'cond',
					'logic' => $logic,
					'sql' => $sql
			];
			return $this;
		}

		// Operadores binários (=, <>, !=, >, <, >=, <=, LIKE, ILIKE, etc.)
		$paramName = $this->nextParam ();
		$this->params [$paramName] = $value;
		$sql = "{$field} {$operator} {$paramName}";

		$this->tokens [] = [ 
				'type' => 'cond',
				'logic' => $logic,
				'sql' => $sql
		];

		return $this;
	}

	/**
	 * Abre um grupo de condições com parênteses: ( ...
	 * )
	 * Ex.: openParenthesis('AND')->addCondition(...)->closeParenthesis();
	 */
	public function openParenthesis(string $logic = 'AND'): self {
		$logic = strtoupper ( trim ( $logic ) );
		if ($logic !== 'AND' && $logic !== 'OR') {
			$logic = 'AND';
		}
		$this->tokens [] = [ 
				'type' => 'open',
				'logic' => $logic
		];
		return $this;
	}

	/**
	 * Fecha um grupo aberto com openParenthesis().
	 */
	public function closeParenthesis(): self {
		$this->tokens [] = [ 
				'type' => 'close'
		];
		return $this;
	}

	/**
	 * Adiciona um trecho cru ao WHERE (use com cuidado).
	 * Ex.: addRaw('AND', "EXISTS (SELECT 1 FROM ...)");
	 */
	public function addRaw(string $logic, string $rawSql): self {
		$logic = strtoupper ( trim ( $logic ) );
		if ($logic !== 'AND' && $logic !== 'OR') {
			$logic = 'AND';
		}
		$rawSql = trim ( $rawSql );
		if ($rawSql !== '') {
			$this->tokens [] = [ 
					'type' => 'cond',
					'logic' => $logic,
					'sql' => $rawSql
			];
		}
		return $this;
	}

	/**
	 * Retorna apenas a parte do WHERE (sem ORDER/LIMIT),
	 * incluindo " WHERE ..." se houver conteúdo.
	 */
	public function buildWhereOnly(): string {
		if (empty ( $this->tokens )) {
			return '';
		}

		$parts = [ ];
		$needLogic = false;

		foreach ( $this->tokens as $token ) {
			$type = $token ['type'];

			if ($type === 'open') {
				$logic = $token ['logic'] ?? 'AND';
				if ($needLogic) {
					$parts [] = $logic . ' (';
				} else {
					$parts [] = '(';
					$needLogic = true;
				}
				continue;
			}

			if ($type === 'close') {
				$parts [] = ')';
				continue;
			}

			if ($type === 'cond') {
				$logic = $token ['logic'] ?? 'AND';
				$sql = $token ['sql'] ?? '';
				if ($sql === '') {
					continue;
				}

				if ($needLogic) {
					$parts [] = "{$logic} {$sql}";
				} else {
					$parts [] = $sql;
					$needLogic = true;
				}
				continue;
			}
		}

		$flat = trim ( implode ( ' ', $parts ) );
		if ($flat === '') {
			return '';
		}
		return ' WHERE ' . $flat;
	}

	/**
	 * Backwards compatibility: build() agora é sinônimo de buildWhereOnly().
	 */
	public function build(): string {
		return $this->buildWhereOnly ();
	}

	/**
	 * Parâmetros nomeados acumulados para bind.
	 */
	public function params(): array {
		return $this->params;
	}

	/**
	 * Limpa todas as condições e parâmetros.
	 */
	public function reset(): self {
		$this->tokens = [ ];
		$this->params = [ ];
		$this->counter = 0;
		return $this;
	}

	// ---------------- Internos ----------------
	private function nextParam(): string {
		$this->counter ++;
		return ':w' . $this->counter;
	}
}
