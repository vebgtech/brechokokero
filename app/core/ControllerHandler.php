<?php
declare ( strict_types = 1 )
	;

namespace app\core;

class ControllerHandler {
	private string 	$method;
	private array 	$parameters = [ ];
	private array 	$headers = [ ];
	private bool 	$corsPass = false;
	private bool 	$pretty = true;
	public function __construct() {
		if (session_status () === PHP_SESSION_NONE && ! headers_sent ()) {
			session_start ();
		}

		// Captura de cabeçalhos HTTP
		$this->headers = self::gatherHeaders ( $_SERVER );

		// Método da requisição
		$requestedMethod 	= strtolower ( $_SERVER ['REQUEST_METHOD'] ?? 'get');
		$overrideHeader 	= strtolower ( $this->headers ['x-http-method-override'] ?? '');

		$this->method = in_array ( $overrideHeader, [ 
				'get',
				'post',
				'put',
				'delete',
				'patch',
				'options'
		], true ) ? $overrideHeader : $requestedMethod;

		// Parâmetros de entrada processados
		$this->parameters = self::parseInput ( $this->headers, $this->method );
	}

	/*
	 * =========================================================================
	 * DISPATCHER PRINCIPAL
	 * =========================================================================
	 */
	public function dispatch(): void {
		$currentMethod = strtolower ( $this->getMethod () );

		switch ($currentMethod) {
			case 'get' :
				$this->doGet ();
				break;
			case 'post' :
				$this->doPost ();
				break;
			case 'put' :
				$this->doPut ();
				break;
			case 'delete' :
				$this->doDelete ();
				break;
			case 'patch' :
				$this->doPatch ();
				break;

			case 'options' :
				$this->sendCorsHeaders ();
				http_response_code ( 204 );
				exit ();

			default :
				$this->methodNotAllowed ( $currentMethod );
		}
	}

	/*
	 * =========================================================================
	 * MÉTODOS A SEREM SOBRESCRITOS NOS CONTROLLERS FILHOS
	 * =========================================================================
	 */
	protected function doGet(): void {
		$this->methodNotAllowed ( 'GET' );
	}
	protected function doPost(): void {
		$this->methodNotAllowed ( 'POST' );
	}
	protected function doPut(): void {
		$this->methodNotAllowed ( 'PUT' );
	}
	protected function doDelete(): void {
		$this->methodNotAllowed ( 'DELETE' );
	}
	protected function doPatch(): void {
		$this->methodNotAllowed ( 'PATCH' );
	}
	protected function methodNotAllowed(string $methodName): void {
		$this->jsonEcho ( [ 
				'error' => 'METHOD_NOT_ALLOWED',
				'message' => "Este endpoint não aceita o método HTTP: {$methodName}."
		], 405 );
	}

	/*
	 * =========================================================================
	 * GETTERS / SETTERS
	 * =========================================================================
	 */
	public function getMethod(): string {
		return $this->method;
	}
	public function setMethod(string $method): void {
		$this->method = strtolower ( $method );
	}

	/* ---------- COOKIES ---------- */
	public function getCookies(?string $cookieName = null): mixed {
		return $cookieName !== null ? ($_COOKIE [$cookieName] ?? null) : $_COOKIE;
	}
	public function setCookie(string $cookieName, string $cookieValue, array $cookieOptions = null): bool {
		$cookieOptions = $cookieOptions ?? [ 
				'expires' => 60 * 60 * 24 * 30, // 30 dias
				'path' => '/',
				'httponly' => true,
				'samesite' => 'Lax'
		];
		$expiry = $cookieOptions ['expires'] ?? 0;
		if (is_int ( $expiry ) && $expiry > 0) {
			$cookieOptions ['expires'] = time () + $expiry;
		} else {
			unset ( $cookieOptions ['expires'] );
		}
		return setcookie ( $cookieName, $cookieValue, $cookieOptions );
	}
	public function getSession(?string $key = null): mixed {
		return $key !== null ? ($_SESSION [$key] ?? null) : $_SESSION;
	}
	public function setSession(string $key, mixed $value): void {
		$_SESSION [$key] = $value;
	}
	public function unsetSession(string $key): void {
		unset ( $_SESSION [$key] );
	}

	/* ---------- HEADERS ---------- */
	public function getHeader(string $headerName, ?string $default = null): ?string {
		$normalizedKey = strtolower ( $headerName );

		return $this->headers [$normalizedKey] ?? $default;
	}
	public function getHeaders(): array {
		return $this->headers;
	}

