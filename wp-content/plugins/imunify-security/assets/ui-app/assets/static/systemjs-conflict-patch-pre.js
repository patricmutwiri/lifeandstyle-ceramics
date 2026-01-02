// SystemJS global conflict fix (required for some panels) - part before SystemJS script.
// Before we load SystemJS with AMD extras, this is the `define` from RequireJS (or similar 3rd party tool).
window.__orig_define = window.define;
