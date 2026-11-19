document.querySelectorAll('[data-preview-form]').forEach((form) => {
    const submit = form.querySelector('button:not([type="button"])');
    const status = form.querySelector('[data-preview-status]');
    const lookupResult = form.querySelector('[data-lookup-result]');

    if (!submit || !status) return;

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (lookupResult) {
            const fields = new FormData(form);
            const found = fields.get('code').trim().toUpperCase() === 'YTDEMO'
                && fields.get('last_name').trim().toLowerCase() === 'traveler';
            lookupResult.hidden = !found;
            status.textContent = found ? 'Sample booking found.' : 'No matching sample booking.';
            return;
        }
        status.textContent = 'Preview complete. Your information was not sent or saved.';
    });

    submit.disabled = false;
});
