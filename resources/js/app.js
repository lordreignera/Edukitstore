import './bootstrap';

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordTarget);
        const showing = input.type === 'text';

        input.type = showing ? 'password' : 'text';
        button.setAttribute('aria-pressed', showing ? 'false' : 'true');
        button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');

        const icon = button.querySelector('svg');
        if (icon) {
            icon.innerHTML = showing
                ? '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle>'
                : '<path d="m3 3 18 18"></path><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path><path d="M9.8 5.2A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-2 2.8"></path><path d="M6.6 6.6C3.6 8.5 2 12 2 12s3.5 7 10 7a9.8 9.8 0 0 0 4.2-.9"></path>';
        }
    });
});

document.querySelectorAll('[data-district-select]').forEach((select) => {
    const input = select.querySelector('[data-district-input]');
    const toggle = select.querySelector('[data-district-toggle]');
    const menu = select.querySelector('[data-district-menu]');
    const empty = select.querySelector('[data-district-empty]');
    const options = Array.from(select.querySelectorAll('[data-district-option]'));

    const setOpen = (open) => {
        menu.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
    };

    const filterOptions = () => {
        const term = input.value.trim().toLowerCase();
        let matches = 0;

        options.forEach((option) => {
            const matchesSearch = option.dataset.districtName.toLowerCase().includes(term);
            option.style.display = matchesSearch ? 'flex' : 'none';
            matches += matchesSearch ? 1 : 0;
        });

        empty.style.display = matches > 0 ? 'none' : 'block';
    };

    input.addEventListener('focus', () => {
        filterOptions();
        setOpen(true);
    });
    input.addEventListener('input', () => {
        filterOptions();
        setOpen(true);
    });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });
    toggle.addEventListener('click', () => {
        filterOptions();
        setOpen(menu.hidden);
        input.focus();
    });
    options.forEach((option) => {
        option.addEventListener('click', () => {
            input.value = option.dataset.districtName;
            input.dispatchEvent(new Event('change', { bubbles: true }));
            setOpen(false);
            input.focus();
        });
    });
    document.addEventListener('click', (event) => {
        if (!select.contains(event.target)) {
            setOpen(false);
        }
    });
});
