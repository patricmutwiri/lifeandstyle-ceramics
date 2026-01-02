// SystemJS global conflict fix (required for some panels) - part after SystemJS script.
// After we load SystemJS with AMD extras, this is the `define` from SystemJS.
window.__systemjs_define = window.define;

// All our `script` tags must have a `data-systemjs-only`, so we can "namespace" our SystemJS `define`.
const existingCreateScriptHook = System.constructor.prototype.createScript;
System.constructor.prototype.createScript = function () {
    return Promise.resolve(existingCreateScriptHook.apply(this, arguments)).then(function (script) {
        script.setAttribute('data-systemjs-only', '1');
        return script;
    });
};

// If it's our script - use SystemJS. If it's not - use the `define` that was present before US (RequireJS).
function getDefine() {
    return document.currentScript.getAttribute('data-systemjs-only') === '1'
        ? window.__systemjs_define
        : window.__orig_define;
}

if (window.__orig_define === undefined) {
    // Our SystemJS is loaded before RequireJS, so we can use getters/setters - cleaner solution.
    Object.defineProperties(window, {
        define: {
            get() {
                return getDefine();
            },
            set(value) {  // we never overwrite define after this script, so it's not outs
                window.__orig_define = value;
            },
        },
    });
} else {
    // Other party already added their own non-configurable `window.define` property,
    // so we cannot use getters/setters anymore, so we have to use a dirtier method.
    window.define = function () {
        return getDefine().apply(this, arguments);
    }
    Object.defineProperties(window.define, {
        amd: {
            get() {
                return getDefine().amd;
            },
            set(value) {
                getDefine().amd = value;
            },
        },
    });
}
