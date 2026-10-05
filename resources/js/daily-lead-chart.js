document.querySelectorAll('[data-daily-chart]').forEach(chart => {
    const values = JSON.parse(chart.dataset.values);
    const start = Number(chart.dataset.start);
    const end = Number(chart.dataset.end);
    const ceiling = Number(chart.dataset.ceiling);
    const plot = chart.querySelector('[data-chart-plot]');
    const cursor = chart.querySelector('[data-chart-cursor]');
    const guide = chart.querySelector('[data-chart-guide]');
    const point = chart.querySelector('[data-chart-point]');
    const label = chart.querySelector('[data-chart-value-text]');
    const dateFormat = new Intl.DateTimeFormat('vi-VN', { timeZone: 'UTC' });
    const numberFormat = new Intl.NumberFormat('vi-VN');
    let selectedDay = end;

    const showDay = timestamp => {
        selectedDay = Math.max(start, Math.min(end, timestamp));
        const date = new Date(selectedDay * 1000);
        const count = values[date.toISOString().slice(0, 10)] ?? 0;
        const x = start === end ? 480 : (selectedDay - start) / (end - start) * 960;
        const y = 260 - count / ceiling * 240;
        guide.setAttribute('x1', String(x));
        guide.setAttribute('x2', String(x));
        point.setAttribute('cx', String(x));
        point.setAttribute('cy', String(y));
        cursor.hidden = false;
        cursor.removeAttribute('hidden');
        label.textContent = `${dateFormat.format(date)} · ${numberFormat.format(count)} khách mới`;
    };

    const showPointerDay = event => {
        const bounds = plot.getBoundingClientRect();
        const fraction = Math.max(0, Math.min(1, (event.clientX - bounds.left) / bounds.width));
        showDay(start + Math.round(fraction * (end - start) / 86400) * 86400);
    };
    plot.addEventListener('pointermove', showPointerDay);
    plot.addEventListener('pointerdown', showPointerDay);
    chart.addEventListener('focus', () => showDay(selectedDay));
    chart.addEventListener('keydown', event => {
        if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) return;
        const target = {
            ArrowLeft: selectedDay - 86400, ArrowRight: selectedDay + 86400, Home: start, End: end,
        }[event.key];
        if (target === undefined) return;
        event.preventDefault();
        showDay(target);
    });
});
