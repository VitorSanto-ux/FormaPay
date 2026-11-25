<?php $ini = strtoupper(substr($usuarioAtual['nome'], 0, 1)); ?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Meu perfil | FormaPay</title>
    <link rel="stylesheet" href="/FormaPay/View/Shared/style.css?v=17">
</head>

<body class="participant-portal">
    <header class="portal-header">
        <div class="portal-header-inner"><a class="dash-brand" href="index.php?pagina=participante"><img class="dash-logo-img" src="/FormaPay/View/Shared/logo-formapay.png" alt=""><span><b>Forma<span>Pay</span></b><small>FORMATURA EM
                        DIA</small></span></a><a class="portal-back" href="index.php?pagina=participante"><span
                    aria-hidden="true">←</span> Voltar ao meu espaço</a></div>
    </header>
    <main class="portal-main">
        <p class="eyebrow">MINHA CONTA</p>
        <h1>Configurações do perfil</h1><?php if (isset($_GET['perfil'])): ?>
            <div class="notice success">Perfil atualizado.</div><?php endif ?>
        <section class="panel settings-card participant-settings">
            <h2>Dados pessoais</h2>
            <form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf"
                    value="<?= e($_SESSION['csrf']) ?>"><input type="hidden" name="acao" value="salvar_perfil">
                <div class="profile-preview"><?php if ($usuarioAtual['foto_perfil']): ?><img
                            src="<?= e($usuarioAtual['foto_perfil']) ?>" alt="Foto do perfil"><?php else: ?><span
                            class="avatar"><?= e($ini) ?></span><?php endif ?><span>Foto PNG, JPG ou WEBP (máximo 2
                        MB).</span></div><label>Nome<input name="nome" value="<?= e($usuarioAtual['nome']) ?>"
                        required></label><label>E-mail<input type="email" name="email"
                        value="<?= e($usuarioAtual['email']) ?>" required></label>
                <div class="upload-preview" id="photo-preview" hidden><img alt="Prévia da nova foto"><span>Prévia da
                        nova foto</span></div><label>Alterar foto<input id="foto_perfil" type="file" name="foto_perfil"
                        accept="image/png,image/jpeg,image/webp"></label><button class="primary-action">Salvar
                    alterações</button>
            </form>
        </section>
    </main>
    <script>document.getElementById("foto_perfil").addEventListener("change", e => { const file = e.target.files[0], box = document.getElementById("photo-preview"); if (!file) { box.hidden = true; return } const reader = new FileReader(); reader.onload = () => { box.querySelector("img").src = reader.result; box.hidden = false }; reader.readAsDataURL(file) });</script>
</body>

</html>