<?php
namespace Core;

use finfo;

/** Envio seguro de imagens: confere tipo real, tamanho e gera nome aleatório. */
class Upload
{
    const TAMANHO_MAXIMO = 2097152;   // 2 MB

    public static function enviado(string $campo): bool
    {
        return isset($_FILES[$campo]) && is_array($_FILES[$campo])
            && ($_FILES[$campo]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    /** @return array [caminhoRelativo|null, mensagemDeErro|null] */
    public static function imagem(string $campo): array
    {
        $f = $_FILES[$campo];
        if ($f['error'] !== UPLOAD_ERR_OK) {
            return [null, 'Não foi possível enviar a imagem. Tente novamente.'];
        }
        if ($f['size'] > self::TAMANHO_MAXIMO) {
            return [null, 'A imagem deve ter no máximo 2 MB.'];
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($tipos[$mime]) || @getimagesize($f['tmp_name']) === false) {
            return [null, 'Envie uma imagem nos formatos JPG, PNG ou WEBP.'];
        }

        if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
            return [null, 'Não foi possível salvar a imagem no servidor.'];
        }

        $nome = bin2hex(random_bytes(12)) . '.' . $tipos[$mime];
        if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . '/' . $nome)) {
            return [null, 'Não foi possível salvar a imagem no servidor.'];
        }
        return ['uploads/' . $nome, null];
    }

    /** Apaga um arquivo enviado (só dentro de uploads/; nunca mexe em assets/). */
    public static function apagar(?string $caminho): void
    {
        if (!$caminho || strpos($caminho, 'uploads/') !== 0) {
            return;
        }
        $arquivo = UPLOAD_DIR . '/' . basename($caminho);
        if (is_file($arquivo)) {
            @unlink($arquivo);
        }
    }
}
