const editor = document.querySelector('[data-vehicle-editor]');

if (editor) {
    const container = editor.querySelector('[data-vehicle-color-rows]');
    const template = editor.querySelector('[data-vehicle-color-template]');
    const status = editor.querySelector('[data-vehicle-color-status]');
    const addBlank = editor.querySelector('[data-add-vehicle-color]');
    const addPresets = editor.querySelector('[data-add-preset-colors]');
    const presets = [...editor.querySelectorAll('[data-color-preset]')];
    const rows = () => [...container.querySelectorAll('[data-vehicle-color-row]')];
    const normalize = name => name.trim().toLocaleLowerCase('vi');
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
            `Bỏ ${name || 'màu mới'} khỏi biểu mẫu`);
    };
    const refresh = () => {
        editor.querySelector('[data-vehicle-color-count]').textContent = `${rows().length}/30 màu`;
        addBlank.disabled = rows().length >= 30;
        addPresets.disabled = !presets.some(preset => preset.checked);
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
    addPresets.addEventListener('click', () => {
        const existing = new Set(rows().map(row => normalize(row.querySelector('[name$="[name]"]').value)));
        let added = 0;
        let duplicate = 0;
        let first;
        presets.filter(preset => preset.checked).forEach(preset => {
            if (existing.has(normalize(preset.dataset.name))) {
                duplicate++;
                preset.checked = false;
                return;
            }
            const row = appendRow(preset.dataset.name, preset.dataset.hex);
            if (!row) return;
            existing.add(normalize(preset.dataset.name));
            first ??= row;
            preset.checked = false;
            added++;
        });
        refresh();
        status.textContent = `Đã thêm ${added} màu. Tất cả màu sẽ được lưu khi nhấn “Lưu mẫu xe”.`
            + (duplicate ? ` Bỏ qua ${duplicate} màu đã có tên trùng.` : '')
            + (presets.some(preset => preset.checked) ? ' Đã đạt giới hạn 30 màu.' : '');
        first?.querySelector('[name$="[name]"]').focus();
    });
    editor.addEventListener('input', event => {
        const row = event.target.closest('[data-vehicle-color-row]');
        if (row) refreshRow(row);
    });
    editor.addEventListener('change', event => {
        if (event.target.matches('[data-color-preset]')) refresh();
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
        if (clear) refreshRow(clear.closest('[data-vehicle-color-row]'));
    });
    refresh();
}
