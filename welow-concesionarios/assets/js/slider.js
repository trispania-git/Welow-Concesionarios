/**
 * Slider Welow — Vanilla JS, sin dependencias.
 * Soporta: fade, autoplay, flechas, dots, swipe táctil.
 *
 * @package Welow_Concesionarios
 */
(function () {
    'use strict';

    function WelowSlider(el) {
        this.el = el;
        this.slides = el.querySelectorAll('.welow-slider__slide');
        this.total = this.slides.length;
        this.current = 0;
        this.autoplayTimer = null;

        if (this.total <= 1) return;

        this.autoplay = el.dataset.autoplay === 'true';
        this.speed = parseInt(el.dataset.speed, 10) || 5000;

        this.bindArrows();
        this.bindDots();
        this.bindSwipe();

        if (this.autoplay) {
            this.startAutoplay();
            // Pausar en hover
            el.addEventListener('mouseenter', this.stopAutoplay.bind(this));
            el.addEventListener('mouseleave', this.startAutoplay.bind(this));
        }
    }

    WelowSlider.prototype.goTo = function (index) {
        if (index === this.current || index < 0 || index >= this.total) return;

        // Desactivar actual
        this.slides[this.current].classList.remove('welow-slider__slide--active');

        // Activar nuevo
        this.current = index;
        this.slides[this.current].classList.add('welow-slider__slide--active');

        // Actualizar dots
        this.updateDots();
    };

    WelowSlider.prototype.next = function () {
        this.goTo((this.current + 1) % this.total);
    };

    WelowSlider.prototype.prev = function () {
        this.goTo((this.current - 1 + this.total) % this.total);
    };

    WelowSlider.prototype.bindArrows = function () {
        var prevBtn = this.el.querySelector('.welow-slider__arrow--prev');
        var nextBtn = this.el.querySelector('.welow-slider__arrow--next');

        if (prevBtn) {
            prevBtn.addEventListener('click', function (e) {
                e.preventDefault();
                this.prev();
                this.resetAutoplay();
            }.bind(this));
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function (e) {
                e.preventDefault();
                this.next();
                this.resetAutoplay();
            }.bind(this));
        }
    };

    WelowSlider.prototype.bindDots = function () {
        var dots = this.el.querySelectorAll('.welow-slider__dot');
        var self = this;

        dots.forEach(function (dot) {
            dot.addEventListener('click', function (e) {
                e.preventDefault();
                var index = parseInt(this.dataset.index, 10);
                self.goTo(index);
                self.resetAutoplay();
            });
        });
    };

    WelowSlider.prototype.updateDots = function () {
        var dots = this.el.querySelectorAll('.welow-slider__dot');
        dots.forEach(function (dot, i) {
            dot.classList.toggle('welow-slider__dot--active', i === this.current);
        }.bind(this));
    };

    WelowSlider.prototype.bindSwipe = function () {
        var startX = 0;
        var threshold = 50;
        var self = this;

        this.el.addEventListener('touchstart', function (e) {
            startX = e.touches[0].clientX;
        }, { passive: true });

        this.el.addEventListener('touchend', function (e) {
            var endX = e.changedTouches[0].clientX;
            var diff = startX - endX;

            if (Math.abs(diff) > threshold) {
                if (diff > 0) {
                    self.next();
                } else {
                    self.prev();
                }
                self.resetAutoplay();
            }
        }, { passive: true });
    };

    WelowSlider.prototype.startAutoplay = function () {
        if (!this.autoplay) return;
        this.stopAutoplay();
        this.autoplayTimer = setInterval(this.next.bind(this), this.speed);
    };

    WelowSlider.prototype.stopAutoplay = function () {
        if (this.autoplayTimer) {
            clearInterval(this.autoplayTimer);
            this.autoplayTimer = null;
        }
    };

    WelowSlider.prototype.resetAutoplay = function () {
        if (this.autoplay) {
            this.stopAutoplay();
            this.startAutoplay();
        }
    };

    // Inicializar todos los sliders del DOM
    function initSliders() {
        var sliders = document.querySelectorAll('.welow-slider');
        sliders.forEach(function (el) {
            new WelowSlider(el);
        });
    }

    // Esperar al DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSliders);
    } else {
        initSliders();
    }
})();
