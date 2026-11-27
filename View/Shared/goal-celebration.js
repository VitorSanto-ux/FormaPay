document.querySelectorAll('[data-goal-celebration]').forEach((banner) => {
    const key = `formapay-goal-celebrated-v2-${banner.dataset.goalCelebration}`;
    let alreadyCelebrated = false;
    try {
        alreadyCelebrated = sessionStorage.getItem(key) === '1';
        if (!alreadyCelebrated) sessionStorage.setItem(key, '1');
    } catch (_) { /* A animação continua sem armazenamento local. */ }
    if (alreadyCelebrated || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    banner.classList.add('is-celebrating');
    const colors = ['#7b35dc', '#b486f3', '#38a879', '#f0bb52', '#e879a9'];
    for (let i = 0; i < 26; i++) {
        const piece = document.createElement('span');
        piece.className = 'goal-confetti';
        piece.style.setProperty('--x', `${Math.random() * 100}%`);
        piece.style.setProperty('--color', colors[i % colors.length]);
        piece.style.setProperty('--rotation', `${Math.random() * 360}deg`);
        piece.style.setProperty('--delay', `${Math.random() * 0.55}s`);
        piece.style.setProperty('--duration', `${1.25 + Math.random() * 0.8}s`);
        banner.append(piece);
        piece.addEventListener('animationend', () => piece.remove(), { once: true });
    }
});
