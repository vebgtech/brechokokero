<?php

declare(strict_types=1);

namespace app\core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class SenderMail {
	
	private PHPMailer $mailer;
	private array $config;
	
	public function __construct() {
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}
		
		if (!isset($_SESSION['config']['smtp']) || !is_array($_SESSION['config']['smtp'])) {
			throw new \Exception("Configuração SMTP não encontrada em \$_SESSION['config']['smtp']");
		}
		
		$this->config = $_SESSION['config']['smtp'];
		$this->mailer = new PHPMailer(true);
		$this->setup();
	}
	
	private function setup(): void {
		$smtp = $this->config;
		
		// Configurações do SMTP
		$this->mailer->isSMTP();
		$this->mailer->Host     = $smtp['host'] ?? '';
		$this->mailer->Port     = $smtp['port'] ?? 587;
		$this->mailer->SMTPAuth = true;
		
		// Compatível com o JSON: username / password
		$this->mailer->Username = $smtp['username'] ?? '';
		$this->mailer->Password = $smtp['password'] ?? '';
		
		// Criptografia: usa o valor do JSON ou TLS como padrão
		$encryption = $smtp['encryption'] ?? 'tls';
		$this->mailer->SMTPSecure = $encryption;
		
		// Remetente padrão
		$fromEmail = $smtp['from_email'] ?? $smtp['username'] ?? '';
		$fromName  = $smtp['from_name']  ?? 'Sistema';
		
		$this->mailer->setFrom($fromEmail, $fromName);
		
		// Formato HTML
		$this->mailer->isHTML(true);
		$this->mailer->CharSet = 'UTF-8';
	}
	
	public function setFrom(string $email, ?string $name = null): void {
		$this->mailer->setFrom($email, $name ?? '');
	}
	
	public function addAttachment(string $filePath, string $fileName = ''): void {
		$this->mailer->addAttachment($filePath, $fileName ?: '');
	}
	
	public function send(string $to, string $subject, string $body): bool {
		try {
			$this->mailer->clearAddresses();
			$this->mailer->addAddress($to);
			
			$this->mailer->Subject = $subject;
			$this->mailer->Body    = $body;
			
			return $this->mailer->send();
		} catch (Exception $e) {
			throw new \Exception("Erro ao enviar e-mail: " . $e->getMessage());
		}
	}
}

