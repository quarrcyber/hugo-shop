import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('siteNav', () => ({
    open: false,
    dismissed: sessionStorage.getItem('hugo-banner-dismissed') === '1',
    compact: false,
    lastY: 0,
    init() {
        document.documentElement.style.setProperty('--banner-h', this.dismissed ? '0px' : '2.5rem');
        window.addEventListener('scroll', () => {
            const current = window.scrollY;
            this.compact = !this.dismissed && current > 48 && current > this.lastY;
            if (current < 16 || current < this.lastY) this.compact = false;
            this.lastY = current;
        }, { passive: true });
    },
    dismiss() {
        this.dismissed = true;
        this.compact = false;
        sessionStorage.setItem('hugo-banner-dismissed', '1');
        document.documentElement.style.setProperty('--banner-h', '0px');
    },
}));

Alpine.data('submitState', () => ({
    state: 'default',
    method: 'card',
    submit(event) {
        this.state = 'loading';
        const button = event.currentTarget.querySelector('[type="submit"]');
        if (button) button.disabled = true;
    },
}));

Alpine.start();
