<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Database;
use App\Models\User;
use App\Models\Client;
use App\Models\Screen;

class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            $this->redirect('/login');
        }

        User::ensureSchema();
        Client::ensureSchema();
        Screen::ensureSchema();

        $currentUser = Auth::user();

        // 1. Estatísticas de Telas e Carros (banco clientes)
        $pdoClients = Database::clients();

        // Total de telas
        $totalTelas = 0;
        try {
            $res = $pdoClients->query("SELECT COUNT(*) FROM telas");
            $totalTelas = (int)$res->fetchColumn();
        } catch (\Exception $e) {}

        // Telas Online nos últimos 3 minutos
        $telasOnline = 0;
        try {
            $res = $pdoClients->query("SELECT COUNT(*) FROM telas WHERE datetime(ultimo_ping) >= datetime('now', '-3 minutes', 'localtime')");
            $telasOnline = (int)$res->fetchColumn();
        } catch (\Exception $e) {}

        // Total de Clientes / Anunciantes
        $totalClientes = 0;
        try {
            $res = $pdoClients->query("SELECT COUNT(*) FROM ibo");
            $totalClientes = (int)$res->fetchColumn();
        } catch (\Exception $e) {}

        // 2. Estatísticas de Usuários / Revendedores (banco users)
        $pdoUsers = Database::users();
        $totalUsers = 0;
        try {
            $res = $pdoUsers->query("SELECT COUNT(*) FROM users");
            $totalUsers = (int)$res->fetchColumn();
        } catch (\Exception $e) {}

        // Telas recentes com status
        $recentScreens = [];
        try {
            $stmt = $pdoClients->query("SELECT id, player_code, title, nome_tela, ultimo_ping, audio_mode, rotation FROM telas ORDER BY id DESC LIMIT 8");
            $recentScreens = $stmt->fetchAll();
        } catch (\Exception $e) {}

        $this->render('admin/dashboard', [
            'pageTitle' => 'Estatísticas - Player99',
            'pageHeading' => 'Painel de Estatísticas & DOOH',
            'currentRoute' => '/admin/dashboard',
            'currentUser' => $currentUser,
            'totalTelas' => $totalTelas,
            'telasOnline' => $telasOnline,
            'totalClientes' => $totalClientes,
            'totalUsers' => $totalUsers,
            'recentScreens' => $recentScreens
        ], 'layouts/master');
    }
}
