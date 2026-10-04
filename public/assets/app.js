document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('backdrop');
    const menu = document.getElementById('menu');

    const setMenuOpen = (open) => {
        sidebar?.classList.toggle('open', open);
        backdrop?.classList.toggle('open', open);
        menu?.setAttribute('aria-expanded', String(open));
        menu?.setAttribute('aria-label', open ? 'Tutup menu navigasi' : 'Buka menu navigasi');
    };

    menu?.addEventListener('click', () => {
        setMenuOpen(menu.getAttribute('aria-expanded') !== 'true');
    });
    backdrop?.addEventListener('click', () => setMenuOpen(false));
    sidebar?.querySelectorAll('.nav-link').forEach((link) => {
        link.addEventListener('click', () => setMenuOpen(false));
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setMenuOpen(false);
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm || 'Lanjutkan?')) event.preventDefault();
        });
    });
});
