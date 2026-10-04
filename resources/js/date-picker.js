import { translate } from './client-translations.js';
import { syncSelects } from './select.js';

const dateLocale = document.documentElement.lang === 'en' ? 'en-US' : 'vi-VN';
const dateLabel = new Intl.DateTimeFormat(dateLocale, { dateStyle: 'full', timeZone: 'UTC' });
const monthLabel = new Intl.DateTimeFormat(dateLocale, { month: 'long', year: 'numeric', timeZone: 'UTC' });
const pad = value => String(value).padStart(2, '0');

function makeDate(year, month, day) {
    const date = new Date(0);
    date.setUTCFullYear(year, month, day);
    return date;
}

function dateValue(date) {
    return `${String(date.getUTCFullYear()).padStart(4, '0')}-${pad(date.getUTCMonth() + 1)}`
        + `-${pad(date.getUTCDate())}`;
}

function parseDate(value) {
    const parts = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
    if (!parts) return null;
    const date = makeDate(Number(parts[1]), Number(parts[2]) - 1, Number(parts[3]));
    return dateValue(date) === value && date.getUTCFullYear() > 0 ? date : null;
}

function addDays(date, amount) {
    return makeDate(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate() + amount);
}

function addMonths(date, amount) {
    const first = makeDate(date.getUTCFullYear(), date.getUTCMonth() + amount, 1);
    const last = makeDate(first.getUTCFullYear(), first.getUTCMonth() + 1, 0);
    return makeDate(first.getUTCFullYear(), first.getUTCMonth(), Math.min(date.getUTCDate(), last.getUTCDate()));
}

