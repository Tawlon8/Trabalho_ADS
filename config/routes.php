<?php
/** Rotas: método, caminho, [controller, ação] e acesso (guest, auth, admin). */
use Core\Router;

$r = new Router();

// ---- Visitantes ----
$r->get('/login',                    ['AuthController', 'formLogin'],       ['guest']);
$r->post('/login',                   ['AuthController', 'login'],           ['guest']);
$r->get('/cadastro',                 ['AuthController', 'formCadastro'],    ['guest']);
$r->post('/cadastro',                ['AuthController', 'cadastro'],        ['guest']);
$r->get('/esqueci-senha',            ['SenhaController', 'formEsqueci'],    ['guest']);
$r->post('/esqueci-senha',           ['SenhaController', 'enviar'],         ['guest']);
$r->get('/redefinir-senha/{token}',  ['SenhaController', 'formRedefinir'],  ['guest']);
$r->post('/redefinir-senha/{token}', ['SenhaController', 'redefinir'],      ['guest']);

// ---- Usuário logado ----
$r->post('/logout',                  ['AuthController', 'logout'],          ['auth']);
$r->get('/',                         ['HomeController', 'index'],           ['auth']);
$r->get('/perfil',                   ['PerfilController', 'mostrar'],       ['auth']);
$r->post('/perfil',                  ['PerfilController', 'atualizar'],     ['auth']);
$r->post('/perfil/senha',            ['PerfilController', 'alterarSenha'],  ['auth']);
$r->post('/perfil/excluir',          ['PerfilController', 'excluir'],       ['auth']);

// ---- Atividades (qualquer professor cadastra; editar/excluir: autor ou administrador) ----
$r->get('/atividades',               ['AtividadeController', 'index'],      ['auth']);
$r->get('/atividades/nova',          ['AtividadeController', 'criar'],      ['auth']);
$r->post('/atividades',              ['AtividadeController', 'salvar'],     ['auth']);
$r->get('/atividades/{id}',          ['AtividadeController', 'mostrar'],    ['auth']);
$r->get('/atividades/{id}/editar',   ['AtividadeController', 'editar'],     ['auth']);
$r->post('/atividades/{id}/editar',  ['AtividadeController', 'atualizar'],  ['auth']);
$r->post('/atividades/{id}/excluir', ['AtividadeController', 'excluir'],    ['auth']);

// ---- Alunos (cada professor vê apenas os seus) ----
$r->get('/alunos',                   ['AlunoController', 'index'],          ['auth']);
$r->get('/alunos/novo',              ['AlunoController', 'criar'],          ['auth']);
$r->post('/alunos',                  ['AlunoController', 'salvar'],         ['auth']);
$r->get('/alunos/{id}',              ['AlunoController', 'mostrar'],        ['auth']);
$r->get('/alunos/{id}/editar',       ['AlunoController', 'editar'],         ['auth']);
$r->post('/alunos/{id}/editar',      ['AlunoController', 'atualizar'],      ['auth']);
$r->post('/alunos/{id}/excluir',     ['AlunoController', 'excluir'],        ['auth']);

// ---- Metas do PEI (plano de apoio do aluno) ----
$r->get('/alunos/{id}/metas/nova',   ['MetaController', 'criar'],           ['auth']);
$r->post('/alunos/{id}/metas',       ['MetaController', 'salvar'],          ['auth']);
$r->get('/metas/{id}/editar',        ['MetaController', 'editar'],          ['auth']);
$r->post('/metas/{id}/editar',       ['MetaController', 'atualizar'],       ['auth']);
$r->post('/metas/{id}/excluir',      ['MetaController', 'excluir'],         ['auth']);

// ---- Registros de acompanhamento (gerados pelas sessões; o professor só pode excluir) ----
$r->post('/acompanhamentos/{id}/excluir',  ['AcompanhamentoController', 'excluir'],   ['auth']);

// ---- Sessão de mini games na tela (estado fica na sessão PHP) ----
$r->get('/alunos/{id}/sessao',         ['SessaoController', 'iniciar'],       ['auth']);
$r->get('/sessao',                     ['SessaoController', 'mostrar'],       ['auth']);
$r->post('/sessao/responder',          ['SessaoController', 'responder'],     ['auth']);
$r->post('/sessao/dica',               ['SessaoController', 'dica'],          ['auth']);
$r->post('/sessao/cancelar',           ['SessaoController', 'cancelar'],      ['auth']);

// ---- Administrador: tipos de deficiência ----
$r->get('/tipos',                    ['TipoDeficienciaController', 'index'],     ['admin']);
$r->get('/tipos/novo',               ['TipoDeficienciaController', 'criar'],     ['admin']);
$r->post('/tipos',                   ['TipoDeficienciaController', 'salvar'],    ['admin']);
$r->get('/tipos/{id}/editar',        ['TipoDeficienciaController', 'editar'],    ['admin']);
$r->post('/tipos/{id}/editar',       ['TipoDeficienciaController', 'atualizar'], ['admin']);
$r->post('/tipos/{id}/excluir',      ['TipoDeficienciaController', 'excluir'],   ['admin']);

return $r;
