var I360_PANEL = 'noPanel';
var i360role = "client";
var I360_ALLOWED_ITEMS = {
    malware: {
        files: {
            hideFilters: true,
            hideCheckboxes: true,
            allowedActions: ['viewFile']
        },
        'on-demand-scan': {
            hideFilters: true,
            allowedActions: [],
        },
    },
    'proactive-defense': {
        events: {
            hideFilters: true,
            hideCheckboxes: true,
            allowedActions: ['viewFile', 'viewDetails'],
        },
        'ignore-list': {
            hideFilters: true,
            hideCheckboxes: true,
            allowedActions: ['viewFile'],
        },
    },
};