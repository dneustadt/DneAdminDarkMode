import template from './dne-dark-mode.html.twig';

const { Component } = Shopware;

Component.register('dne-dark-mode', {
    template,
    data() {
        return {
            isDarkMode: false
        };
    },
    watch: {
        isDarkMode(mode) {
            localStorage.setItem('isDarkMode', mode);

            if (mode) {
                document.body.classList.add('is-dark-mode');
            } else {
                document.body.classList.remove('is-dark-mode');
            }
        }
    },
    created() {
        const isDarkMode = localStorage.getItem('isDarkMode');

        this.isDarkMode = isDarkMode === 'true';
    }
});
