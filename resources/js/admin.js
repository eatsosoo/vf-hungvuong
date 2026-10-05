import './date-picker.js';
import './media.js';
import './select.js';
import './slug.js';
import './vehicle-editor.js';
import './admin-drawer.js';
import './daily-lead-chart.js';

document.querySelectorAll('[data-page-size-form]').forEach(form => {
    const select = form.querySelector('[data-page-size]');
    if (select) {
        form.querySelector('[data-page-size-submit]').hidden = true;
        select.addEventListener('change', () => form.requestSubmit());
    }
});

const menuButton = document.querySelector('[data-admin-menu]');
const navigation = document.querySelector('[data-admin-navigation]');

if (menuButton && navigation) {
    const desktop = window.matchMedia('(min-width: 1024px)');
    const setMenuOpen = open => {
        navigation.classList.toggle('hidden', !open);
        menuButton.setAttribute('aria-expanded', String(open));
        menuButton.setAttribute('aria-label', open ? 'Đóng menu quản trị' : 'Mở menu quản trị');
    };

    setMenuOpen(desktop.matches);
    desktop.addEventListener('change', event => setMenuOpen(event.matches));
    menuButton.addEventListener('click', () => {
        setMenuOpen(menuButton.getAttribute('aria-expanded') !== 'true');
    });
    menuButton.closest('.sidebar').addEventListener('keydown', event => {
        if (event.key === 'Escape' && !desktop.matches) {
            setMenuOpen(false);
            menuButton.focus();
        }
    });
}

const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
const adminShell = document.querySelector('.admin-shell');

if (sidebarToggle && adminShell) {
    const desktopSidebar = window.matchMedia('(min-width: 1024px)');
    let isCollapsed = false;
    try {
        isCollapsed = localStorage.getItem('vf-admin-sidebar-collapsed') === 'true';
    } catch {
        isCollapsed = false;
    }

    const applySidebar = () => {
        const collapsed = isCollapsed && desktopSidebar.matches;
        adminShell.dataset.sidebarCollapsed = String(collapsed);
        sidebarToggle.setAttribute('aria-expanded', String(!collapsed));
        const label = collapsed ? 'Mở rộng sidebar' : 'Thu gọn sidebar';
        sidebarToggle.setAttribute('aria-label', label);
        sidebarToggle.title = label;
    };
    applySidebar();
    desktopSidebar.addEventListener('change', applySidebar);
    sidebarToggle.addEventListener('click', () => {
        isCollapsed = !isCollapsed;
        applySidebar();
        try {
            localStorage.setItem('vf-admin-sidebar-collapsed', String(isCollapsed));
        } catch {
            sidebarToggle.dataset.persistence = 'unavailable';
        }
    });
}

const errorSummary = document.querySelector('[data-error-summary]');
if (errorSummary) {
    errorSummary.querySelectorAll('[data-error-field]').forEach(link => {
        const errorName = link.dataset.errorField;
        const bracketName = errorName.replace(/\.([^.]+)/g, '[$1]');
        const fields = [...document.querySelectorAll('input, select, textarea')];
        const field = fields.find(input => input.name === errorName || input.name === bracketName)
            ?? fields.find(input => input.name === `${errorName}[]`)
            ?? fields.find(input => input.name === `${errorName.split('.')[0]}[]`);

        if (field) {
            link.href = `#${field.id}`;
            link.addEventListener('click', event => {
                event.preventDefault();
                field.focus();
                field.scrollIntoView({ block: 'center' });
            });
        } else {
            link.removeAttribute('href');
        }
    });
    errorSummary.focus();
}

const restoreForms = () => {
    document.querySelectorAll('form[data-submitting]').forEach(form => {
        delete form.dataset.submitting;
        form.removeAttribute('aria-busy');
        form.querySelectorAll('[data-submit-label]').forEach(button => {
            button.textContent = button.dataset.submitLabel;
            button.disabled = false;
            delete button.dataset.submitLabel;
        });
    });
};
window.addEventListener('pageshow', restoreForms);

document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', event => {
        if (event.defaultPrevented) {
            return;
        }
        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
            event.preventDefault();
            return;
        }
        if (form.method.toLowerCase() === 'get') {
            return;
        }
        if (form.dataset.submitting) {
            event.preventDefault();
            return;
        }

        form.dataset.submitting = 'true';
        form.setAttribute('aria-busy', 'true');
        const submitter = event.submitter;
        if (submitter && !submitter.name) {
            submitter.dataset.submitLabel = submitter.textContent;
            submitter.textContent = 'Đang xử lý…';
            requestAnimationFrame(() => {
                submitter.disabled = true;
            });
        }
    });
});

document.querySelectorAll('[data-add-row]').forEach(button => {
    button.addEventListener('click', () => {
        const container = document.querySelector(`[data-repeater="${button.dataset.addRow}"]`);
        const rows = container?.querySelectorAll('.repeat-row');
        if (!rows?.length || rows.length >= 30) {
            return;
        }

        const clone = rows[0].cloneNode(true);
        clone.querySelectorAll('.error').forEach(error => error.remove());
        clone.querySelectorAll('[aria-invalid]').forEach(field => { field.setAttribute('aria-invalid', 'false'); });
        clone.querySelectorAll('[data-media-picker]').forEach(picker => {
            const image = picker.querySelector('[data-media-preview]');
            image.removeAttribute('src');
            image.hidden = true;
            picker.querySelector('[data-media-name]').textContent = 'Chưa chọn ảnh';
            picker.querySelector('[data-media-description]').textContent = 'Tải ảnh mới hoặc chọn từ thư viện.';
            picker.querySelector('[data-media-status]').textContent = '';
            picker.querySelector('[data-media-clear]').hidden = true;
            delete picker.dataset.uploading;
            picker.querySelectorAll('button').forEach(action => { action.disabled = false; });
        });
        clone.querySelectorAll('[data-color-swatch]').forEach(swatch => { swatch.value = '#ffffff'; });
        clone.querySelectorAll('[name]').forEach(input => {
            input.name = input.name.replace(/\[\d+\]/, `[${rows.length}]`);
            input.value = '';
            input.setAttribute('aria-invalid', 'false');
        });
        clone.querySelectorAll('[data-media-upload]').forEach(input => { input.value = ''; });
        clone.querySelectorAll('*').forEach(element => {
            ['id', 'for', 'list', 'aria-describedby'].forEach(attribute => {
                const value = element.getAttribute(attribute);
                if (value) {
                    const updated = value.replace(/-\d+-/, `-${rows.length}-`)
                        .replace(/\S+-error/g, '').trim();
                    if (updated) {
                        element.setAttribute(attribute, updated);
                    } else {
                        element.removeAttribute(attribute);
                    }
                }
            });
        });
        container.appendChild(clone);
        clone.querySelector('input, select, textarea')?.focus();
        if (rows.length + 1 >= 30) {
            button.disabled = true;
            button.textContent = 'Đã đạt giới hạn 30 dòng';
        }
    });
});
