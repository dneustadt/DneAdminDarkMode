import template from './dne-dark-mode.html.twig';

const { Component } = Shopware;
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
            isDarkMode: false
        };
    },
    watch: {
        isDarkMode(mode) {
            localStorage.setItem('isDarkMode', mode);

            darkModeClsSetter(mode);
        }
    },
    created() {
        this.isDarkMode = localStorage.getItem('isDarkMode') === 'true';
    }
});

darkModeClsSetter(localStorage.getItem('isDarkMode') === 'true');
