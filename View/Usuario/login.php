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
            <p class="eyebrow">FINANÇAS DA TURMA SEM PLANILHAS</p>
            <h1>Transparência total para a <span>sua formatura</span>.</h1>
            <p class="intro">Acompanhe metas coletivas, entradas, despesas e a evolução financeira de cada turma de forma isolada e auditável.</p>

            <div class="feature">
                <i>★</i>
                <div>
                    <b>Turmas 100% Isoladas</b>
                    <small>Cada comissão gerencia sua própria meta e código de acesso.</small>
                </div>
            </div>
            <div class="feature">
                <i>✓</i>
                <div>
                    <b>Extrato Transparente</b>
                    <small>Participantes acompanham receitas, despesas e progresso em tempo real.</small>
                </div>
            </div>
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
                <a href="?rota=login" class="active">Entrar</a>
                <a href="?rota=cadastrar">Criar conta</a>
            </div>

            <p class="eyebrow">ACESSO AO PORTAL</p>
            <h2>Bem-vindo de volta</h2>
            <p class="subhead">Entre com seu e-mail e senha para acessar sua turma.</p>

            <?php if (!empty($flashSucesso)): ?>
                <div class="notice success"><?= e($flashSucesso) ?></div>
            <?php endif; ?>
            <?php if (!empty($flashErro)): ?>
                <div class="notice error"><?= e($flashErro) ?></div>
            <?php endif; ?>

            <form method="POST" action="?rota=login" class="auth-form">
                <?= csrf_field() ?>

                <label for="email">E-mail</label>
                <div class="field">
                    <span>@</span>
                    <input type="email" id="email" name="email" required autocomplete="email" placeholder="seu@email.com">
                </div>

                <label for="senha">Senha</label>
                <div class="field">
                    <span>••</span>
                    <input type="password" id="senha" name="senha" required autocomplete="current-password" placeholder="Sua senha">
                </div>

                <button type="submit" class="button">Entrar no FormaPay <span>→</span></button>
            </form>

            <p class="help">Ainda não tem cadastro? <a href="?rota=cadastrar">Crie sua conta aqui</a></p>
        </div>
    </section>
</div>