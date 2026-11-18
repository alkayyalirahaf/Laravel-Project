import './bootstrap';

const menu = document.querySelector('.menu-toggle');
const navigation = document.querySelector('#primary-nav');
const compact = window.matchMedia('(max-width: 45rem)');

if (menu && navigation) {
    const update = (expanded) => {
        menu.hidden = !compact.matches;
        menu.setAttribute('aria-expanded', String(expanded));
        navigation.dataset.collapsed = String(compact.matches && !expanded);
    };

    menu.addEventListener('click', () => update(menu.getAttribute('aria-expanded') !== 'true'));
    compact.addEventListener('change', () => update(false));
    update(false);
}

document.querySelectorAll('[data-compare]').forEach((form) => {
    const choices = [...form.querySelectorAll('input[type="checkbox"]')];
    const status = form.querySelector('[data-compare-status]');

    if (!status) return;

    const update = () => {
        const selected = choices.filter((choice) => choice.checked).length;
        status.textContent = selected ? `${selected} selected (choose 2 or 3).` : 'Select 2 or 3 flights.';
        for (const choice of choices) {
            choice.disabled = !choice.checked && selected >= 3;
        }
    };

    form.addEventListener('change', update);
    form.addEventListener('submit', (event) => {
        if (choices.filter((choice) => choice.checked).length < 2) {
            event.preventDefault();
            status.textContent = 'Select at least two flights to compare.';
        }
    });
    update();
});
import './preview-forms';
