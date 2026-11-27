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
$notificacoes = $usuarioId !== null ? $financeiroModel->listarNotificacoes($usuarioId) : [];

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
                    (string) ($_POST['ano_formatura'] ?? ''),
                    $_FILES['foto_turma'] ?? null
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

        case 'comissao_salvar_recebimento':
            if($usuarioId!==null && $perfil==='comissao') {
                $coorte=(int)($_SESSION['coorte_id']??0);
                if($financeiroModel->usuarioEhComissaoDaCoorte($usuarioId,$coorte)) {
                    $financeiroModel->salvarDadosRecebimento($coorte,trim((string)($_POST['pix_chave']??''))?:null,trim((string)($_POST['banco_nome']??''))?:null,trim((string)($_POST['banco_agencia']??''))?:null,trim((string)($_POST['banco_conta']??''))?:null,trim((string)($_POST['cartao_link']??''))?:null);
                    $_SESSION['flash_sucesso']='Dados de recebimento atualizados.';
                }
                header('Location: ?rota=comissao_configuracoes'); exit;
            }
            break;

        case 'comissao_atualizar_foto_turma':
            if ($usuarioId !== null && $perfil === 'comissao') {
                $coorte=(int)($_SESSION['coorte_id']??0);
                $arquivo=$_FILES['foto_turma']??null;
                if (!$financeiroModel->usuarioEhComissaoDaCoorte($usuarioId,$coorte)) {
                    $_SESSION['flash_erro']='Você não tem permissão para alterar a foto desta turma.';
                } elseif (!$arquivo || (int)($arquivo['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || (int)($arquivo['size']??0)>5*1024*1024) {
                    $_SESSION['flash_erro']='Escolha uma foto JPG, PNG ou WEBP de até 5 MB.';
                } else {
                    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
                    $extensoes=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
                    if (!isset($extensoes[$mime])) {
                        $_SESSION['flash_erro']='Formato de foto inválido. Use JPG, PNG ou WEBP.';
                    } else {
                        $dir=$baseDir.'/uploads/turmas';
                        if (!is_dir($dir) && !mkdir($dir,0755,true) && !is_dir($dir)) {
                            $_SESSION['flash_erro']='Não foi possível preparar o armazenamento da foto.';
                        } else {
                            $nome=bin2hex(random_bytes(16)).'.'.$extensoes[$mime];
                            if (move_uploaded_file($arquivo['tmp_name'],$dir.'/'.$nome)) {
                                $financeiroModel->salvarFotoTurma($coorte,'uploads/turmas/'.$nome);
                                $_SESSION['flash_sucesso']='Foto da turma atualizada.';
                            } else { $_SESSION['flash_erro']='Não foi possível salvar a foto da turma.'; }
                        }
                    }
                }
                header('Location: ?rota=comissao_configuracoes'); exit;
            }
            break;

        case 'comissao_remover_foto_turma':
            if ($usuarioId !== null && $perfil === 'comissao') {
                $coorte=(int)($_SESSION['coorte_id']??0);
                if (!$financeiroModel->usuarioEhComissaoDaCoorte($usuarioId,$coorte)) {
                    $_SESSION['flash_erro']='Você não tem permissão para remover a foto desta turma.';
                } else {
                    $dadosCoorte=$financeiroModel->buscarCoortePorId($coorte);
                    $fotoAtual=(string)($dadosCoorte['foto_turma']??'');
                    if ($fotoAtual==='') {
                        $_SESSION['flash_erro']='Esta turma não possui uma foto para remover.';
                    } elseif (!$financeiroModel->salvarFotoTurma($coorte,null)) {
                        $_SESSION['flash_erro']='Não foi possível remover a foto da turma.';
                    } else {
                        $arquivoRemovido=false;
                        $fotoNormalizada=str_replace('\\','/',$fotoAtual);
                        if (preg_match('~^uploads/turmas/([a-f0-9]{32})\.(?:jpg|png|webp)$~iD',$fotoNormalizada,$partes)) {
                            $diretorioReal=realpath($baseDir.'/uploads/turmas');
                            $arquivoReal=$diretorioReal!==false?realpath($diretorioReal.DIRECTORY_SEPARATOR.$partes[1].'.'.strtolower(pathinfo($fotoNormalizada,PATHINFO_EXTENSION))):false;
                            if ($arquivoReal===false) {
                                $arquivoRemovido=true;
                            } elseif (is_file($arquivoReal)) {
                                $prefixoDiretorio=rtrim($diretorioReal,'\\/').DIRECTORY_SEPARATOR;
                                if (strncasecmp($arquivoReal,$prefixoDiretorio,strlen($prefixoDiretorio))===0) {
                                    $arquivoRemovido=@unlink($arquivoReal);
                                }
                            }
                        }
                        $_SESSION[$arquivoRemovido?'flash_sucesso':'flash_erro']=$arquivoRemovido
                            ? 'Foto da turma removida.'
                            : 'A foto foi removida dos painéis, mas o arquivo antigo não pôde ser apagado do armazenamento.';
                    }
                }
                header('Location: ?rota=comissao_configuracoes'); exit;
            }
            break;

        case 'comissao_editar_movimentacao':
        case 'comissao_excluir_movimentacao':
            if ($usuarioId !== null && $perfil === 'comissao') {
                $coorte=(int)($_SESSION['coorte_id']??0);
                $id=(int)($_POST['movimentacao_id']??0);
                if (!$financeiroModel->usuarioEhComissaoDaCoorte($usuarioId,$coorte)) {
                    $_SESSION['flash_erro']='Sem permissão para alterar lançamentos desta turma.';
                } elseif ($rota==='comissao_excluir_movimentacao') {
                    $ok=$financeiroModel->excluirMovimentacao($coorte,$usuarioId,$id);
                    $_SESSION[$ok?'flash_sucesso':'flash_erro']=$ok?'Movimentação excluída e saldo recalculado.':'Não foi possível excluir. Lançamentos estornados ou inexistentes não podem ser excluídos.';
                } else {
                    $valor=$financeiroController->parseValorMonetario((string)($_POST['valor']??''));
                    $data=(string)($_POST['data_movimento']??'');
                    $validaData=(bool)preg_match('/^\d{4}-\d{2}-\d{2}$/',$data) && checkdate((int)substr($data,5,2),(int)substr($data,8,2),(int)substr($data,0,4));
                    $ok=$valor!==null && $valor>0 && $validaData && $financeiroModel->editarMovimentacao($coorte,$usuarioId,$id,(string)($_POST['tipo']??''),!empty($_POST['categoria_id'])?(int)$_POST['categoria_id']:null,trim((string)($_POST['descricao']??'')),$valor,$data,(string)($_POST['meio']??''));
                    $_SESSION[$ok?'flash_sucesso':'flash_erro']=$ok?'Movimentação atualizada e saldo recalculado.':'Não foi possível editar. Confira os dados ou se o lançamento já foi estornado.';
                }
                header('Location: ?rota=comissao_movimentacoes'); exit;
            }
            break;

        case 'participante_solicitar_pagamento':
            if ($usuarioId !== null && $perfil === 'participante') {
                $coorte = (int) ($_SESSION['coorte_id'] ?? 0);
                $partId = (int) ($_SESSION['participante_id'] ?? 0);
                $meio = (string) ($_POST['meio'] ?? '');
                $valorRaw = preg_replace('/[^0-9,.]/', '', (string) ($_POST['valor'] ?? ''));
                if (str_contains($valorRaw, ',')) { $valorRaw = str_replace(',', '.', str_replace('.', '', $valorRaw)); }
                $valor = (float) $valorRaw;
                $proof = null;
                $file = $_FILES['comprovante'] ?? null;
                if (in_array($meio, ['pix','boleto'], true)) {
                    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || $file['size'] > 4*1024*1024) {
                        $_SESSION['flash_erro'] = 'Anexe o comprovante (máximo 4 MB) para Pix ou boleto.';
                        header('Location: ?rota=participante_dashboard'); exit;
                    }
                    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                    $ext = ['image/jpeg'=>'jpg','image/png'=>'png','application/pdf'=>'pdf'][$mime] ?? null;
                    if (!$ext) { $_SESSION['flash_erro']='Comprovante deve ser PDF, JPG ou PNG.'; header('Location: ?rota=participante_dashboard'); exit; }
                    $dir=$baseDir.'/uploads/comprovantes'; if(!is_dir($dir)) mkdir($dir,0750,true);
                    $name=bin2hex(random_bytes(16)).'.'.$ext;
                    if(!move_uploaded_file($file['tmp_name'],$dir.'/'.$name)) { $_SESSION['flash_erro']='Não foi possível salvar o comprovante.'; header('Location: ?rota=participante_dashboard'); exit; }
                    $proof='uploads/comprovantes/'.$name;
                }
                $date=(string)($_POST['data_pagamento']??'');
                $validMethods=['pix','boleto','transferencia','cartao','dinheiro','outro'];
                $final4=(string)($_POST['cartao_final4']??'');
                if($partId<=0 || $valor<=0 || !in_array($meio,$validMethods,true) || ($meio==='cartao' && !preg_match('/^\d{4}$/',$final4)) || !in_array((string)($_POST['tipo']??''),['receita','despesa'],true) || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$date) || !checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4))) {
                    $_SESSION['flash_erro']='Confira valor, data e forma de pagamento.';
                } else {
                    $tipoTransacao=(string)($_POST['tipo']??'');
                    try {
                        $financeiroModel->solicitarPagamento($coorte,$partId,$valor,$tipoTransacao,(int)($_POST['categoria_id']??0),trim((string)($_POST['descricao']??'Contribuição')), $meio,$date,trim((string)($_POST['referencia']??''))?:null,$meio==='cartao'?$final4:null,$proof);
                        $_SESSION['flash_sucesso']='Pagamento enviado à comissão para aprovação.';
                    } catch (\Throwable $e) { $_SESSION['flash_erro']='Confira a categoria selecionada e tente novamente.'; }
                }
                header('Location: ?rota=participante_dashboard'); exit;
            }
            break;

        case 'comissao_analisar_pagamento':
            if($usuarioId!==null && $perfil==='comissao') {
                $coorte=(int)($_SESSION['coorte_id']??0);
                if($financeiroModel->usuarioEhComissaoDaCoorte($usuarioId,$coorte)) {
                    $ok=$financeiroModel->analisarSolicitacaoPagamento($coorte,(int)($_POST['solicitacao_id']??0),$usuarioId,($_POST['decisao']??'')==='aprovar');
                    $_SESSION[$ok?'flash_sucesso':'flash_erro']=$ok?'Solicitação analisada.':'Solicitação já analisada ou inexistente.';
                }
                header('Location: ?rota=comissao_movimentacoes'); exit;
            }
            break;

        case 'notificacoes_marcar_lidas':
            if ($usuarioId !== null) { $financeiroModel->marcarNotificacoesLidas($usuarioId); }
            $voltar=(string)($_POST['voltar']??'');
            if (!preg_match('/^\?rota=[a-z_]+$/',$voltar)) { $voltar='?rota=login'; }
            header('Location: ' . $voltar); exit;

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
if ($rota === 'participante_verificar_atualizacao' && $usuarioId !== null && $perfil === 'participante') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode(['versao_movimentacoes'=>$financeiroModel->obterUltimoIdMovimentacao((int)$coorteIdSessao)], JSON_THROW_ON_ERROR);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FormaPay — Gestão Financeira de Formaturas</title>
    <link rel="stylesheet" href="View/Shared/style.css?v=<?= (int) filemtime(__DIR__ . '/View/Shared/style.css') ?>">
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
        $solicitacoesPagamento = $financeiroModel->listarSolicitacoesPagamento((int) $coorteIdSessao);
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
        $dadosRecebimento = $financeiroModel->obterDadosRecebimento((int)$coorteIdSessao);
        require $baseDir . '/View/Comissao/configuracoes.php';
        break;

    case 'participante_entrar_turma':
        require $baseDir . '/View/Participante/entrar_turma.php';
        break;

    case 'participante_dashboard':
        $ultimaMovimentacaoId = $financeiroModel->obterUltimoIdMovimentacao((int)$coorteIdSessao);
        $dadosRecebimento = $financeiroModel->obterDadosRecebimento((int)$coorteIdSessao);
        $categoriasCoorte = $financeiroModel->listarCategoriasDaCoorte((int)$coorteIdSessao);
        $partIdSessao = isset($_SESSION['participante_id']) ? (int) $_SESSION['participante_id'] : null;
        $movimentacoesTurma = $financeiroModel->listarMovimentacoes((int) $coorteIdSessao, null, 20);
        $minhasContribuicoes = $partIdSessao
            ? $financeiroModel->listarMovimentacoes((int) $coorteIdSessao, 'receita', 20, $partIdSessao)
            : [];
        $totalContribuicoesParticipante = $partIdSessao ? $financeiroModel->obterTotalContribuicoesParticipante((int)$coorteIdSessao,$partIdSessao) : 0.0;
        $minhasSolicitacoesPagamento = $partIdSessao ? $financeiroModel->listarSolicitacoesPagamento((int)$coorteIdSessao, $partIdSessao) : [];
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
<script src="View/Shared/page-search.js" defer></script>
<script src="View/Shared/notifications.js" defer></script>
<script src="View/Shared/password-toggle.js" defer></script>
<script src="View/Shared/goal-celebration.js" defer></script>
<script src="View/Shared/copy-code.js" defer></script>
</body>
</html>
