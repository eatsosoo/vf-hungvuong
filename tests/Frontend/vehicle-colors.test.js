import assert from 'node:assert/strict';
import { afterEach, test } from 'node:test';

const originalGlobals = new Map(['document', 'window', 'Image'].map(key => [key, globalThis[key]]));
let scenario = 0;

afterEach(() => {
    originalGlobals.forEach((value, key) => {
        if (value === undefined) delete globalThis[key];
        else globalThis[key] = value;
    });
});

const flush = () => new Promise(resolve => setImmediate(resolve));

class Element {
    constructor(attributes = {}) {
        this.attributes = new Map(Object.entries(attributes));
        this.listeners = new Map();
        this.animations = [];
        this.hidden = false;
        this.textContent = '';
        const classes = new Set();
        this.classList = {
            add: name => classes.add(name),
            remove: name => classes.delete(name),
            contains: name => classes.has(name),
        };
    }

    addEventListener(type, handler, options = {}) {
        const listeners = this.listeners.get(type) || [];
        listeners.push({ handler, once: options.once });
        this.listeners.set(type, listeners);
    }

    dispatch(type, input = {}) {
        const event = { button: 0, ...input, prevented: false, preventDefault() { this.prevented = true; } };
        const listeners = [...(this.listeners.get(type) || [])];
        listeners.forEach(listener => {
            listener.handler(event);
            if (listener.once) {
                this.listeners.set(type, this.listeners.get(type).filter(item => item !== listener));
            }
        });
        return event;
    }

    getAttribute(name) { return this.attributes.get(name) ?? null; }
    setAttribute(name, value) { this.attributes.set(name, value); }
    focus() { this.focused = true; }

    animate(frames, timing) {
        let finish;
        let abort;
        const finished = new Promise((resolve, reject) => { finish = resolve; abort = reject; });
        finished.catch(() => {});
        const animation = {
            frames, timing, finished, cancelled: false,
            cancel() { this.cancelled = true; abort(new DOMException('Animation cancelled', 'AbortError')); },
            finish,
        };
        this.animations.push(animation);
        return animation;
    }
}

async function viewerFixture({ reducedMotion = false, complete = true, language = 'vi' } = {}) {
    const image = new Element();
    image.src = 'http://localhost/white.webp';
    image.alt = 'Xe màu trắng';
    image.complete = complete;
    image.naturalWidth = complete ? 900 : 0;
    const outgoing = new Element();
    outgoing.hidden = true;
    const stage = new Element({ 'aria-busy': 'false' });
    const loading = new Element();
    loading.hidden = true;
    const feedback = new Element();
    feedback.classList.add('sr-only');
    const colorName = new Element();
    colorName.textContent = 'Trắng';
    const options = ['Trắng', 'Đỏ', 'Đen'].map((name, index) => {
        const option = new Element({ 'aria-current': String(index === 0) });
        option.dataset = {
            name, image: `http://localhost/color-${index}.webp`, alt: `Xe màu ${name}`,
        };
        option.href = `http://localhost/xe/vf7?color=${index + 1}`;
        return option;
    });
    const elements = new Map([
        ['[data-vehicle-image]', image], ['[data-vehicle-image-outgoing]', outgoing],
        ['[data-vehicle-stage]', stage], ['[data-vehicle-color-name]', colorName],
        ['[data-vehicle-color-loading]', loading], ['[data-vehicle-color-feedback]', feedback],
    ]);
    const viewer = {
        querySelector: selector => elements.get(selector),
        querySelectorAll: selector => selector === '[data-vehicle-color]' ? options : [],
    };
    const requests = [];
    const timers = new Map();
    let timerId = 0;
    const preference = new Element();
    preference.matches = reducedMotion;
    const history = [];
    globalThis.document = {
        documentElement: { lang: language },
        querySelectorAll: selector => selector === '[data-vehicle-color-viewer]' ? [viewer] : [],
    };
    globalThis.window = {
        matchMedia: () => preference,
        setTimeout: handler => { const id = ++timerId; timers.set(id, handler); return id; },
        clearTimeout: id => timers.delete(id),
        location: { href: 'http://localhost/xe/vf7?campaign=demo&color=1#colors' },
        history: {
            state: { marker: 'existing' },
            replaceState(state, title, url) {
                history.push({ state, title, url: url.href });
                window.location.href = url.href;
            },
        },
    };
    globalThis.Image = class {
        set src(source) { this.source = source; requests.push(this); }
        decode() { return this.decodeFailure ? Promise.reject(new Error('Decode failed')) : Promise.resolve(); }
    };
    await import(`../../resources/js/vehicle-colors.js?scenario=${++scenario}`);

    return {
        image, outgoing, stage, loading, feedback, colorName, options, requests, timers, preference, history,
        async load(index = requests.length - 1) { await requests[index].onload(); await flush(); },
        async fail(index = requests.length - 1) { requests[index].onerror(); await flush(); },
    };
}

