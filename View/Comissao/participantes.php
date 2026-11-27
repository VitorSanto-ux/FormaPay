<?php
$progresso = (float) ($resumoFinanceiro['progresso_percentual'] ?? 0);
$metaValor = (float) ($resumoFinanceiro['meta'] ?? 0);
$saldoValor = (float) ($resumoFinanceiro['saldo_atual'] ?? 0);
$totalAtivos = count(array_filter($participantesCoorte, static fn(array $p): bool => ($p['status'] ?? '') === 'ativo'));
$totalInativos = count($participantesCoorte) - $totalAtivos;
$totalContribuidos = array_reduce($participantesCoorte, static fn(float $acc, array $p): float => $acc + (float) ($p['total_contribuido'] ?? 0), 0.0);
?>
<div class="dashboard">
    <aside class="sidebar">
        <a href="?rota=comissao_dashboard" class="dash-brand">
            <img src="View/Shared/logo-formapay.png" alt="FormaPay" class="dash-logo-img">
            <span>
                <b>Forma<span>Pay</span></b>
                <small>COMISSÃO DE FORMATURA</small>
            </span>
        </a>

        <p class="side-caption">TURMA ATIVA</p>
        <div class="cohort">
            <?php if (!empty($coorteAtual['foto_turma'])): ?><img class="cohort-photo" src="<?= e($coorteAtual['foto_turma']) ?>" alt="Foto da turma"><?php else: ?><b>🎓</b><?php endif; ?>
            <span>
                <strong><?= e($coorteAtual['nome'] ?? '') ?></strong>
                <small>Código: <?= e($coorteAtual['codigo_convite'] ?? '') ?></small>
            </span>
        </div>

        <nav class="side-nav">
            <a href="?rota=comissao_dashboard"><i>◈</i> Visão Geral</a>
            <a href="?rota=comissao_movimentacoes"><i>⇄</i> Movimentações</a>
            <a href="?rota=comissao_participantes" class="selected"><i>☺</i> Participantes <em><?= count($participantesCoorte) ?></em></a>
            <a href="?rota=comissao_relatorios"><i>▤</i> Relatórios</a>
            <a href="?rota=comissao_configuracoes"><i>⚙</i> Configurações</a>
            <a href="?rota=comissao_onboarding"><i>+</i> Nova Turma</a>
        </nav>

        <div class="goal-mini">
            <b>Meta da Turma (<?= e((string) $progresso) ?>%)</b>
            <p><?= e(moeda($saldoValor)) ?> de <?= e(moeda($metaValor)) ?></p>
            <div class="mini-track"><i style="width: <?= min(100, max(0, $progresso)) ?>%;"></i></div>
        </div>

        <div class="profile">
            <span class="avatar">
                <?php if (!empty($_SESSION['foto_perfil'])): ?>
                    <img src="<?= e($_SESSION['foto_perfil']) ?>" alt="Foto de perfil">
                <?php else: ?>
                    <?= e(iniciais((string) ($_SESSION['usuario_nome'] ?? 'CM'))) ?>
                <?php endif; ?>
            </span>
            <span>
                <b><?= e($_SESSION['usuario_nome'] ?? '') ?></b>
                <small>Comissão</small>
            </span>
            <form method="POST" action="?rota=logout">
                <?= csrf_field() ?>
                <button type="submit" title="Sair">⎋</button>
            </form>
        </div>
    </aside>

    <header class="topbar">
        <div class="search">
            <span>⌕</span>
            <input type="search" data-page-search placeholder="Pesquisar participantes..." aria-label="Pesquisar participantes">
        </div>
        <div class="top-right">
            <?php require __DIR__ . '/../Shared/notifications.php'; ?>
            <button type="button" class="fp-btn-secondary fp-code-pill copy-code-button" data-copy-code="<?= e($coorteAtual['codigo_convite'] ?? '') ?>" aria-label="Copiar código de convite">
                <span aria-hidden="true"></span> Copiar código <strong><?= e($coorteAtual['codigo_convite'] ?? '') ?></strong>
            </button>
            <div class="top-divider"></div>
        </div>
    </header>

    <main class="dash-main">
        <?php require __DIR__ . '/../Shared/goal-celebration.php'; ?>
        <?php if (!empty($flashSucesso)): ?>
            <div class="notice success"><?= e($flashSucesso) ?></div>
        <?php endif; ?>
        <?php if (!empty($flashErro)): ?>
            <div class="notice error"><?= e($flashErro) ?></div>
        <?php endif; ?>

        <div class="participants-heading">
            <div>
                <p class="eyebrow">MEMBROS VINCULADOS POR CÓDIGO</p>
                <h1>Participantes — <?= e($coorteAtual['nome'] ?? '') ?></h1>
                <p>Somente formandos que informaram o código <strong><?= e($coorteAtual['codigo_convite'] ?? '') ?></strong> entram nesta turma.</p>
            </div>
        </div>

        <section class="participant-metrics">
            <article>
                <div class="metric-icon green">☺</div>
                <div>
                    <small>Participantes Ativos</small>
                    <b><?= $totalAtivos ?></b>
                </div>
            </article>
            <article>
                <div class="metric-icon amber">◔</div>
                <div>
                    <small>Inativos / Desistentes</small>
                    <b><?= $totalInativos ?></b>
                </div>
            </article>
            <article>
                <div class="metric-icon purple">R$</div>
                <div>
                    <small>Contribuições Identificadas</small>
                    <b><?= e(moeda($totalContribuidos)) ?></b>
                </div>
            </article>
        </section>

        <section class="panel participant-panel">
            <div class="participant-panel-head">
                <div>
                    <h2>Formandos da Turma</h2>
                    <p>Gestão de status exclusiva da coorte ativa</p>
                </div>
            </div>

            <?php if (empty($participantesCoorte)): ?>
                <div class="participant-empty">
                    Nenhum participante entrou nesta turma ainda. Compartilhe o código <strong><?= e($coorteAtual['codigo_convite'] ?? '') ?></strong> com os formandos.
                </div>
            <?php else: ?>
                <div class="participant-grid">
                    <?php foreach ($participantesCoorte as $part): ?>
                        <?php
                        $statusAtual = (string) ($part['status'] ?? 'ativo');
                        $contrib = (float) ($part['total_contribuido'] ?? 0);
                        $classePill = match ($statusAtual) {
                            'ativo' => 'up-to-date',
                            'inativo' => 'partial',
                            default => 'pending',
                        };
                        ?>
                        <article class="participant-card">
                            <div class="participant-card-top">
                                <?php if (!empty($part['foto_perfil'])): ?>
                                    <img class="participant-initials participant-photo" src="<?= e($part['foto_perfil']) ?>" alt="Foto de <?= e($part['nome'] ?? 'participante') ?>">
                                <?php else: ?>
                                    <span class="participant-initials"><?= e(iniciais((string) ($part['nome'] ?? 'PT'))) ?></span>
                                <?php endif; ?>
                                <span class="status-pill <?= $classePill ?>"><?= e(ucfirst($statusAtual)) ?></span>
                            </div>

                            <h3><?= e($part['nome']) ?></h3>
                            <p class="joined"><?= e($part['email'] ?? 'Sem e-mail') ?> • Desde <?= e($part['entrou_em']) ?></p>

                            <div class="contribution-labels">
                                <span>
                                    <small>Total contribuído</small>
                                    <b><?= e(moeda($contrib)) ?></b>
                                </span>
                                <span>
                                    <small>Status</small>
                                    <b><?= e(ucfirst($statusAtual)) ?></b>
                                </span>
                            </div>

                            <div class="history-link">
                                <form method="POST" action="?rota=comissao_participante_status" style="display:flex;gap:6px;width:100%;justify-content:flex-end;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="participante_id" value="<?= (int) $part['participante_id'] ?>">
                                    <?php if ($statusAtual !== 'ativo'): ?>
                                        <button type="submit" name="status" value="ativo" class="fp-btn-secondary">Ativar</button>
                                    <?php endif; ?>
                                    <?php if ($statusAtual !== 'inativo'): ?>
                                        <button type="submit" name="status" value="inativo" class="fp-btn-secondary">Inativar</button>
                                    <?php endif; ?>
                                    <?php if ($statusAtual !== 'desistente'): ?>
                                        <button type="submit" name="status" value="desistente" class="fp-btn-danger-sm">Desistente</button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>
