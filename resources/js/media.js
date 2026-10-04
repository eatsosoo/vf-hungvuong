const validColumns = ['2', '3', '4', '6'];
const gridControls = document.querySelectorAll('[data-media-columns]');
gridControls.forEach(control => {
    const grid = document.querySelector(`[data-media-grid="${control.dataset.gridTarget}"]`);
    const key = `vf-media-columns-${control.dataset.gridTarget}`;
    const apply = () => {
        if (grid && validColumns.includes(control.value)) {
            grid.dataset.columns = control.value;
        }
    };
    try {
        const saved = localStorage.getItem(key);
        if (control.dataset.explicitColumns !== 'true' && validColumns.includes(saved)) {
            control.value = saved;
        }
    } catch {
        control.dataset.persistence = 'unavailable';
    }
    apply();
    control.addEventListener('change', () => {
        apply();
        try {
            localStorage.setItem(key, control.value);
        } catch {
            control.dataset.persistence = 'unavailable';
        }
    });
});

const setColor = (control, value) => {
    const text = control.querySelector('[data-color-text]');
    const swatch = control.querySelector('[data-color-swatch]');
    text.value = value;
    if (/^#[0-9a-f]{6}$/i.test(value)) {
        swatch.value = value;
    }
};
document.addEventListener('input', event => {
    const control = event.target.closest('[data-color-control]');
    if (control) {
        setColor(control, event.target.value);
    }
});
document.addEventListener('click', event => {
    const clear = event.target.closest('[data-color-clear]');
    if (clear) {
        const control = clear.closest('[data-color-control]');
        setColor(control, '');
        control.querySelector('[data-color-swatch]').value = '#ffffff';
        control.querySelector('[data-color-text]').focus();
    }
});

const node = (tag, className, text) => {
    const element = document.createElement(tag);
    if (className) {
        element.className = className;
    }
    if (text !== undefined) {
        element.textContent = text;
    }
    return element;
};
const showFilePreview = (file, image) => {
    const reader = new FileReader();
    reader.addEventListener('load', () => { image.src = reader.result; });
    reader.addEventListener('error', () => { image.alt = 'Không thể xem trước ảnh này.'; });
    reader.readAsDataURL(file);
};
const checkFile = (file, maxBytes = 5 * 1024 * 1024) => {
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        return `${file.name}: chỉ nhận JPG, PNG hoặc WebP.`;
    }
    if (file.size > maxBytes) {
        return `${file.name}: vượt quá ${Math.round(maxBytes / 1024 / 1024 * 10) / 10} MB.`;
    }
    return null;
};
const requestJson = async (url, options = {}) => {
    const response = await fetch(url, {
        ...options,
        headers: { Accept: 'application/json', ...options.headers },
        credentials: 'same-origin',
    });
    const data = await response.json();
    if (!response.ok) {
        const messages = Object.values(data.errors ?? {}).flat();
        throw new Error(messages.join(' ') || 'Không thể xử lý yêu cầu. Kiểm tra quyền truy cập và thử lại.');
    }
    return data;
};

