<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/DB/Database.php';
require_once __DIR__ . '/Controller/UsuarioController.php';
require_once __DIR__ . '/Controller/FinanceiroController.php';

function e(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}
function progressoMetaColetiva(float $saldo, float $meta): array
{
    $percentual = $meta > 0 ? max(0, min(100, (int) round($saldo / $meta * 100))) : 0;
    return ['percentual' => $percentual, 'faltante' => max(0, $meta - $saldo)];
}
function salvarFoto(array $arquivo): ?string
{
    if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE)
        return null;
    if (($arquivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || ($arquivo['size'] ?? 0) > 2 * 1024 * 1024)
        throw new InvalidArgumentException('A imagem deve ter até 2 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext)
        throw new InvalidArgumentException('Use uma imagem PNG, JPG ou WEBP.');
    $dir = __DIR__ . '/uploads/perfis';
    if (!is_dir($dir))
        mkdir($dir, 0755, true);
    $nome = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($arquivo['tmp_name'], $dir . '/' . $nome))
        throw new RuntimeException('Não foi possível salvar a foto.');
    return 'uploads/perfis/' . $nome;
}

$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$modo = ($_GET['modo'] ?? 'login') === 'cadastro' ? 'cadastro' : 'login';
$erro = '';
$sucesso = '';
try {
    $controller = new UsuarioController(new UsuarioModel(Database::conectar()));
    $financeiro = new FinanceiroController(new FinanceiroModel(Database::conectar()));
    $coorteAtual = $financeiro->coorte();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? ''))
            throw new RuntimeException('Sessão expirada. Atualize a página e tente novamente.');
        $acao = $_POST['acao'] ?? '';
        if (in_array($acao, ['novo_participante', 'nova_movimentacao', 'salvar_coorte'], true) && ($_SESSION['usuario']['perfil'] ?? '') !== 'comissao')
            throw new RuntimeException('Ação disponível apenas para a comissão.');
        if ($acao === 'registrar_contribuicao') {
            if (($_SESSION['usuario']['perfil'] ?? '') !== 'participante')
                throw new RuntimeException('Ação disponível apenas para participantes.');
            $participante = $financeiro->participanteDoUsuario((int) $coorteAtual['id'], (int) $_SESSION['usuario']['id']);
            if (!$participante)
                throw new RuntimeException('Seu cadastro de participante não está vinculado a esta conta.');
            $parcelaId = !empty($_POST['parcela_id']) ? (int) $_POST['parcela_id'] : null;
            $parcelas = $financeiro->parcelasParticipante((int) $participante['id']);
            $parcelaSelecionada = null;
            if ($parcelaId)
                foreach ($parcelas as $parcela)
                    if ((int) $parcela['id'] === $parcelaId) {
                        $parcelaSelecionada = $parcela;
                        break;
                    }
            if ($parcelaId && !$parcelaSelecionada)
                throw new RuntimeException('A parcela selecionada não pertence ao seu cadastro.');
            if ($parcelaSelecionada && ($parcelaSelecionada['status'] ?? '') === 'cancelada')
                throw new InvalidArgumentException('Não é possível pagar uma parcela cancelada.');
            $valor = (float) str_replace(',', '.', (string) ($_POST['valor'] ?? 0));
            if ($parcelaSelecionada) {
                $restante = max(0, (float) $parcelaSelecionada['valor'] - (float) $parcelaSelecionada['pago']);
                if ($restante <= 0 || $valor > $restante + 0.001)
                    throw new InvalidArgumentException('O valor excede o saldo desta parcela.');
            }
            $categorias = $financeiro->categorias((int) $coorteAtual['id']);
            $categoriaId = 0;
            foreach ($categorias as $categoria)
                if (($categoria['nome'] ?? '') === ($parcelaId ? 'Mensalidade' : 'Receita extra')) {
                    $categoriaId = (int) $categoria['id'];
                    break;
                }
            if (!$categoriaId)
                foreach ($categorias as $categoria)
                    if (($categoria['nome'] ?? '') === 'Outros') {
                        $categoriaId = (int) $categoria['id'];
                        break;
                    }
            if (!$categoriaId)
                throw new RuntimeException('Não há categoria de receita cadastrada para este lançamento.');
            $descricao = $parcelaId ? 'Contribuição · parcela ' . date('m/Y', strtotime($parcelaSelecionada['competencia'])) : 'Contribuição extra';
            $meio = (string) ($_POST['meio'] ?? 'pix');
            if (!in_array($meio, ['pix', 'dinheiro', 'transferencia', 'outro'], true))
                $meio = 'outro';
            $financeiro->lancar((int) $coorteAtual['id'], (int) $_SESSION['usuario']['id'], 'receita', $descricao, $valor, date('Y-m-d'), $meio, $categoriaId, (int) $participante['id'], $parcelaId);
            header('Location: index.php?pagina=participante&contribuicao=1');
            exit;
        }
        if ($acao === 'cadastro') {
            $controller->cadastrar((string) ($_POST['nome'] ?? ''), (string) ($_POST['email'] ?? ''), (string) ($_POST['senha'] ?? ''), (string) ($_POST['perfil'] ?? ''), salvarFoto($_FILES['foto_perfil'] ?? []));
            $sucesso = 'Conta criada! Agora entre com seu e-mail e senha.';
            $modo = 'login';
        } elseif ($acao === 'login') {
            $usuario = $controller->login((string) ($_POST['email'] ?? ''), (string) ($_POST['senha'] ?? ''));
            if (!$usuario)
                throw new InvalidArgumentException('E-mail ou senha incorretos.');
            session_regenerate_id(true);
            $_SESSION['usuario'] = $usuario;
            header('Location: index.php?pagina=' . (($usuario['perfil'] ?? 'comissao') === 'participante' ? 'participante' : 'dashboard'));
            exit;
        } elseif ($acao === 'sair') {
            $_SESSION = [];
            session_destroy();
            header('Location: index.php');
            exit;
        } elseif ($acao === 'novo_participante') {
            $financeiro->adicionarParticipante((int) $coorteAtual['id'], (string) ($_POST['nome'] ?? ''), ($_POST['email'] ?? '') ?: null, (float) ($_POST['valor'] ?? 0), (int) $_SESSION['usuario']['id']);
            header('Location: index.php?pagina=participantes&salvo=1');
            exit;
        } elseif ($acao === 'nova_movimentacao') {
            $financeiro->lancar((int) $coorteAtual['id'], (int) $_SESSION['usuario']['id'], (string) ($_POST['tipo'] ?? ''), (string) ($_POST['descricao'] ?? ''), (float) str_replace(',', '.', (string) ($_POST['valor'] ?? 0)), (string) ($_POST['data'] ?? date('Y-m-d')), (string) ($_POST['meio'] ?? 'pix'), (int) ($_POST['categoria'] ?? 0), !empty($_POST['participante_id']) ? (int) $_POST['participante_id'] : null, !empty($_POST['parcela_id']) ? (int) $_POST['parcela_id'] : null);
            header('Location: index.php?pagina=movimentacoes&salvo=1');
            exit;
        } elseif ($acao === 'salvar_coorte') {
            $financeiro->atualizarCoorte((int) $coorteAtual['id'], (string) ($_POST['nome'] ?? ''), (float) str_replace(',', '.', (string) ($_POST['meta'] ?? 0)), (int) ($_POST['vencimento_dia'] ?? 10));
            header('Location: index.php?pagina=configuracoes&salvo=1');
            exit;
        } elseif ($acao === 'salvar_perfil') {
            $foto = salvarFoto($_FILES['foto_perfil'] ?? []);
            $controller->atualizarPerfil((int) $_SESSION['usuario']['id'], (string) ($_POST['nome'] ?? ''), (string) ($_POST['email'] ?? ''), $foto);
            $_SESSION['usuario'] = $controller->porId((int) $_SESSION['usuario']['id']);
            header('Location: index.php?pagina=configuracoes&perfil=1');
            exit;
        }
    }
} catch (Throwable $ex) {
    $erro = $ex instanceof PDOException ? 'Não foi possível conectar ao banco. Confira a configuração e importe DB/schema.sql.' : $ex->getMessage();
}

