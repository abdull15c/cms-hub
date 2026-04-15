(() => {
    const doc = document;
    const win = window;
    const root = doc.documentElement;
    root.classList.add('app-ready');

    const escapeSelector = (value) => {
        if (win.CSS && typeof win.CSS.escape === 'function') {
            return win.CSS.escape(value);
        }

        return String(value).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
    };

    const getCollapseTarget = (trigger) => {
        const selector = trigger.getAttribute('data-bs-target');
        if (!selector) {
            return null;
        }

        try {
            return doc.querySelector(selector);
        } catch (_error) {
            return null;
        }
    };

    const getDropdownMenu = (toggle) => {
        const parent = toggle.closest('.dropdown');
        return parent ? parent.querySelector('.dropdown-menu') : null;
    };

    const setDropdownState = (toggle, open) => {
        const menu = getDropdownMenu(toggle);
        if (!menu) {
            return;
        }

        menu.classList.toggle('show', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    const closeDropdowns = (exceptToggle = null) => {
        doc.querySelectorAll('[data-bs-toggle="dropdown"]').forEach((toggle) => {
            if (toggle !== exceptToggle) {
                setDropdownState(toggle, false);
            }
        });
    };

    const updateCollapseTriggers = (target, open) => {
        if (!target.id) {
            return;
        }

        const selector = `[data-bs-target="#${escapeSelector(target.id)}"]`;
        doc.querySelectorAll(selector).forEach((trigger) => {
            trigger.classList.toggle('collapsed', !open);
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    };

    const setCollapseState = (target, open) => {
        if (!target) {
            return;
        }

        if (open) {
            const parentSelector = target.getAttribute('data-bs-parent');
            if (parentSelector) {
                const parent = doc.querySelector(parentSelector);
                if (parent) {
                    parent.querySelectorAll('.accordion-collapse.show').forEach((item) => {
                        if (item !== target) {
                            item.classList.remove('show');
                            updateCollapseTriggers(item, false);
                        }
                    });
                }
            }
        }

        target.classList.toggle('show', open);
        updateCollapseTriggers(target, open);
    };

    let modalBackdrop = null;

    const syncModalBackdrop = () => {
        const openModals = doc.querySelectorAll('.modal.show');
        const hasOpenModal = openModals.length > 0;

        if (hasOpenModal) {
            doc.body.classList.add('modal-open');
            if (!modalBackdrop) {
                modalBackdrop = doc.createElement('div');
                modalBackdrop.className = 'modal-backdrop fade show';
                doc.body.appendChild(modalBackdrop);
            }
            return;
        }

        doc.body.classList.remove('modal-open');
        if (modalBackdrop) {
            modalBackdrop.remove();
            modalBackdrop = null;
        }
    };

    const showModal = (modal) => {
        if (!modal) {
            return;
        }

        modal.style.display = 'block';
        modal.removeAttribute('aria-hidden');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('role', 'dialog');
        modal.classList.add('show');
        syncModalBackdrop();
    };

    const hideModal = (modal) => {
        if (!modal) {
            return;
        }

        modal.classList.remove('show');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        modal.removeAttribute('aria-modal');
        syncModalBackdrop();
    };

    doc.querySelectorAll('[data-bs-toggle="dropdown"]').forEach((toggle) => {
        const menu = getDropdownMenu(toggle);
        toggle.setAttribute('aria-expanded', menu && menu.classList.contains('show') ? 'true' : 'false');
    });

    doc.querySelectorAll('.accordion-collapse').forEach((target) => {
        updateCollapseTriggers(target, target.classList.contains('show'));
    });

    doc.addEventListener('click', (event) => {
        const dismissButton = event.target.closest('[data-bs-dismiss]');
        if (dismissButton) {
            const dismissType = dismissButton.getAttribute('data-bs-dismiss');
            if (dismissType === 'alert') {
                const alert = dismissButton.closest('.alert');
                if (alert) {
                    alert.classList.remove('show');
                    win.setTimeout(() => alert.remove(), 150);
                }
                return;
            }

            if (dismissType === 'modal') {
                const modal = dismissButton.closest('.modal');
                hideModal(modal);
                return;
            }
        }

        const modalTrigger = event.target.closest('[data-bs-toggle="modal"]');
        if (modalTrigger) {
            event.preventDefault();
            const target = getCollapseTarget(modalTrigger);
            showModal(target);
            return;
        }

        const collapseTrigger = event.target.closest('[data-bs-toggle="collapse"]');
        if (collapseTrigger) {
            event.preventDefault();
            const target = getCollapseTarget(collapseTrigger);
            if (target) {
                setCollapseState(target, !target.classList.contains('show'));
            }
            return;
        }

        const dropdownToggle = event.target.closest('[data-bs-toggle="dropdown"]');
        if (dropdownToggle) {
            event.preventDefault();
            const menu = getDropdownMenu(dropdownToggle);
            if (!menu) {
                return;
            }

            const isOpen = menu.classList.contains('show');
            closeDropdowns(dropdownToggle);
            setDropdownState(dropdownToggle, !isOpen);
            return;
        }

        if (event.target.closest('.dropdown-menu a')) {
            closeDropdowns();
        } else if (!event.target.closest('.dropdown')) {
            closeDropdowns();
        }

        const modal = event.target.classList.contains('modal') ? event.target : null;
        if (modal) {
            hideModal(modal);
        }
    });

    doc.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        const openModal = doc.querySelector('.modal.show');
        if (openModal) {
            hideModal(openModal);
            return;
        }

        closeDropdowns();
    });
})();