const batchForm = document.querySelector('[data-batch-upload]');
if (batchForm) {
    const filesInput = batchForm.querySelector('[data-upload-files]');
    const list = batchForm.querySelector('[data-upload-queue]');
    const status = batchForm.querySelector('[data-upload-status]');
    const submit = batchForm.querySelector('[data-upload-submit]');
    const heading = batchForm.querySelector('[data-upload-queue-heading]');
    const dropzone = batchForm.querySelector('[data-upload-dropzone]');
    let pending = [];
    const render = () => {
        list.replaceChildren();
        const transfer = new DataTransfer();
        pending.forEach((entry, index) => {
            transfer.items.add(entry.file);
            const row = node('div', 'upload-queue-item');
            const preview = node('img');
            preview.alt = `Xem trước ${entry.file.name}`;
            showFilePreview(entry.file, preview);
            const content = node('div', 'min-w-0');
            content.append(node('strong', '', `${entry.file.name} · ${Math.ceil(entry.file.size / 1024)} KB`));
            const field = node('div', 'field');
            const label = node('label', 'field-label', 'Mô tả alt');
            const input = node('input');
            input.type = 'text';
            input.id = `upload-alt-${index}`;
            input.name = `alts[${index}]`;
            input.maxLength = 255;
            input.placeholder = 'Mô tả nội dung của ảnh…';
            input.value = entry.alt;
            label.htmlFor = input.id;
            input.addEventListener('input', () => { entry.alt = input.value; });
            field.append(label, input);
            content.append(field);
            const remove = node('button', 'secondary', 'Bỏ ảnh');
            remove.type = 'button';
            remove.setAttribute('aria-label', `Bỏ ${entry.file.name}`);
            remove.addEventListener('click', () => {
                pending.splice(index, 1);
                render();
                status.textContent = pending.length ? `${pending.length} ảnh sẵn sàng để tải.` : 'Chưa chọn ảnh.';
                filesInput.focus();
            });
            row.append(preview, content, remove);
            list.append(row);
        });
        filesInput.files = transfer.files;
        heading.hidden = pending.length === 0;
        batchForm.querySelector('[data-upload-count]').textContent = `${pending.length} ảnh đã chọn`;
        submit.disabled = pending.length === 0;
        submit.textContent = pending.length ? `Tải ${pending.length} ảnh đã chọn` : 'Tải ảnh đã chọn';
    };
    const addFiles = files => {
        const messages = [];
        for (const file of files) {
            const error = checkFile(file, Number(batchForm.dataset.maxFileBytes));
            if (error) {
                messages.push(error);
                continue;
            }
            if (pending.some(entry => entry.file.name === file.name && entry.file.size === file.size
                && entry.file.lastModified === file.lastModified)) {
                continue;
            }
            if (pending.length >= 20) {
                messages.push('Tối đa 20 ảnh mỗi lần. Các ảnh vượt giới hạn chưa được thêm.');
                break;
            }
            const totalBytes = pending.reduce((total, entry) => total + entry.file.size, 0) + file.size;
            if (totalBytes > Number(batchForm.dataset.maxBatchBytes)) {
                messages.push('Tổng ảnh vượt giới hạn mỗi lần tải. Tải các ảnh đang chọn rồi thêm ảnh còn lại.');
                continue;
            }
            pending.push({ file, alt: file.name.slice(0, 255) });
        }
        render();
        status.textContent = messages.join(' ') || `${pending.length} ảnh sẵn sàng. Bạn có thể sửa alt trước khi tải.`;
    };
    filesInput.addEventListener('change', () => addFiles([...filesInput.files]));
    batchForm.querySelector('[data-upload-clear]').addEventListener('click', () => {
        pending = [];
        render();
        status.textContent = 'Đã bỏ toàn bộ ảnh đang chờ tải.';
        filesInput.focus();
    });
    ['dragenter', 'dragover'].forEach(type => dropzone.addEventListener(type, event => {
        event.preventDefault();
        dropzone.dataset.dragging = 'true';
    }));
    dropzone.addEventListener('dragleave', () => { delete dropzone.dataset.dragging; });
    dropzone.addEventListener('drop', event => {
        event.preventDefault();
        delete dropzone.dataset.dragging;
        addFiles([...event.dataTransfer.files]);
    });
    render();
}