$_SESSION['csrf'] = bin2hex(random_bytes(32));
$usuarioLogado = $_SESSION['usuario'] ?? null;
if ($usuarioLogado) {
    $pagina = $_GET['pagina'] ?? '';
    if (($usuarioLogado['perfil'] ?? 'comissao') === 'participante') {
        $participanteAtual = $financeiro->participanteDoUsuario((int) $coorteAtual['id'], (int) $usuarioLogado['id']);
        $parcelasAtuais = $participanteAtual ? $financeiro->parcelasParticipante((int) $participanteAtual['id']) : [];
        $listaMovimentacoes = $participanteAtual ? $financeiro->movimentacoes((int) $coorteAtual['id'], (int) $participanteAtual['id']) : [];
        $resumoColetivo = $financeiro->resumo((int) $coorteAtual['id']);
        $usuarioAtual = $controller->porId((int) $usuarioLogado['id']);
        if ($pagina === 'configuracoes') {
            require __DIR__ . '/View/Participante/configuracoes.php';
            exit;
        }
        require __DIR__ . '/View/Participante/dashboard.php';
        exit;
    }
    if (in_array($pagina, ['dashboard', 'inicio'], true)) {
        $resumoAtual = $financeiro->resumo((int) $coorteAtual['id']);
        $listaMovimentacoes = $financeiro->movimentacoes((int) $coorteAtual['id']);
        $evolucaoAtual = $financeiro->evolucaoMensal((int) $coorteAtual['id']);
        $categorias = $financeiro->categorias((int) $coorteAtual['id']);
        $participantesAtivos = $financeiro->participantesAtivos((int) $coorteAtual['id']);
        $parcelasAbertas = $financeiro->parcelasAbertas((int) $coorteAtual['id']);
        require __DIR__ . '/View/Comissao/dashboard.php';
        exit;
    }
    if ($pagina === 'movimentacoes') {
        $resumoAtual = $financeiro->resumo((int) $coorteAtual['id']);
        $listaMovimentacoes = $financeiro->movimentacoes((int) $coorteAtual['id']);
        $categorias = $financeiro->categorias((int) $coorteAtual['id']);
        $participantesAtivos = $financeiro->participantesAtivos((int) $coorteAtual['id']);
        $parcelasAbertas = $financeiro->parcelasAbertas((int) $coorteAtual['id']);
        require __DIR__ . '/View/Comissao/movimentacoes.php';
        exit;
    }
    if ($pagina === 'participantes') {
        $resumoAtual = $financeiro->resumo((int) $coorteAtual['id']);
        $listaParticipantes = $financeiro->participantes((int) $coorteAtual['id']);
        require __DIR__ . '/View/Comissao/participantes.php';
        exit;
    }
    if ($pagina === 'relatorios') {
        $listaMovimentacoes = $financeiro->movimentacoes((int) $coorteAtual['id']);
        $categorias = $financeiro->categorias((int) $coorteAtual['id']);
        $categoriasDespesas = $financeiro->despesasPorCategoria((int) $coorteAtual['id']);
        $resumoAtual = $financeiro->resumo((int) $coorteAtual['id']);
        require __DIR__ . '/View/Comissao/relatorios.php';
        exit;
    }
    if ($pagina === 'configuracoes') {
        $usuarioAtual = $controller->porId((int) $usuarioLogado['id']);
        require __DIR__ . '/View/Comissao/configuracoes.php';
        exit;
    }
}
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#160d20">
    <title><?= $modo === 'cadastro' ? 'Criar conta' : 'Entrar' ?> | FormaPay</title>
    <link rel="stylesheet" href="/FormaPay/View/Shared/style.css?v=19">
