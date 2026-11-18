const form = document.querySelector('.landing-search');

if (form) {
    const { from, to, depart, return: returnDate, trip } = form.elements;
    const travelers = ['adults', 'youth', 'children', 'infants'].map((name) => form.elements[name]);
    const today = new Date();
    today.setMinutes(today.getMinutes() - today.getTimezoneOffset());
    depart.min = today.toISOString().slice(0, 10);

    const updateSearch = () => {
        const roundTrip = trip.value === 'round-trip';
        returnDate.closest('.return-date').hidden = !roundTrip;
        returnDate.disabled = !roundTrip;
        returnDate.required = roundTrip;
        returnDate.min = depart.value || depart.min;
        to.setCustomValidity(from.value && from.value === to.value ? form.dataset.swapError : '');
        const count = travelers.reduce((total, field) => total + Number(field.value), 0);
        form.querySelector('[data-traveler-summary]').textContent = count === 1
            ? form.dataset.travelerOne
            : form.dataset.travelerMany.replace(':count', count);
    };

    form.addEventListener('change', updateSearch);
    form.querySelector('.swap-airports').addEventListener('click', () => {
        [from.value, to.value] = [to.value, from.value];
        updateSearch();
    });
    updateSearch();
}

const promptButtons = [...document.querySelectorAll('[data-prompt]')];
for (const button of promptButtons) {
    button.addEventListener('click', () => {
        document.querySelector('[data-preview-question]').textContent = button.textContent;
        document.querySelector('[data-preview-answer]').textContent = button.dataset.prompt;
        for (const prompt of promptButtons) {
            prompt.setAttribute('aria-pressed', String(prompt === button));
        }
    });
}

const cabinOptions = document.querySelector('.cabin-options');
if (cabinOptions) {
    const showCabin = () => {
        const selected = cabinOptions.querySelector(':checked').value;
        for (const figure of document.querySelectorAll('[data-cabin]')) {
            figure.hidden = figure.dataset.cabin !== selected;
        }
    };
    cabinOptions.addEventListener('change', showCabin);
    showCabin();
}

const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
const journey = document.querySelector('.landing-journey');
if (journey && !motion.matches) {
    const observer = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            if (entry.isIntersecting) {
                entry.target.classList.add('reveal-in');
                observer.unobserve(entry.target);
            }
        }
    }, { threshold: .12 });
    document.querySelectorAll('[data-reveal]').forEach((section) => observer.observe(section));

    let frame;
    const updatePath = () => {
        const { top, height } = journey.getBoundingClientRect();
        const progress = Math.max(0, Math.min(1, (innerHeight * .8 - top) / height));
        journey.style.setProperty('--path-offset', 1 - progress);
        frame = undefined;
    };
    const schedule = () => { frame ??= requestAnimationFrame(updatePath); };
    addEventListener('scroll', schedule, { passive: true });
    addEventListener('resize', schedule);
    updatePath();
}

const hero = document.querySelector('.landing-hero');
if (hero) {
    const slides = [...hero.querySelectorAll('.hero-slide')];
    let active = 0;
    let request = 0;
    let timer;

    const preferSource = (slide) => {
        if (slide.dataset.sourceRequested) return;
        slide.dataset.sourceRequested = 'true';
        const original = new Image();
        original.onload = () => { slide.src = original.src; };
        original.src = slide.dataset.source;
    };

    preferSource(slides[0]);

    const show = async (index) => {
        const token = ++request;
        const slide = slides[index];
        if (!slide.hasAttribute('src')) slide.src = slide.dataset.src;
        try {
            await slide.decode();
        } catch {
            return;
        }
        if (token !== request) return;
        slides[active].classList.remove('is-active');
        slide.classList.add('is-active');
        active = index;
        preferSource(slide);
    };

    const pause = () => clearInterval(timer);
    const resume = () => {
        pause();
        if (!motion.matches && !document.hidden && slides.length > 1) {
            let next = active;
            timer = setInterval(() => {
                next = (next + 1) % slides.length;
                show(next);
            }, 6500);
        }
    };

    document.addEventListener('visibilitychange', resume);
    motion.addEventListener('change', resume);
    resume();
}

for (const photo of document.querySelectorAll('[data-destination-photo]')) {
    const showTextCard = () => {
        photo.hidden = true;
        photo.closest('.destination-card').classList.add('destination-card-text');
    };
    photo.addEventListener('error', showTextCard, { once: true });
    if (photo.complete && !photo.naturalWidth) showTextCard();
}
