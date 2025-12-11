<?php
/**
 * Classe SenderSMS
 * Autor........: Prof. Dr. Cleber S. Oliveira
 * Descrição....: Envia mensagens SMS via Twilio utilizando as credenciais
 *                armazenadas em $_SESSION['config']['sms'].
 *
 * -----------------------------------------------------------------------------
 * REQUISITOS:
 * -----------------------------------------------------------------------------
 * PHP 8.0 ou superior
 * Conta ativa no Twilio (https://www.twilio.com/)
 * Composer instalado no sistema
 * SDK oficial do Twilio instalado via Composer
 *
 * -----------------------------------------------------------------------------
 * COMO PEGAR O SDK DO TWILIO PELO COMPOSER
 * -----------------------------------------------------------------------------
 * 1 Verifique se o Composer está instalado:
 *     $ composer --version
 *
 *     ➜ Caso não esteja instalado, baixe e instale em:
 *        https://getcomposer.org/download/
 *
 * 2 No terminal, dentro da pasta raiz do seu projeto (onde está o index.php),
 *     execute o comando:
 *        $ composer require twilio/sdk
 *
 *     Isso fará o Composer:
 *        - Baixar o SDK oficial do Twilio do repositório Packagist;
 *        - Criar ou atualizar o arquivo composer.json;
 *        - Gerar a pasta /vendor com o autoloader PHP.
 *
 * 3 Inclua o autoloader no seu index.php principal:
 *        require __DIR__ . '/vendor/autoload.php';
 *
 * 4 Utilize o namespace do cliente Twilio no código:
 *        use Twilio\Rest\Client;
 *
 * 5 Estrutura esperada do projeto após instalação:
 *        projeto/
 *        ├─ app/
 *        │   └─ core/
 *        │       └─ SenderSMS.php
 *        ├─ vendor/
 *        │   ├─ autoload.php
 *        │   └─ twilio/
 *        │       └─ sdk/
 *        ├─ composer.json
 *        ├─ composer.lock
 *        └─ testeSMS.php
 *
 * Exemplo de uso:
 *     $sms = new SenderSMS();
 *     $sms->send('+5511900000000', 'Mensagem de teste via Twilio');
 */

declare(strict_types=1);

namespace app\core;

use Twilio\Rest\Client;

final class SenderSMS
{
	private Client $twilio;
	private ?string $from = null;
	private ?string $svc  = null;
	private string $defaultCountryCode = '+55'; // fallback
	
	public function __construct()
	{
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}
		
		$cfg = $_SESSION['config']['sms'] ?? null;
		if (!is_array($cfg)) {
			throw new \RuntimeException(
					'As configurações de SMS não foram encontradas em $_SESSION["config"]["sms"].'
					);
		}
		
		// API suportada
		$api = $cfg['api'] ?? null;
		if ($api !== 'twilio') {
			throw new \RuntimeException('O sistema SMS atual suporta apenas api="twilio".');
		}
		
		// Lê credenciais
		$sid   = (string)($cfg['account_sid'] ?? '');
		$token = (string)($cfg['auth_token']  ?? '');
		$this->from = isset($cfg['from']) ? (string)$cfg['from'] : null;
		$this->svc  = isset($cfg['messaging_service_sid']) ? (string)$cfg['messaging_service_sid'] : null;
		
		if ($sid === '' || $token === '' || ($this->from === null && $this->svc === null)) {
			throw new \InvalidArgumentException(
					'Configuração Twilio inválida: é necessário account_sid, auth_token e (from ou messaging_service_sid).'
					);
		}
		
		// DDI padrão (opcional no config)
		$dcc = (string)($cfg['default_country_code'] ?? '+55');
		$dcc = trim($dcc);
		if ($dcc !== '') {
			// normaliza para formato +NN...
			$dcc = ltrim($dcc, '+');
			if (!ctype_digit($dcc)) {
				throw new \InvalidArgumentException('default_country_code inválido. Use algo como "+55" ou "55".');
			}
			$this->defaultCountryCode = '+' . $dcc;
		}
		
		// Cliente Twilio (Composer autoload deve ter sido carregado no index.php)
		$this->twilio = new Client($sid, $token);
	}
	
	/**
	 * Envia um SMS.
	 * $to pode vir como:
	 *  - E.164 (+5511999999999) → mantido
	 *  - Apenas dígitos BR (11999999999) → prefixa default_country_code (ex.: +55)
	 */
	public function send(string $to, string $message): array
	{
		$to = $this->normalizeToE164($to);
		
		// Mensagem não pode ser vazia
		if (trim($message) === '') {
			return [
					'status' => 400,
					'error'  => 'Mensagem vazia. Informe o conteúdo do SMS.'
			];
		}
		
		$params = ['body' => $message];
		if ($this->svc) {
			$params['messagingServiceSid'] = $this->svc;
		} else {
			$params['from'] = $this->from;
		}
		
		try {
			$msg = $this->twilio->messages->create($to, $params);
			return [
					'status' => 200,
					'sid'    => $msg->sid,
					'state'  => $msg->status,
					'body'   => 'SMS enviado com sucesso.'
			];
		} catch (\Throwable $e) {
			return [
					'status'       => 500,
					'error'        => 'Falha ao enviar SMS: ' . $e->getMessage(),
					'twilio_code'  => (int)$e->getCode(),  // pode ajudar no suporte
			];
		}
	}
	
	private function normalizeToE164(string $number): string
	{
		$n = preg_replace('/\s+/', '', $number);
		
		// Já está em E.164
		if (str_starts_with($n, '+')) {
			return $n;
		}
		
		// Mantém apenas dígitos
		$digits = preg_replace('/\D+/', '', $n) ?? '';
		if ($digits === '') {
			throw new \InvalidArgumentException('Número de destino inválido.');
		}
		
		// Prefixa DDI padrão se faltar
		$ccDigits = ltrim($this->defaultCountryCode, '+'); // ex.: 55
		if (!str_starts_with($digits, $ccDigits)) {
			$digits = $ccDigits . $digits;
		}
		
		return '+' . $digits;
	}
}
