<?php
$primeiroNome = explode(' ', trim($usuarioLogado['nome']))[0];
$iniciais = strtoupper(substr($primeiroNome, 0, 1) . (isset(explode(' ', trim($usuarioLogado['nome']))[1]) ? substr(explode(' ', trim($usuarioLogado['nome']))[1], 0, 1) : ''));
$resumo=$resumoAtual??['receitas'=>0,'despesas'=>0,'saldo'=>0];
$movimentos=array_map(static fn($m)=>[$m['tipo']==='receita'?'entrada':'saida',$m['descricao'],$m['participante']??'', $m['categoria']??'Outros',date('d M. Y',strtotime($m['data_movimento'])),strtoupper($m['meio']),($m['tipo']==='receita'?'+ ':'− ').'R$ '.number_format((float)$m['valor'],2,',','.')],$listaMovimentacoes??[]);
$totalDespesas=$resumo['despesas'];$categorias=array_map(static fn($c)=>[$c['nome'],'R$ '.number_format((float)$c['valor'],2,',','.'),$totalDespesas?max(3,(int)round($c['valor']/$totalDespesas*100)):0],array_filter($categoriasDespesas??[],static fn($c)=>(float)$c['valor']>0));
?>
<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#100d16"><title>Prestação de contas | FormaPay</title><link rel="stylesheet" href="/FormaPay/View/Shared/style.css?v=11"></head>
<body class="dashboard">
<aside class="sidebar">
    <a class="dash-brand" href="?pagina=dashboard"><span class="dash-logo">⌂</span><span><b>Forma<span>Pay</span></b><small>FORMATURA EM DIA</small></span></a>
    <div class="side-caption">ESPAÇO DA TURMA</div><button class="cohort"><b>26</b><span><strong>Formandos 2026</strong><small>3º ano · 32 participantes</small></span><span>⌄</span></button>
    <div class="side-caption">GESTÃO</div><nav class="side-nav"><a href="?pagina=dashboard"><i>▦</i>Visão geral</a><a href="?pagina=movimentacoes"><i>▣</i>Movimentações</a><a href="?pagina=participantes"><i>♙</i>Participantes <em>6</em></a><a class="selected" href="?pagina=relatorios"><i>▤</i>Prestação de contas</a></nav>
    <div class="side-caption">CONFIGURAÇÕES</div><nav class="side-nav"><a href="?pagina=configuracoes"><i>⚙</i>Configurações</a></nav>
    <div class="goal-mini"><span>✧</span><br><b>Rumo à formatura</b><p>Vocês já alcançaram 61% da meta coletiva.</p><div class="mini-track"><i></i></div></div>
    <div class="profile"><span class="avatar"><?= e($iniciais) ?></span><span><b><?= e($usuarioLogado['nome']) ?></b><small>Comissão</small></span><form method="post"><input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>"><button name="acao" value="sair" aria-label="Sair da conta">⇥</button></form></div>
</aside>
<header class="topbar"><label class="search"><span>⌕</span><input placeholder="Buscar participante ou lançamento..." aria-label="Buscar"></label><div class="top-right"><button class="icon-button" aria-label="Notificações">♧<i></i></button><span class="top-divider"></span><span class="avatar"><?= e($iniciais) ?></span><span class="top-user"><b><?= e($primeiroNome) ?></b><small>Comissão</small></span></div></header>
<main class="dash-main report-main">
    <div class="report-heading"><div><p class="eyebrow">TRANSPARÊNCIA</p><h1>Prestação de contas</h1><p>Resumo financeiro por competência, categoria e período.</p></div><button class="primary-action" id="export-report">⇩ &nbsp; Exportar relatório</button></div>
    <section class="period-banner"><div><small>COORTE ATUAL</small><h2><?= e($coorteAtual['nome']) ?></h2><p>Resumo consolidado da movimentação registrada.</p></div><div class="period-balance"><small>Saldo acumulado</small><b>R$ <?= number_format($resumo['saldo'],2,',','.') ?></b><span>Receitas − despesas</span></div></section>
    <section class="report-summaries"><article class="panel financial-summary"><h2>Resumo financeiro</h2><div class="summary-line"><span><i class="dot purple-dot"></i>Total de receitas</span><b>R$ <?= number_format($resumo['receitas'],2,',','.') ?></b></div><div class="summary-line"><span><i class="dot gray-dot"></i>Total de despesas</span><b>R$ <?= number_format($resumo['despesas'],2,',','.') ?></b></div><div class="summary-line total-line"><span>Saldo acumulado</span><b>R$ <?= number_format($resumo['saldo'],2,',','.') ?></b></div><div class="reconciled"><b>✓ &nbsp; Registros financeiros</b><small>Totais calculados com base nos lançamentos ativos da coorte.</small></div></article>
        <article class="panel category-summary"><h2>Despesas por categoria</h2><?php foreach ($categorias as [$categoria,$valor,$width]): ?><div class="category-row"><div><span><?= e($categoria) ?></span><b><?= e($valor) ?></b></div><div class="category-track"><i style="width:<?= $width ?>%"></i></div></div><?php endforeach ?></article></section>
    <section class="panel report-table-panel"><div class="report-table-head"><div><h2>Movimentações do período</h2><p>Documentos e lançamentos de junho</p></div><select id="period-select" aria-label="Competência"><option>Junho 2026</option></select></div><div class="table-scroll"><table class="ledger-table" id="report-table"><thead><tr><th>DESCRIÇÃO</th><th>CATEGORIA</th><th>DATA</th><th>MEIO</th><th>VALOR</th></tr></thead><tbody><?php foreach ($movimentos as [$tipo,$descricao,$pessoa,$categoria,$data,$meio,$valor]): ?><tr><td><span class="row-icon <?= $tipo === 'entrada' ? 'green' : 'red' ?>"><?= $tipo === 'entrada' ? '↙' : '↗' ?></span><span><b><?= e($descricao) ?></b><?php if ($pessoa): ?><small><?= e($pessoa) ?></small><?php endif ?></span></td><td><label class="tag"><?= e($categoria) ?></label></td><td><?= e($data) ?></td><td><?= e($meio) ?></td><td class="<?= $tipo === 'entrada' ? 'money-in' : 'money-out' ?>"><?= e($valor) ?></td></tr><?php endforeach ?></tbody></table></div></section>
</main>
<script>document.getElementById('export-report').addEventListener('click',()=>{const rows=[...document.querySelectorAll('#report-table tr')].map(row=>[...row.cells].map(cell=>'"'+cell.innerText.replace(/\s+/g,' ').trim().replaceAll('"','""')+'"').join(';'));const blob=new Blob(['\ufeff'+rows.join('\r\n')],{type:'text/csv;charset=utf-8'});const url=URL.createObjectURL(blob);const link=document.createElement('a');link.href=url;link.download='formapay-prestacao-de-contas-junho-2026.csv';link.click();URL.revokeObjectURL(url)});document.querySelector('.icon-button').addEventListener('click',()=>alert('Você está em dia com as notificações.'));</script>
</body></html>





