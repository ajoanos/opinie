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

    function setupCarousel(section) {
        var list = section.querySelector('.amr-reviews__list');
        if (!list) {
            return;
        }

        var cards = list.querySelectorAll('.amr-review-card');
        if (!cards || cards.length <= 1) {
            return;
        }

        var autoplayAttr = section.getAttribute('data-autoplay');
        var autoplayEnabled = autoplayAttr !== '0' && autoplayAttr !== 'false';
        var intervalAttr = parseInt(section.getAttribute('data-autoplay-interval') || '', 10);
        var autoplayDelay = !isNaN(intervalAttr) && intervalAttr >= 1000 ? intervalAttr : 6000;
        var timerId = null;
        var prefersReduced = false;

        if (window.matchMedia) {
            var mq = window.matchMedia('(prefers-reduced-motion: reduce)');
            prefersReduced = mq.matches;
            var mqHandler = function (event) {
                prefersReduced = event.matches;
                if (prefersReduced) {
                    stop();
                } else {
                    start();
                }
            };

            if (typeof mq.addEventListener === 'function') {
                mq.addEventListener('change', mqHandler);
            } else if (typeof mq.addListener === 'function') {
                mq.addListener(mqHandler);
            }
        }

        function getGap() {
            if (!window.getComputedStyle) {
                return 0;
            }

            var styles = window.getComputedStyle(list);
            var gap = parseFloat(styles.columnGap || styles.gap || '0');
            return isNaN(gap) ? 0 : gap;
        }

        function getStep() {
            var first = cards[0];
            if (!first) {
                return list.clientWidth || 0;
            }

            var gap = getGap();
            var width = first.getBoundingClientRect().width;
            return Math.max(1, width + gap);
        }

        function getMaxScroll() {
            return Math.max(0, list.scrollWidth - list.clientWidth);
        }

        function go(direction) {
            var limit = getMaxScroll();
            if (limit <= 0) {
                return;
            }

            var step = getStep();
            var current = list.scrollLeft;
            var target;

            if (direction > 0) {
                if (current + step >= limit - 5) {
                    target = 0;
                } else {
                    target = Math.min(limit, current + step);
                }
            } else {
                if (current - step <= 5) {
                    target = limit;
                } else {
                    target = Math.max(0, current - step);
                }
            }

            list.scrollTo({
                left: target,
                behavior: 'smooth'
            });
        }

        function next() {
            go(1);
        }

        function prev() {
            go(-1);
        }

        function stop() {
            if (timerId !== null) {
                window.clearInterval(timerId);
                timerId = null;
            }
        }

        function start() {
            if (!autoplayEnabled || prefersReduced) {
                return;
            }

            if (timerId !== null) {
                return;
            }

            timerId = window.setInterval(next, autoplayDelay);
        }

        function resume() {
            stop();
            start();
        }

        var nextBtn = section.querySelector('.amr-reviews__control--next');
        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                stop();
                next();
                start();
            });
        }

        var prevBtn = section.querySelector('.amr-reviews__control--prev');
        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                stop();
                prev();
                start();
            });
        }

        ['mouseenter', 'touchstart', 'focusin'].forEach(function (eventName) {
            list.addEventListener(eventName, stop);
        });

        ['mouseleave', 'touchend'].forEach(function (eventName) {
            list.addEventListener(eventName, resume);
        });

        list.addEventListener('focusout', function (event) {
            if (!event || !event.relatedTarget) {
                resume();
                return;
            }

            if (!list.contains(event.relatedTarget)) {
                resume();
            }
        });

        ['mouseenter', 'touchstart', 'focusin'].forEach(function (eventName) {
            section.addEventListener(eventName, stop);
        });

        section.addEventListener('mouseleave', resume);
        section.addEventListener('touchend', resume);
        section.addEventListener('focusout', function (event) {
            if (!event || !event.relatedTarget) {
                resume();
                return;
            }

            if (!section.contains(event.relatedTarget)) {
                resume();
            }
        });

        section.addEventListener('keydown', function (event) {
            if (!event) {
                return;
            }

            if (event.key === 'ArrowRight') {
                stop();
                next();
                start();
                event.preventDefault();
            } else if (event.key === 'ArrowLeft') {
                stop();
                prev();
                start();
                event.preventDefault();
            }
        });

        start();
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

        setupCarousel(section);
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
