<div class="fp-shell-wrap">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
        <div>
            <p class="eyebrow" style="margin:0 0 6px;">PERFIL E SEGURANÇA</p>
            <h1 style="margin:0;">Minha Conta</h1>
        </div>
        <div style="display:flex;gap:8px;">
            <?php if (($perfil ?? '') === 'comissao' && $coorteAtual !== null): ?>
                <a href="?rota=comissao_dashboard" class="portal-back"><span>←</span> Voltar ao Painel</a>
            <?php elseif (($perfil ?? '') === 'participante' && $coorteAtual !== null): ?>
                <a href="?rota=participante_dashboard" class="portal-back"><span>←</span> Voltar ao Portal</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($flashSucesso)): ?>
        <div class="notice success"><?= e($flashSucesso) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashErro)): ?>
        <div class="notice error"><?= e($flashErro) ?></div>
    <?php endif; ?>

    <div class="settings-grid">
        <section class="panel settings-card">
            <h2>Dados da Conta</h2>
            <p>Atualize seu nome, e-mail, foto de perfil ou senha.</p>

            <?php if (!empty($usuarioDados['foto_perfil'])): ?>
                <div class="profile-preview" style="margin-bottom:12px;">
                    <img src="<?= e($usuarioDados['foto_perfil']) ?>" alt="Foto de perfil">
                    <span>Foto atual salva</span>
                </div>
            <?php endif; ?>

            <form method="POST" action="?rota=usuario_editar" enctype="multipart/form-data" class="fp-form-stack">
                <?= csrf_field() ?>

                <label>
                    Nome completo *
                    <input type="text" name="nome" required minlength="3" value="<?= e($usuarioDados['nome'] ?? '') ?>">
                </label>

                <label>
                    E-mail *
                    <input type="email" name="email" required value="<?= e($usuarioDados['email'] ?? '') ?>">
                </label>

                <label>
                    Foto de perfil (JPG ou PNG, até 2 MB)
                    <input type="file" name="foto_perfil" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                </label>

                <label>
                    Senha atual (obrigatória se for definir nova senha)
                    <input type="password" name="senha_atual" autocomplete="current-password">
                </label>

                <label>
                    Nova senha (opcional, mínimo 6 caracteres)
                    <input type="password" name="nova_senha" minlength="6" autocomplete="new-password">
                </label>

                <button type="submit" class="primary-action">Salvar Alterações</button>
            </form>
        </section>

        <section class="panel settings-card">
            <h2>Resumo do Vínculo</h2>
            <p>Informações de segurança e escopo atual da sua sessão.</p>

            <div class="years-list" style="margin-top:0;padding-top:0;border-top:0;">
                <div><b>Perfil:</b> <?= e(ucfirst((string) ($usuarioDados['perfil'] ?? ''))) ?></div>
                <div><b>Turma ativa:</b> <?= e($coorteAtual['nome'] ?? 'Nenhuma turma vinculada ainda') ?></div>
                <?php if ($coorteAtual !== null): ?>
                    <div><b>Código da turma:</b> <code><?= e($coorteAtual['codigo_convite'] ?? '') ?></code></div>
                <?php endif; ?>
            </div>
            <div style="margin-top:24px;padding-top:18px;border-top:1px solid #eee8f4;">
                <a href="?rota=usuario_deletar" class="fp-btn-secondary">Encerramento de Conta</a>
            </div>
        </section>
    </div>
</div>