</head>

<body>
    <main class="layout">
        <section class="story" aria-label="Sobre a FormaPay">
            <header class="brand">
                <img class="brand-image" src="/FormaPay/View/Shared/logo-formapay.png" alt="Logo FormaPay">
                <div><strong>Forma<span>Pay</span></strong><small>FORMATURA EM DIA</small></div>
                
            </header>
            <div class="story-copy">
                <p class="eyebrow">CONTAS ORGANIZADAS</p>
                <h1>A conquista é coletiva.<br><span>A transparência<br>também.</span></h1>
                <p class="intro">Acompanhe contribuições, despesas e o caminho até a formatura em um espaço feito para a
                    sua turma.</p>
                <div class="feature"><i>▣</i>
                    <div><b>Finanças centralizadas</b><small>Entradas, despesas e saldo sempre atualizados.</small>
                    </div>
                </div>
                <div class="feature"><i>▤</i>
                    <div><b>Prestação de contas clara</b><small>Relatórios acessíveis para perfis autorizados.</small>
                    </div>
                </div>
            </div>
            <footer><b class="year">26</b>
                <div><b>Formandos 2026</b><small>Juntos desde o 2º ano</small></div>
            </footer>
        </section>
        <section class="form-side">
            <div class="auth-box">
                <nav class="tabs"><a class="<?= $modo === 'login' ? 'active' : '' ?>" href="?modo=login">Entrar</a><a
                        class="<?= $modo === 'cadastro' ? 'active' : '' ?>" href="?modo=cadastro">Criar conta</a></nav>
                <p class="eyebrow"><?= $modo === 'cadastro' ? 'FAÇA PARTE DA TURMA' : 'BEM-VINDO DE VOLTA' ?></p>
                <h2><?= $modo === 'cadastro' ? 'Crie sua conta' : 'Acesse sua conta' ?></h2>
                <p class="subhead">
                    <?= $modo === 'cadastro' ? 'Cadastre-se como participante da coorte Formandos 2026.' : 'Entre para acompanhar a jornada da sua turma.' ?>
                </p>
                <?php if ($erro): ?>
                    <div class="notice error" role="alert"><?= e($erro) ?></div><?php endif ?><?php if ($sucesso): ?>
                    <div class="notice success" role="status"><?= e($sucesso) ?></div><?php endif ?>
                <form method="post" enctype="multipart/form-data" class="auth-form"><input type="hidden" name="csrf"
                        value="<?= e($_SESSION['csrf']) ?>"><input type="hidden" name="acao" value="<?= $modo ?>">
                    <?php if ($modo === 'cadastro'): ?><label for="nome">Nome completo</label>
                        <div class="field"><span>♙</span><input id="nome" name="nome" autocomplete="name"
                                placeholder="Seu nome completo" required maxlength="120"></div><label for="perfil">Cadastrar
                            como</label>
                        <div class="field"><span>♧</span><select id="perfil" name="perfil" required>
                                <option value="participante">Participante</option>
                                <option value="comissao">Comissão</option>
                            </select></div><?php endif ?>
                    <?php if ($modo === 'cadastro'): ?><label for="foto_perfil">Foto de perfil (opcional)</label>
                        <div class="field"><input id="foto_perfil" name="foto_perfil" type="file"
                                accept="image/png,image/jpeg,image/webp"></div>
                        <div class="upload-preview" id="photo-preview" hidden><img
                                alt="Prévia da foto de perfil"><span>Prévia da foto de perfil</span></div><?php endif ?>
                    <label for="email">E-mail</label>
                    <div class="field"><span>@</span><input id="email" name="email" type="email" autocomplete="email"
                            placeholder="voce@email.com" required maxlength="190"></div>
                    <div class="label-row"><label for="senha">Senha</label><?php if ($modo === 'login'): ?><a
                                href="mailto:comissao@formapay.com?subject=Recuperar%20senha">Esqueceu a
                                senha?</a><?php endif ?></div>
                    <div class="field"><span>♙</span><input id="senha" name="senha" type="password"
                            autocomplete="<?= $modo === 'cadastro' ? 'new-password' : 'current-password' ?>"
                            placeholder="Mínimo de 6 caracteres" required minlength="6"><button class="toggle-password"
                            type="button" aria-label="Mostrar senha">◉</button></div>
                    <?php if ($modo === 'cadastro'): ?><label class="terms"><input type="checkbox" required><span>Li e
                                concordo com os termos de uso e a política de privacidade.</span></label><?php endif ?>
                    <button class="button"
                        type="submit"><?= $modo === 'cadastro' ? 'Criar minha conta' : 'Entrar na plataforma' ?><span>→</span></button>
                </form>
                <div class="help">Precisa de ajuda? <a href="mailto:comissao@formapay.com">Fale com a comissão</a></div>
            </div>
        </section>
    </main>
    <script>document.querySelector('.toggle-password')?.addEventListener('click', e => { const input = document.querySelector('#senha'); const visible = input.type === 'password'; input.type = visible ? 'text' : 'password'; e.currentTarget.setAttribute('aria-label', visible ? 'Ocultar senha' : 'Mostrar senha'); }); const photoInput = document.getElementById('foto_perfil'); photoInput?.addEventListener('change', () => { const file = photoInput.files[0], box = document.getElementById('photo-preview'); if (!file) { box.hidden = true; return } if (!file.type.startsWith('image/')) return; const reader = new FileReader(); reader.onload = () => { box.querySelector('img').src = reader.result; box.hidden = false }; reader.readAsDataURL(file) });</script>
</body>

</html>
