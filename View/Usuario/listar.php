<div class="fp-shell-wrap">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
        <div>
            <p class="eyebrow" style="margin:0 0 6px;">ESCOPO DA TURMA</p>
            <h1 style="margin:0;">Participantes Vinculados<?= $coorteAtual ? ' — ' . e($coorteAtual['nome']) : '' ?></h1>
        </div>
        <?php if (($perfil ?? '') === 'comissao'): ?>
            <a href="?rota=comissao_participantes" class="portal-back"><span>←</span> Gerenciar Participantes</a>
        <?php endif; ?>
    </div>

    <section class="panel ledger-panel">
        <div class="ledger-head">
            <div>
                <h2>Lista Restrita à Turma Ativa</h2>
                <p><?= count($participantesCoorte) ?> membro(s) registrado(s) nesta coorte</p>
            </div>
        </div>
        <div class="table-scroll">
            <table class="ledger-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Status</th>
                        <th>Entrada</th>
                        <th style="text-align:right;">Contribuído</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($participantesCoorte)): ?>
                        <tr><td colspan="5" class="empty-filter">Nenhum participante vinculado a esta turma até o momento.</td></tr>
                    <?php else: ?>
                        <?php foreach ($participantesCoorte as $part): ?>
                            <tr>
                                <td>
                                    <span class="participant-initials"><?= e(iniciais((string) ($part['nome'] ?? 'PT'))) ?></span>
                                    <div>
                                        <b><?= e($part['nome']) ?></b>
                                        <small>ID #<?= (int) $part['participante_id'] ?></small>
                                    </div>
                                </td>
                                <td><?= e($part['email'] ?? '—') ?></td>
                                <td><span class="tag"><?= e(ucfirst((string) $part['status'])) ?></span></td>
                                <td><?= e($part['entrou_em']) ?></td>
                                <td class="money-in"><?= e(moeda((float) ($part['total_contribuido'] ?? 0))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>