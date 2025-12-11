<?php
declare(strict_types=1);

namespace app\core;

class CtrlLogs {
	
	private string $logDirectory;
	
	public function __construct() {
		$this->logDirectory = $_SESSION['config']['path']['logs'] ?? '';
		
		if ($this->logDirectory === '') {
			throw new \RuntimeException("logDirectory não definido em \$_SESSION['config']");
		}
		
		if (!str_ends_with($this->logDirectory, DIRECTORY_SEPARATOR)) {
			$this->logDirectory .= DIRECTORY_SEPARATOR;
		}
		
		if (!is_dir($this->logDirectory)) {
			mkdir($this->logDirectory, 0777, true);
		}
	}
	
	/**
	 * Grava log em:
	 *   [prefix_]YYYYMMDDHHMMSSuuuuuu.log
	 *
	 * @param string|array|object $message Mensagem ou dados estruturados
	 * @param string|null         $prefix  Prefixo opcional (ex: "auth", "db", "api")
	 */
	public function log(string|array|object $message, ?string $prefix = null): void {
		$fileName = $this->buildFileName($prefix);
		$fullPath = $this->logDirectory . $fileName;
		
		$date = date('Y-m-d H:i:s.u');
		
		if (is_array($message) || is_object($message)) {
			$text = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		} else {
			$text = (string)$message;
		}
		
		$line = "[$date] $text" . PHP_EOL;
		
		file_put_contents($fullPath, $line, FILE_APPEND);
	}
	
	/**
	 * Gera o nome do arquivo do log.
	 * Formato:
	 *    [prefix_]YYYYMMDDHHMMSSuuuuuu.log
	 */
	private function buildFileName(?string $prefix): string {
		$timestamp = $this->generateTimestamp();
		
		if ($prefix !== null && trim($prefix) !== '') {
			return $prefix . "_" . $timestamp . '.log';
		}
		
		return $timestamp . '.log';
	}
	
	/**
	 * Gera timestamp no formato YYYYMMDDHHMMSSuuuuuu
	 */
	private function generateTimestamp(): string {
		$micro = microtime(true);
		$dt = \DateTime::createFromFormat('U.u', sprintf('%.6F', $micro));
		return $dt->format('YmdHisu');
	}
}



/* --------------------------------------------------------------
 * COMO USAR O CtrlLogs (exemplos)
 * --------------------------------------------------------------
 
 # 1. Criar a instância
 $log = new \app\utils\CtrlLogs();
 
 # 2. Log simples
 $log->log("Usuário acessou o painel");
 
 # 3. Log com prefixo (arquivo auth_YYYYMMDDHHMMSSuuuuuu.log)
 $log->log("Falha de autenticação", "auth");
 
 # 4. Log com dados estruturados (JSON)
 $log->log([
 "evento" => "insert",
 "tabela" => "usuarios",
 "ip"     => $_SERVER['REMOTE_ADDR']
 ], "db");
 
 # 5. Resultado:
 # Os arquivos serão salvos em:
 #   $_SESSION['config']['logDirectory']
 # Com nomes como:
 #   20250119185230998876.log
 #   auth_20250119185240123456.log
 #   db_20250119185250123456.log
 
 --------------------------------------------------------------- */
