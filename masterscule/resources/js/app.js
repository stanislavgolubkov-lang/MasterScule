document.addEventListener('click', (event) => {
    const openTarget = event.target.closest('[data-open]');
    const closeTarget = event.target.closest('[data-close]');
    const aiPrompt = event.target.closest('[data-ai-prompt]');
    const aiOpen = event.target.closest('[data-ai-open]');
    const aiClose = event.target.closest('[data-ai-close]');
    const catalogOpen = event.target.closest('[data-catalog-open]');
    const catalogClose = event.target.closest('[data-catalog-close]');
    const heroDot = event.target.closest('[data-hero-dot]');

    if (openTarget) {
        const target = document.getElementById(openTarget.dataset.open);
        if (target) target.hidden = false;
    }

    if (closeTarget) {
        const target = document.getElementById(closeTarget.dataset.close);
        if (target) target.hidden = true;
    }

    if (aiPrompt) {
        const input = aiPrompt.closest('form')?.querySelector('textarea[name="prompt"]') || document.querySelector('textarea[name="prompt"]');
        if (input) {
            input.value = aiPrompt.dataset.aiPrompt;
            input.focus();
        }
    }

    if (aiOpen) {
        event.preventDefault();
        openAiModal(aiOpen.dataset.aiPrefill || '');
    }

    if (aiClose) {
        closeAiModal();
    }

    if (catalogOpen) {
        event.preventDefault();
        openCatalogModal();
    }

    if (catalogClose) {
        closeCatalogModal();
    }

    if (heroDot) {
        setHeroSlide(heroDot.closest('[data-hero-slider]'), Number(heroDot.dataset.heroDot));
    }
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-hero-slider]').forEach((slider) => {
        slider.dataset.heroIndex = '0';
        window.setInterval(() => changeHeroSlide(slider, 1), 6500);
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeAiModal();
        closeCatalogModal();
    }
});

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('.ai-modal-form');
    if (!form) return;

    event.preventDefault();

    const modal = document.getElementById('ai-modal');
    const state = modal?.querySelector('.ai-modal-state');
    const responseBox = modal?.querySelector('.ai-modal-response');
    const productsBox = modal?.querySelector('.ai-modal-products');
    const button = form.querySelector('button[type="submit"]');

    if (!state || !responseBox || !productsBox || !button) return;

    state.hidden = false;
    state.textContent = 'AI pregateste raspunsul...';
    responseBox.hidden = true;
    productsBox.innerHTML = '';
    button.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new FormData(form),
        });

        if (!response.ok) throw new Error('Request failed');

        const payload = await response.json();
        state.hidden = true;
        responseBox.hidden = false;
        responseBox.textContent = payload.response || '';
        productsBox.innerHTML = (payload.products || []).map((product) => `
            <a class="ai-mini-product" href="${escapeAttr(product.url)}">
                <img src="${escapeAttr(product.image)}" alt="">
                <span><strong>${escapeHtml(product.name)}</strong><small>${escapeHtml(product.brand || '')} - ${escapeHtml(product.sku)} - ${escapeHtml(product.price)}</small></span>
            </a>
        `).join('');
    } catch (error) {
        state.hidden = false;
        state.textContent = 'Nu am putut primi raspunsul acum. Incearca din nou.';
    } finally {
        button.disabled = false;
    }
});

function openAiModal(prefill = '') {
    const modal = document.getElementById('ai-modal');
    if (!modal) return;

    modal.hidden = false;
    document.body.classList.add('ai-modal-open');

    const input = modal.querySelector('textarea[name="prompt"]');
    if (input) {
        if (prefill) input.value = prefill;
        window.setTimeout(() => input.focus(), 30);
    }
}

function closeAiModal() {
    const modal = document.getElementById('ai-modal');
    if (!modal || modal.hidden) return;

    modal.hidden = true;
    document.body.classList.remove('ai-modal-open');
}

function openCatalogModal() {
    const modal = document.getElementById('catalog-modal');
    if (!modal) return;

    const drawer = document.getElementById('mobile-menu');
    if (drawer) drawer.hidden = true;

    modal.hidden = false;
    document.body.classList.add('catalog-modal-open');
}

function closeCatalogModal() {
    const modal = document.getElementById('catalog-modal');
    if (!modal || modal.hidden) return;

    modal.hidden = true;
    document.body.classList.remove('catalog-modal-open');
}

function changeHeroSlide(slider, direction) {
    if (!slider) return;

    const slides = [...slider.querySelectorAll('[data-hero-slide]')];
    if (slides.length < 2) return;

    const current = Number(slider.dataset.heroIndex || 0);
    const next = (current + direction + slides.length) % slides.length;
    setHeroSlide(slider, next);
}

function setHeroSlide(slider, index) {
    if (!slider) return;

    const slides = [...slider.querySelectorAll('[data-hero-slide]')];
    const dots = [...slider.querySelectorAll('[data-hero-dot]')];
    if (!slides[index]) return;

    slider.dataset.heroIndex = String(index);
    slides.forEach((slide, slideIndex) => slide.classList.toggle('is-active', slideIndex === index));
    dots.forEach((dot, dotIndex) => dot.classList.toggle('is-active', dotIndex === index));
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char]));
}

function escapeAttr(value) {
    return escapeHtml(value).replace(/`/g, '&#096;');
}
