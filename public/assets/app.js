document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('backdrop');
    const menu = document.getElementById('menu');

    const setMenuOpen = (open) => {
        sidebar?.classList.toggle('open', open);
        backdrop?.classList.toggle('open', open);
        menu?.setAttribute('aria-expanded', String(open));
        menu?.setAttribute('aria-label', open ? 'Tutup menu navigasi' : 'Buka menu navigasi');
    };

    menu?.addEventListener('click', () => {
        setMenuOpen(menu.getAttribute('aria-expanded') !== 'true');
    });
    backdrop?.addEventListener('click', () => setMenuOpen(false));
    sidebar?.querySelectorAll('.nav-link').forEach((link) => {
        link.addEventListener('click', () => setMenuOpen(false));
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setMenuOpen(false);
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm || 'Lanjutkan?')) event.preventDefault();
        });
    });

    // Use Indonesian messages for native HTML validation across every role/menu.
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('invalid', (event) => {
            const input = event.target;
            if (!(input instanceof HTMLInputElement || input instanceof HTMLSelectElement || input instanceof HTMLTextAreaElement)) return;
            if (input.validity.customError) return;

            const sourceLabel = input.labels?.[0];
            const cleanLabel = sourceLabel?.cloneNode(true);
            cleanLabel?.querySelectorAll('input, select, textarea, button, .required-mark').forEach((element) => element.remove());
            const label = cleanLabel?.textContent.replace(/\s+/g, ' ').trim() || 'Kolom ini';
            let message = '';
            if (input.validity.valueMissing) message = `${label} wajib diisi.`;
            else if (input.validity.rangeOverflow) message = input.dataset.maxMessage || `${label} tidak boleh lebih dari ${input.max}.`;
            else if (input.validity.rangeUnderflow) message = `${label} minimal ${input.min}.`;
            else if (input.validity.stepMismatch) message = `${label} harus menggunakan kelipatan ${input.step}.`;
            else if (input.validity.typeMismatch && input.type === 'email') message = 'Format alamat email belum benar.';
            else if (input.validity.tooShort) message = `${label} minimal ${input.minLength} karakter.`;
            else if (input.validity.tooLong) message = `${label} maksimal ${input.maxLength} karakter.`;
            else if (input.validity.badInput) message = `Masukkan angka yang valid untuk ${label.toLowerCase()}.`;
            else if (input.validity.patternMismatch) message = `${label} tidak sesuai format.`;

            input.setCustomValidity(message);
        }, true);
        form.addEventListener('input', (event) => event.target.setCustomValidity?.(''));
        form.addEventListener('change', (event) => event.target.setCustomValidity?.(''));
    });

    const currencyInputs = document.querySelectorAll('[data-currency-input]');
    const keepRupiahCharacters = (value) => {
        const cleaned = value.replace(/[^\d,]/g, '');
        const decimalIndex = cleaned.indexOf(',');
        if (decimalIndex < 0) return cleaned;
        return `${cleaned.slice(0, decimalIndex)},${cleaned.slice(decimalIndex + 1).replaceAll(',', '').slice(0, 2)}`;
    };
    const formatRupiah = (value) => {
        const normalized = keepRupiahCharacters(value);
        const [integerPart, fractionPart] = normalized.split(',');
        const firstGroupLength = integerPart.length % 3 || 3;
        const chunks = [integerPart.slice(0, firstGroupLength)];
        for (let start = firstGroupLength; start < integerPart.length; start += 3) {
            chunks.push(integerPart.slice(start, start + 3));
        }
        const groupedInteger = chunks.join('.');
        return fractionPart === undefined ? groupedInteger : `${groupedInteger},${fractionPart}`;
    };

    currencyInputs.forEach((input) => {
        input.addEventListener('focus', () => {
            input.value = input.value.replaceAll('.', '');
        });
        input.addEventListener('input', () => {
            input.value = keepRupiahCharacters(input.value);
        });
        input.addEventListener('blur', () => {
            input.value = formatRupiah(input.value);
        });
        input.form?.addEventListener('submit', () => {
            input.value = input.value.replaceAll('.', '').replaceAll(',', '.');
        });
    });
});