	/* ---------- PARÂMETROS ---------- */
	public function getParameters(): array {
		return $this->parameters;
	}
	public function getParameter(?string $parameterName = null, mixed $defaultValue = null): mixed {
		if ($parameterName === null) {
			return $this->parameters;
		}
		return $this->parameters [$parameterName] ?? $_GET [$parameterName] ?? $defaultValue;
	}

	public function getParam(?string $key = null, mixed $defaultValue = null): mixed {
		return $this->getParameter ( $key, $defaultValue );
	}
	public function setParameter(string $parameterName, mixed $parameterValue): void {
		$this->parameters [$parameterName] = $parameterValue;
	}
	public function getData(): array {
		return $this->parameters;
	}
	public function setData(array $data): void {
		$this->parameters = $data;
	}
	public function jsonEcho(array|object $data, int $statusCode = 200, bool $prettyPrint = true): never {
		while ( ob_get_level () > 0 ) {
			@ob_end_clean ();
		}
		header ( 'Content-Type: application/json; charset=utf-8' );
		$this->sendCorsHeaders ();
		http_response_code ( $statusCode );
		$flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
		if ($prettyPrint) {
			$flags |= JSON_PRETTY_PRINT;
		}
		try {
			echo json_encode ( $data, $flags | JSON_THROW_ON_ERROR );
		} catch ( \Throwable $e ) {
			http_response_code ( 500 );
			echo json_encode ( [ 
				'error' => 'JSON_ENCODE_ERROR',
				'message' => $e->getMessage ()
			], $flags );
		}
		exit ();
	}
	public function enableCors(bool $enabled = true): void {
		$this->corsPass = $enabled;
	}
	public function isCorsEnabled(): bool {
		return $this->corsPass;
	}
	protected function sendCorsHeaders(): void {
		if (! $this->corsPass){
			return;
		}
		header ( 'Access-Control-Allow-Origin: *' );
		header ( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS' );
		header ( 'Access-Control-Allow-Headers: Content-Type, Authorization, X-HTTP-Method-Override' );
		header ( 'Access-Control-Max-Age: 86400' );
	}

	private static function parseInput(array $headers, string $httpMethod): array {
		$methodLower 	= strtolower ( $httpMethod );
		$contentType 	= strtolower ( $headers ['content-type'] ?? '');
		$rawInput 		= file_get_contents ( 'php://input' ) ?: '';
		if (str_starts_with ( $contentType, 'application/json' )) {
			if (trim ( $rawInput ) === '') {
				return [ ];
			}
			try {
				$decodedJson = json_decode ( $rawInput, true, 512, JSON_THROW_ON_ERROR );
				return is_array ( $decodedJson ) ? $decodedJson : [ ];
			} catch ( \Throwable ) {
				return [ ];
			}
		}
		switch ($methodLower) {
			case 'get' :	return $_GET ?? [ ];
			case 'post' :	return $_POST ?? [ ];
			case 'put' :
			case 'delete' :
			case 'patch' :
				$parsedBody = [];
				if ($rawInput !== '' && str_starts_with ( $contentType, 'application/x-www-form-urlencoded' )) {
					parse_str ( $rawInput, $parsedBody );
				}
				return $parsedBody;
			default :	return [ ];
		}
	}

	private static function gatherHeaders(array $serverEnv): array {
		$normalizedHeaders = [];
		foreach ( $serverEnv as $serverKey => $serverValue ) {
			if (str_starts_with ( $serverKey, 'HTTP_' )) {
				$headerName = strtolower ( str_replace ( '_', '-', substr ( $serverKey, 5 ) ) );
				$normalizedHeaders [$headerName] = ( string ) $serverValue;
			} elseif (in_array ( $serverKey, [ 'CONTENT_TYPE','CONTENT_LENGTH','CONTENT_MD5' ], true )) {
				$headerName = strtolower ( str_replace ( '_', '-', $serverKey ) );
				$normalizedHeaders [$headerName] = ( string ) $serverValue;
			}
		}
		if (function_exists ( 'getallheaders' )) {
			foreach ( ( array ) getallheaders () as $name => $value ) {
				$normalizedHeaders [strtolower ( ( string ) $name )] = ( string ) $value;
			}
		}
		return $normalizedHeaders;
	}
}
