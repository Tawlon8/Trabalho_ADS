<?php
namespace Core;

use Closure;
use DateTime;

/**
 * Validação no servidor. Regras: required, accepted, min, max, email, int, in, date,
 * past_or_today, confirmed, unique, exists ou função anônima. Campo vazio só falha em required/accepted.
 */
class Validator
{
    private $dados;
    private $regras;
    private $labels;
    private $erros = [];

    public static function make(array $dados, array $regras, array $labels = []): self
    {
        $v         = new self();
        $v->dados  = $dados;
        $v->regras = $regras;
        $v->labels = $labels;
        $v->validar();
        return $v;
    }

    public function fails(): bool
    {
        return !empty($this->erros);
    }

    /** @return array campo => primeira mensagem de erro */
    public function errors(): array
    {
        return $this->erros;
    }

    private function validar(): void
    {
        foreach ($this->regras as $campo => $lista) {
            if (is_string($lista)) {
                $lista = explode('|', $lista);
            }
            $valor = $this->dados[$campo] ?? '';
            $label = $this->labels[$campo] ?? $campo;

            foreach ($lista as $regra) {
                if ($regra instanceof Closure) {
                    $msg = ($valor === '' || $valor === null) ? null : $regra($valor, $this->dados);
                } else {
                    $msg = $this->aplicar((string) $regra, $campo, $valor, $label);
                }
                if ($msg !== null) {
                    $this->erros[$campo] = $msg;
                    break;   // mostra só o primeiro erro de cada campo
                }
            }
        }
    }

    private function aplicar(string $regra, string $campo, $valor, string $label): ?string
    {
        $partes = explode(':', $regra, 2);
        $nome   = $partes[0];
        $arg    = $partes[1] ?? '';
        $vazio  = ($valor === '' || $valor === null);

        if ($nome === 'required') {
            return $vazio ? "O campo $label é obrigatório." : null;
        }
        if ($nome === 'accepted') {
            return in_array($valor, ['1', 'on', 'true', 'sim'], true) ? null : "É necessário aceitar: $label.";
        }
        if ($vazio) {
            return null;
        }

        switch ($nome) {
            case 'min':
                return mb_strlen($valor) >= (int) $arg ? null : "O campo $label deve ter pelo menos $arg caracteres.";
            case 'max':
                return mb_strlen($valor) <= (int) $arg ? null : "O campo $label deve ter no máximo $arg caracteres.";
            case 'email':
                return filter_var($valor, FILTER_VALIDATE_EMAIL) !== false ? null : 'Informe um e-mail válido.';
            case 'int':
                return filter_var($valor, FILTER_VALIDATE_INT) !== false ? null : "O campo $label deve ser um número inteiro.";
            case 'in':
                return in_array($valor, explode(',', $arg), true) ? null : "Valor inválido para $label.";
            case 'date':
                return $this->dataValida($valor) ? null : "Informe uma data válida em $label.";
            case 'past_or_today':
                if (!$this->dataValida($valor)) {
                    return null;   // a regra "date" já acusa o erro
                }
                return $valor <= date('Y-m-d') ? null : "O campo $label não pode ser uma data futura.";
            case 'confirmed':
                return ($this->dados[$campo . '_confirm'] ?? null) === $valor ? null : "A confirmação de $label não confere.";
            case 'unique':
                return $this->unico($arg, $valor) ? null : "Este valor de $label já está em uso.";
            case 'exists':
                return $this->existe($arg, $valor) ? null : "Valor inválido para $label.";
        }
        return null;
    }

    private function dataValida(string $valor): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $valor);
        return $d !== false && $d->format('Y-m-d') === $valor;
    }

    private function identificador(string $nome): bool
    {
        return (bool) preg_match('/^[a-z_]+$/', $nome);   // nome de tabela/coluna seguro
    }

    private function unico(string $arg, $valor): bool
    {
        $p = explode(',', $arg);
        [$tabela, $coluna] = [$p[0] ?? '', $p[1] ?? ''];
        if (!$this->identificador($tabela) || !$this->identificador($coluna)) {
            return false;
        }
        $sql    = "SELECT COUNT(*) FROM `$tabela` WHERE `$coluna` = ?";
        $params = [$valor];
        if (!empty($p[2])) {
            $sql     .= ' AND id <> ?';
            $params[] = (int) $p[2];
        }
        $st = Database::pdo()->prepare($sql);
        $st->execute($params);
        return (int) $st->fetchColumn() === 0;
    }

    private function existe(string $arg, $valor): bool
    {
        $p = explode(',', $arg);
        [$tabela, $coluna] = [$p[0] ?? '', $p[1] ?? ''];
        if (!$this->identificador($tabela) || !$this->identificador($coluna)) {
            return false;
        }
        $st = Database::pdo()->prepare("SELECT COUNT(*) FROM `$tabela` WHERE `$coluna` = ?");
        $st->execute([$valor]);
        return (int) $st->fetchColumn() > 0;
    }
}
