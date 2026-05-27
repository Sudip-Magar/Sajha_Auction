//

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';
import './nepali.datepicker.v5.0.6.min.js';

const datepickerOptions = {
    dateFormat: 'YYYY-MM-DD',
    language: 'english',
    miniEnglishDates: true,
};

window.DateSync = {

    bsToAd(bsDate) {
        const ad = window.NepaliFunctions.BS2AD(bsDate);
        return `${ad.year}-${String(ad.month).padStart(2, '0')}-${String(ad.day).padStart(2, '0')}`;
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
        }

        english.addEventListener('change', () => {
            if (!english.value) return;

            const bsString = this.adToBs(english.value);
            nepali.value = bsString;
            english.dispatchEvent(new Event('input', {bubbles: true}));
            nepali.dispatchEvent(new Event('input', {bubbles: true}));

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