function initializeDatePicker(picker) {
    if (picker.dataset.ready) return;
    const find = part => picker.querySelector(`[data-date-picker-${part}]`);
    const input = find('input');
    const panel = find('panel');
    if (typeof panel.showModal !== 'function') return;
    const toggle = find('toggle');
    const days = find('days');
    const month = find('month');
    const year = find('year');
    const time = find('time');
    const apply = find('apply');
    const todayButton = find('today');
    const clear = find('clear');
    const message = find('message');
    const originalLabel = toggle.getAttribute('aria-label');
    const clock = new Intl.DateTimeFormat('en-GB', {
        timeZone: picker.dataset.timezone,
        year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
    });
    let focusedDate;
    let selectedDate;
    let visibleMonth;

    const currentDateTime = () => {
        const parts = Object.fromEntries(clock.formatToParts(new Date()).map(part => [part.type, part.value]));
        return { date: `${parts.year}-${parts.month}-${parts.day}`, time: `${parts.hour}:${parts.minute}` };
    };
    const minimum = () => parseDate(input.min.slice(0, 10)) ?? makeDate(1, 0, 1);
    const maximum = () => parseDate(input.max.slice(0, 10)) ?? makeDate(9999, 11, 31);
    const clampDate = date => date < minimum() ? minimum() : date > maximum() ? maximum() : date;
    const editable = () => !input.disabled && !input.readOnly && !input.matches(':disabled');

    const probeValue = value => {
        const probe = document.createElement('input');
        probe.type = input.type;
        for (const attribute of ['min', 'max', 'step']) {
            if (input.hasAttribute(attribute)) probe.setAttribute(attribute, input.getAttribute(attribute));
        }
        // The native step base can depend on the original value attribute.
        if (input.hasAttribute('value')) probe.setAttribute('value', input.getAttribute('value'));
        probe.required = true;
        probe.value = value;
        return probe;
    };

    const availableDate = date => {
        if (!date) return false;
        if (date < minimum() || date > maximum()) return false;
        return Boolean(time) || probeValue(dateValue(date)).validity.valid;
    };

    const syncControl = () => {
        toggle.disabled = !editable();
        clear.hidden = input.required;
        const value = parseDate(input.value.slice(0, 10));
        toggle.setAttribute('aria-label', value ? `${originalLabel}, ${dateLabel.format(value)}` : originalLabel);
        if (panel.open && !editable()) panel.close();
    };

    const validateTime = () => {
        if (!time) return true;
        const value = selectedDate && time.value ? `${dateValue(selectedDate)}T${time.value}` : '';
        const probe = probeValue(value);
        const boundaryLabel = boundary => `${dateLabel.format(parseDate(boundary.slice(0, 10)))}`
            + translate(' lúc ') + boundary.split('T')[1];
        let error = '';
        if (!value) error = translate('Vui lòng chọn ngày và nhập giờ.');
        else if (probe.validity.rangeUnderflow) error = translate('Chọn từ :date.', { date: boundaryLabel(input.min) });
        else if (probe.validity.rangeOverflow) error = translate('Chọn đến :date.', { date: boundaryLabel(input.max) });
        else if (probe.validity.stepMismatch) error = translate('Thời gian này không khả dụng. Vui lòng chọn giờ khác.');
        else if (!probe.validity.valid) error = translate('Ngày hoặc giờ chưa hợp lệ.');
        message.textContent = error;
        message.hidden = !error;
        time.setAttribute('aria-invalid', String(Boolean(error)));
        apply.disabled = Boolean(error);
        find('selection').textContent = selectedDate ? dateLabel.format(selectedDate) : translate('Chưa chọn ngày');
        return !error;
    };

    const positionPanel = () => {
        if (!panel.open) return;
        const control = picker.querySelector('.date-picker-control').getBoundingClientRect();
        const bounds = panel.getBoundingClientRect();
        const viewport = window.visualViewport;
        const leftEdge = viewport?.offsetLeft ?? 0;
        const topEdge = viewport?.offsetTop ?? 0;
        const width = viewport?.width ?? window.innerWidth;
        const height = viewport?.height ?? window.innerHeight;
        const gutter = width < 360 ? 2 : 12;
        const left = Math.max(leftEdge + gutter, Math.min(control.left, leftEdge + width - bounds.width - gutter));
        const below = control.bottom + 8;
        const above = control.top - bounds.height - 8;
        const preferredTop = below + bounds.height <= topEdge + height - 12 ? below : above;
        const top = Math.max(topEdge + 12, Math.min(preferredTop, topEdge + height - bounds.height - 12));
        panel.style.left = `${left}px`;
        panel.style.top = `${top}px`;
    };

    const focusDay = () => days.querySelector('[tabindex="0"]')?.focus({ preventScroll: true });

    const render = (moveFocus = false) => {
        month.value = String(visibleMonth.getUTCMonth() + 1);
        syncSelects(picker);
        year.value = String(visibleMonth.getUTCFullYear());
        year.min = String(minimum().getUTCFullYear());
        year.max = String(maximum().getUTCFullYear());
        find('title').textContent = monthLabel.format(visibleMonth);
        const first = makeDate(visibleMonth.getUTCFullYear(), visibleMonth.getUTCMonth(), 1);
        const last = makeDate(first.getUTCFullYear(), first.getUTCMonth() + 1, 0);
        find('previous').disabled = first <= minimum();
        find('next').disabled = last >= maximum();
        for (const option of month.options) {
            const optionFirst = makeDate(first.getUTCFullYear(), Number(option.value) - 1, 1);
            const optionLast = makeDate(first.getUTCFullYear(), Number(option.value), 0);
            option.disabled = optionLast < minimum() || optionFirst > maximum();
        }
        const start = addDays(first, -((first.getUTCDay() + 6) % 7));
        const today = currentDateTime().date;
        const fragment = document.createDocumentFragment();
        for (let week = 0; week < 6; week++) {
            const row = document.createElement('tr');
            for (let day = 0; day < 7; day++) {
                const date = addDays(start, week * 7 + day);
                const value = dateValue(date);
                const cell = document.createElement('td');
                cell.setAttribute('aria-selected', String(selectedDate && value === dateValue(selectedDate) || false));
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'date-picker-day';
                button.dataset.date = value;
                button.textContent = String(date.getUTCDate());
                button.setAttribute('aria-label', dateLabel.format(date));
                button.setAttribute('aria-disabled', String(!availableDate(date)));
                button.disabled = !parseDate(value);
                button.tabIndex = value === dateValue(focusedDate) ? 0 : -1;
                if (date.getUTCMonth() !== first.getUTCMonth()) button.dataset.outside = '';
                if (value === today) button.setAttribute('aria-current', 'date');
                cell.append(button);
                row.append(cell);
            }
            fragment.append(row);
        }
        days.replaceChildren(fragment);
        todayButton.disabled = !availableDate(parseDate(today));
        clear.disabled = !input.value;
        validateTime();
        positionPanel();
        if (moveFocus) focusDay();
    };

    const commit = value => {
        if (!editable()) return;
        if (value && !probeValue(value).validity.valid) return;
        input.value = value;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        syncControl();
        panel.close();
    };

    const selectDate = date => {
        if (!availableDate(date)) return;
        selectedDate = date;
        focusedDate = date;
        visibleMonth = makeDate(date.getUTCFullYear(), date.getUTCMonth(), 1);
        if (time) render(true);
        else commit(dateValue(date));
    };

    const open = () => {
        if (!editable() || panel.open) return;
        const current = currentDateTime();
        const value = parseDate(input.value.slice(0, 10));
        focusedDate = clampDate(value ?? parseDate(current.date));
        selectedDate = value ?? (time ? focusedDate : null);
        visibleMonth = makeDate(focusedDate.getUTCFullYear(), focusedDate.getUTCMonth(), 1);
        if (time) {
            time.step = input.step || '60';
            time.value = input.value.split('T')[1] || current.time;
        }
        syncControl();
        panel.showModal();
        toggle.setAttribute('aria-expanded', 'true');
        render(true);
    };

    const moveMonth = amount => {
        focusedDate = clampDate(addMonths(focusedDate, amount));
        visibleMonth = makeDate(focusedDate.getUTCFullYear(), focusedDate.getUTCMonth(), 1);
        render();
    };

    toggle.addEventListener('click', open);
    input.addEventListener('keydown', event => {
        if (event.altKey && event.key === 'ArrowDown') {
            event.preventDefault();
            open();
        }
    });
    input.addEventListener('input', syncControl);
    input.addEventListener('change', syncControl);
    find('close').addEventListener('click', () => panel.close());
    find('previous').addEventListener('click', () => moveMonth(-1));
    find('next').addEventListener('click', () => moveMonth(1));
    const changeMonthYear = () => {
        if (!year.validity.valid || !year.value) {
            year.value = String(visibleMonth.getUTCFullYear());
            return;
        }
        focusedDate = clampDate(makeDate(Number(year.value), Number(month.value) - 1, 1));
        visibleMonth = focusedDate;
        render();
    };
    month.addEventListener('change', changeMonthYear);
    year.addEventListener('change', changeMonthYear);
    year.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            changeMonthYear();
            focusDay();
        }
    });
    days.addEventListener('click', event => {
        const button = event.target.closest('[data-date]');
        if (button) selectDate(parseDate(button.dataset.date));
    });
    days.addEventListener('focusin', event => {
        const button = event.target.closest('[data-date]');
        if (!button) return;
        const date = parseDate(button.dataset.date);
        if (!date) return;
        focusedDate = date;
        days.querySelectorAll('[data-date]').forEach(day => { day.tabIndex = day === button ? 0 : -1; });
    });
    days.addEventListener('keydown', event => {
        const offsets = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };
        let nextDate;
        if (event.key in offsets) nextDate = addDays(focusedDate, offsets[event.key]);
        else if (event.key === 'Home') nextDate = addDays(focusedDate, -((focusedDate.getUTCDay() + 6) % 7));
        else if (event.key === 'End') nextDate = addDays(focusedDate, 6 - ((focusedDate.getUTCDay() + 6) % 7));
        else if (event.key === 'PageUp') nextDate = addMonths(focusedDate, event.shiftKey ? -12 : -1);
        else if (event.key === 'PageDown') nextDate = addMonths(focusedDate, event.shiftKey ? 12 : 1);
        if (!nextDate) return;
        event.preventDefault();
        focusedDate = clampDate(nextDate);
        visibleMonth = makeDate(focusedDate.getUTCFullYear(), focusedDate.getUTCMonth(), 1);
        render(true);
    });
    todayButton.addEventListener('click', () => selectDate(parseDate(currentDateTime().date)));
    clear.addEventListener('click', () => { if (!input.required) commit(''); });
    time?.addEventListener('input', validateTime);
    time?.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            if (validateTime()) commit(`${dateValue(selectedDate)}T${time.value}`);
        }
    });
    apply?.addEventListener('click', () => {
        if (validateTime()) commit(`${dateValue(selectedDate)}T${time.value}`);
    });
    panel.addEventListener('click', event => {
        if (event.target !== panel) return;
        const bounds = panel.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right
            || event.clientY < bounds.top || event.clientY > bounds.bottom) panel.close();
    });
    panel.addEventListener('close', () => {
        toggle.setAttribute('aria-expanded', 'false');
        if (!toggle.disabled) toggle.focus({ preventScroll: true });
    });
    input.form?.addEventListener('reset', () => {
        if (panel.open) panel.close();
        requestAnimationFrame(syncControl);
    });
    new MutationObserver(syncControl).observe(input, {
        attributes: true, attributeFilter: ['disabled', 'readonly', 'required', 'min', 'max', 'step'],
    });
    window.addEventListener('resize', positionPanel);
    window.visualViewport?.addEventListener('resize', positionPanel);
    window.visualViewport?.addEventListener('scroll', positionPanel);
    picker.dataset.ready = 'true';
    toggle.hidden = false;
    syncControl();
}

document.querySelectorAll('[data-date-picker]').forEach(initializeDatePicker);
