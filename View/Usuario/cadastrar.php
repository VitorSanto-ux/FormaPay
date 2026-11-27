<div class="layout">
    <section class="story">
        <div class="brand">
            <img src="View/Shared/logo-formapay.png" alt="FormaPay" class="brand-image">
            <div>
                <strong>Forma<span>Pay</span></strong>
                <small>GESTÃO DE FORMATURAS</small>
            </div>
            
        </div>

        <div class="story-copy">
            <p class="eyebrow">CADASTRO RÁPIDO E SEGURO</p>
            <h1>Comece sua turma ou entre com <span>código único</span>.</h1>
            <p class="intro">Comissões criam a turma com meta financeira e geram o código de convite. Participantes entram exclusivamente na turma correta.</p>
        </div>

        <footer>
            <div class="logo"><img src="View/Shared/logo-formapay.png" alt="Logo FormaPay"></div>
            <div>
                <b>FormaPay Multi-Turmas</b>
                <small>Controle financeiro colaborativo para comissões e formandos</small>
            </div>
        </footer>
    </section>

    <section class="form-side">
        <div class="auth-box">
            <div class="tabs">
                <a href="?rota=login">Entrar</a>
                <a href="?rota=cadastrar" class="active">Criar conta</a>
            </div>

            <p class="eyebrow">NOVO ACESSO</p>
            <h2>Criar conta</h2>
            <p class="subhead">Selecione seu perfil para iniciar.</p>

            <?php if (!empty($flashErro)): ?>
                <div class="notice error"><?= e($flashErro) ?></div>
            <?php endif; ?>

            <form method="POST" action="?rota=cadastrar" class="auth-form">
                <?= csrf_field() ?>

                <label for="nome">Nome completo</label>
                <div class="field">
                    <span>👤</span>
                    <input type="text" id="nome" name="nome" required minlength="3" placeholder="Ex.: Rafael Silva">
                </div>

                <label for="email">E-mail</label>
                <div class="field">
                    <span>@</span>
                    <input type="email" id="email" name="email" required placeholder="rafael@email.com">
                </div>

                <label for="perfil">Perfil de acesso</label>
                <div class="field">
                    <span>★</span>
                    <select id="perfil" name="perfil" required>
                        <option value="participante">Participante (tenho código da turma)</option>
                        <option value="comissao">Comissão de Formatura (criar turma e meta)</option>
                    </select>
                </div>

                <label for="senha">Senha (mínimo 6 caracteres)</label>
                <div class="field">
                    <span>••</span>
                    <input type="password" id="senha" name="senha" required minlength="6" placeholder="Crie uma senha">
                    <button type="button" class="toggle-password" data-toggle-password="senha" aria-label="Mostrar senha" aria-pressed="false">Mostrar</button>
                </div>

                <label for="confirmar_senha">Confirmar senha</label>
                <div class="field">
                    <span>••</span>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="6" placeholder="Repita a senha">
                    <button type="button" class="toggle-password" data-toggle-password="confirmar_senha" aria-label="Mostrar senha" aria-pressed="false">Mostrar</button>
                </div>

                <button type="submit" class="button">Criar conta <span>→</span></button>
            </form>

            <p class="help">Já possui cadastro? <a href="?rota=login">Voltar para login</a></p>
        </div>
    </section>
</div>
