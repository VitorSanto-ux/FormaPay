<?php
declare(strict_types=1);

namespace FormaPay\Controller;

use FormaPay\Model\FinanceiroModel;
use FormaPay\Model\UsuarioModel;

class UsuarioController
{
    private UsuarioModel $usuarioModel;
    private FinanceiroModel $financeiroModel;

    public function __construct(?UsuarioModel $usuarioModel = null, ?FinanceiroModel $financeiroModel = null)
    {
        $this->usuarioModel = $usuarioModel ?? new UsuarioModel();
        $this->financeiroModel = $financeiroModel ?? new FinanceiroModel();
    }

    public function login(string $email, string $senha): array
    {
        $emailLimpo = mb_strtolower(trim($email));
        if (!filter_var($emailLimpo, FILTER_VALIDATE_EMAIL) || $senha === '') {
            return ['ok' => false, 'erro' => 'Informe um e-mail válido e sua senha.'];
        }

        $usuario = $this->usuarioModel->buscarPorEmail($emailLimpo);
        if ($usuario === null || !password_verify($senha, $usuario['senha'])) {
            return ['ok' => false, 'erro' => 'E-mail ou senha inválidos.'];
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION['usuario_id'] = (int) $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['usuario_perfil'] = $usuario['perfil'];
        $_SESSION['foto_perfil'] = $usuario['foto_perfil'];

        if ($usuario['perfil'] === 'comissao') {
            $coortes = $this->financeiroModel->listarCoortesDaComissao((int) $usuario['id']);
            $coorteEscolhida = null;

            if ($usuario['coorte_ativa_id'] !== null) {
                foreach ($coortes as $c) {
                    if ((int) $c['id'] === (int) $usuario['coorte_ativa_id']) {
                        $coorteEscolhida = (int) $c['id'];
                        break;
                    }
                }
            }

            if ($coorteEscolhida === null && !empty($coortes)) {
                $coorteEscolhida = (int) $coortes[0]['id'];
                $this->usuarioModel->definirCoorteAtiva((int) $usuario['id'], $coorteEscolhida);
            }

            $_SESSION['coorte_id'] = $coorteEscolhida;
            return [
                'ok' => true,
                'redirect' => $coorteEscolhida !== null ? 'comissao_dashboard' : 'comissao_onboarding',
            ];
        }

        // Perfil participante: precisa estar vinculado a uma coorte ativa
        $participante = $this->financeiroModel->obterParticipantePorUsuario((int) $usuario['id']);
        if ($participante !== null && $participante['participante_status'] === 'ativo' && (int) $participante['ativa'] === 1) {
            $coorteId = (int) $participante['coorte_id'];
            $this->usuarioModel->definirCoorteAtiva((int) $usuario['id'], $coorteId);
            $_SESSION['coorte_id'] = $coorteId;
            $_SESSION['participante_id'] = (int) $participante['participante_id'];
            return ['ok' => true, 'redirect' => 'participante_dashboard'];
        }

        $_SESSION['coorte_id'] = null;
        $_SESSION['participante_id'] = null;
        return ['ok' => true, 'redirect' => 'participante_entrar_turma'];
    }

    public function cadastrar(string $nome, string $email, string $senha, string $confirmarSenha, string $perfil = 'participante'): array
    {
        $nomeLimpo = trim($nome);
        $emailLimpo = mb_strtolower(trim($email));

        if (mb_strlen($nomeLimpo) < 3) {
            return ['ok' => false, 'erro' => 'Informe seu nome completo (mínimo de 3 caracteres).'];
        }
        if (!filter_var($emailLimpo, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'erro' => 'Informe um e-mail válido.'];
        }
        if (strlen($senha) < 6) {
            return ['ok' => false, 'erro' => 'A senha deve ter pelo menos 6 caracteres.'];
        }
        if ($senha !== $confirmarSenha) {
            return ['ok' => false, 'erro' => 'A confirmação de senha não confere.'];
        }
        if ($this->usuarioModel->buscarPorEmail($emailLimpo) !== null) {
            return ['ok' => false, 'erro' => 'Este e-mail já está cadastrado no FormaPay.'];
        }

        $perfilSeguro = in_array($perfil, ['comissao', 'participante'], true) ? $perfil : 'participante';
        $novoId = $this->usuarioModel->cadastrar($nomeLimpo, $emailLimpo, $senha, $perfilSeguro);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION['usuario_id'] = $novoId;
        $_SESSION['usuario_nome'] = $nomeLimpo;
        $_SESSION['usuario_email'] = $emailLimpo;
        $_SESSION['usuario_perfil'] = $perfilSeguro;
        $_SESSION['foto_perfil'] = null;
        $_SESSION['coorte_id'] = null;
        $_SESSION['participante_id'] = null;

        return [
            'ok' => true,
            'redirect' => $perfilSeguro === 'comissao' ? 'comissao_onboarding' : 'participante_entrar_turma',
        ];
    }

    public function atualizarConta(int $usuarioId, string $nome, string $email, string $senhaAtual, string $novaSenha, ?array $arquivoFoto): array
    {
        $usuario = $this->usuarioModel->buscarPorId($usuarioId);
        if ($usuario === null) {
            return ['ok' => false, 'erro' => 'Usuário não encontrado.'];
        }

        $nomeLimpo = trim($nome);
        $emailLimpo = mb_strtolower(trim($email));

        if (mb_strlen($nomeLimpo) < 3) {
            return ['ok' => false, 'erro' => 'O nome deve ter ao menos 3 caracteres.'];
        }
        if (!filter_var($emailLimpo, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'erro' => 'E-mail inválido.'];
        }

        $existente = $this->usuarioModel->buscarPorEmail($emailLimpo);
        if ($existente !== null && (int) $existente['id'] !== $usuarioId) {
            return ['ok' => false, 'erro' => 'Este e-mail já pertence a outra conta.'];
        }

        $senhaParaSalvar = null;
        if ($novaSenha !== '') {
            if ($senhaAtual === '' || !password_verify($senhaAtual, $usuario['senha'])) {
                return ['ok' => false, 'erro' => 'Senha atual incorreta.'];
            }
            if (strlen($novaSenha) < 6) {
                return ['ok' => false, 'erro' => 'A nova senha deve ter no mínimo 6 caracteres.'];
            }
            $senhaParaSalvar = $novaSenha;
        }

        $fotoSalva = null;
        if ($arquivoFoto !== null && (int) ($arquivoFoto['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $extPermitidas = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeReal = $finfo ? finfo_file($finfo, $arquivoFoto['tmp_name']) : '';
            if ($finfo) {
                finfo_close($finfo);
            }
            $ext = strtolower(pathinfo((string) $arquivoFoto['name'], PATHINFO_EXTENSION));

            if (!isset($extPermitidas[$ext]) || $mimeReal !== $extPermitidas[$ext]) {
                return ['ok' => false, 'erro' => 'Envie apenas imagem JPG ou PNG válida.'];
            }
            if ((int) $arquivoFoto['size'] > 2 * 1024 * 1024) {
                return ['ok' => false, 'erro' => 'A imagem deve ter no máximo 2 MB.'];
            }

            $dirDestino = dirname(__DIR__) . '/uploads/perfis';
            if (!is_dir($dirDestino)) {
                mkdir($dirDestino, 0755, true);
            }

            $nomeArquivo = bin2hex(random_bytes(16)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
            if (!move_uploaded_file($arquivoFoto['tmp_name'], $dirDestino . '/' . $nomeArquivo)) {
                return ['ok' => false, 'erro' => 'Erro ao gravar foto de perfil.'];
            }

            $fotoSalva = 'uploads/perfis/' . $nomeArquivo;
        }

        $this->usuarioModel->atualizarPerfil($usuarioId, $nomeLimpo, $emailLimpo, $senhaParaSalvar, $fotoSalva);

        $_SESSION['usuario_nome'] = $nomeLimpo;
        $_SESSION['usuario_email'] = $emailLimpo;
        if ($fotoSalva !== null) {
            $_SESSION['foto_perfil'] = $fotoSalva;
        }

        return ['ok' => true, 'mensagem' => 'Dados da conta atualizados com sucesso.'];
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}