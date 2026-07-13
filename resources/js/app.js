document.addEventListener('click', (event) => {
    const openTarget = event.target.closest('[data-open]');
    const closeTarget = event.target.closest('[data-close]');
    const aiPrompt = event.target.closest('[data-ai-prompt]');
    const catalogOpen = event.target.closest('[data-catalog-open]');
    const catalogClose = event.target.closest('[data-catalog-close]');
    const mobileCatalogToggle = event.target.closest('[data-mobile-catalog-toggle]');
    const catalogSidebarMobileToggle = event.target.closest('[data-catalog-sidebar-mobile-toggle]');
    const catalogSidebarToggle = event.target.closest('[data-catalog-sidebar-toggle]');
    const heroDot = event.target.closest('[data-hero-dot]');
    const productGalleryButton = event.target.closest('[data-product-gallery-src]');

    if (openTarget) {
        const target = document.getElementById(openTarget.dataset.open);
        if (target) {
            target.hidden = false;
            if (target.id === 'mobile-menu') {
                window.catalogTrigger = openTarget;
                openMobileMenu();
            } else {
                openTarget.setAttribute('aria-expanded', 'true');
            }
        }
    }

    if (closeTarget) {
        const target = document.getElementById(closeTarget.dataset.close);
        if (target) {
            if (target.id === 'mobile-menu') {
                closeMobileMenu();
            } else {
                target.hidden = true;
            }
        }
    }

    if (aiPrompt) {
        const input = aiPrompt.closest('form')?.querySelector('textarea[name="prompt"]') || document.querySelector('textarea[name="prompt"]');
        if (input) {
            input.value = aiPrompt.dataset.aiPrompt;
            input.focus();
        }
    }

    if (catalogOpen) {
        event.preventDefault();
        toggleCatalogMenu(catalogOpen);
    }

    if (catalogClose) {
        closeCatalogModal();
    }

    if (mobileCatalogToggle) {
        toggleMobileCatalogSection(mobileCatalogToggle);
    }

    if (catalogSidebarMobileToggle) {
        toggleCatalogSidebarNav(catalogSidebarMobileToggle);
    }

    if (catalogSidebarToggle) {
        toggleCatalogSidebarSection(catalogSidebarToggle);
    }

    if (heroDot) {
        setHeroSlide(heroDot.closest('[data-hero-slider]'), Number(heroDot.dataset.heroDot));
    }

    if (productGalleryButton) {
        const mainImage = document.querySelector('[data-product-main-image]');
        if (mainImage) {
            mainImage.src = productGalleryButton.dataset.productGallerySrc;
            document.querySelectorAll('[data-product-gallery-src]').forEach((button) => {
                const active = button === productGalleryButton;
                button.classList.toggle('active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        }
    }
});

document.addEventListener('mouseover', (event) => {
    const section = event.target.closest('[data-mega-section]');
    if (section) setMegaSection(section.dataset.megaSection);
});

document.addEventListener('focusin', (event) => {
    const section = event.target.closest('[data-mega-section]');
    if (section) setMegaSection(section.dataset.megaSection);
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-hero-slider]').forEach((slider) => {
        slider.dataset.heroIndex = '0';
        window.setInterval(() => changeHeroSlide(slider, 1), 6500);
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeCatalogModal();
        closeMobileMenu();
    }
});

function toggleCatalogMenu(trigger = null) {
    if (window.matchMedia('(max-width: 920px)').matches) {
        window.catalogTrigger = trigger;
        openMobileMenu();
        return;
    }

    const modal = document.getElementById('catalog-modal');
    if (modal && !modal.hidden) {
        closeCatalogModal();
        return;
    }

    openCatalogModal(trigger);
}

function openCatalogModal(trigger = null) {
    const modal = document.getElementById('catalog-modal');
    if (!modal) return;

    const drawer = document.getElementById('mobile-menu');
    if (drawer) drawer.hidden = true;
    document.body.classList.remove('mobile-menu-open');

    window.catalogTrigger = trigger;
    modal.hidden = false;
    setCatalogExpanded(true);
    document.body.classList.add('catalog-modal-open');
    window.setTimeout(() => modal.querySelector('.mega-section-link.active')?.focus({ preventScroll: true }), 30);
}

function closeCatalogModal(restoreFocus = true) {
    const modal = document.getElementById('catalog-modal');
    if (!modal || modal.hidden) return;

    modal.hidden = true;
    document.body.classList.remove('catalog-modal-open');
    setCatalogExpanded(false);

    if (restoreFocus) {
        window.catalogTrigger?.focus?.({ preventScroll: true });
    }
}

function setCatalogExpanded(expanded) {
    document.querySelectorAll('[data-catalog-open]').forEach((button) => {
        button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        button.classList.toggle('active', expanded);
    });
}

function setMegaSection(slug) {
    if (!slug) return;

    document.querySelectorAll('[data-mega-section]').forEach((section) => {
        const active = section.dataset.megaSection === slug;
        section.classList.toggle('active', active);
        section.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    document.querySelectorAll('[data-mega-panel]').forEach((panel) => {
        panel.classList.toggle('active', panel.dataset.megaPanel === slug);
    });
}

function openMobileMenu() {
    const drawer = document.getElementById('mobile-menu');
    if (!drawer) return;

    closeCatalogModal(false);
    drawer.hidden = false;
    document.body.classList.add('mobile-menu-open');
    document.querySelectorAll('[data-open="mobile-menu"]').forEach((button) => button.setAttribute('aria-expanded', 'true'));
    window.catalogTrigger?.setAttribute?.('aria-expanded', 'true');
    window.setTimeout(() => drawer.querySelector('input[name="q"]')?.focus({ preventScroll: true }), 30);
}

function closeMobileMenu() {
    const drawer = document.getElementById('mobile-menu');
    if (!drawer || drawer.hidden) return;

    drawer.hidden = true;
    document.body.classList.remove('mobile-menu-open');
    document.querySelectorAll('[data-open="mobile-menu"]').forEach((button) => button.setAttribute('aria-expanded', 'false'));
    window.catalogTrigger?.setAttribute?.('aria-expanded', 'false');
    window.catalogTrigger?.focus?.({ preventScroll: true });
}

function toggleMobileCatalogSection(button) {
    const expanded = button.getAttribute('aria-expanded') === 'true';
    const panel = document.getElementById(button.getAttribute('aria-controls'));
    if (!panel) return;

    button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    panel.hidden = expanded;
    button.closest('.mobile-catalog-item')?.classList.toggle('open', !expanded);
}

function toggleCatalogSidebarNav(button) {
    const expanded = button.getAttribute('aria-expanded') === 'true';
    const panel = document.getElementById(button.getAttribute('aria-controls'));
    if (!panel) return;

    const nextExpanded = !expanded;
    button.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
    button.setAttribute('aria-label', nextExpanded ? button.dataset.labelClose : button.dataset.labelOpen);
    panel.classList.toggle('is-collapsed', !nextExpanded);
}

function toggleCatalogSidebarSection(button) {
    const expanded = button.getAttribute('aria-expanded') === 'true';
    const panel = document.getElementById(button.getAttribute('aria-controls'));
    if (!panel) return;

    if (!expanded) {
        button.closest('.catalog-sidebar__list')?.querySelectorAll('[data-catalog-sidebar-toggle]').forEach((otherButton) => {
            if (otherButton === button) return;

            const otherPanel = document.getElementById(otherButton.getAttribute('aria-controls'));
            otherButton.setAttribute('aria-expanded', 'false');
            if (otherPanel) otherPanel.hidden = true;
            otherButton.closest('.catalog-sidebar__item')?.classList.remove('is-open');
        });
    }

    button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    panel.hidden = expanded;
    button.closest('.catalog-sidebar__item')?.classList.toggle('is-open', !expanded);
}

window.addEventListener('resize', () => {
    if (window.matchMedia('(max-width: 920px)').matches) {
        closeCatalogModal(false);
    } else {
        closeMobileMenu();
    }
});

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
