<div class="layout">
    <section class="story">
        <div class="brand">
            <img src="View/Shared/logo-formapay.png" alt="FormaPay" class="brand-image">
            <div>
                <strong>Forma<span>Pay</span></strong>
                <small>PORTAL DO PARTICIPANTE</small>
            </div>
            <span class="secure">Acesso por Convite</span>
        </div>

        <div class="story-copy">
            <p class="eyebrow">VÍNCULO SEGURO DE TURMA</p>
            <h1>Entre na sua formatura com o <span>código da comissão</span>.</h1>
            <p class="intro">Isso garante que você visualize apenas os dados, metas e contribuições da sua própria turma.</p>
        </div>

        <footer>
            <div class="year">FP</div>
            <div>
                <b><?= e($_SESSION['usuario_nome'] ?? '') ?></b>
                <small><?= e($_SESSION['usuario_email'] ?? '') ?></small>
            </div>
            <form method="POST" action="?rota=logout" class="credit">
                <?= csrf_field() ?>
                <button type="submit" class="fp-btn-secondary">Sair</button>
            </form>
        </footer>
    </section>

    <section class="form-side">
        <div class="auth-box">
            <p class="eyebrow">CÓDIGO DE CONVITE</p>
            <h2>Vincular à Turma</h2>
            <p class="subhead">Digite o código único enviado pela comissão da sua formatura.</p>

            <?php if (!empty($flashSucesso)): ?>
                <div class="notice success"><?= e($flashSucesso) ?></div>
            <?php endif; ?>
            <?php if (!empty($flashErro)): ?>
                <div class="notice error"><?= e($flashErro) ?></div>
            <?php endif; ?>

            <form method="POST" action="?rota=participante_entrar_turma" class="auth-form">
                <?= csrf_field() ?>

                <label for="codigo_convite">Código da turma</label>
                <div class="field">
                    <span>#</span>
                    <input type="text" id="codigo_convite" name="codigo_convite" required placeholder="Ex.: FP-8K4M2P9X" value="<?= e($_POST['codigo_convite'] ?? '') ?>">
                </div>

                <?php if (!empty($coortePrevia)): ?>
                    <div class="notice success">
                        <strong>Turma encontrada:</strong> <?= e($coortePrevia['nome']) ?>
                        <?php if (!empty($coortePrevia['instituicao_curso'])): ?>
                            — <?= e($coortePrevia['instituicao_curso']) ?>
                        <?php endif; ?>
                        <?php if (!empty($coortePrevia['ano_formatura'])): ?>
                            (<?= e((string) $coortePrevia['ano_formatura']) ?>)
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="confirmar_entrada" value="1">
                    <button type="submit" class="button">Confirmar e Entrar na Turma <span>→</span></button>
                <?php else: ?>
                    <button type="submit" class="button">Validar Código <span>→</span></button>
                <?php endif; ?>
            </form>
        </div>
    </section>
</div>