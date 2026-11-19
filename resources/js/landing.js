const travelTools = document.querySelector('[data-travel-tools]');
if (travelTools) {
    const tabs = [...travelTools.querySelectorAll('[role="tab"]')];
    const activate = (selected) => {
        for (const tab of tabs) {
            const active = tab === selected;
            tab.setAttribute('aria-selected', String(active));
            tab.tabIndex = active ? 0 : -1;
            document.getElementById(tab.getAttribute('aria-controls')).hidden = !active;
        }
    };
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activate(tab));
        tab.addEventListener('keydown', (event) => {
            const direction = getComputedStyle(tab).direction === 'rtl' ? -1 : 1;
            const target = {
                Home: tabs[0],
                End: tabs.at(-1),
                ArrowRight: tabs[(index + direction + tabs.length) % tabs.length],
                ArrowLeft: tabs[(index - direction + tabs.length) % tabs.length],
            }[event.key];
            if (!(target instanceof HTMLElement)) return;
            event.preventDefault();
            activate(target);
            target.focus();
        });
    });

    const statusForm = travelTools.querySelector('[data-flight-status]');
    const mode = statusForm.elements.status_mode;
    const updateStatusFields = () => {
        for (const group of statusForm.querySelectorAll('[data-status-fields]')) {
            const active = group.dataset.statusFields === mode.value;
            group.hidden = !active;
            for (const field of group.querySelectorAll('input, select')) {
                field.disabled = !active;
                field.required = active;
            }
        }
    };
    mode.addEventListener('change', updateStatusFields);
    updateStatusFields();
}

const form = document.querySelector('.landing-search');

