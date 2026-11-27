document.querySelectorAll('[data-copy-code]').forEach((button) => {
    const original = button.innerHTML;
    button.addEventListener('click', async () => {
        const code = button.dataset.copyCode || '';
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(code);
            } else {
                const helper = document.createElement('textarea');
                helper.value = code;
                helper.style.position = 'fixed';
                helper.style.opacity = '0';
                document.body.append(helper);
                helper.select();
                const copied = document.execCommand('copy');
                helper.remove();
                if (!copied) throw new Error('copy failed');
            }
            button.innerHTML = '✓ Código copiado';
            window.setTimeout(() => { button.innerHTML = original; }, 1600);
        } catch (_) {
            button.innerHTML = 'Não foi possível copiar';
            window.setTimeout(() => { button.innerHTML = original; }, 2000);
        }
    });
});
