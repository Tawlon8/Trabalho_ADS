<?php
namespace App\Controllers;

use App\Models\Aluno;
use App\Models\Atividade;
use App\Models\Meta;
use App\Models\TipoDeficiencia;
use Core\Auth;
use Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('home/index', [
            'titulo'            => 'Início',
            'tipos'             => TipoDeficiencia::comContagem(),
            'recentes'          => Atividade::recentes(5),
            'totalAlunos'       => Aluno::contarDoUsuario(Auth::id()),
            'revisoesPendentes' => Meta::vencidasDoUsuario(Auth::id(), date('Y-m-d')),
        ]);
    }
}
