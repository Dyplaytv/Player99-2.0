<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Database;
use App\Models\User;
use App\Models\Client;

class UserController extends Controller
{
    public function list(Request $request): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            $this->redirect('/login');
        }

        Client::ensureSchema();
        $pdoClients = Database::clients();

        // Processamento de Ações Rápidas (Alterar Data, Bloquear, Múltiplo, Excluir)
        if ($request->isPost()) {
            $action = $request->input('action', '');

            if ($action === 'update_date') {
                $cid = (int)$request->input('id');
                $newDate = trim($request->input('new_date'));
                if ($cid > 0 && !empty($newDate)) {
                    $stmt = $pdoClients->prepare("UPDATE ibo SET expire_date = :exp WHERE id = :id");
                    $stmt->execute([':exp' => $newDate, ':id' => $cid]);
                    echo json_encode(['status' => 'success']);
                    return;
                }
            } elseif ($action === 'toggle_block') {
                $cid = (int)$request->input('id');
                $newState = (int)$request->input('new_state');
                if ($cid > 0) {
                    $stmt = $pdoClients->prepare("UPDATE ibo SET active = :act WHERE id = :id");
                    $stmt->bindValue(':act', $newState, \PDO::PARAM_INT);
                    $stmt->bindValue(':id', $cid, \PDO::PARAM_INT);
                    $stmt->execute();

                    // Se bloqueou (0), força reload nas telas desse cliente para entrarem em modo de bloqueio
                    // Se desbloqueou (1), força reload para voltarem a tocar anúncios
                    try {
                        $pdoClients->prepare("UPDATE telas SET force_reload = 1 WHERE id_cliente = :cid")->execute([':cid' => $cid]);
                    } catch (\Throwable $e) {}

                    echo json_encode(['status' => 'success', 'new_state' => $newState]);
                    return;
                }
            } elseif ($action === 'update_multiplo') {
                $cid = (int)$request->input('id');
                $newMultiplo = (float)$request->input('new_multiplo', 1.0);
                if ($cid > 0) {
                    $stmt = $pdoClients->prepare("UPDATE ibo SET multiplo = :mult WHERE id = :id");
                    $stmt->execute([':mult' => $newMultiplo, ':id' => $cid]);
                    echo json_encode(['status' => 'success']);
                    return;
                }
            } elseif ($action === 'delete_client') {
                $cid = (int)$request->input('id');
                if ($cid > 0) {
                    $stmt = $pdoClients->prepare("DELETE FROM ibo WHERE id = :id");
                    $stmt->execute([':id' => $cid]);
                    try {
                        $pdoClients->prepare("DELETE FROM telas WHERE id_cliente = :id")->execute([':id' => $cid]);
                        $pdoClients->prepare("DELETE FROM videos WHERE id_cliente = :id")->execute([':id' => $cid]);
                    } catch (\Throwable $e) {}
                    echo json_encode(['status' => 'success']);
                    return;
                }
            }
        }

        // 1. Carrega todos os Clientes / Motoristas / Franqueados da tabela 'ibo'
        $tab = $request->input('tab', 'clientes');
        $stmtClients = $pdoClients->query("SELECT * FROM ibo ORDER BY id DESC");
        $clients = $stmtClients->fetchAll();

        // 2. Carrega todos os Usuários Administrativos / Revendas da tabela 'users'
        $users = User::all();

        $this->render('admin/users/index', [
            'pageTitle' => 'Usuários & Clientes - Player99',
            'pageHeading' => 'Gestão de Usuários, Clientes & Franquias',
            'currentRoute' => '/admin/usuarios',
            'currentUser' => Auth::user(),
            'clients' => $clients,
            'users' => $users,
            'currentTab' => $tab
        ], 'layouts/master');
    }

    /**
     * Modo Espião: Permite que o Root / Admin entre no sistema exatamente como aquele cliente
     */
    public function spy(Request $request, $id): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            $this->redirect('/login');
        }

        $clientId = (int)$id;
        $client = Client::find($clientId);

        if ($client) {
            // Guarda a sessão de origem do Admin para poder voltar quando quiser
            $_SESSION['admin_spy_origin'] = $_SESSION['id'] ?? 1;
            $_SESSION['admin_spy_user'] = $_SESSION['U'] ?? 'Root';

            // Injeta as credenciais da sessão do cliente
            $_SESSION['cliente_logado'] = true;
            $_SESSION['cliente_id'] = (int)$client['id'];
            $_SESSION['cliente_user'] = $client['username'];
            $_SESSION['cliente_email'] = $client['email'] ?? '';

            // Redireciona para o portal do cliente como se fosse ele
            $this->redirect('/painel-cliente');
            return;
        }

        $this->redirect('/admin/usuarios?err=client_not_found');
    }

    public function create(Request $request): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            $this->redirect('/login');
        }

        if ($request->isPost()) {
            $name = trim($request->input('name', ''));
            $username = trim($request->input('username', ''));
            $email = strtolower(trim($request->input('email', '')));
            $password = trim($request->input('password', ''));
            $nivel = $request->input('nivel', '2');
            $macAmount = $request->input('mac_amount', '10');

            if (!empty($username) && !empty($password)) {
                User::create([
                    'name' => $name,
                    'username' => $username,
                    'email' => $email,
                    'password' => Auth::hashPassword($password),
                    'nivel' => $nivel,
                    'admin' => $nivel === '1' ? '1' : '0',
                    'mac_amount' => $macAmount
                ]);

                $this->redirect('/admin/usuarios?tab=admins&msg=created');
            }
        }

        $this->render('admin/users/create', [
            'pageTitle' => 'Novo Usuário - Player99',
            'pageHeading' => 'Cadastrar Novo Administrador ou Revendedor',
            'currentRoute' => '/admin/usuarios',
            'currentUser' => Auth::user()
        ], 'layouts/master');
    }

    public function delete(Request $request, $id): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            $this->redirect('/login');
        }

        $user = User::find($id);
        if ($user) {
            // BLINDAGEM ROOT: Imune à exclusão
            if (Auth::isRoot($user)) {
                $this->redirect('/admin/usuarios?tab=admins&err=root_protected');
            }

            User::deleteById($id);
        }

        $this->redirect('/admin/usuarios?tab=admins&msg=deleted');
    }
}
