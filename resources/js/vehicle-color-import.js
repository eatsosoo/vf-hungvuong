import { applyMedia, checkFile, requestJson, showFilePreview } from './media.js';

const normalized = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .replace(/[đĐ]/g, 'd').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
const colors = [
    ['Xanh dương', '#2563eb', ['xanh duong', 'xanh bien', 'blue']],
    ['Xanh lá', '#166534', ['xanh la', 'green']],
    ['Trắng', '#ffffff', ['trang', 'white']],
    ['Đen', '#171717', ['den', 'black']],
    ['Xám', '#808080', ['xam', 'gray', 'grey']],
    ['Bạc', '#c0c0c0', ['bac', 'silver']],
    ['Đỏ', '#b91c1c', ['do', 'red']],
    ['Cam', '#ea580c', ['cam', 'orange']],
    ['Vàng', '#eab308', ['vang', 'yellow']],
    ['Nâu', '#92400e', ['nau', 'brown']],
    ['Hồng', '#ec4899', ['hong', 'pink']],
];

const suggestColor = (filename, vehicleName) => {
    const stem = filename.replace(/\.[^.]+$/, '');
    const words = normalized(stem);
    const matches = colors.flatMap(([name, hex, aliases]) => aliases.flatMap(alias => {
        const position = ` ${words} `.indexOf(` ${alias} `);
        return position < 0 ? [] : [{ name, hex, position }];
    })).sort((left, right) => left.position - right.position);
    const unique = matches.filter((match, index) => matches.findIndex(item => item.name === match.name) === index);
    if (unique.length) {
        const [body, roof] = unique;
        return { name: roof ? `${body.name} / nóc ${roof.name}` : body.name,
            hex: body.hex, roof: roof?.hex ?? '' };
    }
    const model = normalized(vehicleName);
    const name = model && words.startsWith(`${model} `) ? words.slice(model.length).trim() : stem;
    return { name: name.replace(/[_-]+/g, ' ').trim().slice(0, 100), hex: '', roof: '' };
};

const element = (tag, className, text) => {
    const item = document.createElement(tag);
    if (className) item.className = className;
    if (text !== undefined) item.textContent = text;
    return item;
};

