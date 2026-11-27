(() => {
  const wrap = document.querySelector('.notification-wrap');
  if (!wrap) return;

  const button = wrap.querySelector('.icon-button');
  const panel = wrap.querySelector('.notification-popover');

  const close = () => {
    panel.hidden = true;
    button.setAttribute('aria-expanded', 'false');
  };

  button.addEventListener('click', () => {
    const opening = panel.hidden;
    panel.hidden = !opening;
    button.setAttribute('aria-expanded', String(opening));
  });

  document.addEventListener('click', (event) => {
    if (!wrap.contains(event.target)) close();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      close();
      button.focus();
    }
  });
})();
