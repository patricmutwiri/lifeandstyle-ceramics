var Restore = function(options) { this.init(options); };

const QUEUE_STATUS_PENDING = 1;
const QUEUE_STATUS_STARTED = 2;
const QUEUE_STATUS_PREPARING = 3;
const QUEUE_STATUS_DONE = 100;
const QUEUE_STATUS_PARTIALLY = 101;
const QUEUE_STATUS_FAILED = 102;
const QUEUE_STATUS_ABORTED = 103;
const QUEUE_STATUS_NEVER_FINISHED = 104;

const logBuffer = [];
let updateScheduled = false;
const maxLogLines = 500;
let logCursor = -1;       // -1 = initial tail mode
let logAtBottom = true;   // autoscroll only if already at bottom

let logEl = null;
const buffered = [];
let rafScheduled = false;

// Lazily resolve #log-container and bind scroll listener once
function ensureLogEl() {
    if (!logEl) {
        logEl = document.getElementById('log-container');
        if (logEl && !logEl._scrollBound) {
            logEl.addEventListener('scroll', () => {
                logAtBottom = (logEl.scrollHeight - logEl.clientHeight - logEl.scrollTop) < 20;
            });
            logEl._scrollBound = true;
            // initialize scroll state
            logAtBottom = (logEl.scrollHeight - logEl.clientHeight - logEl.scrollTop) < 20;
        }
    }
    return !!logEl;
}

// Try to resolve as soon as DOM is ready (covers header-enqueued scripts)
document.addEventListener('DOMContentLoaded', ensureLogEl);

// Append helpers (safe if element not ready yet)
function appendLines(lines) {
    if (!lines || !lines.length) return;
    if (!ensureLogEl()) return; // bail until container exists

    const frag = document.createDocumentFragment();
    for (const line of lines) {
        const div = document.createElement('div');
        div.className = 'log-entry';
        div.textContent = line;
        frag.appendChild(div);
    }
    logEl.appendChild(frag);

    // cap nodes
    while (logEl.children.length > maxLogLines) {
        logEl.removeChild(logEl.firstChild);
    }

    if (logAtBottom) {
        logEl.scrollTop = logEl.scrollHeight;
    }
}

function bufferAppend(lines) {
    if (!lines || !lines.length) return;
    buffered.push(...lines);
    if (!rafScheduled) {
        rafScheduled = true;
        requestAnimationFrame(() => {
            appendLines(buffered.splice(0, buffered.length));
            rafScheduled = false;
        });
    }
}

// --- legacy helpers kept, but made safe ---
function appendLogEntry(entry) {
    if (!ensureLogEl()) return;
    const newEntry = document.createElement("div");
    newEntry.className = "log-entry";
    newEntry.textContent = entry;
    logEl.appendChild(newEntry);
    while (logEl.children.length > maxLogLines) {
        logEl.removeChild(logEl.firstChild);
    }
    logEl.scrollTop = logEl.scrollHeight;
}

function bufferedLog(entry) {
    logBuffer.push(entry);
    if (!updateScheduled) {
        updateScheduled = true;
        setTimeout(() => {
            for (const e of logBuffer) appendLogEntry(e);
            logBuffer.length = 0;
            updateScheduled = false;
        }, 300);
    }
}

