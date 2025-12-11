<?php
/**
 * ============================================================================
 *  ZaitTiny Framework - DBConnection
 *  ----------------------------------------------------------------------------
 *  Autor......: Prof. Dr. Cleber S. Oliveira
 *  Propósito..: Gerenciar conexões PDO com múltiplos SGBDs (MySQL, SQLite,
 *               PostgreSQL e SQL Server) utilizando configurações vindas de
 *               $_SESSION['config']['database'].
 *  Licença....: Didático / acadêmico
 * ============================================================================
 */
declare ( strict_types = 1 );

namespace app\core;

use PDO;
use PDOException;
use RuntimeException;
use InvalidArgumentException;

if (session_status () === PHP_SESSION_NONE) {
	session_start ();
}

class DBConnection {
	private ?\PDO $pdoConnection = null;
	public function __construct() {
		if (session_status () === PHP_SESSION_NONE) {
			session_start ();
		}
		$configDB = $_SESSION ['config'] ['database'] ?? [ ];
		if (empty ( $configDB )) {
			throw new InvalidArgumentException ( 'Configuração de banco de dados ausente em $_SESSION["config"]["database"].' );
		}
		$sgbd 		= strtolower ( $configDB ['sgbd'] ?? 'mysql');
		$host 		= $configDB ['host'] ?? 'localhost';
		$username 	= $configDB ['username'] ?? 'root';
		$password 	= $configDB ['password'] ?? '';
		$dbname 	= $configDB ['dbname'] ?? '';
		$port 		= $configDB ['port'] ?? '3306';
		$charset 	= $configDB ['charset'] ?? 'utf8mb4';
		$persistent	= $configDB ['persistent'] ?? false; // booleano;
		$file 		= $configDB ['file'] ?? ''; // usado em SQLite
		$optionsCfg	= $configDB ['options'] ?? [ ]; // array associativo
		if (! in_array ( $sgbd, ['mysql','pgsql','sqlite','sqlsrv'], true )) {
			throw new \InvalidArgumentException ( 'SGBD inválido. Use "mysql", "pgsql", "sqlite" ou "sqlsrv".' );
		}
		switch ($sgbd) {
			case 'mysql' : $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset};port={$port}"; break;
			case 'pgsql' : $dsn = "pgsql:host={$host};dbname={$dbname};port={$port}"; break;
			case 'sqlsrv': $dsn = "sqlsrv:Server={$host},{$port};Database={$dbname}"; break;
			case 'sqlite' : 
				$sqliteFile = $file !== '' ? $file : $dbname;
				if ($sqliteFile === '') {
					throw new \InvalidArgumentException ( 'Para SQLite, informe "file" ou "dbname" com o caminho do arquivo do banco.' );
				}
				$dsn = "sqlite:{$sqliteFile}";
			break;
			default : throw new \InvalidArgumentException ( "SGBD '{$sgbd}' não suportado." );
		}
		try {
			$propertiesConn = [ 
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
			];
			if ($persistent === true) {
				$propertiesConn [PDO::ATTR_PERSISTENT] = true;
			}
			if (is_array ( $optionsCfg )) {
				foreach ( $optionsCfg as $optionName => $optionValue ) {
					switch (strtolower ( $optionName )) {
						case 'timeout' : $propertiesConn [PDO::ATTR_TIMEOUT] = ( int ) $optionValue; break;
						case 'emulate_prepares' : $propertiesConn [PDO::ATTR_EMULATE_PREPARES] = ( bool ) $optionValue; break;
					}
				}
			}
			$this->setPDOConnection ( new \PDO ( $dsn, $username, $password, $propertiesConn ) );
			if ($sgbd === 'sqlite') {
				$this->getPDOConnection ()->exec ( 'PRAGMA foreign_keys = ON' );
			}
		} catch ( \PDOException $e ) {
			throw new \RuntimeException ( 'Falha na conexão PDO: ' . $e->getMessage (), ( int ) $e->getCode (), $e );
		}
	}
	public function query(string $sql, array $params = [ ]): array {
		try {
			if (empty ( $params )) {
				$stmt = $this->getPDOConnection ()->query ( $sql );
			} else {
				$stmt = $this->getPDOConnection ()->prepare ( $sql );
				$stmt->execute ( $params );
			}
			return $stmt->fetchAll ( \PDO::FETCH_ASSOC );
		} catch ( \PDOException $e ) {
			throw new \RuntimeException ( $this->formatPdoError ( $e ), ( int ) $e->getCode (), $e );
		}
	}
	public function execute(string $sql, array $params = [ ]): int {
		try {
			$stmt = $this->getPDOConnection ()->prepare ( $sql );
			$stmt->execute ( $params );
			return $stmt->rowCount ();
		} catch ( \PDOException $e ) {
			throw new \RuntimeException ( $this->formatPdoError ( $e ), ( int ) $e->getCode (), $e );
		}
	}
	public function fetchOne(string $sql, array $params = [ ]): ?array {
		try {
			$stmt = $this->getPDOConnection ()->prepare ( $sql );
			$stmt->execute ( $params );
			$row = $stmt->fetch ( \PDO::FETCH_ASSOC );
			return $row === false ? null : $row;
		} catch ( \PDOException $e ) {
			throw new \RuntimeException ( $this->formatPdoError ( $e ), ( int ) $e->getCode (), $e );
		}
	}
	public function begin(): void {
		$this->getPDOConnection ()->beginTransaction ();
	}
	public function commit(): void {
		$this->getPDOConnection ()->commit ();
	}
	public function rollBack(): void {
		if ($this->getPDOConnection ()->inTransaction ()) {
			$this->getPDOConnection ()->rollBack ();
		}
	}
	public function inTransaction(): bool {
		return $this->getPDOConnection ()->inTransaction ();
	}
	public function lastInsertId(string $name = null): string {
		return $this->getPDOConnection ()->lastInsertId ( $name );
	}
	public function ping(): bool {
		try {
			$sql = 'SELECT 1';
			$this->getPDOConnection ()->query ( $sql );
			return true;
		} catch ( \Throwable ) {
			return false;
		}
	}
	public function close(): void {
		$this->setPDOConnection(null);
	}
	public function setPDOConnection(?\PDO $pdoConnection): void {
		$this->pdoConnection = $pdoConnection ;
	}
	public function getPDOConnection(): \PDO {
		if (! $this->pdoConnection) {
			throw new \RuntimeException ( 'Conexão PDO não inicializada.' );
		}
		return $this->pdoConnection;
	}
	private function formatPdoError(\PDOException $exception): string {
		$sgbdFromSession = $_SESSION['config']['database']['sgbd'] ?? 'Não informado nas configurações!';
		$driver          = strtolower((string) $sgbdFromSession);
		$code            = (string) $exception->getCode();
		$mapMsg          = $this->getErrorMessage($driver, $code);
		return "Erro {$driver} #{$code}: {$mapMsg}\n" . $exception->getMessage();
	}
	private function getErrorMessage(string $sgbd, string $errorNumber): string {
		$pdoErrors = $_SESSION ['config'] ['path'] ['pdoErrors'] ?? null;
		if ($pdoErrors && is_file ( $pdoErrors )) {
			$json = file_get_contents ( $pdoErrors );
			$errors = json_decode ( $json, true );
			if (is_array ( $errors ) && isset ( $errors [$sgbd] [$errorNumber] )) {
				$err = $errors [$sgbd] [$errorNumber];
				$msg = $err ['message'] ?? 'Erro desconhecido';
				$expl = $err ['explanation'] ?? 'Sem detalhes adicionais.';
				return "{$msg} | {$expl}";
			}
		}
		return 'Erro não catalogado — Consulte o administrador do sistema.';
	}
}
