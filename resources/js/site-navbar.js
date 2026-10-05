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

const vehicleMenu = document.querySelector('[data-vehicle-menu]');
const vehicleMenuTrigger = document.querySelector('[data-vehicle-menu-open]');

if (vehicleMenu && vehicleMenuTrigger) {
    const panel = vehicleMenu.querySelector('[data-vehicle-menu-panel]');
    const header = vehicleMenu.closest('header');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let isOpen = false;
    let animation;
    let previousOverflow = '';

    const positionMenu = () => {
        const bottom = header.getBoundingClientRect().bottom;
        vehicleMenu.style.setProperty('--vehicle-menu-top', `${Math.max(0, bottom)}px`);
    };
    const setVehicleMenuOpen = (open, { restoreFocus = false, immediate = false } = {}) => {
        if (open === isOpen && !immediate) return;
        const currentTransform = vehicleMenu.hidden ? 'translateY(-100%)' : getComputedStyle(panel).transform;
        animation?.cancel();
        isOpen = open;
        vehicleMenuTrigger.setAttribute('aria-expanded', String(open));
        if (open) {
            previousOverflow = document.documentElement.style.overflow;
            document.documentElement.style.overflow = 'hidden';
            vehicleMenu.hidden = false;
            panel.inert = false;
            positionMenu();
            panel.querySelector('a')?.focus({ preventScroll: true });
        } else {
            panel.inert = true;
            document.documentElement.style.overflow = previousOverflow;
            if (restoreFocus) {
                const target = vehicleMenuTrigger.closest('[hidden]')
                    ? navbar.querySelector('[data-site-menu]') : vehicleMenuTrigger;
                target.focus({ preventScroll: true });
            }
        }
        if (immediate || reducedMotion.matches) {
            vehicleMenu.hidden = !open;
            return;
        }
        animation = panel.animate([
            { transform: currentTransform },
            { transform: open ? 'translateY(0)' : 'translateY(-100%)' },
        ], {
            duration: open ? 480 : 340,
            easing: open ? 'cubic-bezier(0.22, 1, 0.36, 1)' : 'cubic-bezier(0.4, 0, 0.6, 1)',
            fill: 'forwards',
        });
        animation.onfinish = () => {
            vehicleMenu.hidden = !isOpen;
            animation.cancel();
            animation = undefined;
        };
    };

    vehicleMenuTrigger.addEventListener('click', event => {
        event.preventDefault();
        setVehicleMenuOpen(!isOpen, { restoreFocus: isOpen });
    });
    vehicleMenu.querySelectorAll('[data-vehicle-menu-close]').forEach(button => {
        button.addEventListener('click', () => setVehicleMenuOpen(false, { restoreFocus: true }));
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && isOpen) {
            event.preventDefault();
            setVehicleMenuOpen(false, { restoreFocus: true });
        }
    });
    document.addEventListener('click', event => {
        if (!isOpen || vehicleMenuTrigger.contains(event.target)) return;
        if (event.target.closest('[data-test-drive-open]')) {
            setVehicleMenuOpen(false, { immediate: true });
        } else if (!panel.contains(event.target) || event.target.closest('a')) {
            setVehicleMenuOpen(false);
        }
    }, true);
    document.addEventListener('focusin', event => {
        if (isOpen && !header.contains(event.target)) setVehicleMenuOpen(false);
    });
    window.addEventListener('resize', () => {
        if (isOpen) positionMenu();
    });
}
