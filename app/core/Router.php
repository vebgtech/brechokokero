<?php
declare(strict_types=1);

/**
 * ============================================================================
 *  ZaitTiny Framework - Router
 *  ----------------------------------------------------------------------------
 *  Arquivo....: app/utils/Router.php
 *  Autor......: Prof. Dr. Cleber S. Oliveira (arquitetura)
 *  Propósito..: Interpretar a URI e decidir qual arquivo servir (view/estático),
 *				 aplicando regras de sessão/permissão e sanitização conforme
 *				 já definidas em $_SESSION['config'] e $_SESSION['route'].
 *  Uso........:
 *		 // index.php
 *		 if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
 *		 require __DIR__ . '/app/utils/Router.php';
 *		 $uri = $_GET['uri'] ?? '/';
 *		 (new Router())->get($uri);
 *  Dependências:
 *		 - $_SESSION['config'] = conf carregada (ex.: app/etc/conf.js)
 *		 - $_SESSION['route']  = rotas carregadas (ex.: app/etc/routes.js)
 *  Observações:
 *		 - Serve "public/index.php" quando URI é "/"
 *		 - Serve arquivos sob "public/" diretamente (com MIME correto)
 *		 - Suporta rotas com múltiplos segmentos (ex.: "usuario/add")
 *		 - Protege contra path traversal ao resolver caminhos
 *		 - Sanitiza GET/POST/COOKIE/REQUEST e cria $GLOBALS['_PUT'] / $GLOBALS['_DELETE']
 *  Licença....: Didático / acadêmico
 * ============================================================================
 */

namespace app\core;

class Router{
	
	private array $conf;	/** @var array config carregada de $_SESSION['config'] */
	private array $routes;	/** @var array rotas carregadas de $_SESSION['route'] */
	private string $rootDir;/** @var string diretório raiz do projeto */
	
	public function __construct() {
		if (session_status() !== PHP_SESSION_ACTIVE) {
			@session_start();
		}
		$this->conf   = $_SESSION['config'] ?? [];
		$this->routes = $_SESSION['route']  ?? [];
		$baseDir = $this->conf['path']['baseDir'] ?? null;
		$this->rootDir = ($baseDir && is_dir($baseDir)) ? rtrim($baseDir, '/') : dirname(__DIR__, 2);
	}
	
	public function get(string $uri): void {
		$uri = $this->normalizeUri($uri);
		if ($uri === '' || $uri === '/') {
    	header('Location: home');

			return;
		}
		if (str_starts_with(ltrim($uri, '/'), 'public/')) {
			$this->servePath(ltrim($uri, '/'));
			return;
		}
		
		$routeName = $this->uriToRouteName($uri);
		
		$pathMap = $this->conf['pathMap'] ?? [];
		if (isset($pathMap[$uri])) {
			$routeName = $pathMap[$uri];
		}
		if (!array_key_exists($routeName, $this->routes)) {
			$this->abort404();
			return;
		}
		$route = $this->routes[$routeName];
		
		$this->doSanitize($route['sanitize'] ?? []);
		
		if (!$this->authorize($route['sessionKey'] ?? [])) {
			$this->servePath($route['errorPath'] ?? 'app/view/error.php?code=403', 403);
			return;
		}
		$this->servePath($route['path'] ?? 'app/view/error.php?code=404');
	}
	
	private function normalizeUri(string $uri): string {
		if (false !== ($q = strpos($uri, '?'))) {
			$uri = substr($uri, 0, $q);
		}
		$uri = urldecode($uri);
		$uri = '/' . trim($uri, '/');
		$uri = preg_replace('#/+#', '/', $uri) ?? '/';
		return $uri === '/' ? '/' : $uri;
	}
	
	private function uriToRouteName(string $uri): string {
		if ($uri === '/' || $uri === ''){
			return 'home';
		}
		return ltrim($uri, '/');
	}
	
