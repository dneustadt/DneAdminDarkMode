import './main.scss';
import template from './sw-page.html.twig';
import './dne-dark-mode';

const { Component } = Shopware;

Component.override('sw-page', {
    template
});
