<?php
namespace App\Controllers;

use App\Models\Atividade;
use App\Models\TipoDeficiencia;
use Core\Auth;
use App\Services\SessaoAtividade;
use Core\Config;
use Core\Controller;
use Core\Request;
use Core\Session;
use Core\Upload;
use Core\Validator;

/** Catálogo de atividades: busca, filtros, paginação e cadastro. */
class AtividadeController extends Controller
{
    private const CAMPOS = ['titulo', 'area', 'descricao', 'objetivo', 'materiais', 'fonte',
                            'video', 'legenda', 'transcricao', 'imagem_alt', 'formato'];

    public function index(): void
    {
        $busca     = Request::get('q');
        $tipo      = (int) Request::get('tipo');
        $area      = $this->areaValida(Request::get('area'));
        $pagina    = max(1, (int) Request::get('pagina', '1'));
        $porPagina = (int) Config::get('por_pagina', 8);

        $res = Atividade::listar($busca, $tipo, $area, $pagina, $porPagina);

        $this->view('atividades/index', [
            'titulo'    => 'Atividades',
            'itens'     => $res['itens'],
            'total'     => $res['total'],
            'busca'     => $busca,
            'tipo'      => $tipo,
            'area'      => $area,
            'pagina'    => $pagina,
            'porPagina' => $porPagina,
            'tipos'     => TipoDeficiencia::listar(),
        ]);
    }

    public function mostrar(string $id): void
    {
        $atividade = Atividade::buscar((int) $id);
        if (!$atividade) {
            $this->erro(404, 'Atividade não encontrada.');
        }
        $this->view('atividades/mostrar', [
            'titulo'        => $atividade['titulo'],
            'atividade'     => $atividade,
            'tiposAtividade' => Atividade::tipos((int) $id),
            'podeEditar'    => $this->podeEditar($atividade),
        ]);
    }

    public function criar(): void
    {
        $this->view('atividades/form', [
            'titulo'            => 'Nova atividade',
            'atividade'         => null,
            'tipos'             => TipoDeficiencia::listar(),
            'tiposSelecionados' => [],
        ]);
    }

    public function salvar(): void
    {
        $dados = Request::only(self::CAMPOS);
        $dados['tipos'] = $this->tiposEnviados();
        $erros = $this->validar($dados);

        $nova = null;
        if (Upload::enviado('imagem')) {
            [$nova, $erroUpload] = Upload::imagem('imagem');
            if ($erroUpload) {
                $erros['imagem'] = $erroUpload;
            } elseif ($dados['imagem_alt'] === '') {
                $erros['imagem_alt'] = 'Descreva a imagem enviada (texto alternativo para leitores de tela).';
            }
        }

        if ($erros) {
            Upload::apagar($nova);   // não deixa arquivo órfão
            $this->voltarComErros('atividades/nova', $dados, $erros);
        }

        $dados['imagem'] = $nova;
        if ($nova === null) {
            $dados['imagem_alt'] = '';
        }
        $id = Atividade::criar($dados, Auth::id());

        Session::flash('sucesso', 'Atividade cadastrada com sucesso.');
        redirect('atividades/' . $id);
    }

    public function editar(string $id): void
    {
        $atividade = $this->carregarEditavel((int) $id);
        $this->view('atividades/form', [
            'titulo'            => 'Editar atividade',
            'atividade'         => $atividade,
            'tipos'             => TipoDeficiencia::listar(),
            'tiposSelecionados' => array_column(Atividade::tipos((int) $id), 'id'),
        ]);
    }

    public function atualizar(string $id): void
    {
        $id        = (int) $id;
        $atividade = $this->carregarEditavel($id);

        $dados   = Request::only(self::CAMPOS);
        $dados['tipos'] = $this->tiposEnviados();
        $erros   = $this->validar($dados);
        $nova    = null;
        $remover = Request::str('remover_imagem') === '1';

        if (Upload::enviado('imagem')) {
            [$nova, $erroUpload] = Upload::imagem('imagem');
            if ($erroUpload) {
                $erros['imagem'] = $erroUpload;
            }
        }

        $ficaComImagem = $nova !== null || (!empty($atividade['imagem']) && !$remover);
        if ($ficaComImagem && $dados['imagem_alt'] === '' && !isset($erros['imagem_alt'])) {
            $erros['imagem_alt'] = 'Descreva a imagem (texto alternativo para leitores de tela).';
        }

        if ($erros) {
            Upload::apagar($nova);
            $this->voltarComErros("atividades/$id/editar", $dados, $erros);
        }

        $imagem = $atividade['imagem'];
        if ($nova !== null) {
            Upload::apagar($atividade['imagem']);
            $imagem = $nova;
        } elseif ($remover) {
            Upload::apagar($atividade['imagem']);
            $imagem = null;
        }
        $dados['imagem'] = $imagem;
        if ($imagem === null) {
            $dados['imagem_alt'] = '';
        }

        Atividade::atualizar($id, $dados);
        Session::flash('sucesso', 'Atividade atualizada com sucesso.');
        redirect('atividades/' . $id);
    }

