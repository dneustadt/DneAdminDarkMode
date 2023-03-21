import template from './dne-dark-mode.html.twig';

const { Component } = Shopware;

Component.register('dne-dark-mode', {
    template,
    data() {
        return {
            isDarkMode: localStorage.getItem('isDarkMode') === 'true'
        };
    },
    watch: {
        isDarkMode(mode) {
            localStorage.setItem('isDarkMode', mode ? 'true' : 'false');

            document.documentElement.setAttribute('dark-theme', mode ? 'true' : 'false');
        },
    },
});
