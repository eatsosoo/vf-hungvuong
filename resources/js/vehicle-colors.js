import { translate } from './client-translations.js';

document.querySelectorAll('[data-vehicle-color-viewer]').forEach(viewer => {
    const image = viewer.querySelector('[data-vehicle-image]');
    const outgoingImage = viewer.querySelector('[data-vehicle-image-outgoing]');
    const options = [...viewer.querySelectorAll('[data-vehicle-color]')];
    if (!image) return;

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let entranceAnimation;
    let entranceCancelled = false;
    const cancelEntrance = () => {
        entranceCancelled = true;
        entranceAnimation?.cancel();
    };
    const enterVehicle = () => {
        if (entranceCancelled || reducedMotion.matches || !image.naturalWidth) return;
        entranceAnimation = image.animate([
            { opacity: 0, transform: 'translateX(min(40vw, 320px))' },
            { opacity: 1, transform: 'translateX(0)' },
        ], { duration: 850, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' });
    };
    if (image.complete) enterVehicle();
    else image.addEventListener('load', enterVehicle, { once: true });
    reducedMotion.addEventListener('change', cancelEntrance);
    if (!outgoingImage || !options.length) return;

    const stage = viewer.querySelector('[data-vehicle-stage]');
    const colorName = viewer.querySelector('[data-vehicle-color-name]');
    const loading = viewer.querySelector('[data-vehicle-color-loading]');
    const feedback = viewer.querySelector('[data-vehicle-color-feedback]');
    const cachedImages = new Map();
    let selectionVersion = 0;
    let animations = [];

    const stopAnimation = () => {
        cancelEntrance();
        animations.forEach(animation => animation.cancel());
        animations = [];
        outgoingImage.hidden = true;
    };

    const loadImage = source => {
        if (cachedImages.has(source)) return cachedImages.get(source);

        const promise = new Promise((resolve, reject) => {
            const nextImage = new Image();
            const timeout = window.setTimeout(() => reject(new Error('Image loading timed out')), 15000);
            nextImage.onload = async () => {
                window.clearTimeout(timeout);
                try {
                    await nextImage.decode();
                    resolve(nextImage);
                } catch (error) {
                    reject(error);
                }
            };
            nextImage.onerror = () => {
                window.clearTimeout(timeout);
                reject(new Error('Image could not be loaded'));
            };
            nextImage.src = source;
        }).catch(error => {
            cachedImages.delete(source);
            throw error;
        });

        cachedImages.set(source, promise);
        return promise;
    };

    const selectColor = async (option, requestedDirection) => {
        const version = ++selectionVersion;
        stopAnimation();
        feedback.textContent = '';
        feedback.classList.add('sr-only');

        if (option.getAttribute('aria-current') === 'true') {
            stage.setAttribute('aria-busy', 'false');
            loading.hidden = true;
            return;
        }

        stage.setAttribute('aria-busy', 'true');
        loading.hidden = false;
        feedback.textContent = translate('Đang tải màu :color…', { color: option.dataset.name });

        try {
            await loadImage(option.dataset.image);
            if (version !== selectionVersion) return;

            const currentIndex = options.findIndex(item => item.getAttribute('aria-current') === 'true');
            const direction = requestedDirection ?? (options.indexOf(option) > currentIndex ? 1 : -1);
            outgoingImage.src = image.src;
            image.src = option.dataset.image;
            image.alt = option.dataset.alt;
            colorName.textContent = option.dataset.name;
            options.forEach(item => item.setAttribute('aria-current', String(item === option)));
            const url = new URL(window.location.href);
            url.searchParams.set('color', new URL(option.href).searchParams.get('color'));
            window.history.replaceState(window.history.state, '', url);
            feedback.textContent = '';

            outgoingImage.hidden = false;
            const timing = { duration: 550, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' };
            animations = [
                image.animate([
                    { transform: `translateX(${direction * 100}%)` },
                    { transform: 'translateX(0)' },
                ], timing),
                outgoingImage.animate([
                    { transform: 'translateX(0)' },
                    { transform: `translateX(${direction * -100}%)` },
                ], timing),
            ];
            Promise.allSettled(animations.map(animation => animation.finished)).then(() => {
                if (version === selectionVersion) stopAnimation();
            });
        } catch {
            if (version !== selectionVersion) return;
            feedback.classList.remove('sr-only');
            feedback.textContent = translate('Chưa tải được ảnh màu này. Vui lòng chọn lại để thử lần nữa.');
        } finally {
            if (version === selectionVersion) {
                loading.hidden = true;
                stage.setAttribute('aria-busy', 'false');
            }
        }
    };

    options.forEach((option, index) => {
        option.addEventListener('click', event => {
            if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            selectColor(option);
        });
        option.addEventListener('keydown', event => {
            let nextIndex;
            if (event.key === 'ArrowRight') nextIndex = (index + 1) % options.length;
            if (event.key === 'ArrowLeft') nextIndex = (index - 1 + options.length) % options.length;
            if (event.key === 'Home') nextIndex = 0;
            if (event.key === 'End') nextIndex = options.length - 1;
            if (nextIndex === undefined) return;
            event.preventDefault();
            options[nextIndex].focus();
            const direction = event.key === 'ArrowRight' ? 1 : event.key === 'ArrowLeft' ? -1 : undefined;
            selectColor(options[nextIndex], direction);
        });
    });
});
