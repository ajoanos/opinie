(function () {
    'use strict';

    var STRINGS = window.AllemediaReviewsConfig || {
        more: 'Pokaż więcej',
        less: 'Pokaż mniej'
    };

    function escapeSelector(value) {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(value);
        }
        return value.replace(/([\.\#\:\[\],])/g, '\\$1');
    }

    function toggleReview(button, target) {
        var expanded = button.getAttribute('aria-expanded') === 'true';
        var nextState = !expanded;
        var fullText = target.getAttribute('data-full') || '';
        var shortText = target.getAttribute('data-short') || '';
        var label = button.querySelector('.amr-review-card__toggle-label');

        button.setAttribute('aria-expanded', nextState ? 'true' : 'false');
        target.textContent = nextState ? fullText : shortText;

        if (label) {
            label.textContent = nextState ? STRINGS.less : STRINGS.more;
        }
    }

    function initSection(section) {
        section.classList.remove('amr-is-loading');
        section.classList.add('amr-is-loaded');

        var buttons = section.querySelectorAll('.amr-review-card__toggle');
        buttons.forEach(function (button) {
            var targetId = button.getAttribute('data-target');
            if (!targetId) {
                return;
            }

            var target = section.querySelector('#' + escapeSelector(targetId));
            if (!target) {
                return;
            }

            button.addEventListener('click', function () {
                toggleReview(button, target);
            });
        });
    }

    function init() {
        var sections = document.querySelectorAll('.amr-reviews');
        sections.forEach(initSection);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
