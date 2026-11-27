<?php if (!empty($coorteAtual) && (float)($resumoFinanceiro['progresso_percentual'] ?? 0) >= 100): ?>
    <section class="goal-celebration" data-goal-celebration="<?= (int)$coorteAtual['id'] ?>" aria-live="polite">
        <span class="goal-celebration-mark" aria-hidden="true">★</span>
        <div><strong>Meta alcançada!</strong><span>A turma atingiu o objetivo financeiro. Parabéns a todos que contribuíram!</span></div>
        <span class="goal-celebration-value">100%</span>
    </section>
<?php endif; ?>