test('color selection updates the car, accessible selection and URL after the image loads', async () => {
    const fixture = await viewerFixture();
    const event = fixture.options[1].dispatch('click');
    assert.equal(event.prevented, true);
    assert.equal(fixture.stage.getAttribute('aria-busy'), 'true');
    assert.equal(fixture.loading.hidden, false);
    assert.equal(fixture.image.src, 'http://localhost/white.webp');
    await fixture.load();
    assert.equal(fixture.image.src, fixture.options[1].dataset.image);
    assert.equal(fixture.image.alt, 'Xe màu Đỏ');
    assert.equal(fixture.colorName.textContent, 'Đỏ');
    assert.deepEqual(fixture.options.map(option => option.getAttribute('aria-current')), ['false', 'true', 'false']);
    assert.equal(fixture.history[0].url, 'http://localhost/xe/vf7?campaign=demo&color=2#colors');
    assert.equal(fixture.history[0].state.marker, 'existing');
    assert.equal(fixture.stage.getAttribute('aria-busy'), 'false');
    assert.equal(fixture.loading.hidden, true);
    assert.equal(fixture.outgoing.src, 'http://localhost/white.webp');
    assert.equal(fixture.image.animations.at(-1).frames[0].transform, 'translateX(100%)');
    assert.equal(fixture.outgoing.animations.at(-1).frames[1].transform, 'translateX(-100%)');
});

test('color changes fade the previous car out and the loaded car in alongside the carousel', async () => {
    const fixture = await viewerFixture();
    for (const index of [2, 1]) {
        fixture.options[index].dispatch('click');
        await fixture.load();
        const incoming = fixture.image.animations.at(-1);
        const outgoing = fixture.outgoing.animations.at(-1);
        assert.deepEqual(incoming.frames.map(frame => frame.opacity), [0, 1]);
        assert.deepEqual(outgoing.frames.map(frame => frame.opacity), [1, 0]);
        assert.equal(incoming.timing.duration, 550);
        assert.deepEqual(incoming.timing, outgoing.timing);
        incoming.finish();
        outgoing.finish();
        await flush();
        assert.equal(fixture.outgoing.hidden, true);
        assert.equal(fixture.image.src, fixture.options[index].dataset.image);
    }
});

test('the latest selection wins when images finish loading out of order', async () => {
    const fixture = await viewerFixture();
    fixture.options[1].dispatch('click');
    fixture.options[2].dispatch('click');
    await fixture.load(1);
    await fixture.load(0);
    assert.equal(fixture.image.src, fixture.options[2].dataset.image);
    assert.equal(fixture.colorName.textContent, 'Đen');
    assert.equal(fixture.history.length, 1);
    assert.equal(fixture.feedback.textContent, '');
});

test('an earlier failed request cannot replace the latest successful selection with an error', async () => {
    const fixture = await viewerFixture();
    fixture.options[1].dispatch('click');
    fixture.options[2].dispatch('click');
    await fixture.load(1);
    await fixture.fail(0);
    assert.equal(fixture.image.src, fixture.options[2].dataset.image);
    assert.equal(fixture.feedback.textContent, '');
    assert.equal(fixture.feedback.classList.contains('sr-only'), true);
});

test('load errors keep the current car and allow a fresh request on retry', async () => {
    const fixture = await viewerFixture({ language: 'en' });
    fixture.options[1].dispatch('click');
    await fixture.fail();
    assert.equal(fixture.image.src, 'http://localhost/white.webp');
    assert.equal(fixture.options[0].getAttribute('aria-current'), 'true');
    assert.equal(fixture.history.length, 0);
    assert.equal(fixture.feedback.textContent, 'Unable to load this color. Please select it again to retry.');
    assert.equal(fixture.feedback.classList.contains('sr-only'), false);
    assert.equal(fixture.stage.getAttribute('aria-busy'), 'false');
    fixture.options[1].dispatch('click');
    assert.equal(fixture.requests.length, 2);
    await fixture.load();
    assert.equal(fixture.image.src, fixture.options[1].dataset.image);
    assert.equal(fixture.feedback.textContent, '');
});

test('decode failures and timeouts release loading state without changing the selected car', async () => {
    for (const failure of ['decode', 'timeout']) {
        const fixture = await viewerFixture();
        fixture.options[1].dispatch('click');
        if (failure === 'decode') {
            fixture.requests[0].decodeFailure = true;
            await fixture.load();
        } else {
            [...fixture.timers.values()].forEach(handler => handler());
            await flush();
        }
        assert.equal(fixture.image.src, 'http://localhost/white.webp');
        assert.equal(fixture.loading.hidden, true);
        assert.equal(fixture.stage.getAttribute('aria-busy'), 'false');
        assert.equal(fixture.feedback.classList.contains('sr-only'), false);
    }
});

