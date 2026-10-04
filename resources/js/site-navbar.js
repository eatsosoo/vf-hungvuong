const navbar = document.querySelector('[data-site-navbar]');

if (navbar) {
    const toggle = navbar.querySelector('[data-site-menu]');
    const navigation = navbar.querySelector('[data-site-navigation]');
    const desktop = window.matchMedia('(min-width: 1024px)');
    const setOpen = open => {
        toggle.setAttribute('aria-expanded', String(open));
        navigation.hidden = !desktop.matches && !open;
    };
    setOpen(false);
    toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
    desktop.addEventListener('change', () => setOpen(false));
    navigation.querySelectorAll('a').forEach(link => link.addEventListener('click', () => setOpen(false)));
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            setOpen(false);
            toggle.focus();
        }
    });
}

const languagePicker = document.querySelector('[data-language-switcher]');

if (languagePicker) {
    document.addEventListener('click', event => {
        if (!languagePicker.contains(event.target)) languagePicker.open = false;
    });
    languagePicker.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            languagePicker.open = false;
            languagePicker.querySelector('summary').focus();
        }
    });
}
