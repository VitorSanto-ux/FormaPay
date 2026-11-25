<?php
$primeiroNome = explode(' ', trim($usuarioLogado['nome']))[0];
$iniciais = strtoupper(substr($primeiroNome, 0, 1) . (isset(explode(' ', trim($usuarioLogado['nome']))[1]) ? substr(explode(' ', trim($usuarioLogado['nome']))[1], 0, 1) : ''));
$movimentos = array_map(static fn($m) => [$m['tipo'] === 'receita' ? 'entrada' : 'saida', $m['descricao'], $m['participante'] ?? '', $m['categoria'] ?? 'Outros', date('d M. Y', strtotime($m['data_movimento'])), strtoupper($m['meio']), ($m['tipo'] === 'receita' ? '+ ' : '− ') . 'R$ ' . number_format((float) $m['valor'], 2, ',', '.')], $listaMovimentacoes ?? []);
$resumo = $resumoAtual ?? ['receitas' => 0, 'despesas' => 0, 'saldo' => 0];
$metaAtual = (float) $coorteAtual['meta'];
$progressoAtual = progressoMetaColetiva((float) $resumo['saldo'], $metaAtual);
$percentualAtual = $progressoAtual['percentual'];
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#100d16">
    <title>Movimentações | FormaPay</title>
    <link rel="stylesheet" href="/FormaPay/View/Shared/style.css?v=17">
</head>

