const slugify = value => value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[đĐ]/g, 'd')
    .toLowerCase()
    .replace(/_+/g, '-')
    .replace(/@/g, '-at-')
    .replace(/[^a-z0-9\s-]+/g, '')
    .replace(/[\s-]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 180)
    .replace(/-+$/g, '');

document.querySelectorAll('input[data-slug-source]').forEach(slug => {
    const source = [...(slug.form?.elements ?? [])].find(field => field.name === slug.dataset.slugSource);
    if (!source) {
        return;
    }

    const initiallyAutomatic = slug.dataset.slugAuto === 'true'
        && (!slug.value || slug.value === slugify(source.value));
    let automatic = initiallyAutomatic;
    let updating = false;
    const update = () => {
        if (!automatic) {
            return;
        }
        slug.value = slugify(source.value);
        updating = true;
        slug.dispatchEvent(new Event('input', { bubbles: true }));
        updating = false;
    };

    source.addEventListener('input', event => {
        if (!event.isComposing) {
            update();
        }
    });
    source.addEventListener('compositionend', update);
    slug.addEventListener('input', () => {
        if (!updating) {
            automatic = !slug.value;
        }
    });
    slug.addEventListener('blur', () => {
        if (!slug.value) {
            automatic = true;
            update();
        }
    });
    slug.form?.addEventListener('reset', () => {
        automatic = initiallyAutomatic;
        requestAnimationFrame(update);
    });
    update();
});
