<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

$baseDir = __DIR__;

spl_autoload_register(static function (string $class) use ($baseDir): void {
    $prefix = 'FormaPay\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relativeClass = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = $baseDir . '/' . $relativeClass . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

require_once $baseDir . '/DB/Database.php';
require_once $baseDir . '/Model/UsuarioModel.php';
require_once $baseDir . '/Model/FinanceiroModel.php';
require_once $baseDir . '/Controller/UsuarioController.php';
require_once $baseDir . '/Controller/FinanceiroController.php';

use FormaPay\Controller\FinanceiroController;
use FormaPay\Controller\UsuarioController;
use FormaPay\Model\FinanceiroModel;
use FormaPay\Model\UsuarioModel;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function moeda(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function iniciais(string $nome): string
{
    $partes = preg_split('/\s+/', trim($nome)) ?: [];
    $sigla = '';
    foreach (array_slice($partes, 0, 2) as $p) {
        $sigla .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $sigla !== '' ? $sigla : 'FP';
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token'] ?? '') . '">';
}

function validar_csrf(): bool
{
    $tokenPost = (string) ($_POST['csrf_token'] ?? '');
    $tokenSessao = (string) ($_SESSION['csrf_token'] ?? '');
    return $tokenPost !== '' && $tokenSessao !== '' && hash_equals($tokenSessao, $tokenPost);
}

$usuarioController = new UsuarioController();
$financeiroController = new FinanceiroController();
$usuarioModel = new UsuarioModel();
$financeiroModel = new FinanceiroModel();

$flashSucesso = $_SESSION['flash_sucesso'] ?? null;
$flashErro = $_SESSION['flash_erro'] ?? null;
unset($_SESSION['flash_sucesso'], $_SESSION['flash_erro']);

$rota = (string) ($_GET['rota'] ?? $_POST['rota'] ?? 'login');
$rotasPublicas = ['login', 'cadastrar'];

$usuarioId = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
$perfil = (string) ($_SESSION['usuario_perfil'] ?? '');

if ($usuarioId === null && !in_array($rota, $rotasPublicas, true)) {
    $rota = 'login';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validar_csrf()) {
        $_SESSION['flash_erro'] = 'Sessão expirada ou token inválido. Tente novamente.';
        header('Location: ?rota=' . urlencode($rota));
        exit;
    }

    switch ($rota) {
        case 'login':
            $res = $usuarioController->login((string) ($_POST['email'] ?? ''), (string) ($_POST['senha'] ?? ''));
            if ($res['ok']) {
                header('Location: ?rota=' . urlencode($res['redirect']));
                exit;
            }
            $flashErro = $res['erro'];
            break;

        case 'cadastrar':
            $res = $usuarioController->cadastrar(
                (string) ($_POST['nome'] ?? ''),
                (string) ($_POST['email'] ?? ''),
                (string) ($_POST['senha'] ?? ''),
                (string) ($_POST['confirmar_senha'] ?? ''),
                (string) ($_POST['perfil'] ?? 'participante')
            );
            if ($res['ok']) {
                $_SESSION['flash_sucesso'] = 'Conta criada com sucesso!';
                header('Location: ?rota=' . urlencode($res['redirect']));
                exit;
            }
            $flashErro = $res['erro'];
            break;

        case 'logout':
            $usuarioController->logout();
            header('Location: ?rota=login');
            exit;

        case 'comissao_criar_turma':
            if ($usuarioId !== null && $perfil === 'comissao') {
                $res = $financeiroController->criarNovaCoorteComissao(
                    $usuarioId,
                    (string) ($_POST['nome'] ?? ''),
                    (string) ($_POST['meta'] ?? ''),
                    (string) ($_POST['vencimento_dia'] ?? '10'),
                    (string) ($_POST['instituicao_curso'] ?? ''),
                    (string) ($_POST['ano_formatura'] ?? '')
                );
                if ($res['ok']) {
                    $_SESSION['flash_sucesso'] = $res['mensagem'];
                    header('Location: ?rota=comissao_dashboard');
                    exit;
                }
                $_SESSION['flash_erro'] = $res['erro'];
                header('Location: ?rota=comissao_onboarding');
                exit;
            }
            break;

        case 'comissao_trocar_turma':
            if ($usuarioId !== null && $perfil === 'comissao') {
                $coorteAlvo = (int) ($_POST['coorte_id'] ?? 0);
                $res = $financeiroController->trocarCoorteAtivaComissao($usuarioId, $coorteAlvo);
                $_SESSION[$res['ok'] ? 'flash_sucesso' : 'flash_erro'] = $res['mensagem'] ?? $res['erro'];
                header('Location: ?rota=comissao_dashboard');
                exit;
            }
            break;

        case 'comissao_salvar_config':
            if ($usuarioId !== null && $perfil === 'comissao') {
                $coorteAtualId = (int) ($_SESSION['coorte_id'] ?? 0);
                $res = $financeiroController->salvarConfiguracoesCoorte(
                    $usuarioId,
                    $coorteAtualId,
                    (string) ($_POST['nome'] ?? ''),
                    (string) ($_POST['meta'] ?? ''),
                    (string) ($_POST['vencimento_dia'] ?? '10'),
                    (string) ($_POST['instituicao_curso'] ?? ''),
                    (string) ($_POST['ano_formatura'] ?? ''),
                    !empty($_POST['ativa'])
                );
                $_SESSION[$res['ok'] ? 'flash_sucesso' : 'flash_erro'] = $res['mensagem'] ?? $res['erro'];
                header('Location: ?rota=comissao_configuracoes');
                exit;
            }
            break;

        case 'comissao_regenerar_codigo':
            if ($usuarioId !== null && $perfil === 'comissao') {
                $coorteAtualId = (int) ($_SESSION['coorte_id'] ?? 0);
                $res = $financeiroController->regenerarCodigoCoorte($usuarioId, $coorteAtualId);
                $_SESSION[$res['ok'] ? 'flash_sucesso' : 'flash_erro'] = $res['mensagem'] ?? $res['erro'];
                header('Location: ?rota=comissao_configuracoes');
                exit;
            }
            break;

        case 'comissao_nova_movimentacao':
            if ($usuarioId !== null && $perfil === 'comissao') {
                $coorteAtualId = (int) ($_SESSION['coorte_id'] ?? 0);
                $catId = !empty($_POST['categoria_id']) ? (int) $_POST['categoria_id'] : null;
                $partId = !empty($_POST['participante_id']) ? (int) $_POST['participante_id'] : null;
                $res = $financeiroController->adicionarMovimentacao(
                    $usuarioId,
                    $coorteAtualId,
                    (string) ($_POST['tipo'] ?? 'receita'),
                    $catId,
                    (string) ($_POST['descricao'] ?? ''),
                    (string) ($_POST['valor'] ?? ''),
                    (string) ($_POST['data_movimento'] ?? date('Y-m-d')),
                    (string) ($_POST['meio'] ?? 'pix'),
                    $partId
                );
                $_SESSION[$res['ok'] ? 'flash_sucesso' : 'flash_erro'] = $res['mensagem'] ?? $res['erro'];
                header('Location: ?rota=comissao_movimentacoes');
                exit;
            }
            break;

        case 'comissao_estornar_movimentacao':
            if ($usuarioId !== null && $perfil === 'comissao') {
                $coorteAtualId = (int) ($_SESSION['coorte_id'] ?? 0);
                $movId = (int) ($_POST['movimentacao_id'] ?? 0);
                $res = $financeiroController->estornarMovimentacaoCoorte($usuarioId, $coorteAtualId, $movId);
                $_SESSION[$res['ok'] ? 'flash_sucesso' : 'flash_erro'] = $res['mensagem'] ?? $res['erro'];
                header('Location: ?rota=comissao_movimentacoes');
                exit;
            }
            break;

        case 'comissao_participante_status':
            if ($usuarioId !== null && $perfil === 'comissao') {
                $coorteAtualId = (int) ($_SESSION['coorte_id'] ?? 0);
                $partId = (int) ($_POST['participante_id'] ?? 0);
                $novoStatus = (string) ($_POST['status'] ?? 'ativo');
                if ($financeiroModel->usuarioEhComissaoDaCoorte($usuarioId, $coorteAtualId)) {
                    $usuarioModel->alterarStatusParticipante($coorteAtualId, $partId, $novoStatus);
                    $_SESSION['flash_sucesso'] = 'Status do participante atualizado.';
                } else {
                    $_SESSION['flash_erro'] = 'Sem permissão nesta turma.';
                }
                header('Location: ?rota=comissao_participantes');
                exit;
            }
            break;

        case 'participante_entrar_turma':
            if ($usuarioId !== null && $perfil === 'participante') {
                $codigo = (string) ($_POST['codigo_convite'] ?? '');
                $confirmar = !empty($_POST['confirmar_entrada']);

                if (!$confirmar) {
                    $previa = $financeiroController->validarCodigoPrevia($codigo);
                    if ($previa['ok']) {
                        $coortePrevia = $previa['coorte'];
                    } else {
                        $flashErro = $previa['erro'];
                    }
                } else {
                    $res = $financeiroController->entrarNaCoorteParticipante(
                        $usuarioId,
                        (string) ($_SESSION['usuario_nome'] ?? ''),
                        (string) ($_SESSION['usuario_email'] ?? ''),
                        $codigo
                    );
                    if ($res['ok']) {
                        $_SESSION['flash_sucesso'] = $res['mensagem'];
                        header('Location: ?rota=participante_dashboard');
                        exit;
                    }
                    $flashErro = $res['erro'];
                }
            }
            break;

        case 'usuario_editar':
            if ($usuarioId !== null) {
                $res = $usuarioController->atualizarConta(
                    $usuarioId,
                    (string) ($_POST['nome'] ?? ''),
                    (string) ($_POST['email'] ?? ''),
                    (string) ($_POST['senha_atual'] ?? ''),
                    (string) ($_POST['nova_senha'] ?? ''),
                    $_FILES['foto_perfil'] ?? null
                );
                $_SESSION[$res['ok'] ? 'flash_sucesso' : 'flash_erro'] = $res['mensagem'] ?? $res['erro'];
                header('Location: ?rota=usuario_editar');
                exit;
            }
            break;
    }
}

// Guardas de isolamento por coorte_id
$coorteIdSessao = isset($_SESSION['coorte_id']) && $_SESSION['coorte_id'] !== null ? (int) $_SESSION['coorte_id'] : null;

if ($usuarioId !== null && !in_array($rota, ['login', 'cadastrar', 'logout'], true)) {
    if ($perfil === 'comissao') {
        $coortesComissao = $financeiroModel->listarCoortesDaComissao($usuarioId);
        if (empty($coortesComissao) && $rota !== 'comissao_onboarding') {
            header('Location: ?rota=comissao_onboarding');
            exit;
        }
        if (!empty($coortesComissao) && $rota !== 'comissao_onboarding') {
            $valida = false;
            foreach ($coortesComissao as $c) {
                if ((int) $c['id'] === $coorteIdSessao) {
                    $valida = true;
                    break;
                }
            }
            if (!$valida) {
                $coorteIdSessao = (int) $coortesComissao[0]['id'];
                $_SESSION['coorte_id'] = $coorteIdSessao;
                $usuarioModel->definirCoorteAtiva($usuarioId, $coorteIdSessao);
            }
        }
        if (str_starts_with($rota, 'participante_')) {
            header('Location: ?rota=comissao_dashboard');
            exit;
        }
    } elseif ($perfil === 'participante') {
        $participanteVinculo = $financeiroModel->obterParticipantePorUsuario($usuarioId);
        $vinculoValido = $participanteVinculo !== null
            && $participanteVinculo['participante_status'] === 'ativo'
            && (int) $participanteVinculo['ativa'] === 1;

        if (!$vinculoValido && $rota !== 'participante_entrar_turma' && $rota !== 'usuario_editar') {
            header('Location: ?rota=participante_entrar_turma');
            exit;
        }
        if ($vinculoValido) {
            $coorteIdSessao = (int) $participanteVinculo['coorte_id'];
            $_SESSION['coorte_id'] = $coorteIdSessao;
            $_SESSION['participante_id'] = (int) $participanteVinculo['participante_id'];
        }
        if (str_starts_with($rota, 'comissao_')) {
            header('Location: ?rota=participante_dashboard');
            exit;
        }
    }
}

$coorteAtual = $coorteIdSessao ? $financeiroModel->buscarCoortePorId($coorteIdSessao) : null;
$resumoFinanceiro = $coorteIdSessao ? $financeiroModel->obterResumoFinanceiro($coorteIdSessao) : null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FormaPay — Gestão Financeira de Formaturas</title>
    <link rel="stylesheet" href="View/Shared/style.css">
</head>
<body>
<?php
switch ($rota) {
    case 'cadastrar':
        require $baseDir . '/View/Usuario/cadastrar.php';
        break;

    case 'usuario_editar':
        $usuarioDados = $usuarioModel->buscarPorId((int) $usuarioId);
        require $baseDir . '/View/Usuario/editar.php';
        break;

    case 'usuario_deletar':
        require $baseDir . '/View/Usuario/deletar.php';
        break;

    case 'usuario_listar':
        $participantesCoorte = $coorteIdSessao ? $usuarioModel->listarParticipantesDaCoorte($coorteIdSessao) : [];
        require $baseDir . '/View/Usuario/listar.php';
        break;

    case 'comissao_onboarding':
        $coortesComissao = $financeiroModel->listarCoortesDaComissao((int) $usuarioId);
        require $baseDir . '/View/Comissao/onboarding.php';
        break;

    case 'comissao_dashboard':
        $coortesComissao = $financeiroModel->listarCoortesDaComissao((int) $usuarioId);
        $ultimasMovimentacoes = $financeiroModel->listarMovimentacoes((int) $coorteIdSessao, null, 6);
        $resumoMensal = $financeiroModel->obterResumoMensal((int) $coorteIdSessao, 6);
        require $baseDir . '/View/Comissao/dashboard.php';
        break;

    case 'comissao_movimentacoes':
        $filtroTipo = isset($_GET['tipo']) && in_array($_GET['tipo'], ['receita', 'despesa'], true) ? $_GET['tipo'] : null;
        $movimentacoes = $financeiroModel->listarMovimentacoes((int) $coorteIdSessao, $filtroTipo);
        $categoriasCoorte = $financeiroModel->listarCategoriasDaCoorte((int) $coorteIdSessao);
        $participantesCoorte = $usuarioModel->listarParticipantesDaCoorte((int) $coorteIdSessao);
        require $baseDir . '/View/Comissao/movimentacoes.php';
        break;

    case 'comissao_participantes':
        $participantesCoorte = $usuarioModel->listarParticipantesDaCoorte((int) $coorteIdSessao);
        require $baseDir . '/View/Comissao/participantes.php';
        break;

    case 'comissao_relatorios':
        $movimentacoes = $financeiroModel->listarMovimentacoes((int) $coorteIdSessao);
        $resumoCategorias = $financeiroModel->obterResumoPorCategoria((int) $coorteIdSessao);
        require $baseDir . '/View/Comissao/relatorios.php';
        break;

    case 'comissao_configuracoes':
        require $baseDir . '/View/Comissao/configuracoes.php';
        break;

    case 'participante_entrar_turma':
        require $baseDir . '/View/Participante/entrar_turma.php';
        break;

    case 'participante_dashboard':
        $partIdSessao = isset($_SESSION['participante_id']) ? (int) $_SESSION['participante_id'] : null;
        $movimentacoesTurma = $financeiroModel->listarMovimentacoes((int) $coorteIdSessao, null, 20);
        $minhasContribuicoes = $partIdSessao
            ? $financeiroModel->listarMovimentacoes((int) $coorteIdSessao, 'receita', 20, $partIdSessao)
            : [];
        require $baseDir . '/View/Participante/dashboard.php';
        break;

    case 'participante_configuracoes':
        require $baseDir . '/View/Participante/configuracoes.php';
        break;

    case 'login':
    default:
        if ($usuarioId !== null) {
            $destino = $perfil === 'comissao'
                ? ($coorteIdSessao ? 'comissao_dashboard' : 'comissao_onboarding')
                : ($coorteIdSessao ? 'participante_dashboard' : 'participante_entrar_turma');
            header('Location: ?rota=' . $destino);
            exit;
        }
        require $baseDir . '/View/Usuario/login.php';
        break;
}
?>
</body>
</html>
