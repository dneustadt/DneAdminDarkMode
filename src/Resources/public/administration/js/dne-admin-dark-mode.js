!function(e){var t={};function n(r){if(t[r])return t[r].exports;var o=t[r]={i:r,l:!1,exports:{}};return e[r].call(o.exports,o,o.exports,n),o.l=!0,o.exports}n.m=e,n.c=t,n.d=function(e,t,r){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:r})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var r=Object.create(null);if(n.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var o in e)n.d(r,o,function(t){return e[t]}.bind(null,o));return r},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="/bundles/dneadmindarkmode/",n(n.s="vVg8")}({"20Tg":function(e,t,n){var r=n("DHmF");r.__esModule&&(r=r.default),"string"==typeof r&&(r=[[e.i,r,""]]),r.locals&&(e.exports=r.locals);(0,n("SZ7m").default)("627d4e1a",r,!0,{})},DHmF:function(e,t,n){},Nk4s:function(e,t){e.exports="{% block sw_page_notification_center %}\n    <dne-dark-mode></dne-dark-mode>\n    {% parent %}\n{% endblock %}\n"},SZ7m:function(e,t,n){"use strict";function r(e,t){for(var n=[],r={},o=0;o<t.length;o++){var a=t[o],i=a[0],s={id:e+":"+o,css:a[1],media:a[2],sourceMap:a[3]};r[i]?r[i].parts.push(s):n.push(r[i]={id:i,parts:[s]})}return n}n.r(t),n.d(t,"default",(function(){return m}));var o="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!o)throw new Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var a={},i=o&&(document.head||document.getElementsByTagName("head")[0]),s=null,d=0,u=!1,c=function(){},l=null,f="data-vue-ssr-id",p="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function m(e,t,n,o){u=n,l=o||{};var i=r(e,t);return v(i),function(t){for(var n=[],o=0;o<i.length;o++){var s=i[o];(d=a[s.id]).refs--,n.push(d)}t?v(i=r(e,t)):i=[];for(o=0;o<n.length;o++){var d;if(0===(d=n[o]).refs){for(var u=0;u<d.parts.length;u++)d.parts[u]();delete a[d.id]}}}}function v(e){for(var t=0;t<e.length;t++){var n=e[t],r=a[n.id];if(r){r.refs++;for(var o=0;o<r.parts.length;o++)r.parts[o](n.parts[o]);for(;o<n.parts.length;o++)r.parts.push(h(n.parts[o]));r.parts.length>n.parts.length&&(r.parts.length=n.parts.length)}else{var i=[];for(o=0;o<n.parts.length;o++)i.push(h(n.parts[o]));a[n.id]={id:n.id,refs:1,parts:i}}}}function g(){var e=document.createElement("style");return e.type="text/css",i.appendChild(e),e}function h(e){var t,n,r=document.querySelector("style["+f+'~="'+e.id+'"]');if(r){if(u)return c;r.parentNode.removeChild(r)}if(p){var o=d++;r=s||(s=g()),t=w.bind(null,r,o,!1),n=w.bind(null,r,o,!0)}else r=g(),t=k.bind(null,r),n=function(){r.parentNode.removeChild(r)};return t(e),function(r){if(r){if(r.css===e.css&&r.media===e.media&&r.sourceMap===e.sourceMap)return;t(e=r)}else n()}}var b,y=(b=[],function(e,t){return b[e]=t,b.filter(Boolean).join("\n")});function w(e,t,n,r){var o=n?"":r.css;if(e.styleSheet)e.styleSheet.cssText=y(t,o);else{var a=document.createTextNode(o),i=e.childNodes;i[t]&&e.removeChild(i[t]),i.length?e.insertBefore(a,i[t]):e.appendChild(a)}}function k(e,t){var n=t.css,r=t.media,o=t.sourceMap;if(r&&e.setAttribute("media",r),l.ssrId&&e.setAttribute(f,t.id),o&&(n+="\n/*# sourceURL="+o.sources[0]+" */",n+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(o))))+" */"),e.styleSheet)e.styleSheet.cssText=n;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(n))}}},vVg8:function(e,t,n){"use strict";n.r(t);n("20Tg");var r=n("Nk4s"),o=n.n(r),a=n("wwnt"),i=n.n(a),s=Shopware.Component,d=function(e){e?document.body.classList.add("is-dark-mode"):document.body.classList.remove("is-dark-mode")};s.register("dne-dark-mode",{template:i.a,data:function(){return{isDarkMode:!1}},watch:{isDarkMode:function(e){localStorage.setItem("isDarkMode",e),d(e)}},created:function(){this.isDarkMode="true"===localStorage.getItem("isDarkMode")}}),d("true"===localStorage.getItem("isDarkMode")),Shopware.Component.override("sw-page",{template:o.a})},wwnt:function(e,t){e.exports='{% block dne_dark_mode %}\n    <div class="dne-dark-mode">\n        <sw-icon class="sw-context-button" name="default-action-moon" v-if="isDarkMode"></sw-icon>\n        <sw-icon class="sw-context-button" name="default-action-sun" v-else></sw-icon>\n        <sw-switch-field v-model="isDarkMode"></sw-switch-field>\n    </div>\n{% endblock %}\n'}});