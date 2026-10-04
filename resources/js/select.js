const controls = new WeakMap();
let sequence = 0;

export function syncSelects(root = document) {
    root.querySelectorAll('select').forEach(select => controls.get(select)?.sync());
}

function enhanceSelect(select) {
    if (controls.has(select) || !HTMLElement.prototype.showPopover) return;
    if (select.closest('.honeypot, [data-native-select]')) return;

    const clonedWrapper = select.closest('.project-select');
    if (clonedWrapper) {
        clonedWrapper.replaceWith(select);
        select.classList.remove('project-select-native');
        select.removeAttribute('aria-hidden');
        select.removeAttribute('tabindex');
    }

    const id = `project-select-${++sequence}`;
    const wrapper = document.createElement('div');
    wrapper.className = 'project-select';
    if (getComputedStyle(select).width !== getComputedStyle(select.parentElement).width
        && ['flex', 'inline-flex'].includes(getComputedStyle(select.parentElement).display)) {
        wrapper.dataset.compact = 'true';
    }
    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'project-select-trigger';
    trigger.id = `${id}-trigger`;
    trigger.setAttribute('role', 'combobox');
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    trigger.setAttribute('aria-controls', `${id}-options`);
    const label = select.getAttribute('aria-label') || [...select.labels].map(element => {
        const copy = element.cloneNode(true);
        copy.querySelectorAll('select, .error, .field-hint').forEach(child => child.remove());
        return copy.textContent.trim().replace(/\s+/g, ' ');
    }).join(' ') || select.name;
    trigger.setAttribute('aria-label', label);
    if (select.hasAttribute('aria-labelledby')) {
        trigger.setAttribute('aria-labelledby', select.getAttribute('aria-labelledby'));
    }
    const value = document.createElement('span');
    value.className = 'project-select-value';
    trigger.append(value);

    const popup = document.createElement('div');
    popup.className = 'project-select-options';
    popup.id = `${id}-options`;
    popup.setAttribute('popover', 'auto');
    popup.setAttribute('role', 'listbox');
    popup.setAttribute('aria-label', label);
    if (select.multiple) popup.setAttribute('aria-multiselectable', 'true');
    const error = document.createElement('small');
    error.className = 'project-select-error';
    error.id = `${id}-error`;
    error.setAttribute('role', 'alert');
    error.hidden = true;

    select.before(wrapper);
    wrapper.append(select, trigger, popup, error);
    select.classList.add('project-select-native');
    select.setAttribute('aria-hidden', 'true');
    select.tabIndex = -1;
    if (select.multiple) {
        const hint = select.closest('.field')?.querySelector('.field-hint');
        if (hint) hint.textContent = document.documentElement.lang === 'en'
            ? 'Click each option to select or deselect it.' : 'Nhấn từng mục để chọn hoặc bỏ chọn.';
    }
    let options = [];
    let activeIndex = -1;
    let query = '';
    let lastTyped = 0;
    const isOpen = () => popup.matches(':popover-open');
    const close = () => { if (isOpen()) popup.hidePopover(); };
    const sync = () => {
        value.textContent = [...select.selectedOptions].map(option => option.textContent.trim()).join(', ') || '—';
        trigger.disabled = select.disabled;
        trigger.setAttribute('aria-required', String(select.required));
        trigger.setAttribute('aria-invalid', select.getAttribute('aria-invalid') || 'false');
        const description = [select.getAttribute('aria-describedby'), !error.hidden ? error.id : '']
            .filter(Boolean).join(' ');
        if (description) trigger.setAttribute('aria-describedby', description);
        else trigger.removeAttribute('aria-describedby');
        if (select.disabled) close();
    };
    const position = () => {
        if (!isOpen()) return;
        const bounds = trigger.getBoundingClientRect();
        const viewport = window.visualViewport;
        const width = viewport?.width ?? innerWidth;
        const height = viewport?.height ?? innerHeight;
        const left = viewport?.offsetLeft ?? 0;
        const top = viewport?.offsetTop ?? 0;
        const below = top + height - bounds.bottom - 12;
        const above = bounds.top - top - 12;
        const opensAbove = below < Math.min(240, popup.scrollHeight) && above > below;
        popup.style.width = `${Math.min(Math.max(bounds.width, 160), width - 24)}px`;
        popup.style.maxHeight = `${Math.max(44, Math.min(280, (opensAbove ? above : below) - 8))}px`;
        popup.style.left = `${Math.max(left + 12, Math.min(bounds.left,
            left + width - popup.getBoundingClientRect().width - 12))}px`;
        popup.style.top = `${opensAbove ? bounds.top - popup.getBoundingClientRect().height - 8
            : bounds.bottom + 8}px`;
    };
    const activate = index => {
        activeIndex = index;
        options.forEach((option, candidate) => {
            option.element.dataset.active = String(candidate === index);
        });
        const active = options[index];
        if (!active) return;
        trigger.setAttribute('aria-activedescendant', active.element.id);
        active.element.scrollIntoView({ block: 'nearest' });
    };
    const render = () => {
        popup.replaceChildren();
        options = [];
        let previousGroup;
        [...select.options].forEach((option, index) => {
            const group = option.closest('optgroup');
            if (option.hidden || group?.hidden) return;
            if (group && group !== previousGroup) {
                const heading = document.createElement('div');
                heading.className = 'project-select-group';
                heading.textContent = group.label;
                popup.append(heading);
            }
            previousGroup = group;
            const element = document.createElement('div');
            element.className = 'project-select-option';
            element.id = `${id}-option-${index}`;
            element.setAttribute('role', 'option');
            element.setAttribute('aria-selected', String(option.selected));
            element.setAttribute('aria-disabled', String(option.disabled || Boolean(group?.disabled)));
            element.textContent = option.textContent.trim();
            element.addEventListener('pointerdown', event => event.preventDefault());
            const item = { element, index, disabled: option.disabled || Boolean(group?.disabled) };
            element.addEventListener('click', () => { if (!item.disabled) choose(item); });
            options.push(item);
            popup.append(element);
        });
    };
    const choose = option => {
        if (select.multiple) select.options[option.index].selected = !select.options[option.index].selected;
        else select.selectedIndex = option.index;
        error.hidden = true;
        select.setAttribute('aria-invalid', 'false');
        if (select.multiple) {
            const index = options.indexOf(option);
            render();
            activate(index);
        } else close();
        sync();
        select.dispatchEvent(new Event('input', { bubbles: true }));
        select.dispatchEvent(new Event('change', { bubbles: true }));
        trigger.focus({ preventScroll: true });
    };
    const open = () => {
        sync();
        if (trigger.disabled || isOpen()) return;
        render();
        popup.showPopover();
        position();
        const selected = options.findIndex(option => option.index === select.selectedIndex && !option.disabled);
        activate(selected >= 0 ? selected : options.findIndex(option => !option.disabled));
    };
    trigger.addEventListener('click', () => { if (isOpen()) close(); else open(); });
    popup.addEventListener('toggle', () => {
        trigger.setAttribute('aria-expanded', String(isOpen()));
        if (!isOpen()) trigger.removeAttribute('aria-activedescendant');
    });
    trigger.addEventListener('keydown', event => {
        if (event.key === 'Escape' && isOpen()) {
            event.preventDefault();
            event.stopPropagation();
            close();
        } else if (event.key === 'Tab') {
            close();
        } else if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
            event.preventDefault();
            const wasOpen = isOpen();
            open();
            const enabled = options.map((option, index) => !option.disabled ? index : -1).filter(index => index >= 0);
            let next = enabled.indexOf(activeIndex);
            if (event.key === 'Home') next = 0;
            else if (event.key === 'End') next = enabled.length - 1;
            else if (wasOpen) next += event.key === 'ArrowDown' ? 1 : -1;
            activate(enabled[Math.max(0, Math.min(next, enabled.length - 1))] ?? -1);
        } else if (['Enter', ' '].includes(event.key) && isOpen()) {
            event.preventDefault();
            if (options[activeIndex] && !options[activeIndex].disabled) choose(options[activeIndex]);
        } else if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
            if (event.key === ' ' && !isOpen()) return;
            event.preventDefault();
            open();
            query = Date.now() - lastTyped > 700 ? event.key : query + event.key;
            lastTyped = Date.now();
            const normalize = text => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
            const match = options.findIndex(option => !option.disabled
                && normalize(option.element.textContent).startsWith(normalize(query)));
            if (match >= 0) activate(match);
        }
    });
    select.addEventListener('change', () => { error.hidden = true; sync(); });
    select.addEventListener('focus', () => trigger.focus());
    select.addEventListener('invalid', event => {
        event.preventDefault();
        error.textContent = select.validationMessage;
        error.hidden = false;
        select.setAttribute('aria-invalid', 'true');
        sync();
        trigger.focus();
    });
    select.form?.addEventListener('reset', () => {
        close();
        error.hidden = true;
        requestAnimationFrame(sync);
    });
    new MutationObserver(() => {
        sync();
        if (isOpen()) { render(); position(); activate(activeIndex); }
    }).observe(select, {
        attributes: true, childList: true, subtree: true, characterData: true,
        attributeFilter: ['disabled', 'hidden', 'selected', 'required', 'aria-invalid', 'aria-describedby'],
    });
    window.addEventListener('resize', position);
    window.addEventListener('scroll', position, true);
    window.visualViewport?.addEventListener('resize', position);
    controls.set(select, { sync });
    sync();
}

document.querySelectorAll('select').forEach(enhanceSelect);
new MutationObserver(records => {
    records.forEach(record => record.addedNodes.forEach(node => {
        if (!(node instanceof Element)) return;
        if (node.matches('select')) enhanceSelect(node);
        node.querySelectorAll('select').forEach(enhanceSelect);
    }));
}).observe(document.body, { childList: true, subtree: true });
