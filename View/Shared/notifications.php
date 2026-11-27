<?php
$notificacoesNaoLidas = count(array_filter($notificacoes ?? [], static fn(array $n): bool => empty($n['lida_em'])));
?>
<div class="notification-wrap">
    <button type="button" class="icon-button notification-trigger" aria-label="Notificações" aria-expanded="false"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg><?php if ($notificacoesNaoLidas > 0): ?><i><?= $notificacoesNaoLidas > 9 ? '9+' : $notificacoesNaoLidas ?></i><?php endif; ?></button>
    <section class="notification-popover" hidden aria-label="Notificações recentes">
        <header><div><strong>Notificações</strong><small><?= $notificacoesNaoLidas ?> não lida(s)</small></div>
            <?php if ($notificacoesNaoLidas > 0): ?><form method="POST" action="?rota=notificacoes_marcar_lidas"><?= csrf_field() ?><input type="hidden" name="voltar" value="<?= e($_SERVER['REQUEST_URI'] ?? '?rota=login') ?>"><button type="submit">Marcar como lidas</button></form><?php endif; ?>
        </header>
        <div class="notification-list">
            <?php if (empty($notificacoes)): ?><div class="notification-empty"><b>Nenhuma notificação</b><p>Novidades sobre pagamentos aparecerão aqui.</p></div><?php endif; ?>
            <?php foreach ($notificacoes as $notificacao): ?><a class="notification-entry <?= empty($notificacao['lida_em']) ? 'unread' : '' ?>" href="<?= e($notificacao['link']) ?>"><b><?= e($notificacao['titulo']) ?></b><span><?= e($notificacao['mensagem']) ?></span><small><?= e($notificacao['criada_em']) ?></small></a><?php endforeach; ?>
        </div>
    </section>
</div>