	private function doSanitize(array $sanitize): void {
		
		if (empty($sanitize['requestVars'])) {
			return;
		}
		$_GET     = filter_input_array(INPUT_GET,     FILTER_SANITIZE_SPECIAL_CHARS) ?: $_GET;
		$_POST    = filter_input_array(INPUT_POST,    FILTER_SANITIZE_SPECIAL_CHARS) ?: $_POST;
		$_COOKIE  = filter_input_array(INPUT_COOKIE,  FILTER_SANITIZE_SPECIAL_CHARS) ?: $_COOKIE;
		$_REQUEST = array_merge($_GET, $_POST, $_COOKIE);
		
		$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		if ($method === 'PUT' || $method === 'DELETE') {
			$rawBody = file_get_contents('php://input') ?: '';
			$ct      = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
			
			$parsed = [];
			if (stripos($ct, 'application/json') !== false) {
				$json = json_decode($rawBody, true);
				if (is_array($json)) $parsed = $json;
			} else {
				parse_str($rawBody, $parsed);
			}	
			$sanitized = is_array($parsed) ? filter_var_array($parsed, FILTER_SANITIZE_SPECIAL_CHARS) : [];
			
			if (!is_array($sanitized)){
				$sanitized = $parsed;
			}
			
			if ($method === 'PUT') {
				$GLOBALS['_PUT'] = $sanitized;
			} else {
				$GLOBALS['_DELETE'] = $sanitized;
			}
		}
		
		if (!empty($sanitize['code'])) {
		
		}
		if (!empty($sanitize['sql'])) {
			// Está descontinuado nessa versão pois já está sendo tratado no DBQuery
		}
	}
	
	/**
	 * Autoriza o acesso com base em $_SESSION e nas regras de sessionKey.
	 * Aceita:
	 *  - { "chave": true } → precisa existir e ser truthy
	 *  - { "chave": false } → precisa estar vazia ou ausente
	 *  - { "chave": "valor" } → precisa ser igual (não estrito)
	 *  - { "chave": ["v1","v2","v3"] } → precisa ser um desses valores
	 */
	private function authorize(array $sessionKeyRules): bool
	{
		if (empty($sessionKeyRules)) return true;
		
		foreach ($sessionKeyRules as $rule) {
			if (!is_array($rule)) continue;
			foreach ($rule as $key => $expected) {
				$val = $_SESSION[$key] ?? null;
				
				if ($expected === true) {
					if (empty($val)) return false;
					continue;
				}
				
				if ($expected === false) {
					if (!empty($val)) return false;
					continue;
				}
				
				if (is_array($expected)) {
					if (!in_array($val, $expected, false)) {
						return false;
					}
					continue;
				}
				
				if ($val != $expected) {
					return false;
				}
			}
		}
		
		return true;
	}
	

	private function servePath(string $relative, int $statusIfMissing = 404): void {
		$file = $this->secureRealpath($relative);
		if (!$file || !is_file($file)) {
			http_response_code($statusIfMissing);
			$this->serveFallback404();
			exit;
		}
		$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
		$textual = ['php','html','htm','css','js','json','xml','txt','base64','csv'];
		if (in_array($ext, $textual, true)) {
			require $file; 
		} else {
			$mime = @mime_content_type($file) ?: $this->fallbackMime($ext);
			header('X-Content-Type-Options: nosniff');
			header('Cache-Control: public, max-age=86400');
			header("Content-Type: {$mime}");
			header('Content-Length: ' . filesize($file));
			readfile($file);
		}
		exit;
	}
	
	private function serveFallback404(): void {
		$p404 = $this->secureRealpath('app/view/error.php');
		if ($p404 && is_file($p404)) {
			require $p404;
		} else {
			(new ControllerHandler())->jsonEcho(['Error'=>'404 - Not Found']);
		}
	}
	
	private function abort404(): void {
		http_response_code(404);
		$this->serveFallback404();
	}
	
	private function secureRealpath(string $relative): ?string {
		$candidate = $this->rootDir . '/' . ltrim($relative, '/');
		$real = realpath($candidate);
		if ($real === false) return null;
		
		$root  = rtrim(realpath($this->rootDir) ?: $this->rootDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		$realN = rtrim($real, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		
		if (str_starts_with($realN, $root)) {
			return rtrim($real, DIRECTORY_SEPARATOR);
		}
		return null;
	}
	
	private function fallbackMime(string $ext): string {
		return match ($ext) {
			'png'        => 'image/png',
			'jpg', 'jpeg'=> 'image/jpeg',
			'gif'        => 'image/gif',
			'svg'        => 'image/svg+xml',
			'webp'       => 'image/webp',
			'ico'        => 'image/x-icon',
			'mp4'        => 'video/mp4',
			'mp3'        => 'audio/mpeg',
			'wav'        => 'audio/wav',
			'pdf'        => 'application/pdf',
			'woff'       => 'font/woff',
			'woff2'      => 'font/woff2',
			'ttf'        => 'font/ttf',
			'eot'        => 'application/vnd.ms-fontobject',
			'otf'        => 'font/otf',
			'zip'        => 'application/zip',
			default      => 'application/octet-stream',
		};
	}
}
?>