import { translate } from './client-translations.js';

import './date-picker.js';
import { syncSelects } from './select.js';

const drawer = document.querySelector('[data-test-drive-drawer]');

if (drawer && typeof drawer.showModal === 'function') {
    const form = drawer.querySelector('[data-test-drive-form]');
    const submit = drawer.querySelector('[data-test-drive-submit]');
    const feedback = drawer.querySelector('[data-test-drive-feedback]');
    const success = drawer.querySelector('[data-test-drive-success]');
    const vehicle = drawer.querySelector('#test-drive-vehicle');
    const variant = drawer.querySelector('#test-drive-variant');
    const vehicleSummary = drawer.querySelector('[data-test-drive-vehicle-summary]');
    const vehicleFields = drawer.querySelector('[data-test-drive-vehicle-fields]');
    const changeVehicle = drawer.querySelector('[data-test-drive-change-vehicle]');
    const vehicleImage = drawer.querySelector('[data-test-drive-vehicle-image]');
    const vehicleFallback = drawer.querySelector('[data-test-drive-vehicle-fallback]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const fields = [...form.querySelectorAll('[name]')];
    const originalSubmit = submit.innerHTML;
    let opener;
    let closeTimer;
    let previousOverflow;
    let previousPadding;
    let wasSubmitted = false;

    fields.forEach(field => { field.dataset.baseDescription = field.getAttribute('aria-describedby') || ''; });

    const setVehicleSelectionExpanded = expanded => {
        vehicleFields.hidden = !expanded && Boolean(vehicle.value);
        changeVehicle.setAttribute('aria-expanded', String(!vehicleFields.hidden));
        changeVehicle.textContent = vehicleFields.hidden ? translate('Đổi xe') : translate('Xong');
    };

    const updateVehicleSummary = () => {
        const selectedVehicle = vehicle.selectedOptions[0];
        const selectedVariant = variant.selectedOptions[0];
        const hasVehicle = Boolean(vehicle.value && selectedVehicle);
        vehicleSummary.hidden = !hasVehicle;
        if (!hasVehicle) {
            setVehicleSelectionExpanded(true);
            return;
        }
        drawer.querySelector('[data-test-drive-vehicle-name]').textContent = selectedVehicle.dataset.model;
        drawer.querySelector('[data-test-drive-vehicle-detail]').textContent = [
            selectedVehicle.dataset.segment,
            variant.value ? selectedVariant.textContent.trim() : '',
        ].filter(Boolean).join(' · ');
        const imageUrl = selectedVehicle.dataset.image;
        vehicleImage.hidden = !imageUrl;
        vehicleFallback.hidden = Boolean(imageUrl);
        if (imageUrl) vehicleImage.src = imageUrl;
        else vehicleImage.removeAttribute('src');
    };

    const updateVariants = () => {
        [...variant.options].forEach(option => {
            const unavailable = Boolean(option.dataset.vehicle && option.dataset.vehicle !== vehicle.value);
            option.hidden = unavailable;
            option.disabled = unavailable;
            if (unavailable && option.selected) variant.value = '';
        });
        syncSelects(form);
    };

    const clearErrors = () => {
        drawer.querySelectorAll('[data-drawer-field-error]').forEach(error => error.remove());
        fields.forEach(field => {
            field.setAttribute('aria-invalid', 'false');
            if (field.dataset.baseDescription) field.setAttribute('aria-describedby', field.dataset.baseDescription);
            else field.removeAttribute('aria-describedby');
        });
        feedback.hidden = true;
        feedback.textContent = '';
    };

    const showFeedback = message => {
        feedback.textContent = message;
        feedback.hidden = false;
        feedback.focus();
    };

    const showErrors = errors => {
        let firstInvalid;
        Object.entries(errors).forEach(([name, messages]) => {
            const field = fields.find(control => control.name === name);
            if (!field || !field.id || field.type === 'hidden') return;
            if (field === vehicle || field === variant) setVehicleSelectionExpanded(true);
            const error = document.createElement('small');
            error.id = `${field.id}-drawer-error`;
            error.className = 'error';
            error.dataset.drawerFieldError = '';
            error.textContent = messages[0];
            const wrapper = field.closest('.field, .test-drive-consent') ?? field.parentElement;
            wrapper.append(error);
            field.setAttribute('aria-invalid', 'true');
            field.setAttribute('aria-describedby', `${field.dataset.baseDescription} ${error.id}`.trim());
            firstInvalid ??= field;
        });
        showFeedback(translate('Vui lòng kiểm tra lại các thông tin được đánh dấu bên dưới.'));
        firstInvalid?.focus();
    };

    const closeDrawer = () => {
        if (!drawer.open || drawer.dataset.closing) return;
        if (reducedMotion.matches) {
            drawer.close();
            return;
        }
        drawer.dataset.closing = 'true';
        closeTimer = window.setTimeout(() => drawer.close(), 280);
    };

    const openDrawer = trigger => {
        if (drawer.open) return;
        opener = trigger;
        if (wasSubmitted) {
            form.reset();
            form.hidden = false;
            success.hidden = true;
            clearErrors();
            wasSubmitted = false;
        }
        const parameters = new URL(trigger.href, window.location.href).searchParams;
        const vehicleId = parameters.get('vehicle');
        const modelName = trigger.dataset.model?.toLowerCase();
        const contextVehicleId = !vehicleId && !modelName ? drawer.dataset.contextVehicle : null;
        const selectedVehicleId = vehicleId || contextVehicleId;
        const option = [...vehicle.options].find(candidate =>
            selectedVehicleId ? candidate.value === selectedVehicleId
                : modelName && candidate.dataset.model?.toLowerCase()
                .includes(modelName));
        if (option) vehicle.value = option.value;
        updateVariants();
        const variantId = parameters.get('variant');
        if (variantId && [...variant.options].some(candidate => candidate.value === variantId && !candidate.disabled)) {
            variant.value = variantId;
        }
        updateVehicleSummary();
        setVehicleSelectionExpanded(!vehicle.value || vehicle.getAttribute('aria-invalid') === 'true'
            || variant.getAttribute('aria-invalid') === 'true');
        syncSelects(form);
        previousOverflow = document.documentElement.style.overflow;
        previousPadding = document.body.style.paddingRight;
        const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
        if (scrollbarWidth) {
            const padding = Number.parseFloat(getComputedStyle(document.body).paddingRight) || 0;
            document.body.style.paddingRight = `${padding + scrollbarWidth}px`;
        }
        document.documentElement.style.overflow = 'hidden';
        drawer.dataset.opening = 'true';
        drawer.showModal();
        trigger.setAttribute('aria-expanded', 'true');
        requestAnimationFrame(() => requestAnimationFrame(() => {
            delete drawer.dataset.opening;
            form.elements.namedItem('name').focus({ preventScroll: true });
        }));
    };

    document.querySelectorAll('[data-test-drive-open]').forEach(trigger => {
        trigger.setAttribute('aria-haspopup', 'dialog');
        trigger.setAttribute('aria-controls', drawer.id);
        trigger.setAttribute('aria-expanded', 'false');
        trigger.addEventListener('click', event => {
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
            event.preventDefault();
            openDrawer(trigger);
        });
    });

    drawer.querySelectorAll('[data-test-drive-close]').forEach(button => {
        button.addEventListener('click', closeDrawer);
    });
    drawer.addEventListener('cancel', event => {
        event.preventDefault();
        closeDrawer();
    });
    drawer.addEventListener('click', event => {
        if (event.target !== drawer) return;
        const bounds = drawer.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right
            || event.clientY < bounds.top || event.clientY > bounds.bottom) closeDrawer();
    });
    drawer.addEventListener('close', () => {
        window.clearTimeout(closeTimer);
        delete drawer.dataset.closing;
        delete drawer.dataset.opening;
        document.documentElement.style.overflow = previousOverflow;
        document.body.style.paddingRight = previousPadding;
        opener?.setAttribute('aria-expanded', 'false');
        opener?.focus({ preventScroll: true });
    });
    vehicle.addEventListener('change', () => {
        updateVariants();
        updateVehicleSummary();
    });
    variant.addEventListener('change', updateVehicleSummary);
    vehicleImage.addEventListener('error', () => {
        vehicleImage.hidden = true;
        vehicleFallback.hidden = false;
    });
    changeVehicle.addEventListener('click', () => {
        setVehicleSelectionExpanded(vehicleFields.hidden);
        if (!vehicleFields.hidden) vehicle.focus();
    });
    updateVariants();
    updateVehicleSummary();

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (form.dataset.submitting) return;
        clearErrors();
        const data = new FormData(form);
        form.dataset.submitting = 'true';
        form.setAttribute('aria-busy', 'true');
        submit.disabled = true;
        submit.textContent = translate('Đang gửi đăng ký…');
        try {
            const response = await fetch(form.action, {
                method: 'POST', body: data, credentials: 'same-origin', headers: { Accept: 'application/json' },
            });
            if (response.status === 419) {
                showFeedback(translate('Phiên làm việc đã hết hạn. Vui lòng tải lại trang rồi thử lại.'));
                return;
            }
            if (response.status === 429) {
                showFeedback(translate('Bạn đã gửi nhiều yêu cầu. Vui lòng chờ một phút rồi thử lại.'));
                return;
            }
            if (!response.headers.get('content-type')?.includes('application/json')) {
                throw new Error('The registration endpoint did not return JSON.');
            }
            const result = await response.json();
            if (response.status === 422) {
                showErrors(result.errors ?? {});
                return;
            }
            if (!response.ok) throw new Error(`Registration failed with status ${response.status}.`);
            form.hidden = true;
            success.hidden = false;
            success.querySelector('[data-test-drive-success-message]').textContent = result.message;
            wasSubmitted = true;
            if (drawer.open) success.focus();
        } catch (error) {
            console.error('Unable to submit the test drive registration.', error);
            showFeedback(translate('Chưa thể gửi đăng ký. Thông tin của bạn vẫn được giữ lại, vui lòng thử lại.'));
        } finally {
            delete form.dataset.submitting;
            form.removeAttribute('aria-busy');
            submit.disabled = false;
            submit.innerHTML = originalSubmit;
        }
    });
}
