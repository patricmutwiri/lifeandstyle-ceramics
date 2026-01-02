/**
 *  Note for future updates of this file:
 *  need to update its version (?v) in all places where it used
 *  to avoid cache issues after package update
 */
(function () {
    let path_prefix;
    const scriptTags = document.querySelectorAll('script');
    const currentScript = Array.from(scriptTags).find(
        (script) => script.src.includes('load-scripts-after-zone.js')
    );

    if (currentScript) {
        path_prefix = currentScript.src.split('static/load-scripts-after-zone.js')[0];
    }

    path_prefix = window.I360_PATH_TO_STATIC || path_prefix;

    let count = 0;
    let importmapLoaded = false;

    function loadScriptsWhenZoneIsAvailable() {
        if (window.Zone && importmapLoaded) {
            addScript('static/shared-dependencies/long-stack-trace-zone.min.js');
            setTimeout(()=> startApp());
        } else {
            if (count > 5000) {
                count = 0;
                addScript('static/shared-dependencies/zone.min.js');
            }
            setTimeout(loadScriptsWhenZoneIsAvailable, 50);
        }
        count++;
    }

    function startApp() {
        addScript('js/config.js', true);
        addScript('static/index.js', true);
    }

    function addScript(scriptPath, disableCache = false) {
        if (disableCache) {
            const timestamp = new Date().getTime();
            scriptPath = scriptPath + '?' + timestamp;
        }

        let script = document.createElement('script');
        script.setAttribute('data-systemjs-only', '1');
        script.setAttribute('src',  path_prefix + scriptPath);
        document.body.appendChild(script);
        return script;
    }

    function loadImportmap() {
        if (window.I360_DISABLE_LOAD_IMPORTMAP) {
            importmapLoaded = true;
            return;
        }

        const timestamp = new Date().getTime(); // for disable caching
        fetch(path_prefix + 'static/importmap.json?' + timestamp)
            .then(response => response.json())
            .then(importmap => {
                System.addImportMap(updateImportPathPrefix(importmap, path_prefix));
            })
            .finally(() => {
                importmapLoaded = true;
            });
    }

    function updateImportPathPrefix(importmap, prefix) {
        const imports = importmap?.imports || {};
        const updatedImports = {};

        Object.keys(imports).forEach(key => {
            updatedImports[key] = `${prefix}static/${imports[key]}`;
        });

        return { imports: updatedImports };
    }

    loadImportmap();
    loadScriptsWhenZoneIsAvailable();
})();
