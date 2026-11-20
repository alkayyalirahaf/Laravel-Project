@props(['passengerIndex'])

<section class="passport-scan" data-passport-scan data-passenger-index="{{ $passengerIndex }}" data-endpoint="{{ url('/book/passport-scan') }}">
    <h3>Optional passport scan</h3>
    <p>The image is sent to Google Gemini for processing and is not kept by this app. Review suggestions carefully; scanning will not submit the passenger form.</p>
    <label class="field">
        <span>Passport image (JPEG, PNG, or WebP; up to 5 MB)</span>
        <input type="file" accept="image/jpeg,image/png,image/webp" data-passport-image>
    </label>
    <label>
        <input type="checkbox" data-passport-consent>
        I agree to send this passport image to Google Gemini for one-time extraction.
    </label>
    <button class="button secondary" type="button" data-passport-scan-button>Scan passport</button>
    <p role="status" aria-live="polite" data-passport-status></p>
    <div data-passport-results hidden>
        <p><strong>Check every suggestion against the passport before using it.</strong> You can edit these values below before confirming.</p>
        <ul data-passport-warnings></ul>
        <div class="form-grid" data-passport-review></div>
        <button class="button" type="button" data-passport-confirm disabled>Use reviewed values</button>
    </div>
</section>

<script>
    document.querySelectorAll('[data-passport-scan]:not([data-initialized])').forEach((component) => {
        component.dataset.initialized = 'true';

        const imageInput = component.querySelector('[data-passport-image]');
        const consentInput = component.querySelector('[data-passport-consent]');
        const scanButton = component.querySelector('[data-passport-scan-button]');
        const confirmButton = component.querySelector('[data-passport-confirm]');
        const status = component.querySelector('[data-passport-status]');
        const results = component.querySelector('[data-passport-results]');
        const review = component.querySelector('[data-passport-review]');
        const warnings = component.querySelector('[data-passport-warnings]');
        const labels = {
            first_name: 'First / given name',
            last_name: 'Last / family name',
            date_of_birth: 'Date of birth',
            gender: 'Gender',
            nationality: 'Nationality',
            passport_number: 'Passport number',
            passport_country: 'Issuing country',
            passport_expiry: 'Passport expiry',
        };

        scanButton.addEventListener('click', async () => {
            const file = imageInput.files?.[0];
            if (!file || !consentInput.checked) {
                status.textContent = 'Choose an image and confirm consent to continue.';
                return;
            }

            const formData = new FormData();
            formData.append('passport_image', file);
            formData.append('consent', '1');

            const form = component.closest('form');
            const csrfToken = form?.querySelector('input[name="_token"]')?.value;
            scanButton.disabled = true;
            confirmButton.disabled = true;
            results.hidden = true;
            review.replaceChildren();
            warnings.replaceChildren();
            component.dataset.confirmed = 'false';
            status.textContent = 'Scanning passport…';

            try {
                const response = await fetch(component.dataset.endpoint, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                    },
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (!response.ok || data.available !== true || !data.fields) {
                    const message = Array.isArray(data.warnings) ? data.warnings[0] : null;
                    throw new Error(message || 'Passport scanning is unavailable. Please enter the details manually.');
                }

                for (const message of (Array.isArray(data.warnings) ? data.warnings : [])) {
                    const item = document.createElement('li');
                    item.textContent = message;
                    warnings.append(item);
                }

                for (const [field, label] of Object.entries(labels)) {
                    const wrapper = document.createElement('label');
                    wrapper.className = 'field';
                    const caption = document.createElement('span');
                    caption.textContent = label;
                    wrapper.append(caption);

                    const value = data.fields[field];
                    if (typeof value !== 'string' || value === '') {
                        const unreadable = document.createElement('span');
                        unreadable.textContent = 'Not read — leave blank and enter manually if needed.';
                        wrapper.append(unreadable);
                        review.append(wrapper);
                        continue;
                    }

                    let control;
                    if (field === 'gender') {
                        control = document.createElement('select');
                        for (const [optionValue, optionLabel] of [['', 'Select'], ['male', 'Male'], ['female', 'Female']]) {
                            const option = document.createElement('option');
                            option.value = optionValue;
                            option.textContent = optionLabel;
                            control.append(option);
                        }
                        control.value = value;
                    } else {
                        control = document.createElement('input');
                        control.type = ['date_of_birth', 'passport_expiry'].includes(field) ? 'date' : 'text';
                        control.value = value;
                        control.maxLength = field === 'passport_number' ? 20 : 100;
                    }
                    control.dataset.passportField = field;
                    wrapper.append(control);

                    const score = Number(data.confidence?.[field]);
                    if (Number.isFinite(score) && score > 0) {
                        const confidence = document.createElement('small');
                        confidence.textContent = `Reading confidence: ${Math.round(score * 100)}%`;
                        wrapper.append(confidence);
                    }
                    review.append(wrapper);
                }

                results.hidden = false;
                confirmButton.disabled = false;
                status.textContent = 'Scan complete. Review and confirm suggestions, or enter details manually.';
            } catch (error) {
                status.textContent = error instanceof Error
                    ? error.message
                    : 'Passport scanning is unavailable. Please enter the details manually.';
            } finally {
                imageInput.value = '';
                scanButton.disabled = false;
            }
        });

        confirmButton.addEventListener('click', () => {
            const form = component.closest('form');
            const passengerIndex = component.dataset.passengerIndex;
            const confirmedFields = {};

            component.querySelectorAll('[data-passport-field]').forEach((suggestion) => {
                const field = suggestion.dataset.passportField;
                const target = form?.elements.namedItem(`passengers[${passengerIndex}][${field}]`);
                if (!target || !suggestion.value) return;

                target.value = suggestion.value;
                target.dispatchEvent(new Event('input', { bubbles: true }));
                target.dispatchEvent(new Event('change', { bubbles: true }));
                confirmedFields[field] = suggestion.value;
            });

            if (Object.keys(confirmedFields).length === 0) {
                status.textContent = 'No suggested values were selected. Enter the passenger details manually.';
                return;
            }

            const fieldset = component.closest('fieldset');
            const confirmationName = `passengers[${passengerIndex}][filled_by_ai]`;
            let confirmation = [...(fieldset?.querySelectorAll('input[type="hidden"]') ?? [])]
                .find((input) => input.name === confirmationName);

            if (!fieldset) return;
            if (!confirmation) {
                confirmation = document.createElement('input');
                confirmation.type = 'hidden';
                confirmation.name = confirmationName;
                fieldset.append(confirmation);
            }
            confirmation.value = '1';

            component.dataset.confirmed = 'true';
            status.textContent = 'Reviewed values copied to the passenger form. Check them again before continuing.';
            form?.dispatchEvent(new CustomEvent('passport-scan:confirmed', {
                bubbles: true,
                detail: { passengerIndex, fields: confirmedFields, filledByAi: true },
            }));
        });
    });
</script>