const dialog = document.querySelector('[data-media-dialog]');
if (dialog) {
    const searchForm = dialog.querySelector('[data-media-search]');
    const results = dialog.querySelector('[data-library-results]');
    const status = dialog.querySelector('[data-library-status]');
    const previous = dialog.querySelector('[data-library-prev]');
    const next = dialog.querySelector('[data-library-next]');
    let activePicker = null;
    let opener = null;
    let currentPage = 1;
    let lastPage = 1;
    let pendingRequest = null;
    const applyMedia = (picker, media) => {
        picker.querySelector('[data-media-value]').value = media?.id ?? '';
        const image = picker.querySelector('[data-media-preview]');
        image.hidden = !media;
        if (media) {
            image.src = media.url;
            image.alt = media.alt || media.original_name;
        } else {
            image.removeAttribute('src');
        }
        picker.querySelector('[data-media-name]').textContent = media?.original_name || 'Chưa chọn ảnh';
        picker.querySelector('[data-media-description]').textContent
            = media?.alt || 'Tải ảnh mới hoặc chọn từ thư viện.';
        picker.querySelector('[data-media-clear]').hidden = !media;
        picker.querySelector('[data-media-open]').setAttribute('aria-invalid', 'false');
    };
    const loadLibrary = async (page = 1) => {
        pendingRequest?.abort();
        const controller = new AbortController();
        pendingRequest = controller;
        status.textContent = 'Đang tải thư viện ảnh…';
        results.replaceChildren();
        results.setAttribute('aria-busy', 'true');
        previous.disabled = true;
        next.disabled = true;
        const url = new URL(dialog.dataset.libraryUrl, window.location.origin);
        url.searchParams.set('q', searchForm.elements.library_q.value);
        url.searchParams.set('sort', searchForm.elements.library_sort.value);
        url.searchParams.set('page', String(page));
        try {
            const data = await requestJson(url, { signal: controller.signal });
            currentPage = data.meta.current_page;
            lastPage = data.meta.last_page;
            const selected = activePicker?.querySelector('[data-media-value]').value;
            data.data.forEach(media => {
                const button = node('button', 'media-choice');
                button.type = 'button';
                button.setAttribute('aria-label', `Chọn ảnh ${media.original_name} (ID ${media.id})`);
                button.setAttribute('aria-pressed', String(String(media.id) === selected));
                const image = node('img', 'media-card-image');
                image.src = media.url;
                image.alt = media.alt || media.original_name;
                image.loading = 'lazy';
                image.width = media.width;
                image.height = media.height;
                const name = node('strong', '', media.original_name);
                name.title = media.original_name;
                button.append(image, name, node('small', '', `#${media.id} · ${media.width} × ${media.height}`));
                button.addEventListener('click', () => {
                    if (activePicker) {
                        applyMedia(activePicker, media);
                        activePicker.querySelector('[data-media-status]').textContent = 'Đã chọn ảnh từ thư viện.';
                    }
                    dialog.close();
                });
                results.append(button);
            });
            status.textContent = data.meta.total ? `${data.meta.total} ảnh · Chọn một ảnh bên dưới.`
                : 'Không tìm thấy ảnh. Thử từ khóa khác hoặc tải ảnh mới.';
            dialog.querySelector('[data-library-page]').textContent = `Trang ${currentPage} / ${lastPage}`;
            previous.disabled = currentPage <= 1;
            next.disabled = currentPage >= lastPage;
        } catch (error) {
            if (error.name !== 'AbortError') {
                status.textContent = error.message;
            }
        } finally {
            if (pendingRequest === controller) {
                results.removeAttribute('aria-busy');
            }
        }
    };
    searchForm.addEventListener('submit', event => {
        event.preventDefault();
        loadLibrary();
    });
    searchForm.elements.library_sort.addEventListener('change', () => loadLibrary());
    previous.addEventListener('click', () => loadLibrary(currentPage - 1));
    next.addEventListener('click', () => loadLibrary(currentPage + 1));
    dialog.querySelector('[data-media-dialog-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => {
        pendingRequest?.abort();
        opener?.focus();
        activePicker = null;
    });
    document.addEventListener('click', event => {
        const button = event.target.closest('[data-media-open], [data-media-upload-open], [data-media-clear]');
        const picker = button?.closest('[data-media-picker]');
        if (!picker) {
            return;
        }
        if (button.hasAttribute('data-media-open')) {
            activePicker = picker;
            opener = button;
            dialog.showModal();
            loadLibrary();
        } else if (button.hasAttribute('data-media-upload-open')) {
            picker.querySelector('[data-media-upload]').click();
        } else {
            applyMedia(picker, null);
            picker.querySelector('[data-media-status]').textContent = 'Đã bỏ chọn ảnh.';
        }
    });
    document.addEventListener('change', async event => {
        if (!event.target.matches('[data-media-upload]')) {
            return;
        }
        const file = event.target.files[0];
        const picker = event.target.closest('[data-media-picker]');
        const message = picker.querySelector('[data-media-status]');
        if (!file || picker.dataset.uploading) {
            return;
        }
        const error = checkFile(file, Number(dialog.dataset.maxFileBytes));
        if (error) {
            message.textContent = error;
            event.target.value = '';
            return;
        }
        const data = new FormData();
        data.append('image', file);
        data.append('alt', file.name.slice(0, 255));
        picker.dataset.uploading = 'true';
        picker.setAttribute('aria-busy', 'true');
        picker.querySelectorAll('button').forEach(button => { button.disabled = true; });
        message.textContent = 'Đang tải và tối ưu ảnh…';
        try {
            const media = await requestJson(dialog.dataset.uploadUrl, {
                method: 'POST', body: data,
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            applyMedia(picker, media);
            message.textContent = 'Đã tải ảnh và chọn cho trường này. Lưu biểu mẫu để áp dụng.';
        } catch (error) {
            message.textContent = error.message;
        } finally {
            delete picker.dataset.uploading;
            picker.removeAttribute('aria-busy');
            picker.querySelectorAll('button').forEach(button => { button.disabled = false; });
            event.target.value = '';
        }
    });
    document.addEventListener('submit', event => {
        if (event.target.querySelector('[data-media-picker][data-uploading]')) {
            event.preventDefault();
            event.target.querySelector('[data-media-picker][data-uploading] [data-media-status]').textContent
                = 'Ảnh đang được tải. Vui lòng chờ hoàn tất trước khi lưu.';
        }
    }, true);
    document.querySelectorAll('[data-media-picker]').forEach(async picker => {
        const id = picker.querySelector('[data-media-value]').value;
        if (id && picker.querySelector('[data-media-preview]').hidden) {
            const url = new URL(dialog.dataset.libraryUrl, window.location.origin);
            url.searchParams.set('id', id);
            try {
                const data = await requestJson(url);
                if (data.data[0] && picker.querySelector('[data-media-value]').value === id) {
                    applyMedia(picker, data.data[0]);
                }
            } catch (error) {
                picker.querySelector('[data-media-status]').textContent = error.message;
            }
        }
    });
}
