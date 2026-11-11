<?php
$primeiroNome = explode(' ', trim($usuarioLogado['nome']))[0];
$iniciais = strtoupper(substr($primeiroNome, 0, 1) . (isset(explode(' ', trim($usuarioLogado['nome']))[1]) ? substr(explode(' ', trim($usuarioLogado['nome']))[1], 0, 1) : ''));
$participantes = array_map(static function($p){$nome=$p['nome'];$parts=explode(' ',$nome);$ini=strtoupper(substr($parts[0],0,1).(isset($parts[1])?substr($parts[1],0,1):''));$prev=(float)$p['previsto'];$paid=(float)$p['contribuido'];$status=$paid<=0?'Pendente':($paid+0.001>=$prev?'Em dia':'Parcial');return [$nome,$ini,$status,$paid,$prev,$p['email']??''];},$listaParticipantes??[]);
?>
<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#100d16"><title>Participantes | FormaPay</title><link rel="stylesheet" href="/FormaPay/View/Shared/style.css?v=11"></head>
<body class="dashboard">
<aside class="sidebar">
    <a class="dash-brand" href="?pagina=dashboard"><span class="dash-logo">⌂</span><span><b>Forma<span>Pay</span></b><small>FORMATURA EM DIA</small></span></a>
    <div class="side-caption">ESPAÇO DA TURMA</div><button class="cohort"><b>26</b><span><strong>Formandos 2026</strong><small>3º ano · 32 participantes</small></span><span>⌄</span></button>
    <div class="side-caption">GESTÃO</div><nav class="side-nav"><a href="?pagina=dashboard"><i>▦</i>Visão geral</a><a href="?pagina=movimentacoes"><i>▣</i>Movimentações</a><a class="selected" href="?pagina=participantes"><i>♙</i>Participantes <em>6</em></a><a href="?pagina=relatorios"><i>▤</i>Prestação de contas</a></nav>
    <div class="side-caption">CONFIGURAÇÕES</div><nav class="side-nav"><a href="?pagina=configuracoes"><i>⚙</i>Configurações</a></nav>
    <div class="goal-mini"><span>✧</span><br><b>Rumo à formatura</b><p>Vocês já alcançaram 61% da meta coletiva.</p><div class="mini-track"><i></i></div></div>
    <div class="profile"><span class="avatar"><?= e($iniciais) ?></span><span><b><?= e($usuarioLogado['nome']) ?></b><small>Tesoureiro</small></span><form method="post"><input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>"><button name="acao" value="sair" aria-label="Sair da conta">⇥</button></form></div>
