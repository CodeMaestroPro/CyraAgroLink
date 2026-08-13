import './bootstrap';
import './dashboard';
import './investor-dashboard';
import './buyer-dashboard';
import './market-intelligence';
import './weather-intelligence';
import './reporting-analytics';
import './business-intelligence';
import './government-dashboard';
import './financial-institution-dashboard';
import './enterprise-admin-dashboard';
import './digital-twin-farm';
import './precision-agriculture';
import './carbon-credit-marketplace';
import './risk-intelligence';
import './commodity-futures';
import './smart-city-distribution';
import './commodity-auction';
import './food-security-dashboard';
import './farm-registration';
import './supply-chain';
import './exchange';
import { applyTheme, initTheme, resolveTheme, toggleTheme } from './theme';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.cyraTheme = { applyTheme, initTheme, resolveTheme, toggleTheme };

initTheme();

document.addEventListener('alpine:init', () => {
    Alpine.data('cyraReveal', () => ({
        init() {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                this.$el.classList.add('is-visible');
                return;
            }

            const observer = new IntersectionObserver(
                ([entry]) => {
                    if (entry.isIntersecting) {
                        this.$el.classList.add('is-visible');
                        observer.disconnect();
                    }
                },
                { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
            );

            observer.observe(this.$el);
        },
    }));
});

Alpine.start();
