<?php
namespace Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

/** Envio de e-mails via SMTP (PHPMailer). Falhas são só registradas no log. */
class Mailer
{
    public static function enviarRecuperacao(string $para, string $link, int $minutos): bool
    {
        $cfg      = Config::get('mail');
        $autoload = BASE_DIR . '/vendor/autoload.php';
        if (!is_file($autoload) || $cfg['user'] === '' || $cfg['pass'] === '') {
            error_log('E-mail não enviado: rode "composer install" e configure config/config.local.php.');
            return false;
        }
        require_once $autoload;
        if (!class_exists(PHPMailer::class)) {
            error_log('E-mail não enviado: PHPMailer não está instalado. Rode "composer install" na pasta do projeto.');
            return false;
        }

        $m = new PHPMailer(true);
        try {
            $m->isSMTP();
            $m->Host       = $cfg['host'];
            $m->SMTPAuth   = true;
            $m->Username   = $cfg['user'];
            $m->Password   = $cfg['pass'];
            $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $m->Port       = (int) $cfg['port'];
            $m->CharSet    = 'UTF-8';

            $m->setFrom($cfg['user'], $cfg['from_name']);
            $m->addAddress($para);
            $m->isHTML(true);
            $m->Subject = 'Recupere sua senha - UNIA';

            $linkHtml   = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
            $m->Body    = '<p>Recebemos um pedido para redefinir a sua senha.</p>'
                        . '<p><a href="' . $linkHtml . '">Clique aqui para criar uma nova senha</a></p>'
                        . "<p>O link vale por $minutos minutos. Se você não fez esse pedido, ignore este e-mail.</p>";
            $m->AltBody = "Para redefinir sua senha, acesse: $link (válido por $minutos minutos). "
                        . 'Se você não fez esse pedido, ignore este e-mail.';
            $m->send();
            return true;
        } catch (MailException $e) {
            error_log('Falha ao enviar e-mail: ' . $m->ErrorInfo);
            return false;
        }
    }
}
