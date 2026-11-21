export default async function run(page) {
    const browserErrors = [];
    const pageFailures = [];
    const pages = {};
    const searches = {};
    const screenshotRoot = 'C:\\Users\\Orange\\AppData\\Local\\Temp\\opencode';

    page.on('console', (message) => {
        if (message.type() === 'error') browserErrors.push(message.text());
    });
    page.on('pageerror', (error) => pageFailures.push(error.message));

    await page.setViewportSize({ width: 390, height: 844 });

    const visit = async (name, path, screenshot = false) => {
        const response = await page.goto(new URL(path, 'http://127.0.0.1:8000').href, { waitUntil: 'domcontentloaded' });
        const layout = await page.evaluate(() => ({
            width: window.innerWidth,
            documentWidth: document.documentElement.scrollWidth,
            horizontalOverflow: document.documentElement.scrollWidth > window.innerWidth + 1,
            title: document.title,
            text: document.body.innerText.slice(0, 450),
        }));
        if (screenshot) {
            await page.screenshot({ path: `${screenshotRoot}\\passport-fullflow-${name}.png`, fullPage: true });
        }
        pages[name] = { status: response?.status(), url: page.url(), ...layout };
        return response;
    };

    await visit('landing', '/');
    await page.screenshot({ path: `${screenshotRoot}\\passport-fullflow-landing-390.png`, fullPage: true });

    const query = (from, to, depart) => `/flights?${new URLSearchParams({
        trip: 'one-way', from, to, depart, adults: '1', youth: '0',
        children: '0', infants: '0', cabin: '', sort: 'departure_asc',
    })}`;
    await visit('search-amm-ist', query('AMM', 'IST', '2026-11-30'));
    await page.screenshot({ path: `${screenshotRoot}\\passport-fullflow-search-390.png`, fullPage: true });
    searches['AMM-IST'] = await page.locator('.flight-result').evaluateAll((results) => results.map((result) => result.innerText));
    const detailsLinks = new Set(await page.locator('.flight-result a').evaluateAll((links) => links
        .map((link) => new URL(link.href).pathname)
        .filter((path) => /^\/flights\/\d+$/.test(path))));
    const flightIds = new Set([...detailsLinks].map((href) => Number(href.match(/\/flights\/(\d+)/)?.[1])).filter(Number.isInteger));

    await visit('round-trip-search', `/flights?${new URLSearchParams({
        trip: 'round-trip', from: 'AMM', to: 'IST', depart: '2026-11-30', return: '2026-12-01',
        adults: '1', youth: '0', children: '0', infants: '0', cabin: '', sort: 'departure_asc',
    })}`);
    searches['round-trip'] = await page.locator('.flight-result').evaluateAll((results) => results.map((result) => result.innerText));

    for (const [from, to, depart] of [['AMM', 'DXB', '2026-12-02'], ['AMM', 'CAI', '2026-12-03'], ['AMM', 'LHR', '2026-12-04']]) {
        await visit(`search-${to.toLowerCase()}`, query(from, to, depart));
        searches[to] = await page.locator('.flight-result').evaluateAll((results) => results.map((result) => result.innerText));
        const ids = await page.locator('.flight-result a').evaluateAll((links) => links
            .map((link) => Number(new URL(link.href).pathname.match(/\/flights\/(\d+)/)?.[1])));
        ids.filter(Number.isInteger).forEach((id) => flightIds.add(id));
    }

    const detailHref = [...detailsLinks][0];
    if (detailHref) await visit('flight-details', detailHref, true);
    const compareIds = [...flightIds].slice(0, 3);
    if (compareIds.length >= 2) {
        await visit('compare', `/compare?${compareIds.map((id) => `flights%5B%5D=${id}`).join('&')}`, true);
    } else {
        pages.compare = { status: 'not enough live matching flight ids', flightIds: compareIds };
    }

    await visit('destinations', '/destinations', true);
    await visit('contact', '/contact', true);
    await visit('not-found', '/this-page-does-not-exist');
    await visit('passport-scan-route', '/book/passport-scan');

    for (const [name, path] of [
        ['passengers-guest', '/book/passengers'],
        ['seats-guest', '/book/seats'],
        ['payment-guest', '/book/payment'],
        ['confirmation-guest', '/book/confirmation'],
    ]) {
        await visit(name, path);
    }

    const pageSummary = Object.fromEntries(Object.entries(pages).map(([name, result]) => [name, {
        status: result.status,
        url: result.url,
        width: result.width,
        documentWidth: result.documentWidth,
        horizontalOverflow: result.horizontalOverflow,
    }]));

    return { viewport: '390x844', pages: pageSummary, searches, flightIds: [...flightIds], browserErrors, pageFailures };
}
