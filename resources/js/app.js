import './bootstrap';

const menu = document.querySelector('.menu-toggle');
const navigation = document.querySelector('#primary-nav');
const compact = window.matchMedia('(max-width: 64rem)');

if (menu && navigation) {
    const update = (expanded) => {
        menu.hidden = !compact.matches;
        menu.setAttribute('aria-expanded', String(expanded));
        menu.setAttribute('aria-label', expanded ? menu.dataset.closeLabel : menu.dataset.openLabel);
        navigation.dataset.collapsed = String(compact.matches && !expanded);
    };

    menu.addEventListener('click', () => update(menu.getAttribute('aria-expanded') !== 'true'));
    menu.closest('.site-header').addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || menu.getAttribute('aria-expanded') !== 'true') return;
        update(false);
        menu.focus();
    });
    compact.addEventListener('change', () => update(false));
    update(false);
}

document.querySelectorAll('[data-compare]').forEach((form) => {
    const choices = [...form.elements].filter((field) => field.type === 'checkbox');
    const status = form.querySelector('[data-compare-status]');

    if (!status) return;

    const update = () => {
        const selected = choices.filter((choice) => choice.checked).length;
        status.textContent = selected ? `${selected} selected (choose 2 or 3).` : 'Select 2 or 3 flights.';
        for (const choice of choices) {
            choice.disabled = !choice.checked && selected >= 3;
        }
    };

    choices.forEach((choice) => choice.addEventListener('change', update));
    form.addEventListener('submit', (event) => {
        if (choices.filter((choice) => choice.checked).length < 2) {
            event.preventDefault();
            status.textContent = 'Select at least two flights to compare.';
        }
    });
    update();
});
import './preview-forms';
import './landing';

document.querySelectorAll('[data-seat-picker]').forEach((picker) => {
    const toolbar = picker.querySelector('[data-seat-traveler-switch]');
    const travelers = [...toolbar.querySelectorAll('[data-seat-traveler]')];
    const panels = [...picker.querySelectorAll('[data-seat-panel]')];
    const groups = panels.map((panel) => [...panel.querySelectorAll('input[type="radio"]')]);
    const unavailable = groups.map((seats) => new Set(seats.filter((seat) => seat.disabled)));
    let active = 0;

    const update = () => {
        const selected = groups.map((seats) => seats.find((seat) => seat.checked));

        groups.forEach((seats, index) => {
            for (const seat of seats) {
                const assigned = selected.some((choice, owner) => owner !== index && choice?.value === seat.value);
                seat.disabled = unavailable[index].has(seat) || assigned;
            }

            panels[index].hidden = index !== active;
            travelers[index].setAttribute('aria-pressed', String(index === active));
            travelers[index].querySelector('[data-seat-summary]').textContent = selected[index]
                ? ` · ${selected[index].dataset.seatLabel}`
                : '';
        });
    };

    travelers.forEach((traveler, index) => {
        traveler.addEventListener('click', () => {
            active = index;
            update();
        });
    });

    picker.addEventListener('change', update);
    picker.addEventListener('invalid', (event) => {
        const index = panels.indexOf(event.target.closest('[data-seat-panel]'));
        if (index !== -1 && panels[index].hidden) {
            active = index;
            update();
        }
    }, true);

    toolbar.hidden = travelers.length < 2;
    picker.classList.add('seat-picker-ready');
    update();
});
