document.addEventListener('DOMContentLoaded', () => {
    const username = document.querySelector('#username');
    const password = document.querySelector('#password');

    document.querySelectorAll('[data-demo-user]').forEach((button) => {
        button.addEventListener('click', () => {
            if (username && password) {
                username.value = button.dataset.demoUser ?? '';
                password.value = button.dataset.demoPassword ?? '';
                password.focus();
            }
        });
    });

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = button.parentElement?.querySelector('input');
            if (!(input instanceof HTMLInputElement)) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            button.setAttribute('aria-label', input.type === 'password' ? 'Show password' : 'Hide password');
        });
    });

    const sidebar = document.querySelector('[data-sidebar]');
    const overlay = document.querySelector('[data-mobile-overlay]');
    const menuToggle = document.querySelector('[data-menu-toggle]');
    const closeMenu = () => {
        sidebar?.classList.remove('sidebar-open');
        overlay?.classList.remove('overlay-visible');
    };
    menuToggle?.addEventListener('click', () => {
        sidebar?.classList.toggle('sidebar-open');
        overlay?.classList.toggle('overlay-visible');
    });
    overlay?.addEventListener('click', closeMenu);

    document.querySelectorAll('[data-dismiss]').forEach((button) => {
        button.addEventListener('click', () => button.parentElement?.remove());
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.getAttribute('data-confirm');
            if (message && !window.confirm(message)) event.preventDefault();
        });
    });
});
