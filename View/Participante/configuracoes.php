<section class="fp-card">
    <h2>Informações da Turma — <?= e($turmaAtual['nome_turma'] ?? '') ?></h2>
    <p><strong>Curso / Instituição:</strong> <?= e($turmaAtual['instituicao_curso'] ?? 'Não informado') ?></p>
    <p><strong>Ano da formatura:</strong> <?= e($turmaAtual['ano_formatura'] ?? 'Não informado') ?></p>
    <p><strong>Chave PIX da comissão:</strong> <?= e($configTurma['chave_pix'] ?? 'Não informada') ?></p>
    <p><strong>Favorecido:</strong> <?= e($configTurma['favorecido_pix'] ?? 'Não informado') ?></p>
    <p><strong>Comunicados:</strong> <?= e($configTurma['observacoes'] ?? 'Sem comunicados no momento.') ?></p>
</section>