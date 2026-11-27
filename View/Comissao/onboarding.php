<div class="layout">
    <section class="story">
        <div class="brand">
            <img src="View/Shared/logo-formapay.png" alt="FormaPay" class="brand-image">
            <div>
                <strong>Forma<span>Pay</span></strong>
                <small>PAINEL DA COMISSÃO</small>
            </div>
            <span class="secure">Configuração da Turma</span>
        </div>

        <div class="story-copy">
            <p class="eyebrow">PASSO OBRIGATÓRIO DA COMISSÃO</p>
            <h1>Defina o nome da turma e a <span>meta financeira</span>.</h1>
            <p class="intro">Ao criar a turma, geramos automaticamente um código único e seguro para compartilhar com os formandos.</p>
        </div>

        <footer>
            <div class="logo"><img src="View/Shared/logo-formapay.png" alt="Logo FormaPay"></div>
            <div>
                <b>Logado como <?= e($_SESSION['usuario_nome'] ?? '') ?></b>
                <small>Comissão de Formatura</small>
            </div>
            <form method="POST" action="?rota=logout" class="credit">
                <?= csrf_field() ?>
                <button type="submit" class="fp-btn-secondary">Sair</button>
            </form>
        </footer>
    </section>

    <section class="form-side">
        <div class="auth-box">
            <p class="eyebrow">NOVA TURMA DE FORMANDOS</p>
            <h2>Configurar Turma</h2>
            <p class="subhead">Preencha os dados oficiais para liberar o painel financeiro.</p>

            <?php if (!empty($flashSucesso)): ?>
                <div class="notice success"><?= e($flashSucesso) ?></div>
            <?php endif; ?>
            <?php if (!empty($flashErro)): ?>
                <div class="notice error"><?= e($flashErro) ?></div>
            <?php endif; ?>

            <form method="POST" action="?rota=comissao_criar_turma" enctype="multipart/form-data" class="auth-form">
                <?= csrf_field() ?>

                <label for="nome">Nome da turma dos formandos *</label>
                <div class="field">
                    <span>🎓</span>
                    <input type="text" id="nome" name="nome" required minlength="3" placeholder="Ex.: Sistemas de Informação 2027">
                </div>

                <label for="meta">Meta financeira (R$) *</label>
                <div class="field">
                    <span>R$</span>
                    <input type="text" id="meta" name="meta" required inputmode="decimal" placeholder="Ex.: 65000.00">
                </div>

                <label for="instituicao_curso">Instituição / Curso (opcional)</label>
                <div class="field">
                    <span>🏛</span>
                    <input type="text" id="instituicao_curso" name="instituicao_curso" placeholder="Ex.: FEMA - Bacharelado">
                </div>

                <label for="foto_turma">Foto da turma (opcional · JPG, PNG ou WEBP · até 5 MB)</label>
                <div class="field file-field"><input type="file" id="foto_turma" name="foto_turma" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></div>

                <div class="fp-form-grid-2">
                    <div>
                        <label for="ano_formatura">Ano formatura</label>
                        <div class="field">
                            <input type="number" id="ano_formatura" name="ano_formatura" min="2020" max="2050" placeholder="2027">
                        </div>
                    </div>
                    <div>
                        <label for="vencimento_dia">Dia vencimento</label>
                        <div class="field">
                            <input type="number" id="vencimento_dia" name="vencimento_dia" min="1" max="28" value="10">
                        </div>
                    </div>
                </div>

                <button type="submit" class="button">Criar Turma e Gerar Código <span>→</span></button>
            </form>

            <?php if (!empty($coortesComissao)): ?>
                <div class="years-list">
                    <b>Ou acesse uma turma já existente:</b>
                    <?php foreach ($coortesComissao as $c): ?>
                        <form method="POST" action="?rota=comissao_trocar_turma" style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="coorte_id" value="<?= (int) $c['id'] ?>">
                            <span><?= e($c['nome']) ?> (<code><?= e($c['codigo_convite']) ?></code>)</span>
                            <button type="submit" class="fp-btn-secondary">Abrir</button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