if (form) {
    const { from, to, depart, return: returnDate, trip } = form.elements;
    const travelers = ['adults', 'youth', 'children', 'infants'].map((name) => form.elements[name]);
    const today = new Date();
    today.setMinutes(today.getMinutes() - today.getTimezoneOffset());
    depart.min = today.toISOString().slice(0, 10);
    const single = form.querySelector('[data-single-trip]');
    const multi = form.querySelector('[data-multi-trip]');
    const legs = [...multi.querySelectorAll('[data-trip-leg]')];
    const addLeg = multi.querySelector('[data-add-leg]');
    const removeLeg = multi.querySelector('[data-remove-leg]');
    let legCount = 2;

    const updateSearch = () => {
        const roundTrip = trip.value === 'round-trip';
        const multiCity = trip.value === 'multi-city';
        single.hidden = single.disabled = multiCity;
        multi.hidden = multi.disabled = !multiCity;
        addLeg.hidden = legCount === legs.length;
        removeLeg.hidden = legCount === 2;
        let earliest = depart.min;
        legs.forEach((leg, index) => {
            leg.hidden = leg.disabled = index >= legCount;
            if (leg.disabled) return;
            const [origin, destination, date] = leg.elements;
            destination.setCustomValidity(origin.value && origin.value === destination.value ? form.dataset.swapError : '');
            date.min = earliest;
            if (date.value > earliest) earliest = date.value;
        });
        returnDate.closest('.return-date').hidden = !roundTrip;
        returnDate.disabled = !roundTrip;
        returnDate.required = roundTrip;
        returnDate.min = depart.value || depart.min;
        to.setCustomValidity(from.value && from.value === to.value ? form.dataset.swapError : '');
        const count = travelers.reduce((total, field) => total + Number(field.value), 0);
        for (const field of travelers) {
            const stepper = field.closest('[data-traveler-counter]');
            stepper.querySelector('[data-step="-1"]').disabled = field.value === field.min;
            stepper.querySelector('[data-step="1"]').disabled = field.value === field.max;
        }
        form.querySelector('[data-traveler-summary]').textContent = count === 1
            ? form.dataset.travelerOne
            : form.dataset.travelerMany.replace(':count', count);
    };

    form.addEventListener('change', updateSearch);
    addLeg.addEventListener('click', () => {
        legCount += 1;
        updateSearch();
        legs[legCount - 1].elements[0].focus();
    });
    removeLeg.addEventListener('click', () => {
        legCount -= 1;
        updateSearch();
        addLeg.focus();
    });
    for (const button of form.querySelectorAll('[data-step]')) {
        button.addEventListener('click', () => {
            const field = button.closest('[data-traveler-counter]').querySelector('input');
            field.stepUp(Number(button.dataset.step));
            updateSearch();
        });
    }
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
const destinationTrack = document.querySelector('[data-destination-track]');
if (destinationTrack?.children.length > 1) {
    const slides = [...destinationTrack.querySelectorAll('.destination-slide')];
    const edgeCount = Math.min(3, slides.length);
    const cloneSlide = (slide) => {
        const clone = slide.cloneNode(true);
        clone.setAttribute('aria-hidden', 'true');
        return clone;
    };
    destinationTrack.prepend(...slides.slice(-edgeCount).map(cloneSlide));
    destinationTrack.append(...slides.slice(0, edgeCount).map(cloneSlide));
    const trackSlides = [...destinationTrack.children];
    const originalIndex = (index) => (index - edgeCount + slides.length) % slides.length;
    const viewportCenter = () => {
        const { left, width } = destinationTrack.getBoundingClientRect();
        return left + width / 2;
    };
    const offset = (slide, center) => {
        const { left, width } = slide.getBoundingClientRect();
        return left + width / 2 - center;
    };
    let centered;
    const updateCentered = () => {
        const center = viewportCenter();
        const nearest = trackSlides.reduce((first, next) =>
            Math.abs(offset(next, center)) < Math.abs(offset(first, center)) ? next : first);
        if (nearest === centered) return;
        centered = nearest;
        const index = originalIndex(trackSlides.indexOf(centered));
        trackSlides.forEach((slide, position) => {
            slide.classList.toggle('is-centered', originalIndex(position) === index);
        });
    };
    const scrollToSlide = (slide, behavior = motion.matches ? 'instant' : 'smooth') => {
        destinationTrack.scrollBy({ left: offset(slide, viewportCenter()), behavior });
    };

    scrollToSlide(slides[Math.floor(slides.length / 2)], 'instant');
    updateCentered();
    destinationTrack.addEventListener('scroll', updateCentered, { passive: true });
    new ResizeObserver(() => scrollToSlide(centered, 'instant')).observe(destinationTrack);

    let drag;
    destinationTrack.addEventListener('scrollend', () => {
        if (drag) return;
        updateCentered();
        const original = slides[originalIndex(trackSlides.indexOf(centered))];
        if (original !== centered) {
            scrollToSlide(original, 'instant');
            updateCentered();
        }
    });
    destinationTrack.addEventListener('pointerdown', (event) => {
        if (event.pointerType !== 'mouse' || event.button !== 0) return;
        drag = { id: event.pointerId, x: event.clientX, left: destinationTrack.scrollLeft };
    });
    destinationTrack.addEventListener('pointermove', (event) => {
        if (!drag || event.pointerId !== drag.id) return;
        const distance = event.clientX - drag.x;
        if (!drag.moved && Math.abs(distance) < 6) return;
        if (!drag.moved) {
            drag.moved = true;
            destinationTrack.setPointerCapture(event.pointerId);
            destinationTrack.classList.add('is-dragging');
        }
        destinationTrack.scrollLeft = drag.left - distance;
    });
    const endDrag = (event) => {
        if (!drag || event.pointerId !== drag.id) return;
        if (drag.moved) {
            updateCentered();
            destinationTrack.classList.remove('is-dragging');
            scrollToSlide(centered);
        }
        drag = undefined;
    };
    destinationTrack.addEventListener('pointerup', endDrag);
    destinationTrack.addEventListener('pointercancel', endDrag);
    destinationTrack.addEventListener('pointerleave', (event) => {
        if (drag && !drag.moved) endDrag(event);
    });

    setInterval(() => {
        if (motion.matches || document.hidden || drag || destinationTrack.matches(':focus-visible')) return;
        const next = trackSlides[trackSlides.indexOf(centered) + 1];
        if (next) scrollToSlide(next);
    }, 6500);
}

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

const heroVideo = document.querySelector('.hero-video');
if (heroVideo) {
    const updatePlayback = () => {
        if (motion.matches || document.hidden) {
            heroVideo.pause();
        } else {
            heroVideo.play().catch(() => {});
        }
    };
    document.addEventListener('visibilitychange', updatePlayback);
    motion.addEventListener('change', updatePlayback);
    updatePlayback();
}

for (const photo of document.querySelectorAll('[data-destination-photo]')) {
    const showTextCard = () => {
        photo.hidden = true;
        photo.closest('.destination-card').classList.add('destination-card-text');
    };
    photo.addEventListener('error', showTextCard, { once: true });
    if (photo.complete && !photo.naturalWidth) showTextCard();
}
