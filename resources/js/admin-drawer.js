import { syncSelects } from './select.js';

document.querySelectorAll('[data-admin-drawer]').forEach(drawer => {
    if (typeof drawer.showModal !== 'function') return;
    const form = drawer.querySelector('form');
    let opener;
    let previousOverflow;
    const open = () => {
        previousOverflow = document.documentElement.style.overflow;
        document.documentElement.style.overflow = 'hidden';
        drawer.showModal();
        const invalid = form.querySelector('[aria-invalid="true"]');
        const focus = invalid?.closest('.project-select')?.querySelector('.project-select-trigger')
            ?? invalid ?? form.querySelector('input:not([type="hidden"]), .project-select-trigger, select');
        focus?.focus();
    };
    const close = () => drawer.close();
    drawer.querySelector('[data-drawer-close]').addEventListener('click', close);
    drawer.querySelectorAll('[data-drawer-cancel]').forEach(button => button.addEventListener('click', close));
    drawer.addEventListener('click', event => {
        if (event.target !== drawer) return;
        const bounds = drawer.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right
            || event.clientY < bounds.top || event.clientY > bounds.bottom) close();
    });
    drawer.addEventListener('close', () => {
        document.documentElement.style.overflow = previousOverflow;
        opener?.focus();
    });
    document.querySelectorAll('[data-drawer-open]').forEach(button => {
        if (button.dataset.drawerOpen !== drawer.id) return;
        button.addEventListener('click', event => {
            if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            opener = button;
            form.reset();
            drawer.querySelectorAll('.error, [data-drawer-errors]').forEach(error => error.remove());
            form.querySelectorAll('[aria-invalid]').forEach(field => {
                field.setAttribute('aria-invalid', 'false');
                const descriptions = (field.getAttribute('aria-describedby') ?? '')
                    .split(' ').filter(id => id && !id.endsWith('-error'));
                if (descriptions.length) field.setAttribute('aria-describedby', descriptions.join(' '));
                else field.removeAttribute('aria-describedby');
            });
            form.action = button.dataset.drawerAction;
            drawer.querySelector('[data-drawer-title]').textContent = button.dataset.drawerTitle;
            const values = JSON.parse(button.dataset.drawerValues);
            Object.entries(values).forEach(([name, value]) => {
                const field = form.elements.namedItem(name);
                if (field) field.value = value ?? '';
            });
            const editing = Boolean(values._record);
            form.querySelector('[name="_method"]').disabled = !editing;
            form.querySelectorAll('input[type="password"]').forEach(field => {
                field.required = !editing;
                field.closest('.field').querySelectorAll('.field-label > span')
                    .forEach(label => { label.hidden = editing; });
            });
            const passwordHint = form.querySelector('[data-drawer-password-hint]');
            if (passwordHint) passwordHint.hidden = !editing;
            form.querySelector('[data-slug-source]')?.dispatchEvent(new Event('input', { bubbles: true }));
            syncSelects(drawer);
            open();
        });
    });
    if (drawer.dataset.autoOpen === 'true') {
        const url = new URL(window.location.href);
        url.searchParams.delete('drawer');
        window.history.replaceState(window.history.state, '', url);
        open();
    }
});
