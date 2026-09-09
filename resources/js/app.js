//

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';
import './nepali.datepicker.v5.0.6.min.js';
import './tiptap-editor.js';

const datepickerOptions = {
    dateFormat: 'YYYY-MM-DD',
    language: 'english',
    miniEnglishDates: true,
};

window.DateSync = {

    bsToAd(bsDate) {
        // Unlike AD2BS (which returns a {year, month, day} object), BS2AD
        // returns an already-formatted "YYYY-MM-DD" string.
        return window.NepaliFunctions.BS2AD(bsDate);
    },

    adToBs(adDate) {
        const [y, m, d] = adDate.split('-');
        const bs = window.NepaliFunctions.AD2BS({year: +y, month: +m, day: +d});
        return `${bs.year}-${String(bs.month).padStart(2, '0')}-${String(bs.day).padStart(2, '0')}`;
    },

    attach(nepali, english) {
        if (!nepali || !english || nepali.dataset.synced || !window.NepaliFunctions) {
            return;
        }

        nepali.NepaliDatePicker({
            ...datepickerOptions,
            onSelect: (bs) => {
                english.value = this.bsToAd(bs);
                nepali.dispatchEvent(new Event('input', {bubbles: true}));
                english.dispatchEvent(new Event('input', {bubbles: true}));

            }
        });

        if (english.value && !nepali.value) {
            nepali.value = this.adToBs(english.value);
            // Livewire's wire:model only learns about a value change from a
            // DOM event — a plain `.value =` assignment is invisible to it,
            // so the auto-filled date would look right on screen but never
            // reach the component (submitted as empty, failing "required").
            nepali.dispatchEvent(new Event('input', {bubbles: true}));
        }

        english.addEventListener('change', () => {
            if (!english.value) return;

            const bsString = this.adToBs(english.value);
            nepali.value = bsString;
            english.dispatchEvent(new Event('input', {bubbles: true}));
            nepali.dispatchEvent(new Event('input', {bubbles: true}));

        });

        // A date typed directly into the Nepali field (not picked from the
        // calendar popup) never fires the picker's onSelect callback, so
        // sync it here too once the field loses focus.
        nepali.addEventListener('blur', () => {
            if (!nepali.value) return;

            let adDate;
            try {
                adDate = this.bsToAd(nepali.value);
            } catch (e) {
                return;
            }

            if (!adDate || adDate.includes('NaN') || adDate === english.value) {
                return;
            }

            english.value = adDate;
            nepali.dispatchEvent(new Event('input', {bubbles: true}));
            english.dispatchEvent(new Event('input', {bubbles: true}));
        });

        nepali.dataset.synced = true;
    }
};

window.initializeNepaliDatePickers = () => {
    document.querySelectorAll('[data-nepali-date]').forEach((nepali) => {
        const key = nepali.dataset.nepaliDate;
        const english = document.querySelector(`[data-english-date="${key}"]`);

        window.DateSync.attach(nepali, english);
    });
};

window.attachNepaliDatePickerFor = (element) => {
    if (!element) {
        return;
    }

    const nepali = element.closest('[data-nepali-date]');
    if (!nepali) {
        return;
    }

    const key = nepali.dataset.nepaliDate;
    const english = document.querySelector(`[data-english-date="${key}"]`);
    window.DateSync.attach(nepali, english);
};

document.addEventListener('DOMContentLoaded', window.initializeNepaliDatePickers);
document.addEventListener('livewire:navigated', window.initializeNepaliDatePickers);
document.addEventListener('init-nepali-date-pickers', () => queueMicrotask(window.initializeNepaliDatePickers));
document.addEventListener('focusin', (event) => window.attachNepaliDatePickerFor(event.target));
document.addEventListener('click', (event) => window.attachNepaliDatePickerFor(event.target));
document.addEventListener('livewire:initialized', () => {
    Livewire.hook('morph.updated', () => {
        queueMicrotask(window.initializeNepaliDatePickers);
    });
});