</aside>
<header class="topbar"><label class="search"><span>⌕</span><input id="participant-search" placeholder="Buscar participante ou lançamento..." aria-label="Buscar participante"></label><div class="top-right"><button class="icon-button" aria-label="Notificações">♧<i></i></button><span class="top-divider"></span><span class="avatar"><?= e($iniciais) ?></span><span class="top-user"><b><?= e($primeiroNome) ?></b><small>Comissão</small></span></div></header>
<main class="dash-main participants-main">
    <div class="participants-heading"><div><p class="eyebrow">COORTE <?= e(mb_strtoupper($coorteAtual['nome'])) ?></p><h1>Participantes</h1><p>Adesões, situação financeira e histórico de participação.</p></div><button class="primary-action" onclick="document.getElementById('participant-modal').showModal()">♙ &nbsp; Adicionar participante</button></div>
    <?php if(isset($_GET['salvo'])):?><div class="notice success">Participante adicionado e parcelas mensais geradas.</div><?php endif ?>
    <section class="participant-metrics"><article><span class="metric-icon purple">♙</span><div><small>Participantes ativos</small><b><?= count($participantes) ?></b></div></article><article><span class="metric-icon green">✓</span><div><small>Em dia</small><b><?= count(array_filter($participantes,fn($p)=>$p[2]==='Em dia')) ?></b></div></article><article><span class="metric-icon amber">▤</span><div><small>Com pendências</small><b><?= count(array_filter($participantes,fn($p)=>$p[2]!=='Em dia')) ?></b></div></article></section>
    <section class="panel participant-panel"><div class="participant-panel-head"><div><h2>Participantes da coorte</h2><p>Plano mensal · R$ 150,00 · vencimento dia 10</p></div><select id="status-filter" aria-label="Filtrar por situação"><option value="todas">Situação: todas</option><option value="em-dia">Em dia</option><option value="parcial">Parcial</option><option value="pendente">Pendente</option></select></div>
        <div class="participant-grid" id="participant-grid"><?php foreach ($participantes as [$nome,$sigla,$status,$contribuido,$previsto]): $percentual = (int) round($contribuido / $previsto * 100); $statusClass = $status === 'Em dia' ? 'up-to-date' : ($status === 'Parcial' ? 'partial' : 'pending'); $statusKey = $status === 'Em dia' ? 'em-dia' : strtolower($status); ?><article class="participant-card" data-status="<?= e($statusKey) ?>" data-name="<?= e(mb_strtolower($nome)) ?>"><div class="participant-card-top"><span class="participant-initials"><?= e($sigla) ?></span><span class="status-pill <?= $statusClass ?>"><?= e($status) ?></span></div><h3><?= e($nome) ?></h3><p class="joined">Participante desde fev. 2025</p><div class="contribution-labels"><span><small>Contribuído</small><b>R$ <?= number_format($contribuido, 2, ',', '.') ?></b></span><span><small>Previsto</small><b>R$ <?= number_format($previsto, 2, ',', '.') ?></b></span></div><div class="participant-progress"><i style="width:<?= $percentual ?>%"></i></div><a class="history-link" href="#historico" data-person="<?= e($nome) ?>">Ver histórico <span>›</span></a></article><?php endforeach ?></div>
        <div class="participant-empty" hidden>Nenhum participante encontrado para esse filtro.</div>
    </section>
</main>
<dialog id="participant-modal"><form method="post" class="modal-card"><button type="button" class="modal-close" onclick="this.closest('dialog').close()" aria-label="Fechar">×</button><p class="eyebrow">NOVA ADESÃO</p><h2>Adicionar participante</h2><input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>"><input type="hidden" name="acao" value="novo_participante"><label>Nome completo<input name="nome" required maxlength="120"></label><label>E-mail (opcional)<input name="email" type="email" maxlength="190"></label><label>Contribuição mensal<input name="valor" type="number" min="1" step="0.01" value="150.00" required></label><button class="primary-action">Salvar participante</button></form></dialog>
<dialog id="history-modal"><form method="dialog" class="modal-card"><button class="modal-close" aria-label="Fechar">×</button><p class="eyebrow">HISTÓRICO DE PARTICIPAÇÃO</p><h2 id="history-title">Participante</h2><p>Os detalhes de parcelas e pagamentos aparecerão aqui quando o histórico financeiro estiver conectado.</p><button class="primary-action">Fechar</button></form></dialog>
<script>
const cards=[...document.querySelectorAll('.participant-card')], search=document.getElementById('participant-search'), filter=document.getElementById('status-filter');
function filterCards(){let count=0;cards.forEach(card=>{const matchesStatus=filter.value==='todas'||card.dataset.status===filter.value;const matchesSearch=card.dataset.name.includes(search.value.trim().toLocaleLowerCase('pt-BR'));card.hidden=!(matchesStatus&&matchesSearch);if(!card.hidden)count++});document.querySelector('.participant-empty').hidden=count!==0}
filter.addEventListener('change',filterCards);search.addEventListener('input',filterCards);
document.querySelectorAll('.history-link').forEach(link=>link.addEventListener('click',event=>{event.preventDefault();document.getElementById('history-title').textContent=link.dataset.person;document.getElementById('history-modal').showModal()}));
document.querySelector('.icon-button').addEventListener('click',()=>alert('Você está em dia com as notificações.'));
</script>
</body></html>