Restore.prototype = {
    _queueId: '',
    _interval: null,
    _intervalTime: 3000,
    _completed: false,
    _restoreErrorCount: {},

    _updateProgressBar: function (id, progress) {
        if (isNaN(progress) || progress < 0) progress = 0;
        else if (progress > 100) progress = 100;
        const elm = document.getElementById(id);
        if (!elm) return;
        elm.style.width = progress + '%';
        elm.innerText = progress + '%';
    },

    _ajax: function (options, name) {
        if(this._restoreErrorCount[name] === undefined) this._restoreErrorCount[name] = 0;
        if (options.data === undefined || typeof options.data != 'object') options.data = {};
        if (options.success === undefined || typeof options.success != 'function') options.success = function(){};
        if (options.fail === undefined || typeof options.fail != 'function') options.fail = function(){};
        options.data.id = this._queueId;

        const xhr = new XMLHttpRequest();
        xhr.open("POST", location.protocol + '//' + location.host + location.pathname, true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = () => {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.response);
                        if (response.success) {
                            this._restoreErrorCount[name] = 0;
                            options.success(response.message, response.data);
                        } else {
                            this._restoreErrorCount[name]++;
                            this._logError(`Error ${xhr.status}: ${response.message} (Attempt ${this._restoreErrorCount[name]}/10)`);
                            if (this._restoreErrorCount[name] >= 10) {
                                options.fail(xhr.status, response.message);
                            } else {
                                setTimeout(() => this._ajax(options, name), 3000);
                            }
                        }
                    } catch (e) {
                        this._restoreErrorCount[name]++;
                        this._logError(`Error ${xhr.status}: Invalid JSON response (Attempt ${this._restoreErrorCount[name]}/10)`);
                        if (this._restoreErrorCount[name] >= 10) {
                            options.fail(xhr.status, "Invalid response received from the server");
                        } else {
                            setTimeout(() => this._ajax(options, name), 3000);
                        }
                    }
                } else {
                    this._restoreErrorCount[name]++;
                    this._logError(`HTTP Error ${xhr.status}: Attempt ${this._restoreErrorCount[name]}/10`);
                    if (this._restoreErrorCount[name] >= 10) {
                        options.fail(xhr.status, xhr.response);
                    } else {
                        setTimeout(() => this._ajax(options, name), 3000);
                    }
                }
            }
        };
        xhr.send(new URLSearchParams(options.data).toString());
    },

    // FIXED: this previously referenced undefined variables
    _logError: function (message) {
        bufferAppend([`[client] ${message}`]);
    },

    _redirect: function () {
        setTimeout(function () {
            let pathArray = window.location.pathname.split('/');
            let wpIndex = pathArray.indexOf('wp-content');
            if (wpIndex !== -1) pathArray = pathArray.slice(0, wpIndex);
            else pathArray.pop();
            let basePath = pathArray.join('/');
            window.location.href = window.location.origin + basePath + '/wp-admin';
        }, 3000);
    },

    cancel: function () {
        this.stop();
        this._ajax({
            data: { action: 'cancel' },
            success: () => {
                this._success('Restore has been canceled. Redirecting...');
                this._redirect();
            },
            fail: (status, message) => { this._error(message); }
        }, 'cancel');
    },

    refresh: function () { window.location.reload(); },

    completed: function () {
        this._ajax({
            data: { action: 'completed' },
            success: () => {
                this._success('Restore is completed! Redirecting to your website...');
                this._redirect();
            },
            fail: (status, message) => { this._error(message); }
        }, 'completed');
    },

    _success: function (message) {
        const el = document.getElementById('success-message');
        const box = document.getElementById('success-alert');
        const progress_bars = document.getElementById('progress-bars-container');
        if (el) el.innerText = message;
        if (box) box.style.display = 'block';
        if (progress_bars) progress_bars.style.display = 'none';
    },

    _error: function (message) {
        const el = document.getElementById('error-message');
        const box = document.getElementById('error-alert');
        if (el) el.innerText = message;
        if (box) box.style.display = 'block';
    },

    closeSuccess: function () {
        const box = document.getElementById('success-alert');
        if (box) box.style.display = 'none';
    },

    closeError: function () {
        const box = document.getElementById('error-alert');
        if (box) box.style.display = 'none';
    },

    stop: function () {
        if (this._interval) {
            clearInterval(this._interval);
            this._interval = null;
        }
    },

    _checkStatus: function () {
        const self = this;
        this._ajax({
            data: { action: 'status', cursor: logCursor },
            success: function (message, data) {
                // progress bars
                self._updateProgressBar('progress', data.progress.percentage);
                self._updateProgressBar('subprogress', data.progress.sub_percentage);
                const t = document.getElementById('subprogress-title');
                const c = document.getElementById('subprogress-container');
                if (t) t.innerText = data.progress.message + ':';
                if (c) c.style.display = 'block';

                // logs (incremental)
                if (data.reset && ensureLogEl()) {
                    logEl.innerHTML = '';
                }
                if (typeof data.log_chunk === 'string' && data.log_chunk.length) {
                    const lines = data.log_chunk.replace(/\r/g, '').split('\n');
                    if (lines.length && lines[lines.length - 1] === '') lines.pop();
                    bufferAppend(lines);
                }
                if (typeof data.cursor === 'number') {
                    logCursor = data.cursor;
                }

                // re-poll
                if (data.status < QUEUE_STATUS_DONE) {
                    setTimeout(function () { self._checkStatus(); }, self._intervalTime);
                } else {
                    if (data.status > QUEUE_STATUS_DONE) {
                        if (data.status === QUEUE_STATUS_PARTIALLY) self._error("Restore partially completed");
                        else if (data.status === QUEUE_STATUS_FAILED) self._error("Error occurred during restore, please review the logs");
                        else if (data.status === QUEUE_STATUS_ABORTED) self._error("Restore aborted");
                        else if (data.status === QUEUE_STATUS_NEVER_FINISHED) self._error("Restore never finished");
                    } else {
                        self._success("Restore is completed!");
                        const btn = document.getElementById('finalize-btn');
                        if (btn) btn.style.display = 'inline-block';
                    }
                }
            },
            fail: function (status, message) {
                self._error(message);
            }
        }, '_checkStatus');
    },

    start: function () {
        const self = this;
        // try to resolve the log element before first poll
        ensureLogEl();
        setTimeout(function () { self._checkStatus(); }, 1000);
        this._restore();
    },

    _restore: function () {
        const self = this;
        this._ajax({
            data: { action: 'restore' },
            success: function(message, data) {
                if(!self._completed && data.status < QUEUE_STATUS_DONE) self._restore();
            },
            fail: function (status, message) {
                self._error(message);
            }
        }, '_restore');
    },

    init: function (options) {
        this._queueId = options.queue_id;
        if (options.interval !== undefined) this._intervalTime = options.interval;
    }
};