<body class="dashboard">
    <aside class="sidebar">
        <a class="dash-brand" href="?pagina=dashboard"><img class="dash-logo-img" src="/FormaPay/View/Shared/logo-formapay.png" alt=""><span><b>Forma<span>Pay</span></b><small>FORMATURA EM DIA</small></span></a>
        <div class="side-caption">ESPAÇO DA TURMA</div><button class="cohort"><b>26</b><span><strong>Formandos
                    2026</strong><small>3º ano · 32 participantes</small></span><span>⌄</span></button>
        <div class="side-caption">GESTÃO</div>
        <nav class="side-nav"><a href="?pagina=dashboard"><i>▦</i>Visão geral</a><a class="selected"
                href="?pagina=movimentacoes"><i>▣</i>Movimentações</a><a
                href="?pagina=participantes"><i>♙</i>Participantes <em>6</em></a><a
                href="?pagina=relatorios"><i>▤</i>Prestação de contas</a></nav>
        <div class="side-caption">CONFIGURAÇÕES</div>
        <nav class="side-nav"><a href="?pagina=configuracoes"><i>⚙</i>Configurações</a></nav>
        <div class="goal-mini"><span>✧</span><b> Rumo à formatura</b>
            <p><?= $resumo['saldo'] < 0 ? 'Saldo negativo de R$ ' . number_format(abs((float) $resumo['saldo']), 2, ',', '.') : ($percentualAtual . '% da meta coletiva alcançada.') ?>
            </p>
            <div class="mini-track"><i style="width:<?= $percentualAtual ?>%"></i></div>
        </div>
        <div class="profile"><span
                class="avatar"><?= e($iniciais) ?></span><span><b><?= e($usuarioLogado['nome']) ?></b><small>Tesoureiro</small></span>
            <form method="post"><input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>"><button name="acao"
                    value="sair" aria-label="Sair da conta">⇥</button></form>
        </div>
    </aside>
    <header class="topbar"><label class="search"><span>⌕</span><input placeholder="Buscar participante ou lançamento..."
                aria-label="Buscar"></label>
        <div class="top-right"><button class="icon-button" aria-label="Notificações">♧<i></i></button><span
                class="top-divider"></span><span class="avatar"><?= e($iniciais) ?></span><span
                class="top-user"><b><?= e($primeiroNome) ?></b><small>Comissão</small></span></div>
    </header>
    <main class="dash-main ledger-main">
        <div class="ledger-heading">
            <div>
                <p class="eyebrow">CAIXA</p>
                <h1>Movimentações</h1>
                <p>Receitas, contribuições e despesas registradas pela comissão.</p>
            </div><button type="button" class="primary-action" id="open-movement">＋ &nbsp; Nova movimentação</button>
        </div>
        <?php if (isset($_GET['salvo'])): ?>
            <div class="notice success">Movimentação salva.</div><?php endif ?>
        <section class="ledger-metrics">
            <article class="ledger-metric"><span class="metric-icon green">↙</span>
                <div><small>Receitas</small><b>R$ <?= number_format($resumo['receitas'], 2, ',', '.') ?></b></div>
            </article>
            <article class="ledger-metric"><span class="metric-icon red">↗</span>
                <div><small>Despesas</small><b>R$ <?= number_format($resumo['despesas'], 2, ',', '.') ?></b></div>
            </article>
            <article class="ledger-metric"><span class="metric-icon purple">＄</span>
                <div><small>Saldo calculado</small><b>R$ <?= number_format($resumo['saldo'], 2, ',', '.') ?></b></div>
            </article>
        </section>
        <section class="panel ledger-panel">
            <div class="ledger-head">
                <div>
                    <h2>Histórico de caixa</h2>
                    <p id="count-label">6 registros encontrados</p>
                </div><select id="movement-filter" aria-label="Filtrar movimentações">
                    <option value="todos">Todos os tipos</option>
                    <option value="entrada">Receitas</option>
                    <option value="saida">Despesas</option>
                </select>
            </div>
            <div class="table-scroll">
                <table class="ledger-table">
                    <thead>
                        <tr>
                            <th>DESCRIÇÃO</th>
                            <th>CATEGORIA</th>
                            <th>DATA</th>
                            <th>MEIO</th>
                            <th>VALOR</th>
                        </tr>
                    </thead>
                    <tbody><?php foreach ($movimentos as [$tipo, $descricao, $pessoa, $categoria, $data, $meio, $valor]): ?>
                            <tr data-tipo="<?= e($tipo) ?>">
                                <td><span
                                        class="row-icon <?= $tipo === 'entrada' ? 'green' : 'red' ?>"><?= $tipo === 'entrada' ? '↙' : '↗' ?></span><span><b><?= e($descricao) ?></b><?php if ($pessoa): ?><small><?= e($pessoa) ?></small><?php endif ?></span>
                                </td>
                                <td><label class="tag"><?= e($categoria) ?></label></td>
                                <td><?= e($data) ?></td>
                                <td><?= e($meio) ?></td>
                                <td class="<?= $tipo === 'entrada' ? 'money-in' : 'money-out' ?>"><?= e($valor) ?></td>
                            </tr><?php endforeach ?>
                    </tbody>
                </table>
                <div class="empty-filter" hidden>Nenhuma movimentação neste filtro.</div>
            </div>
        </section>
    </main>
    <dialog id="movement-modal">
        <form method="post" class="modal-card"><button type="button" class="modal-close"
                onclick="this.closest('dialog').close()" aria-label="Fechar">×</button>
            <p class="eyebrow">REGISTRO FINANCEIRO</p>
            <h2>Nova movimentação</h2><input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>"><input
                type="hidden" name="acao" value="nova_movimentacao"><label>Tipo<select name="tipo">
                    <option value="receita">Receita / contribuição</option>
                    <option value="despesa">Despesa</option>
                </select></label><label>Descrição<input name="descricao" required
                    maxlength="180"></label><label>Valor<input name="valor" type="number" min="0.01" step="0.01"
                    required></label><label>Data<input name="data" type="date" value="<?= date('Y-m-d') ?>"
                    required></label><label>Categoria<select name="categoria"
                    required><?php foreach ($categorias as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>"><?= e($cat['nome']) ?></option><?php endforeach ?>
                </select></label><label>Participante (para contribuição)<select name="participante_id">
                    <option value="">Sem associação individual</option><?php foreach ($participantesAtivos as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"><?= e($p['nome']) ?></option><?php endforeach ?>
                </select></label><label>Parcela (opcional, concilia pagamento)<select name="parcela_id">
                    <option value="">Sem parcela</option><?php foreach ($parcelasAbertas as $parcela): ?>
                        <option value="<?= (int) $parcela['id'] ?>"><?= e($parcela['nome']) ?> ·
                            <?= date('m/Y', strtotime($parcela['competencia'])) ?> · falta R$
                            <?= number_format(max(0, (float) $parcela['valor'] - (float) $parcela['pago']), 2, ',', '.') ?></option>
                    <?php endforeach ?>
                </select></label><label>Meio<select name="meio">
                    <option value="pix">PIX</option>
                    <option value="dinheiro">Dinheiro</option>
                    <option value="transferencia">Transferência</option>
                    <option value="outro">Outro</option>
                </select></label><button class="primary-action">Salvar movimentação</button>
        </form>
    </dialog>
    <script>document.getElementById('open-movement').addEventListener('click', () => { const dialog = document.getElementById('movement-modal'); if (typeof dialog.showModal === 'function') dialog.showModal(); else dialog.setAttribute('open', '') }); const filter = document.getElementById('movement-filter'); filter.addEventListener('change', () => { let shown = 0; document.querySelectorAll('.ledger-table tbody tr').forEach(row => { const visible = filter.value === 'todos' || row.dataset.tipo === filter.value; row.hidden = !visible; if (visible) shown++ }); document.getElementById('count-label').textContent = shown + ' registros encontrados'; document.querySelector('.empty-filter').hidden = shown !== 0 }); document.querySelector('.icon-button').addEventListener('click', () => alert('Você está em dia com as notificações.'));</script>
</body>

</html>