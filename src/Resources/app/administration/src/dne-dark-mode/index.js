import template from './dne-dark-mode.html.twig';

const { Component } = Shopware;
const isDarkMode = localStorage.getItem('isDarkMode') === 'true';
const darkModeClsSetter = (mode) => {
    if (mode) {
        document.body.classList.add('is-dark-mode');
    } else {
        document.body.classList.remove('is-dark-mode');
    }
};

Component.register('dne-dark-mode', {
    template,
    data() {
        return {
            isDarkMode: isDarkMode
        };
    },
    watch: {
        isDarkMode(mode) {
            localStorage.setItem('isDarkMode', mode);

            darkModeClsSetter(mode);
        }
    }
});

darkModeClsSetter(isDarkMode);
