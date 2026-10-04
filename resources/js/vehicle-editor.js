import { setupColorImport } from './vehicle-color-import.js';

const editor = document.querySelector('[data-vehicle-editor]');

if (editor) {
    const container = editor.querySelector('[data-vehicle-color-rows]');
    const template = editor.querySelector('[data-vehicle-color-template]');
    const status = editor.querySelector('[data-vehicle-color-status]');
    const addBlank = editor.querySelector('[data-add-vehicle-color]');
    const rows = () => [...container.querySelectorAll('[data-vehicle-color-row]')];
    let nextIndex = Math.max(-1, ...rows().map(row =>
        Number(row.querySelector('[name]').name.match(/\[(\d+)\]/)?.[1] ?? -1)))
        + 1;

    const refreshRow = row => {
        const name = row.querySelector('[name$="[name]"]').value.trim();
        const hex = row.querySelector('[name$="[hex]"]').value;
        const roof = row.querySelector('[name$="[secondary_hex]"]').value;
        const bodyColor = /^#[0-9a-f]{6}$/i.test(hex) ? hex : '#ffffff';
        row.querySelector('[data-color-row-title]').textContent = name || 'Màu mới';
        row.querySelector('[data-color-preview-body]').setAttribute('fill', bodyColor);
        row.querySelector('[data-color-preview-roof]').setAttribute('fill',
            /^#[0-9a-f]{6}$/i.test(roof) ? roof : bodyColor);
        row.querySelector('[data-remove-vehicle-color]').setAttribute('aria-label',
            `Xóa card ${name || 'màu mới'} khỏi biểu mẫu`);
    };
    const refresh = () => {
        editor.querySelector('[data-vehicle-color-count]').textContent = `${rows().length}/30 màu`;
        addBlank.disabled = rows().length >= 30;
        rows().forEach(refreshRow);
    };
    const appendRow = (name = '', hex = '') => {
        let row = name ? rows().find(candidate =>
            [...candidate.querySelectorAll('[name]')].every(input => !input.value.trim())) : null;
        if (!row) {
            if (rows().length >= 30) return null;
            const fragment = document.createElement('template');
            fragment.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
            row = fragment.content.querySelector('[data-vehicle-color-row]');
            container.append(fragment.content);
        }
        row.querySelector('[name$="[name]"]').value = name;
        row.querySelector('[name$="[hex]"]').value = hex;
        row.querySelector('[data-color-swatch]').value = hex || '#ffffff';
        return row;
    };
    addBlank.addEventListener('click', () => {
        const row = appendRow();
        refresh();
        if (row) {
            status.textContent = 'Đã thêm một dòng màu mới.';
            row.querySelector('[name$="[name]"]').focus();
        }
    });
    editor.addEventListener('input', event => {
        const row = event.target.closest('[data-vehicle-color-row]');
        if (row) refreshRow(row);
    });
    editor.addEventListener('click', event => {
        const remove = event.target.closest('[data-remove-vehicle-color]');
        if (remove) {
            const row = remove.closest('[data-vehicle-color-row]');
            const next = row.nextElementSibling || row.previousElementSibling;
            row.remove();
            refresh();
            status.textContent = 'Đã bỏ màu khỏi biểu mẫu. Nhấn “Lưu mẫu xe” để cập nhật.';
            (next?.querySelector('[name$="[name]"]') || addBlank).focus();
        }
        const clear = event.target.closest('[data-color-clear]');
        const colorRow = clear?.closest('[data-vehicle-color-row]');
        if (colorRow) refreshRow(colorRow);
    });
    setupColorImport(editor, { rows, appendRow, refresh });
    refresh();
}
