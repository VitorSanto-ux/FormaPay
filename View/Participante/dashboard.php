<?php
$nome = trim($usuarioLogado['nome'] ?? 'Ana Luiza');
$primeiroNome = explode(' ', $nome)[0];
$iniciais = strtoupper(substr($primeiroNome, 0, 1) . (isset(explode(' ', $nome)[1]) ? substr(explode(' ', $nome)[1], 0, 1) : ''));
$parcelasAtuais = $parcelasAtuais ?? [];
$listaMovimentacoes = $listaMovimentacoes ?? [];
$movimentacoesValidas = array_filter($listaMovimentacoes, static fn($m) => ($m['tipo'] ?? '') === 'receita' && empty($m['estorno_de']));
$totalPago = array_sum(array_map(static fn($m) => (float) $m['valor'], $movimentacoesValidas));
$proxima = null;
foreach ($parcelasAtuais as $parcela) {
    if ((float) $parcela['pago'] + 0.001 < (float) $parcela['valor']) {
        $proxima = $parcela;
        break;
    }
}
$pagamentos = array_map(static fn($m) => [$m['descricao'], $m['participante'] ?? '', $m['categoria'] ?? 'Contribuição', date('d M. Y', strtotime($m['data_movimento'])), strtoupper($m['meio']), '+ R$ ' . number_format((float) $m['valor'], 2, ',', '.')], $movimentacoesValidas);
$notificacoes = [];
if (!empty($erro))
    $notificacoes[] = ['tipo' => 'erro', 'titulo' => 'Não foi possível concluir', 'mensagem' => $erro];
if (isset($_GET['contribuicao']))
    $notificacoes[] = ['tipo' => 'sucesso', 'titulo' => 'Contribuição registrada', 'mensagem' => 'O lançamento foi adicionado ao caixa da turma.'];
$hoje = strtotime(date('Y-m-d'));
foreach ($parcelasAtuais as $parcela) {
    $restante = max(0, (float) $parcela['valor'] - (float) $parcela['pago']);
    if ($restante <= 0.001 || ($parcela['status'] ?? '') === 'cancelada')
        continue;
    $vencimento = strtotime(date('Y-m-d', strtotime($parcela['vencimento'])));
    $dias = (int) (($vencimento - $hoje) / 86400);
    if ($dias < 0)
        $notificacoes[] = ['tipo' => 'alerta', 'titulo' => 'Parcela vencida · ' . date('m/Y', strtotime($parcela['competencia'])), 'mensagem' => 'Saldo de R$ ' . number_format($restante, 2, ',', '.') . ' em aberto.'];
    elseif ($dias <= 30)
        $notificacoes[] = ['tipo' => 'lembrete', 'titulo' => $dias === 0 ? 'Parcela vence hoje' : 'Vencimento próximo · ' . date('m/Y', strtotime($parcela['competencia'])), 'mensagem' => ($dias === 0 ? 'Vence hoje.' : 'Vence em ' . $dias . ' dias.') . ' Valor restante: R$ ' . number_format($restante, 2, ',', '.') . '.'];
}
$resumoColetivo = $resumoColetivo ?? ['receitas' => 0, 'despesas' => 0, 'saldo' => 0];
$metaColetiva = (float) $coorteAtual['meta'];
$progressoColetivo = progressoMetaColetiva((float) $resumoColetivo['saldo'], $metaColetiva);
$pctColetivo = $progressoColetivo['percentual'];
$saldoColetivo = (float) $resumoColetivo['saldo']; ?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#100d16">
    <title>Meu espaço | FormaPay</title>
    <link rel="stylesheet" href="/FormaPay/View/Shared/style.css?v=18">
</head>