    public function excluir(string $id): void
    {
        $id        = (int) $id;
        $atividade = $this->carregarEditavel($id);

        Upload::apagar($atividade['imagem']);
        Atividade::excluir($id);   // registros de acompanhamento ligados a ela ficam sem atividade

        Session::flash('sucesso', 'Atividade excluída.');
        redirect('atividades');
    }

    // ------------------------------------------------------------------

    /** Ids dos tipos marcados no formulário (ignora valores que não são números). */
    private function tiposEnviados(): array
    {
        $bruto = $_POST['tipos'] ?? [];
        if (!is_array($bruto)) {
            return [];
        }
        $ids = [];
        foreach ($bruto as $valor) {
            $n = filter_var($valor, FILTER_VALIDATE_INT);
            if ($n !== false && $n > 0) {
                $ids[] = $n;
            }
        }
        return array_values(array_unique($ids));
    }

    /** O administrador edita qualquer atividade; o professor, só as que ele cadastrou. */
    private function podeEditar(array $atividade): bool
    {
        return Auth::isAdmin() || (int) ($atividade['usuario_id'] ?? 0) === Auth::id();
    }

    private function carregarEditavel(int $id): array
    {
        $atividade = Atividade::buscar($id);
        if (!$atividade) {
            $this->erro(404, 'Atividade não encontrada.');
        }
        if (!$this->podeEditar($atividade)) {
            $this->erro(403, 'Você só pode alterar as atividades que você mesmo cadastrou.');
        }
        return $atividade;
    }

    /** Aceita só uma área existente na configuração; qualquer outro valor vira "todas". */
    private function areaValida(string $valor): string
    {
        return array_key_exists($valor, Config::get('areas')) ? $valor : '';
    }

    private function validar(array $dados): array
    {
        $regraVideo = function ($valor) {
            $ok = preg_match('#^(https?://\S+|assets/video/[A-Za-z0-9_.\-]+\.(mp4|webm))$#', $valor);
            return $ok ? null : 'Use um link (http ou https) ou um caminho como assets/video/arquivo.mp4.';
        };
        $regraLegenda = function ($valor) {
            $ok = preg_match('#^assets/video/[A-Za-z0-9_.\-]+\.vtt$#', $valor);
            return $ok ? null : 'Use um caminho como assets/video/legenda.vtt.';
        };

        $v = Validator::make(
            $dados,
            [
                'titulo'      => 'required|min:3|max:100',
                'area'        => 'required|in:' . implode(',', array_keys(Config::get('areas'))),
                'descricao'   => 'required|min:10|max:5000',
                'objetivo'    => 'max:255',
                'materiais'   => 'max:255',
                'fonte'       => 'required|min:3|max:255',
                'video'       => ['max:255', $regraVideo],
                'legenda'     => ['max:255', $regraLegenda],
                'transcricao' => 'max:5000',
                'imagem_alt'  => 'max:200',
                'formato'     =>'required|in:' . implode(',', SessaoAtividade::FORMATOS),
            ],
            [
                'titulo'      => 'Título',
                'area'        => 'Área de habilidade',
                'descricao'   => 'Descrição',
                'objetivo'    => 'Objetivo',
                'materiais'   => 'Materiais',
                'fonte'       => 'Fonte da atividade',
                'video'       => 'Vídeo',
                'legenda'     => 'Legenda',
                'transcricao' => 'Transcrição',
                'imagem_alt'  => 'Texto alternativo da imagem',
                'formato'     => 'Mini game na tela',
            ]
        );
        $erros = $v->errors();

        // Pelo menos um tipo, e todos os tipos enviados precisam existir
        if (empty($dados['tipos'])) {
            $erros['tipos'] = 'Escolha pelo menos um tipo de deficiência.';
        } else {
            foreach ($dados['tipos'] as $tipoId) {
                if (!TipoDeficiencia::buscar((int) $tipoId)) {
                    $erros['tipos'] = 'Tipo de deficiência inválido.';
                    break;
                }
            }
        }
        return $erros;
    }
}
