!function(t){var e={};function o(n){if(e[n])return e[n].exports;var r=e[n]={i:n,l:!1,exports:{}};return t[n].call(r.exports,r,r.exports,o),r.l=!0,r.exports}o.m=t,o.c=e,o.d=function(t,e,n){o.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:n})},o.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},o.t=function(t,e){if(1&e&&(t=o(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var n=Object.create(null);if(o.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var r in t)o.d(n,r,function(e){return t[e]}.bind(null,r));return n},o.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return o.d(e,"a",e),e},o.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},o.p=".",o(o.s=0)}({"./src/accordionAutoplay.js":function(t,e){!function(t){"use strict";window.VcvAccordionAutoplay=function(e){let o,n,r;const a=e;o=function(e,o){const r=Array.prototype.slice.call(arguments,1);return this.each((function(){const i=t(this);let u=i.data(a.autoplayDataSelector);i.data("vcv-autoplay-on-editor-disabled")||(u||(u=new n(i,t.extend(!0,{},n.DEFAULTS,i.data("vce-tta-autoplay"),o)),i.data(a.autoplayDataSelector,u)),"string"==typeof e?u[e].apply(u,r):u.start(r))}))},(n=function(t,e){this.$element=t,this.options=e}).DEFAULTS={delay:5e3,pauseOnHover:!0,stopOnClick:!0},n.prototype.show=function(){this.$element.find(a.accordionDataSelector+":eq(0)")[a.accordionPropertyName]("showNext",{changeHash:!1,scrollTo:!1})},n.prototype.hasTimer=function(){return void 0!==this.$element.data(a.autoplayTimerSelector)},n.prototype.setTimer=function(t){this.$element.data(a.autoplayTimerSelector,t)},n.prototype.getTimer=function(){return this.$element.data(a.autoplayTimerSelector)},n.prototype.deleteTimer=function(){this.$element.removeData(a.autoplayTimerSelector)},n.prototype.start=function(){const t=this.$element,e=this;this.hasTimer()||(this.setTimer(window.setInterval(this.show.bind(this),this.options.delay)),this.options.stopOnClick&&t.on(a.autoplayOnEventSelector,a.accordionDataSelector,(function(n){n.preventDefault&&n.preventDefault(),e.hasTimer()&&o.call(t,"stop")})),this.options.pauseOnHover&&t.hover((function(n){n.preventDefault&&n.preventDefault(),e.hasTimer()&&o.call(t,"mouseleave"===n.type?"resume":"pause")})))},n.prototype.resume=function(){this.hasTimer()&&this.setTimer(window.setInterval(this.show.bind(this),this.options.delay))},n.prototype.stop=function(){this.pause(),this.deleteTimer(),this.$element.off(a.autoplayOnEventSelector+" mouseenter mouseleave")},n.prototype.pause=function(){const t=this.getTimer();void 0!==t&&window.clearInterval(t)},this.setupAutoplayProperty=function(){r=t.fn[a.autoplayPropertyName],t.fn[a.autoplayPropertyName]=o,t.fn[a.autoplayPropertyName].Constructor=n,t.fn[a.autoplayPropertyName].noConflict=function(){return t.fn[a.autoplayPropertyName]=r,this}},this.startAutoPlay=function(){t(a.autoplaySelector).each((function(){t(this)[a.autoplayPropertyName]()}))}},window.VcvAccordionAutoplay.prototype.init=function(){this.setupAutoplayProperty(),this.startAutoPlay()}}(window.jQuery)},0:function(t,e,o){t.exports=o("./src/accordionAutoplay.js")}});