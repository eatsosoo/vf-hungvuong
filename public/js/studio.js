document.querySelectorAll('form[data-confirm]').forEach(form => {
  form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
  });
});

document.querySelectorAll('[data-add-row]').forEach(button => {
  button.addEventListener('click', () => {
    const container = document.querySelector(`[data-repeater="${button.dataset.addRow}"]`);
    const rows = container.querySelectorAll('.repeat-row');
    if (rows.length >= 30) return;
    const clone = rows[0].cloneNode(true);
    clone.querySelectorAll('[name]').forEach(input => {
      input.name = input.name.replace(/\[\d+\]/, `[${rows.length}]`);
      input.value = '';
    });
    container.appendChild(clone);
  });
});

const leadType = document.getElementById('leadType');
const appointment = document.getElementById('appointmentField');
if (leadType && appointment) {
  const updateType = () => {
    const input = appointment.querySelector('input');
    const isTestDrive = leadType.value === 'test_drive';
    appointment.hidden = !isTestDrive;
    input.required = isTestDrive;
    if (!isTestDrive) input.value = '';
  };
  leadType.addEventListener('change', updateType);
  updateType();
}

const vehicleSelect = document.getElementById('leadVehicle');
const variantSelect = document.getElementById('leadVariant');
if (vehicleSelect && variantSelect) {
  const updateVariants = () => {
    [...variantSelect.options].forEach(option => {
      const hidden = option.dataset.vehicle && option.dataset.vehicle !== vehicleSelect.value;
      option.hidden = Boolean(hidden);
      option.disabled = Boolean(hidden);
      if (hidden && option.selected) variantSelect.value = '';
    });
  };
  vehicleSelect.addEventListener('change', updateVariants);
  updateVariants();
}

// Only validated video IDs can load the supported embed origin.
document.querySelectorAll('[data-load-video]').forEach(button => {
  button.addEventListener('click', () => {
    const container = button.closest('[data-youtube]');
    const videoId = container.dataset.youtube;
    if (!/^[a-zA-Z0-9_-]{11}$/.test(videoId)) return;
    const frame = document.createElement('iframe');
    frame.src = `https://www.youtube-nocookie.com/embed/${videoId}`;
    frame.title = document.documentElement.lang === 'en' ? 'Article video' : 'Video bài viết';
    frame.loading = 'lazy';
    frame.referrerPolicy = 'no-referrer';
    frame.allowFullscreen = true;
    frame.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-presentation');
    container.replaceChildren(frame);
  });
});
