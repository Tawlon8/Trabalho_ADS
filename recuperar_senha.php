<?php
// === CONFIG DB ===
$host = "localhost";
$user = "root";
$pass = "";
$db   = "banco_acessibilidade";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// === CONFIG GMAIL ===
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$gmailUser = "tawlonrose@gmail.com"; // Gmail
$gmailPass = "irskvvyjnbrtpfyn";        // Senha de app gerada no Gmail

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Verificar se e-mail existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        // Criar token e data de expiração
        $token = bin2hex(random_bytes(50));
        $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

        $stmt = $conn->prepare("UPDATE usuarios SET token_recuperacao = ?, token_expira = ? WHERE email = ?");
        $stmt->bind_param("sss", $token, $expira, $email);
        $stmt->execute();

        // Obter IP local automaticamente
        $ip_local = $_SERVER['HTTP_HOST']; 

        // Criar link correto
        $link = "http://{$ip_local}/GitHub/Trabalho_ADS/reset_senha.php?token=" . $token;

        // Enviar e-mail
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $gmailUser;
            $mail->Password = $gmailPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom($gmailUser, 'Recuperação de Senha');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Recupere sua senha';
            $mail->Body    = "Clique no link para redefinir sua senha: <a href='$link'>$link</a>";

            $mail->send();
            echo "E-mail enviado com sucesso! Verifique sua caixa de entrada.";
        } catch (Exception $e) {
            echo "Erro ao enviar o e-mail: {$mail->ErrorInfo}";
        }
    } else {
        echo "E-mail não encontrado.";
    }
}
?>