test('keyboard arrows wrap options while preserving their requested slide direction', async () => {
    const fixture = await viewerFixture();
    fixture.options[0].dispatch('keydown', { key: 'ArrowLeft' });
    await fixture.load();
    assert.equal(fixture.options[2].focused, true);
    assert.equal(fixture.image.animations.at(-1).frames[0].transform, 'translateX(-100%)');
    fixture.options[2].dispatch('keydown', { key: 'ArrowRight' });
    await fixture.load();
    assert.equal(fixture.options[0].focused, true);
    assert.equal(fixture.image.animations.at(-1).frames[0].transform, 'translateX(100%)');
});

test('Home and End select boundary colors and unrelated keys retain native behavior', async () => {
    const fixture = await viewerFixture();
    assert.equal(fixture.options[0].dispatch('keydown', { key: 'Tab' }).prevented, false);
    fixture.options[0].dispatch('keydown', { key: 'End' });
    await fixture.load();
    assert.equal(fixture.options[2].getAttribute('aria-current'), 'true');
    fixture.options[2].dispatch('keydown', { key: 'Home' });
    await fixture.load();
    assert.equal(fixture.options[0].getAttribute('aria-current'), 'true');
});

test('modified navigation keys retain browser shortcuts without selecting another color', async () => {
    const fixture = await viewerFixture();
    for (const modifier of ['metaKey', 'ctrlKey', 'shiftKey', 'altKey']) {
        for (const key of ['Home', 'End', 'ArrowLeft', 'ArrowRight']) {
            const event = fixture.options[1].dispatch('keydown', { key, [modifier]: true });
            assert.equal(event.prevented, false);
        }
    }
    assert.equal(fixture.requests.length, 0);
    assert.equal(fixture.history.length, 0);
    assert.deepEqual(fixture.options.map(option => option.getAttribute('aria-current')), ['true', 'false', 'false']);
});

test('reduced motion skips entrance while explicit color changes retain carousel movement', async () => {
    const fixture = await viewerFixture({ reducedMotion: true });
    assert.equal(fixture.image.animations.length, 0);
    fixture.options[1].dispatch('click');
    await fixture.load();
    assert.equal(fixture.image.animations.at(-1).timing.duration, 550);
    assert.equal(fixture.outgoing.hidden, false);
});

test('manual selection cancels pending entrance and a completed slide removes the outgoing image', async () => {
    const fixture = await viewerFixture({ complete: false });
    fixture.options[1].dispatch('click');
    fixture.image.naturalWidth = 900;
    fixture.image.dispatch('load');
    assert.equal(fixture.image.animations.length, 0);
    await fixture.load();
    fixture.image.animations.at(-1).finish();
    fixture.outgoing.animations.at(-1).finish();
    await flush();
    assert.equal(fixture.outgoing.hidden, true);
});

test('cached colors load once and returning to the current color cancels pending selection', async () => {
    const fixture = await viewerFixture();
    fixture.options[1].dispatch('click');
    await fixture.load();
    fixture.options[2].dispatch('click');
    await fixture.load();
    fixture.options[1].dispatch('click');
    await flush();
    assert.equal(fixture.requests.length, 2);
    assert.equal(fixture.image.src, fixture.options[1].dataset.image);
    fixture.options[0].dispatch('click');
    fixture.options[1].dispatch('click');
    await fixture.load();
    assert.equal(fixture.image.src, fixture.options[1].dataset.image);
    assert.equal(fixture.loading.hidden, true);
});

test('cancelled animation rejections cannot hide the outgoing image for a newer cached selection', async () => {
    const fixture = await viewerFixture();
    fixture.options[1].dispatch('click');
    await fixture.load();
    fixture.options[2].dispatch('click');
    await fixture.load();
    const oldPair = [fixture.image.animations.at(-1), fixture.outgoing.animations.at(-1)];
    fixture.options[1].dispatch('click');
    await flush();
    const oldResults = await Promise.allSettled(oldPair.map(animation => animation.finished));

    assert.deepEqual(oldResults.map(result => result.status), ['rejected', 'rejected']);
    assert.equal(oldPair.every(animation => animation.cancelled), true);
    assert.equal(fixture.image.src, fixture.options[1].dataset.image);
    assert.equal(fixture.outgoing.src, fixture.options[2].dataset.image);
    assert.equal(fixture.outgoing.hidden, false);
    assert.equal(fixture.image.animations.at(-1).cancelled, false);
    assert.equal(fixture.outgoing.animations.at(-1).cancelled, false);
});

test('modified clicks leave browser link behavior intact', async () => {
    const fixture = await viewerFixture();
    const modifiers = [{ metaKey: true }, { ctrlKey: true }, { shiftKey: true }, { altKey: true }, { button: 1 }];
    for (const modifier of modifiers) {
        assert.equal(fixture.options[1].dispatch('click', modifier).prevented, false);
    }
    assert.equal(fixture.requests.length, 0);
    assert.equal(fixture.history.length, 0);
});
