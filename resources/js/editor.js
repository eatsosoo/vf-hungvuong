import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { Markdown } from '@tiptap/markdown';
import Image from '@tiptap/extension-image';
import { TableKit } from '@tiptap/extension-table';
import Placeholder from '@tiptap/extension-placeholder';
import { syncSelects } from './select.js';

const root = document.querySelector('[data-post-editor]');
const form = root?.closest('form');

if (root && form) {
    const body = form.elements.namedItem('body');
    const canvas = root.querySelector('[data-editor-canvas]');
    const source = root.querySelector('[data-editor-markdown]');
    const preview = root.querySelector('[data-editor-rendered]');
    const controls = root.querySelector('[data-editor-controls]');
    const heading = root.querySelector('[data-heading-level]');
    const error = root.querySelector('[data-editor-error]');
    const draftStatus = root.querySelector('[data-editor-draft-status]');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const fieldNames = [
        'title', 'slug', 'excerpt', 'body', 'focus_keyword', 'seo_title', 'seo_description', 'media_id',
    ];
    const getValues = () => Object.fromEntries(fieldNames.map(name => [
        name, form.elements.namedItem(name)?.value ?? '',
    ]));
    const initialValues = getValues();
    let mode = 'rich';
    let analysisTimer;
    let analysisController;
    let analysisVersion = 0;
    let recoveryDraft;
    let dialogOpener;

    const safeUrl = value => {
        const url = value.trim();
        if (/^\/(?!\/)/.test(url) && !/[\\\s]/.test(url)) {
            return url;
        }
        try {
            const parsed = new URL(url);
            return ['http:', 'https:'].includes(parsed.protocol) && !parsed.username && !parsed.password
                ? parsed.href : null;
        } catch {
            return null;
        }
    };

    const setError = message => {
        error.textContent = message;
        error.hidden = !message;
    };

    const saveDraft = () => {
        const values = getValues();
        if (JSON.stringify(values) === JSON.stringify(initialValues)) {
            return;
        }
        try {
            sessionStorage.setItem(root.dataset.draftKey, JSON.stringify({ values, savedAt: Date.now() }));
            const time = new Intl.DateTimeFormat('vi-VN', { hour: '2-digit', minute: '2-digit' }).format(new Date());
            const message = `Đã giữ bản khôi phục lúc ${time}. Chưa lưu vào database.`;
            if (draftStatus.textContent !== message) {
                draftStatus.textContent = message;
            }
        } catch {
            draftStatus.textContent = 'Trình duyệt không thể giữ bản khôi phục. Hãy lưu nội dung thường xuyên.';
        }
    };

    const paintAssessment = assessment => {
        const section = document.querySelector('[data-seo-assessment]');
        if (section) {
            section.querySelector('[data-score-value]').textContent = assessment.score;
            section.querySelector('[data-score-label]').textContent = assessment.label;
        }
        assessment.checks.forEach(check => {
            const item = section?.querySelector(`[data-seo-check="${check.key}"]`);
            if (item) {
                item.dataset.passed = String(check.passed);
                item.querySelector('[data-check-indicator]').textContent = check.passed ? '✓' : '○';
                item.querySelector('[data-check-points]').textContent = `${check.points}/${check.max}`;
            }
        });
        root.querySelector('[data-editor-count]').textContent =
            `${assessment.wordCount} từ · Khoảng ${assessment.readingMinutes} phút đọc`;
    };

    const analyze = async () => {
        clearTimeout(analysisTimer);
        analysisController?.abort();
        analysisController = new AbortController();
        const version = ++analysisVersion;
        if (mode === 'preview') {
            preview.setAttribute('aria-busy', 'true');
        }
        try {
            const response = await fetch(root.dataset.analysisUrl, {
                method: 'POST', credentials: 'same-origin', signal: analysisController.signal,
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(getValues()),
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(Object.values(result.errors ?? {}).flat()[0] ?? 'Không thể cập nhật xem trước và SEO.');
            }
            if (version !== analysisVersion) {
                return;
            }
            paintAssessment(result.assessment);
            preview.innerHTML = result.html;
            setError('');
        } catch (failure) {
            if (failure.name !== 'AbortError' && version === analysisVersion) {
                setError(failure.message || 'Kết nối bị gián đoạn. Nội dung soạn thảo vẫn được giữ.');
            }
        } finally {
            if (version === analysisVersion) {
                preview.removeAttribute('aria-busy');
            }
        }
    };
    const scheduleAnalysis = () => {
        clearTimeout(analysisTimer);
        analysisTimer = setTimeout(analyze, 900);
        saveDraft();
    };

    const updateToolbar = editor => {
        const marks = ['bold', 'italic', 'strike', 'bulletList', 'orderedList', 'blockquote', 'codeBlock', 'link'];
        root.querySelectorAll('[data-editor-command]').forEach(button => {
            const command = button.dataset.editorCommand;
            if (marks.includes(command)) {
                button.setAttribute('aria-pressed', String(editor.isActive(command)));
            }
            if (['undo', 'redo'].includes(command)) {
                button.disabled = !editor.can()[command]();
            }
            if (command === 'unlink') {
                button.disabled = !editor.isActive('link');
            }
        });
        heading.value = String(editor.getAttributes('heading').level ?? 'paragraph');
        syncSelects(root);
        root.querySelector('[data-table-tools]').hidden = !editor.isActive('table');
    };

    const editor = new Editor({
        element: canvas,
        content: body.value,
        contentType: 'markdown',
        injectCSS: false,
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3, 4] }, underline: false,
                link: { openOnClick: false, autolink: false, isAllowedUri: url => Boolean(safeUrl(url)) },
            }),
            Markdown, Image.configure({ allowBase64: false }), TableKit,
            Placeholder.configure({ placeholder: root.dataset.placeholder }),
        ],
        editorProps: {
            attributes: {
                role: 'textbox', 'aria-multiline': 'true', 'aria-labelledby': 'editor-body-label',
                'aria-describedby': ['editor-body-hint', body.getAttribute('aria-describedby')]
                    .filter(Boolean).join(' '),
                'aria-invalid': body.getAttribute('aria-invalid') ?? 'false',
                spellcheck: 'true',
            },
        },
        onUpdate: ({ editor: currentEditor }) => {
            body.value = currentEditor.getMarkdown();
            scheduleAnalysis();
            updateToolbar(currentEditor);
        },
        onSelectionUpdate: ({ editor: currentEditor }) => updateToolbar(currentEditor),
        onTransaction: ({ editor: currentEditor }) => updateToolbar(currentEditor),
    });

    body.required = false;
    source.hidden = true;
    root.dataset.enhanced = 'true';
    updateToolbar(editor);
    body.addEventListener('focus', () => {
        if (mode === 'rich') {
            editor.commands.focus();
        }
    });
    document.querySelector('#editor-body-label').addEventListener('click', () => {
        if (mode === 'rich') {
            editor.commands.focus();
        }
    });

    const setMode = nextMode => {
        if (nextMode === 'rich' && mode !== 'rich') {
            editor.commands.setContent(body.value, { contentType: 'markdown', emitUpdate: false });
        }
        mode = nextMode;
        canvas.hidden = mode !== 'rich';
        source.hidden = mode !== 'source';
        preview.hidden = mode !== 'preview';
        controls.hidden = mode !== 'rich';
        root.querySelector('[data-editor-source]').setAttribute('aria-pressed', String(mode === 'source'));
        root.querySelector('[data-editor-preview]').setAttribute('aria-pressed', String(mode === 'preview'));
        if (mode === 'preview') {
            analyze();
        }
    };
    root.querySelector('[data-editor-source]').addEventListener('click', () => {
        setMode(mode === 'source' ? 'rich' : 'source');
    });
    root.querySelector('[data-editor-preview]').addEventListener('click', () => {
        setMode(mode === 'preview' ? 'rich' : 'preview');
    });
    heading.addEventListener('change', () => {
        const chain = editor.chain().focus();
        if (heading.value === 'paragraph') {
            chain.setParagraph().run();
        } else {
            chain.toggleHeading({ level: Number(heading.value) }).run();
        }
    });

    const dialogs = [...document.querySelectorAll('[data-editor-dialog]')];
    const dialogError = (dialog, message = '') => {
        const element = dialog.querySelector('[data-dialog-error]');
        element.textContent = message;
        element.hidden = !message;
    };
    const openDialog = (name, opener) => {
        const dialog = dialogs.find(item => item.dataset.editorDialog === name);
        dialogOpener = opener;
        dialogError(dialog);
        if (['image', 'video'].includes(name) && editor.isActive('table')) {
            setError('Đặt con trỏ ở đoạn văn ngoài bảng để chèn ảnh hoặc video.');
            return;
        }
        if (name === 'link') {
            dialog.querySelector('[name="editor_link_url"]').value = editor.getAttributes('link').href ?? '';
        }
        dialog.showModal();
        dialog.querySelector('input')?.focus();
    };
    dialogs.forEach(dialog => {
        dialog.querySelector('[data-close-dialog]').addEventListener('click', () => dialog.close());
        dialog.addEventListener('close', () => dialogOpener?.focus());
    });

    const commandMethods = {
        bold: 'toggleBold', italic: 'toggleItalic', strike: 'toggleStrike', bulletList: 'toggleBulletList',
        orderedList: 'toggleOrderedList', blockquote: 'toggleBlockquote', codeBlock: 'toggleCodeBlock',
        horizontalRule: 'setHorizontalRule', unlink: 'unsetLink', undo: 'undo', redo: 'redo',
    };
    root.querySelectorAll('[data-editor-command]').forEach(button => {
        button.addEventListener('click', () => {
            const command = button.dataset.editorCommand;
            if (['link', 'image', 'video'].includes(command)) {
                openDialog(command, button);
            } else if (command === 'table') {
                editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
            } else {
                editor.chain().focus()[commandMethods[command]]().run();
            }
        });
    });
    root.querySelectorAll('[data-table-command]').forEach(button => {
        button.addEventListener('click', () => editor.chain().focus()[button.dataset.tableCommand]().run());
    });
    root.querySelector('[role="toolbar"]').addEventListener('keydown', event => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) {
            return;
        }
        event.preventDefault();
        const buttons = [...controls.querySelectorAll('[data-editor-command]:not(:disabled)')];
        const current = buttons.indexOf(document.activeElement);
        let next = event.key === 'Home' ? 0 : buttons.length - 1;
        if (['ArrowLeft', 'ArrowRight'].includes(event.key)) {
            next = (current + (event.key === 'ArrowRight' ? 1 : -1) + buttons.length) % buttons.length;
        }
        controls.querySelectorAll('[data-editor-command]').forEach(button => { button.tabIndex = -1; });
        buttons[next].tabIndex = 0;
        buttons[next].focus();
    });

    const linkDialog = dialogs.find(dialog => dialog.dataset.editorDialog === 'link');
    linkDialog.querySelector('[data-insert-link]').addEventListener('click', () => {
        const url = safeUrl(linkDialog.querySelector('input').value);
        if (!url) {
            dialogError(linkDialog, 'Nhập URL http/https hoặc đường dẫn nội bộ bắt đầu bằng /.');
            return;
        }
        const chain = editor.chain().focus();
        if (editor.state.selection.empty && !editor.isActive('link')) {
            chain.insertContent({ type: 'text', text: url, marks: [{ type: 'link', attrs: { href: url } }] }).run();
        } else {
            chain.extendMarkRange('link').setLink({ href: url }).run();
        }
        linkDialog.close();
    });

    const videoDialog = dialogs.find(dialog => dialog.dataset.editorDialog === 'video');
    videoDialog.querySelector('[data-insert-video]').addEventListener('click', () => {
        let id;
        try {
            const url = new URL(videoDialog.querySelector('input').value.trim());
            if (url.protocol === 'https:') {
                if (url.hostname === 'youtu.be') {
                    id = url.pathname.slice(1);
                } else if (['youtube.com', 'www.youtube.com'].includes(url.hostname) && url.pathname === '/watch') {
                    id = url.searchParams.get('v');
                }
            }
        } catch {
            id = null;
        }
        if (!/^[\w-]{11}$/.test(id ?? '')) {
            dialogError(videoDialog, 'Dùng URL YouTube dạng https://www.youtube.com/watch?v=… hoặc https://youtu.be/…');
            return;
        }
        editor.chain().focus().insertContent([
            { type: 'paragraph', content: [{ type: 'text', text: 'video',
                marks: [{ type: 'link', attrs: { href: `https://www.youtube.com/watch?v=${id}` } }] }] },
            { type: 'paragraph' },
        ]).run();
        videoDialog.close();
    });

    const imageDialog = dialogs.find(dialog => dialog.dataset.editorDialog === 'image');
    const imageUrl = imageDialog.querySelector('[name="editor_image_url"]');
    const imageAlt = imageDialog.querySelector('[name="editor_image_alt"]');
    imageDialog.querySelectorAll('[data-library-image]').forEach(button => {
        button.addEventListener('click', () => {
            imageUrl.value = button.dataset.url;
            imageAlt.value = button.dataset.alt;
            imageAlt.focus();
        });
    });
    imageDialog.querySelector('[data-insert-image]').addEventListener('click', () => {
        const url = safeUrl(imageUrl.value);
        if (!url || !imageAlt.value.trim()) {
            dialogError(imageDialog, 'Cần URL ảnh hợp lệ và mô tả ảnh (alt).');
            return;
        }
        editor.chain().focus().setImage({ src: url, alt: imageAlt.value.trim() }).run();
        imageDialog.close();
    });
    const uploadButton = imageDialog.querySelector('[data-upload-editor-image]');
    uploadButton.addEventListener('click', async () => {
        const file = imageDialog.querySelector('[type="file"]').files[0];
        if (!file || file.size > 5 * 1024 * 1024 || !imageAlt.value.trim()) {
            dialogError(imageDialog, 'Chọn ảnh tối đa 5 MB và nhập mô tả ảnh trước khi tải lên.');
            return;
        }
        const data = new FormData();
        data.append('image', file);
        data.append('alt', imageAlt.value.trim());
        uploadButton.disabled = true;
        uploadButton.textContent = 'Đang tải ảnh…';
        dialogError(imageDialog);
        try {
            const response = await fetch(uploadButton.dataset.uploadUrl, {
                method: 'POST', credentials: 'same-origin', body: data,
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(Object.values(result.errors ?? {}).flat()[0] ?? 'Không thể tải ảnh.');
            }
            imageUrl.value = result.url;
            imageAlt.value = result.alt;
            form.querySelectorAll('datalist[id^="media-"]').forEach(list => {
                const option = document.createElement('option');
                option.value = result.id;
                option.textContent = result.alt;
                list.appendChild(option);
            });
            imageDialog.querySelector('[type="file"]').value = '';
            uploadButton.textContent = 'Đã tải ảnh. Có thể chèn vào nội dung.';
        } catch (failure) {
            dialogError(imageDialog, failure.message || 'Không thể kết nối để tải ảnh.');
            uploadButton.textContent = 'Tải lên thư viện';
        } finally {
            uploadButton.disabled = false;
        }
    });

    const fullscreen = root.querySelector('[data-editor-fullscreen]');
    const setFullscreen = active => {
        root.classList.toggle('editor-is-fullscreen', active);
        root.dataset.fullscreen = String(active);
        document.body.classList.toggle('editor-fullscreen-active', active);
        fullscreen.setAttribute('aria-pressed', String(active));
        fullscreen.setAttribute('aria-label', active ? 'Thoát editor toàn màn hình' : 'Mở editor toàn màn hình');
        if (active) {
            root.setAttribute('role', 'dialog');
            root.setAttribute('aria-modal', 'true');
            root.setAttribute('aria-labelledby', 'editor-body-label');
        } else {
            root.removeAttribute('role');
            root.removeAttribute('aria-modal');
            root.removeAttribute('aria-labelledby');
            fullscreen.focus();
        }
    };
    fullscreen.addEventListener('click', () => setFullscreen(!root.classList.contains('editor-is-fullscreen')));
    root.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !dialogs.some(dialog => dialog.open)) {
            setFullscreen(false);
        }
        if (event.key === 'Tab' && root.dataset.fullscreen === 'true') {
            const focusable = [...root.querySelectorAll(
                'button:not(:disabled), input, select, textarea, [contenteditable], [tabindex="0"], summary',
            )].filter(element => element.offsetParent !== null && element.tabIndex !== -1);
            const first = focusable[0];
            const last = focusable.at(-1);
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last?.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first?.focus();
            }
        }
    });
    document.addEventListener('focusin', event => {
        if (root.dataset.fullscreen === 'true' && !dialogs.some(dialog => dialog.open)
            && !root.contains(event.target)) {
            fullscreen.focus();
        }
    });
    document.querySelectorAll('[data-error-field="body"]').forEach(link => {
        link.addEventListener('click', event => {
            event.preventDefault();
            setMode('rich');
            editor.commands.focus();
            canvas.scrollIntoView({ block: 'center' });
        });
    });
    preview.addEventListener('click', event => {
        const button = event.target.closest('[data-load-video]');
        const wrapper = button?.closest('[data-youtube]');
        if (wrapper && /^[\w-]{11}$/.test(wrapper.dataset.youtube)) {
            const frame = document.createElement('iframe');
            frame.src = `https://www.youtube-nocookie.com/embed/${wrapper.dataset.youtube}?autoplay=1`;
            frame.title = 'Video YouTube';
            frame.allow = 'autoplay; encrypted-media; picture-in-picture';
            frame.allowFullscreen = true;
            wrapper.replaceChildren(frame);
        }
    });

    form.addEventListener('input', scheduleAnalysis);
    form.addEventListener('change', scheduleAnalysis);
    form.addEventListener('submit', event => {
        if (!body.value.trim()) {
            event.preventDefault();
            setError('Nhập nội dung trước khi lưu.');
            setMode('rich');
            editor.commands.focus();
        } else {
            saveDraft();
            try {
                sessionStorage.setItem(root.dataset.pendingKey, root.dataset.draftKey);
            } catch {
                draftStatus.textContent = 'Không thể cập nhật bản khôi phục. Nội dung vẫn được gửi để lưu.';
            }
        }
    }, true);

    try {
        if (root.dataset.saveSucceeded === 'true') {
            const pendingKey = sessionStorage.getItem(root.dataset.pendingKey);
            if (pendingKey?.startsWith(root.dataset.draftPrefix)) {
                sessionStorage.removeItem(pendingKey);
            }
            sessionStorage.removeItem(root.dataset.pendingKey);
        }
        const saved = JSON.parse(sessionStorage.getItem(root.dataset.draftKey) ?? 'null');
        if (saved?.values && JSON.stringify(saved.values) !== JSON.stringify(initialValues)) {
            recoveryDraft = saved.values;
            root.querySelector('[data-draft-recovery]').hidden = false;
        } else if (saved) {
            sessionStorage.removeItem(root.dataset.draftKey);
        }
    } catch {
        draftStatus.textContent = 'Bản khôi phục của phiên trước không khả dụng.';
    }
    root.querySelector('[data-restore-draft]').addEventListener('click', () => {
        fieldNames.forEach(name => {
            const input = form.elements.namedItem(name);
            if (input && typeof recoveryDraft?.[name] === 'string') {
                input.value = recoveryDraft[name];
            }
        });
        editor.commands.setContent(body.value, { contentType: 'markdown', emitUpdate: false });
        root.querySelector('[data-draft-recovery]').hidden = true;
        analyze();
        draftStatus.textContent = 'Đã khôi phục nội dung. Nhấn nút Lưu để lưu nội dung.';
    });
    root.querySelector('[data-discard-draft]').addEventListener('click', () => {
        try {
            sessionStorage.removeItem(root.dataset.draftKey);
            root.querySelector('[data-draft-recovery]').hidden = true;
            draftStatus.textContent = 'Đã bỏ bản khôi phục của phiên trước.';
        } catch {
            draftStatus.textContent = 'Không thể bỏ bản khôi phục trong trình duyệt này.';
        }
    });
    analyze();
}
