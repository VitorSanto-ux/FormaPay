document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.togglePassword);
        if (!input) return;
        const showing = input.type === 'password';
        input.type = showing ? 'text' : 'password';
        button.textContent = showing ? 'Ocultar' : 'Mostrar';
        button.setAttribute('aria-label', showing ? 'Ocultar senha' : 'Mostrar senha');
        button.setAttribute('aria-pressed', String(showing));
    });
});