<body class="participant-portal">
    <header class="portal-header">
        <div class="portal-header-inner"><a class="dash-brand" href="?pagina=participante"><img class="dash-logo-img" src="/FormaPay/View/Shared/logo-formapay.png" alt=""><span><b>Forma<span>Pay</span></b><small>FORMATURA EM
                        DIA</small></span></a>
            <div class="portal-tools"><a class="portal-config" href="index.php?pagina=configuracoes"
                    aria-label="Abrir configurações do perfil">Configurações</a><button class="icon-button"
                    id="notifications-button" type="button" aria-label="Ir para notificações"
                    aria-controls="notifications-panel">♧<i></i></button><?php if (!empty($usuarioAtual['foto_perfil'])): ?><img
                        class="avatar avatar-photo" src="<?= e($usuarioAtual['foto_perfil']) ?>" alt=""><?php else: ?><span
                        class="avatar"><?= e($iniciais) ?></span><?php endif ?><span
                    class="top-user"><b><?= e($primeiroNome) ?></b><small>Participante</small></span>
                <form method="post"><input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>"><button
                        class="portal-logout" name="acao" value="sair" aria-label="Sair">⇥</button></form>
            </div>
        </div>
    </header>
    <main class="portal-main">
        <div class="portal-title portal-hero">
            <div>
                <p class="eyebrow">MEU ESPAÇO</p>
                <h1>Olá, <?= e($primeiroNome) ?>.</h1>
                <p>Acompanhe suas contribuições para a formatura.</p>
            </div>
            <div class="portal-cohort"><b>26</b><span><strong>Formandos 2026</strong><small>3º ano · mesma coorte desde
                        2025</small></span></div>
        </div>
        <div class="privacy-banner"><span>♢</span>
            <div><b>Seus dados são privados</b><small>Você está visualizando somente suas próprias contribuições. Dados
                    financeiros de outros participantes não são exibidos.</small></div>
        </div>
        <section class="panel notification-center" id="notifications-panel" tabindex="-1"
            aria-labelledby="notifications-title">
            <div class="notification-heading">
                <div>
                    <p class="eyebrow">CENTRAL DE AVISOS</p>
                    <h2 id="notifications-title">Notificações</h2>
                </div><span class="notification-count"><?= count($notificacoes) ?></span>
            </div><?php if ($notificacoes): ?>
                <ul class="notification-list"><?php foreach ($notificacoes as $notificacao): ?>
                        <li class="notification-item <?= e($notificacao['tipo']) ?>"><span
                                class="notification-symbol"><?= $notificacao['tipo'] === 'erro' ? '!' : ($notificacao['tipo'] === 'alerta' ? '⚠' : ($notificacao['tipo'] === 'sucesso' ? '✓' : '◷')) ?></span>
                            <div><b><?= e($notificacao['titulo']) ?></b>
                                <p><?= e($notificacao['mensagem']) ?></p>
                            </div>
                            <?php if (str_contains($notificacao['tipo'], 'alerta') || $notificacao['tipo'] === 'lembrete'): ?><a
                                    href="#contribuir">Contribuir</a><?php endif ?>
                        </li><?php endforeach ?>
                </ul><?php else: ?>
                <div class="notification-empty"><span>✓</span>
                    <div><b>Nenhuma notificação no momento</b>
                        <p>Avisos de vencimento e atualizações da sua conta aparecerão aqui.</p>
                    </div>
                </div><?php endif ?>
        </section>
        <section class="portal-metrics">
            <article><small>Total contribuído</small><b>R$ <?= number_format($totalPago, 2, ',', '.') ?></b><span>✓
                    &nbsp;<?= count(array_filter($parcelasAtuais, fn($p) => (float) $p['pago'] + 0.001 >= (float) $p['valor'])) ?>
                    parcelas quitadas</span></article>
            <article><small>Próximo
                    vencimento</small><b><?= $proxima ? date('d M. Y', strtotime($proxima['vencimento'])) : 'Sem parcelas' ?></b><span>▦
                    &nbsp;Mensalidade de R$ <?= number_format((float) ($proxima['valor'] ?? 0), 2, ',', '.') ?></span>
            </article>
            <article><small>Situação atual</small><b
                    class="on-track"><?= $proxima && strtotime($proxima['vencimento']) < strtotime('today') ? 'Pendente' : 'Em dia' ?></b><span><?= $proxima && strtotime($proxima['vencimento']) < strtotime('today') ? '• Parcela vencida' : '✓ &nbsp;Nenhuma pendência' ?></span>
            </article>
        </section>
        <section class="portal-plan-row">
            <article class="panel portal-plan">
                <div class="portal-section-head">
                    <div>
                        <h2>Plano de contribuição</h2>
                        <p>Mensal · <?= count($parcelasAtuais) ?> parcelas previstas</p>
                    </div><span class="status-pill up-to-date">ATIVO</span>
                </div>
                <?php $qtdPagas = count(array_filter($parcelasAtuais, fn($p) => (float) $p['pago'] + 0.001 >= (float) $p['valor']));
                $pct = count($parcelasAtuais) ? (int) round($qtdPagas / count($parcelasAtuais) * 100) : 0 ?>
                <div class="plan-progress-head"><span><?= $qtdPagas ?> de <?= count($parcelasAtuais) ?>
                        parcelas</span><b><?= $pct ?>% concluído</b></div>
                <div class="plan-progress"><i style="width:<?= $pct ?>%"></i></div><?php if ($proxima): ?>
                    <div class="next-due">
                        <div class="due-date">
                            <b><?= mb_strtoupper(date('M', strtotime($proxima['vencimento']))) ?></b><strong><?= date('d', strtotime($proxima['vencimento'])) ?></strong>
                        </div>
                        <div><small>Próxima parcela</small><b>R$
                                <?= number_format((float) $proxima['valor'], 2, ',', '.') ?></b><small>Competência
                                <?= date('m/Y', strtotime($proxima['competencia'])) ?></small></div><span
                            class="due-status">A vencer</span>
                    </div><?php endif ?>
            </article>
            <article class="collective-goal"><small>META COLETIVA</small>
                <h3>Progresso pelo saldo líquido</h3><strong><?= $pctColetivo ?>%</strong>
                <div class="collective-progress"><i style="width:<?= $pctColetivo ?>%"></i></div>
                <p class="collective-balance">Saldo líquido: R$ <?= number_format($saldoColetivo, 2, ',', '.') ?> ·
                    Meta:
                    R$ <?= number_format($metaColetiva, 2, ',', '.') ?></p>
                <p>Faltam R$ <?= number_format($progressoColetivo['faltante'], 2, ',', '.') ?> para a meta da formatura.
                </p>
            </article>
        </section>
        <section class="panel contribution-card" id="contribuir">
            <div>
                <p class="eyebrow">CONTRIBUIÇÃO</p>
                <h2>Contribuir ou adiantar uma parcela</h2>
                <p>Registre um pagamento que você já realizou. O valor será lançado no caixa da turma.</p>
            </div>
            <form method="post" class="contribution-form"><input type="hidden" name="csrf"
                    value="<?= e($_SESSION['csrf']) ?>"><input type="hidden" name="acao"
                    value="registrar_contribuicao"><label>Destino da contribuição<select name="parcela_id"
                        id="contribution-parcel">
                        <option value="">Contribuição extra (sem parcela)</option>
                        <?php foreach ($parcelasAtuais as $parcela):
                            $restante = max(0, (float) $parcela['valor'] - (float) $parcela['pago']);
                            if ($restante <= 0.001 || ($parcela['status'] ?? '') === 'cancelada')
                                continue ?>
                                <option value="<?= (int) $parcela['id'] ?>"
                                data-restante="<?= number_format($restante, 2, '.', '') ?>">
                                <?= date('m/Y', strtotime($parcela['competencia'])) ?> · falta R$
                                <?= number_format($restante, 2, ',', '.') ?>
                                <?= strtotime($parcela['vencimento']) > strtotime('today') ? ' · adiantamento' : '' ?>
                            </option><?php endforeach ?>
                    </select></label><label>Valor (R$)<input type="number" name="valor" id="contribution-amount"
                        min="0.01" step="0.01" required placeholder="Ex.: 150,00"></label><label>Como você pagou?<select
                        name="meio">
                        <option value="pix">PIX</option>
                        <option value="transferencia">Transferência</option>
                        <option value="dinheiro">Dinheiro</option>
                        <option value="outro">Outro</option>
                    </select></label><button class="primary-action" type="submit">Registrar contribuição</button></form>
            <small class="contribution-note">Este registro não realiza cobrança. Faça o pagamento pelo meio combinado
                com a comissão antes de confirmar.</small>
        </section>
        <section class="panel portal-history">
            <div class="portal-section-head">
                <div>
                    <h2>Meu histórico de pagamentos</h2>
                    <p>Somente contribuições associadas ao seu cadastro</p>
                </div><button id="download-statement">⇩ &nbsp; Baixar extrato</button>
            </div>
            <div class="table-scroll">
                <table class="ledger-table" id="payment-table">
                    <thead>
                        <tr>
                            <th>DESCRIÇÃO</th>
                            <th>CATEGORIA</th>
                            <th>DATA</th>
                            <th>MEIO</th>
                            <th>VALOR</th>
                        </tr>
                    </thead>
                    <tbody><?php foreach ($pagamentos as [$descricao, $pessoa, $categoria, $data, $meio, $valor]): ?>
                            <tr>
                                <td><span
                                        class="row-icon green">↙</span><span><b><?= e($descricao) ?></b><small><?= e($pessoa) ?></small></span>
                                </td>
                                <td><label class="tag"><?= e($categoria) ?></label></td>
                                <td><?= e($data) ?></td>
                                <td><?= e($meio) ?></td>
                                <td class="money-in"><?= e($valor) ?></td>
                            </tr><?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <script>document.getElementById('notifications-button').addEventListener('click', () => { const panel = document.getElementById('notifications-panel'); panel.scrollIntoView({ behavior: 'smooth', block: 'center' }); panel.focus({ preventScroll: true }); panel.classList.add('notification-highlight'); setTimeout(() => panel.classList.remove('notification-highlight'), 1200) }); const contributionParcel = document.getElementById('contribution-parcel'), contributionAmount = document.getElementById('contribution-amount'); contributionParcel.addEventListener('change', () => { const option = contributionParcel.selectedOptions[0]; if (option.dataset.restante) { contributionAmount.value = option.dataset.restante; contributionAmount.max = option.dataset.restante } else { contributionAmount.value = ''; contributionAmount.removeAttribute('max') } }); document.getElementById('download-statement').addEventListener('click', () => { const rows = [...document.querySelectorAll('#payment-table tr')].map(r => [...r.cells].map(c => '"' + c.innerText.replace(/\s+/g, ' ').trim().replaceAll('"', '""') + '"').join(';')); const blob = new Blob(['\ufeff' + rows.join('\r\n')], { type: 'text/csv;charset=utf-8' }); const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'meu-extrato-formapay.csv'; a.click(); URL.revokeObjectURL(a.href) });</script>
</body>

</html>
