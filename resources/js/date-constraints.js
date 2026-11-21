// Use the site's travel timezone, including when a form stays open past midnight.
export const travelToday = () => new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Amman', year: 'numeric', month: '2-digit', day: '2-digit',
}).format(new Date());

document.querySelectorAll('form').forEach((form) => {
    const fields = [...form.querySelectorAll('input[type="date"], input[type="datetime-local"]')];
    if (!fields.length) return;

    // Keep explicit constraints such as passport expiry after the last flight.
    const originalMin = new Map(fields.map((field) => [field, field.min]));
    const update = () => {
        const today = travelToday();
        for (const field of fields) {
            if (field.name.includes('date_of_birth')) {
                field.max = today;
                continue;
            }
            let minimum = field.type === 'date' ? today : `${today}T00:00`;
            if (originalMin.get(field) > minimum) minimum = originalMin.get(field);
            const pairedName = { return: 'depart', arrival_at: 'departure_at', arrival: 'departure' }[field.name];
            const departure = pairedName && form.elements.namedItem(pairedName);
            if (departure?.value > minimum) minimum = departure.value;
            field.min = minimum;
        }
    };
    form.addEventListener('input', update);
    form.addEventListener('change', update);
    form.addEventListener('focusin', update);
    form.addEventListener('submit', (event) => {
        update();
        if (!form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
        }
    });
    update();
});
