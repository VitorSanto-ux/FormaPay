(() => {
    const input = document.querySelector('[data-page-search]');
    if (!input) return;

    const main = document.querySelector('.dash-main, .portal-main');
    if (!main) return;

    const items = [...main.querySelectorAll('.participant-card, .settings-card, tbody tr')]
        .filter((item) => !item.querySelector('td[colspan]'));
    const status = document.createElement('div');
    status.className = 'page-search-empty';
    status.setAttribute('aria-live', 'polite');
    status.hidden = true;
    status.innerHTML = '<span class="page-search-empty-icon" aria-hidden="true">⌕</span><span><strong>Nenhum resultado encontrado</strong><small>Tente outro termo ou confira a escrita da pesquisa.</small></span>';
    main.prepend(status);

    input.addEventListener('input', () => {
        const term = input.value.trim().toLocaleLowerCase('pt-BR');
        let visibleCount = 0;

        for (const item of items) {
            const matches = term === '' || item.textContent.toLocaleLowerCase('pt-BR').includes(term);
            item.hidden = !matches;
            if (matches) visibleCount++;
        }

        status.hidden = term === '' || visibleCount > 0;
    });
})();