export const setupColorImport = (editor, { rows, appendRow, refresh }) => {
    const section = editor.querySelector('[data-color-import]');
    const dialog = document.querySelector('[data-media-dialog]');
    if (!section || !dialog) return;
    section.hidden = false;
    const input = section.querySelector('[data-color-files]');
    const open = section.querySelector('[data-color-files-open]');
    const queue = section.querySelector('[data-color-import-queue]');
    const status = section.querySelector('[data-color-import-status]');
    const submit = section.querySelector('[data-color-import-submit]');
    const dropzone = section.querySelector('[data-color-dropzone]');
    const maxFile = Number(dialog.dataset.maxFileBytes);
    const maxBatch = Number(dialog.dataset.maxBatchBytes);
    let pending = [];
    let nextId = 0;
    let uploading = false;
    const groupsContainer = editor.querySelector('[data-color-import-groups]');
    const groups = [];
    let nextGroup = 1;
    const refreshGroups = () => {
        groups.forEach(group => {
            const remaining = group.rows.filter(row => row.isConnected);
            group.button.hidden = remaining.length === 0;
            group.button.textContent = `Xóa nhóm ${group.number} vừa thêm (${remaining.length} màu)`;
        });
        groupsContainer.hidden = !groups.some(group => group.rows.some(row => row.isConnected));
    };
    const registerGroup = importedRows => {
        const button = element('button', 'danger');
        button.type = 'button';
        const group = { button, rows: importedRows, number: nextGroup++ };
        groups.push(group);
        button.addEventListener('click', () => {
            if (uploading) return;
            group.rows.forEach(row => row.remove());
            refresh();
            refreshGroups();
            status.textContent = `Đã xóa nhóm ${group.number} khỏi biểu mẫu. Nhấn “Lưu mẫu xe” để cập nhật.`;
            open.focus();
        });
        groupsContainer.append(button);
        refreshGroups();
    };
    new MutationObserver(refreshGroups).observe(editor.querySelector('[data-vehicle-color-rows]'), { childList: true });
    section.querySelector('[data-color-import-limits]').textContent =
        `Mỗi ảnh tối đa ${(maxFile / 1024 / 1024).toFixed(1)} MB; tổng mỗi lần tối đa `
        + `${(maxBatch / 1024 / 1024).toFixed(1)} MB. Tên màu phải khác các màu đã có.`;

    const addField = (entry, key, label, color = false) => {
        const wrapper = element('div', 'field');
        const id = `color-import-${entry.id}-${key}`;
        const caption = element('label', 'field-label', label);
        caption.htmlFor = id;
        const field = element('input');
        field.type = 'text';
        field.id = id;
        field.value = entry[key];
        field.maxLength = color ? 7 : 100;
        field.placeholder = color ? '#ffffff' : 'Ví dụ: Trắng';
        field.setAttribute('aria-describedby', `color-import-error-${entry.id}`);
        field.addEventListener('input', () => { entry[key] = field.value; });
        entry.fields[key] = field;
        wrapper.append(caption);
        if (color) {
            const control = element('div', 'color-control');
            control.dataset.colorControl = '';
            field.dataset.colorText = '';
            const swatch = element('input');
            swatch.type = 'color';
            swatch.value = entry[key] || '#ffffff';
            swatch.dataset.colorSwatch = '';
            swatch.setAttribute('aria-label', `Chọn ${label.toLowerCase()} cho ${entry.file.name}`);
            swatch.addEventListener('input', () => { entry[key] = swatch.value; });
            control.append(swatch, field);
            if (key === 'roof') {
                const clear = element('button', 'secondary color-clear');
                clear.type = 'button';
                clear.dataset.colorClear = '';
                clear.setAttribute('aria-label', `Bỏ màu nóc cho ${entry.file.name}`);
                clear.append(editor.querySelector('[data-color-clear] svg').cloneNode(true));
                clear.addEventListener('click', () => { entry.roof = ''; });
                control.append(clear);
            }
            wrapper.append(control);
        } else {
            wrapper.append(field);
        }
        return wrapper;
    };
    const render = () => {
        queue.replaceChildren();
        for (const entry of pending) {
            entry.fields = {};
            const card = element('article', 'vehicle-color-import-item');
            const image = element('img');
            image.alt = `Xem trước ${entry.file.name}`;
            showFilePreview(entry.file, image);
            const filename = element('strong', '', entry.file.name);
            filename.title = entry.file.name;
            const heading = element('div', 'vehicle-color-import-heading');
            entry.error = element('small', '');
            entry.error.dataset.importError = '';
            entry.error.id = `color-import-error-${entry.id}`;
            entry.error.setAttribute('aria-live', 'polite');
            const remove = element('button', 'vehicle-card-delete danger');
            remove.type = 'button';
            remove.title = 'Xóa card ảnh';
            remove.setAttribute('aria-label', `Xóa card ảnh ${entry.file.name}`);
            remove.append(section.querySelector('[data-color-import-delete-icon]').content.cloneNode(true));
            remove.addEventListener('click', () => {
                if (uploading) return;
                const index = pending.indexOf(entry);
                pending = pending.filter(item => item !== entry);
                render();
                const next = pending[index] ?? pending[index - 1];
                (next?.fields.name ?? open).focus();
                status.textContent = `Đã xóa ${entry.file.name}. Còn ${pending.length} ảnh đang chờ tạo màu.`;
            });
            heading.append(filename, remove);
            card.append(heading, image, addField(entry, 'name', 'Tên màu'),
                addField(entry, 'hex', 'Màu thân xe', true), addField(entry, 'roof', 'Màu nóc (tùy chọn)', true),
                entry.error);
            queue.append(card);
        }
        section.querySelector('[data-color-import-actions]').hidden = !pending.length;
        submit.textContent = `Tạo ${pending.length} màu từ ảnh`;
    };
    const blankRows = () => rows().filter(row =>
        [...row.querySelectorAll('[name]')].every(field => !field.value.trim())).length;
    const addFiles = files => {
        if (uploading) return;
        const messages = [];
        const capacity = 30 - rows().length + blankRows();
        for (const file of files) {
            const error = checkFile(file, maxFile);
            if (error) { messages.push(error); continue; }
            if (pending.some(entry => entry.file.name === file.name && entry.file.size === file.size
                && entry.file.lastModified === file.lastModified)) continue;
            if (pending.length >= Math.min(20, capacity)) {
                messages.push('Đã đạt giới hạn 20 ảnh/lần hoặc 30 màu/xe. Các ảnh vượt giới hạn chưa được thêm.');
                break;
            }
            if (pending.reduce((sum, entry) => sum + entry.file.size, file.size) > maxBatch) {
                messages.push(`${file.name}: tổng dung lượng vượt giới hạn tải mỗi lần.`);
                continue;
            }
            pending.push({ file, id: nextId++, ...suggestColor(file.name, editor.elements.name.value) });
        }
        render();
        status.textContent = messages.join(' ')
            || `${pending.length} ảnh đã chọn. Rà soát tên và mã màu trước khi tạo.`;
        input.value = '';
    };
    open.addEventListener('click', () => input.click());
    input.addEventListener('change', () => addFiles([...input.files]));
    section.querySelector('[data-color-import-clear]').addEventListener('click', () => {
        pending = [];
        render();
        status.textContent = 'Đã bỏ ảnh đang chờ. Các màu trong biểu mẫu vẫn được giữ nguyên.';
        open.focus();
    });
    ['dragenter', 'dragover'].forEach(type => dropzone.addEventListener(type, event => {
        event.preventDefault();
        if (!uploading) dropzone.dataset.dragging = 'true';
    }));
    dropzone.addEventListener('dragleave', () => { delete dropzone.dataset.dragging; });
    dropzone.addEventListener('drop', event => {
        event.preventDefault();
        delete dropzone.dataset.dragging;
        addFiles([...event.dataTransfer.files]);
    });
    editor.addEventListener('submit', event => {
        if (uploading || pending.length) {
            event.preventDefault();
            status.textContent = uploading ? 'Ảnh đang tải. Chờ hoàn tất trước khi lưu mẫu xe.'
                : 'Tạo màu từ ảnh đã chọn hoặc bỏ ảnh đang chờ trước khi lưu mẫu xe.';
            if (!uploading) submit.focus();
        }
    }, true);
    submit.addEventListener('click', async () => {
        if (uploading || !pending.length) return;
        if (editor.querySelector('[data-media-picker][data-uploading]')) {
            status.textContent = 'Một ảnh trong biểu mẫu đang tải. Chờ hoàn tất rồi tạo các màu mới.';
            return;
        }
        const seen = new Set(rows().map(row => row.querySelector('[name$="[name]"]').value.trim()
            .toLocaleLowerCase('vi')));
        let firstInvalid;
        for (const entry of pending) {
            entry.name = entry.name.trim();
            const name = entry.name.toLocaleLowerCase('vi');
            let error = '';
            if (!name) error = 'Nhập tên màu.';
            else if (seen.has(name)) error = 'Tên màu trùng với màu đã có hoặc ảnh khác trong lô này.';
            else if (!/^#[0-9a-f]{6}$/i.test(entry.hex)) error = 'Chọn mã HEX thân xe gồm 6 ký tự.';
            else if (entry.roof && !/^#[0-9a-f]{6}$/i.test(entry.roof)) error = 'Mã HEX nóc xe chưa hợp lệ.';
            seen.add(name);
            entry.error.textContent = error;
            Object.values(entry.fields).forEach(field => field.setAttribute('aria-invalid', String(Boolean(error))));
            if (error) firstInvalid ??= entry;
        }
        if (firstInvalid) {
            status.textContent = 'Kiểm tra các ảnh có lỗi bên dưới trước khi tạo màu.';
            firstInvalid.fields.name.focus();
            return;
        }
        if (rows().length - blankRows() + pending.length > 30) {
            status.textContent = 'Tổng số màu vượt 30. Bỏ bớt ảnh đang chờ hoặc màu trong biểu mẫu.';
            return;
        }
        const batch = [...pending];
        const data = new FormData();
        batch.forEach(entry => {
            data.append('images[]', entry.file);
            data.append('alts[]', `${editor.elements.name.value} — ${entry.name}`.slice(0, 255));
        });
        uploading = true;
        section.setAttribute('aria-busy', 'true');
        const controls = [...editor.querySelectorAll('input, button, select, textarea')]
            .filter(control => !control.disabled);
        controls.forEach(control => { control.disabled = true; });
        status.textContent = `Đang tải và tối ưu ${batch.length} ảnh…`;
        let first;
        const importedRows = [];
        try {
            const result = await requestJson(dialog.dataset.uploadUrl, {
                method: 'POST', body: data,
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            if (!Array.isArray(result.data) || result.data.length !== batch.length) {
                throw new Error('Phản hồi tải ảnh chưa đầy đủ. Kiểm tra thư viện trước khi thử lại.');
            }
            batch.forEach((entry, index) => {
                const row = appendRow(entry.name, entry.hex);
                row.querySelector('[name$="[secondary_hex]"]').value = entry.roof;
                row.querySelector('[name$="[secondary_hex]"]').closest('[data-color-control]')
                    .querySelector('[data-color-swatch]').value = entry.roof || '#ffffff';
                applyMedia(row.querySelector('[data-media-picker]'), result.data[index]);
                importedRows.push(row);
                first ??= row;
            });
            registerGroup(importedRows);
            pending = [];
            render();
            status.textContent = `Đã tạo ${batch.length} màu kèm ảnh. Rà soát các card rồi nhấn “Lưu mẫu xe”.`;
        } catch (error) {
            status.textContent = `${error.message} Các ảnh đang chờ được giữ lại để bạn chỉnh và thử lại.`;
        } finally {
            uploading = false;
            section.removeAttribute('aria-busy');
            controls.filter(control => control.isConnected).forEach(control => { control.disabled = false; });
            refresh();
            first?.querySelector('[name$="[name]"]').focus();
        }
    });
};
