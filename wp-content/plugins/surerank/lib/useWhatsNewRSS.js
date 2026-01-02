/**
 * === Whats New RSS ===
 *
 * Version: 1.1.0
 * Generated on: 25th November, 2025
 * Documentation: https://github.com/brainstormforce/whats-new-rss/blob/master/README.md
 */

import { useEffect, useRef } from "react";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
const WhatsNewRSSDefaultArgs = {
    rssFeedURL: '',
    selector: '',
    uniqueKey: '',
    loaderIcon: `<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid">
	<circle cx="50" cy="50" fill="none" stroke="#9f9f9f" stroke-width="10" r="35" stroke-dasharray="164.93361431346415 56.97787143782138">
		<animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" dur="1s" values="0 50 50;360 50 50" keyTimes="0;1"></animateTransform>
	</circle>
	</svg>`,
    viewAll: {
        link: '',
        label: 'View All',
    },
    triggerButton: {
        label: '',
        icon: `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8.61703 13.1998C8.04294 13.1503 7.46192 13.125 6.875 13.125H6.25C4.17893 13.125 2.5 11.4461 2.5 9.375C2.5 7.30393 4.17893 5.625 6.25 5.625H6.875C7.46192 5.625 8.04294 5.59972 8.61703 5.55018M8.61703 13.1998C8.82774 14.0012 9.1031 14.7764 9.43719 15.5195C9.64341 15.9782 9.48685 16.5273 9.05134 16.7787L8.50441 17.0945C8.04492 17.3598 7.45466 17.1921 7.23201 16.7106C6.70983 15.5811 6.30451 14.3866 6.03155 13.1425M8.61703 13.1998C8.29598 11.9787 8.125 10.6968 8.125 9.375C8.125 8.05316 8.29598 6.77125 8.61703 5.55018M8.61703 13.1998C11.25 13.427 13.737 14.1643 15.9789 15.3124M8.61703 5.55018C11.25 5.323 13.737 4.58569 15.9789 3.43757M15.9789 3.43757C15.8808 3.12162 15.7751 2.80903 15.662 2.5M15.9789 3.43757C16.4247 4.87356 16.7131 6.37885 16.8238 7.93326M15.9789 15.3124C15.8808 15.6284 15.7751 15.941 15.662 16.25M15.9789 15.3124C16.4247 13.8764 16.7131 12.3711 16.8238 10.8167M16.8238 7.93326C17.237 8.2772 17.5 8.79539 17.5 9.375C17.5 9.95461 17.237 10.4728 16.8238 10.8167M16.8238 7.93326C16.8578 8.40942 16.875 8.8902 16.875 9.375C16.875 9.8598 16.8578 10.3406 16.8238 10.8167" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
        beforeBtn: '',
        afterBtn: '',
        className: '',
        onClick: () => { },
    },
    notification: {
        setLastPostUnixTime: null,
        getLastPostUnixTime: null
    },
    flyout: {
        title: "What's New?",
        innerContent: {
            titleLink: true,
            additionalClasses: []
        },
        excerpt: {
            wordLimit: 500,
            moreSymbol: '&hellip;',
            readMore: {
                label: 'Read More',
                className: '',
            }
        },
        className: '',
        closeOnEsc: true,
        closeOnOverlayClick: true,
        closeBtnIcon: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 18L18 6M6 6L18 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
        formatDate: null,
        onOpen: () => { },
        onClose: () => { },
        onReady: () => { },
    }
};
class WhatsNewRSS {
    ;
    /**
     * Initialize our class.
     *
     * @param {ConstructorArgs} args
     */
    constructor(args) {
        this.rssFeedURLs = [];
        /**
         * UnixTime stamp of the last seen or read post.
         */
        this.lastPostUnixTime = 0;
        /**
         * UnixTime stamp of the last seen or read post for multi feeds by feed key.
         */
        this.multiLastPostUnixTime = {};
        /**
         * Total number of new notification counts.
         */
        this.notificationsCount = 0;
        /**
         * Notification counts for multi feeds by feed key.
         */
        this.multiNotificationCount = {};
        /**
         * Check if has new feeds.
         */
        this.hasNewFeeds = false;
        /**
         * Check if has new feeds in multi feeds mode.
         */
        this.multiHasNewFeeds = {};
        this.validateArgs(args);
        this.parseDefaults(args);
        this.setElement();
        if (!this.getElement()) {
            console.warn('WNR: Cannot find element with', this.getArgs().selector);
            return;
        }
        this.setID();
        this.setRSSFeedURLs();
        WhatsNewRSSCacheUtils.setInstanceID(this.getID());
        this.RSS_Fetch_Instance = new WhatsNewRSSFetch(this);
        this.RSS_View_Instance = new WhatsNewRSSView(this);
        this.setNotificationsCount();
        this.setTriggers();
    }
    /**
     * Validate the passed arguments in constructor.
     *
     * @param {ConstructorArgs} args
     */
    validateArgs(args) {
        ["rssFeedURL", "selector", "uniqueKey"].forEach((requiredArg) => {
            if (!args[requiredArg]) {
                throw new Error(`${requiredArg} is a required argument. It cannot be empty or undefined.`);
            }
            switch (requiredArg) {
                case 'rssFeedURL':
                    const arg = args[requiredArg];
                    if (Array.isArray(arg)) {
                        arg.forEach((rssFeedURL) => {
                            if (!(rssFeedURL === null || rssFeedURL === void 0 ? void 0 : rssFeedURL.key)) {
                                throw new Error(`The parameter "key" is required for "${requiredArg}" parameter in multi-feed mode.`);
                            }
                            if (rssFeedURL.key.includes(' ')) {
                                throw new Error(`The parameter "key" cannot have spaces for "${requiredArg}" parameter in multi-feed mode. Ref Key: "${rssFeedURL.key}"`);
                            }
                        });
                    }
                    break;
                default:
                    break;
            }
        });
    }
    /**
     * Parse the arguments passed by the user with the defaults.
     *
     * @param {ConstructorArgs} args
     */
    parseDefaults(args) {
        var _a, _b;
        this.args = Object.assign(Object.assign(Object.assign({}, WhatsNewRSSDefaultArgs), args), { viewAll: Object.assign(Object.assign({}, WhatsNewRSSDefaultArgs.viewAll), args === null || args === void 0 ? void 0 : args.viewAll), triggerButton: Object.assign(Object.assign({}, WhatsNewRSSDefaultArgs.triggerButton), args === null || args === void 0 ? void 0 : args.triggerButton), flyout: Object.assign(Object.assign(Object.assign({}, WhatsNewRSSDefaultArgs.flyout), args === null || args === void 0 ? void 0 : args.flyout), { innerContent: Object.assign(Object.assign({}, WhatsNewRSSDefaultArgs.flyout.innerContent), (_a = args === null || args === void 0 ? void 0 : args.flyout) === null || _a === void 0 ? void 0 : _a.innerContent), excerpt: Object.assign(Object.assign({}, WhatsNewRSSDefaultArgs.flyout.excerpt), (_b = args === null || args === void 0 ? void 0 : args.flyout) === null || _b === void 0 ? void 0 : _b.excerpt) }) });
    }
    /**
     * Returns parsed args.
     *
     * @returns {ConstructorArgs}
     */
    getArgs() {
        return this.args;
    }
    /**
     * Sets the HTML element queried using passed selector.
     */
    setElement() {
        this.element = document.querySelector(this.args.selector);
    }
    /**
     * Returns the html element according to the selector.
     *
     * @returns {HTMLElement}
     */
    getElement() {
        return this.element;
    }
    /**
     * Creates unique ID for current instance, that can be used by the library elements.
     */
    setID() {
        const data = [this.getArgs().selector, this.getArgs().uniqueKey];
        const rssFeedURL = this.getArgs().rssFeedURL;
        if (Array.isArray(rssFeedURL)) {
            rssFeedURL.forEach((_rssFeedURL) => {
                data.push(_rssFeedURL.key);
            });
        }
        else {
            data.push(rssFeedURL);
        }
        this.ID = btoa(data.join('-')).slice(-12).replace(/=/g, '') + '-' + this.getArgs().uniqueKey;
    }
    /**
     * Whether or not multiple feed urls is provided or not.
     *
     * @returns {boolean}
     */
    isMultiFeedRSS() {
        return 'string' !== typeof this.getArgs().rssFeedURL;
    }
    setRSSFeedURLs() {
        const rssFeedURL = this.getArgs().rssFeedURL;
        if (!this.isMultiFeedRSS()) {
            this.rssFeedURLs.push({
                key: null,
                label: '',
                url: rssFeedURL.toString(),
            });
        }
        else {
            if (Array.isArray(rssFeedURL)) {
                rssFeedURL.forEach(_item => {
                    this.rssFeedURLs.push(_item);
                });
            }
        }
    }
    getRSSFeedURLs() {
        return this.rssFeedURLs;
    }
    /**
     * Returns the current instance unique ID.
     *
     * @returns {string}
     */
    getID() {
        return this.ID;
    }
    /**
     * Checks and counts new notification for the notification badge.
     */
    setNotificationsCount() {
        return __awaiter(this, void 0, void 0, function* () {
            yield Promise.all(this.getRSSFeedURLs().map((_a) => __awaiter(this, [_a], void 0, function* ({ key }) {
                let lastPostUnixTime = 0;
                if (('function' === typeof this.getArgs().notification.getLastPostUnixTime)) {
                    lastPostUnixTime = yield this.getArgs().notification.getLastPostUnixTime(key, this);
                }
                else {
                    lastPostUnixTime = WhatsNewRSSCacheUtils.getLastPostUnixTime(key);
                }
                if (this.isMultiFeedRSS()) {
                    this.multiLastPostUnixTime[key] = +lastPostUnixTime;
                }
                else {
                    this.lastPostUnixTime = +lastPostUnixTime;
                }
            })));
            yield this.RSS_Fetch_Instance.fetchData()
                .then((res) => {
                Object.keys(res).forEach((key) => {
                    const data = res[key];
                    if (!data.length) {
                        return;
                    }
                    this.multiNotificationCount[key] = 0;
                    const currentPostUnixTime = +data[0].date;
                    const lastPostUnixTime = this.isMultiFeedRSS() ? this.multiLastPostUnixTime[key] : this.lastPostUnixTime;
                    if (currentPostUnixTime > lastPostUnixTime) {
                        data.forEach((item) => {
                            if (item.date > lastPostUnixTime) {
                                if (this.isMultiFeedRSS()) {
                                    this.multiNotificationCount[key]++;
                                    this.multiHasNewFeeds[key] = true;
                                }
                                // Keep a record of total notifications even in multi-feed mode.
                                this.notificationsCount++;
                                this.hasNewFeeds = true;
                            }
                        });
                        this.RSS_View_Instance.setNotification(this.notificationsCount);
                    }
                });
            })
                .catch(console.error);
        });
    }
    /**
     * Returns total number of new notifications.
     *
     * @returns {number}
     */
    getNotificationsCount() {
        return this.notificationsCount;
    }
    /**
     * Sets the triggers for the library, eg: close, open, fetch.
     */
    setTriggers() {
        const triggerButton = document.getElementById(this.RSS_View_Instance.getTriggerButtonID());
        const flyout = document.getElementById(this.RSS_View_Instance.getFlyoutID());
        const flyoutInner = flyout.querySelector('.whats-new-rss-flyout-inner-content');
        const flyoutCloseBtn = document.getElementById(this.RSS_View_Instance.getFlyoutCloseBtnID());
        const multiFeedNav = document.getElementById(this.RSS_View_Instance.getFlyoutMultiFeedNavID());
        const injectContents = (key) => {
            /**
             * Fetch data on flyout open.
             */
            this.RSS_Fetch_Instance.fetchData()
                .then((res) => {
                flyoutInner.innerHTML = '';
                const data = res[key];
                if (!data.length) {
                    return;
                }
                const currentPostUnixTime = +data[0].date;
                const lastPostUnixTime = this.isMultiFeedRSS() ? this.multiLastPostUnixTime[key] : this.lastPostUnixTime;
                data.forEach((item) => {
                    const isNewPost = !!lastPostUnixTime ? item.date > lastPostUnixTime : false;
                    const contentTitle = this.getArgs().flyout.innerContent.titleLink ?
                        `<a href="${item.postLink}" target="_blank">
								<h2>${item.title}</h2>
							</a>`
                        :
                            `<h2>${item.title}</h2>`;
                    const innerContent = `
								<div class="rss-content-header">
									<p>${this.RSS_View_Instance.formatDate(new Date(item.date))}</p>
									${contentTitle}
								</div>
								${this.RSS_View_Instance.createExcerpt(item.description, item.postLink, this.getArgs().flyout.excerpt)}
								${this.RSS_View_Instance.listChildrenPosts(item.children)}
							`;
                    const additionalClasses = this.getArgs().flyout.innerContent.additionalClasses;
                    if (!!key) {
                        additionalClasses.push('`inner-content-item-feed-key-${key}`');
                    }
                    flyoutInner.innerHTML += this.RSS_View_Instance.innerContentWrapper(innerContent, isNewPost, additionalClasses.join(' '));
                });
                if (this.getArgs().viewAll.link) {
                    // If we have link provided for the view all button then append a view all button at the end of the contents.
                    flyoutInner.innerHTML += this.RSS_View_Instance.innerContentWrapper(`
							<a href="${this.getArgs().viewAll.link}" class="button view-all">${this.getArgs().viewAll.label}</a>
							`);
                }
                this.RSS_View_Instance.setIsLoading(false);
                flyout.classList.add('ready');
                this.getArgs().flyout.onReady(this);
                /**
                 * Change focus to flyout on flyout ready.
                 */
                flyout.focus();
                // Set the last latest post date for notification handling.
                if (!this.isMultiFeedRSS()) {
                    this.lastPostUnixTime = currentPostUnixTime;
                    if (this.hasNewFeeds) {
                        if ('function' === typeof this.getArgs().notification.setLastPostUnixTime) {
                            this.getArgs().notification.setLastPostUnixTime(currentPostUnixTime, key);
                        }
                        else {
                            WhatsNewRSSCacheUtils.setLastPostUnixTime(currentPostUnixTime, key);
                        }
                    }
                }
            })
                .catch(console.error);
        };
        /**
         * Open flyout on trigger button click.
         * Flyout has three states: `closed | open | ready`
         */
        triggerButton.addEventListener("click", (e) => {
            e.preventDefault();
            const scrollBarWidth = window.innerWidth - document.documentElement.clientWidth;
            this.getArgs().triggerButton.onClick(this);
            this.RSS_View_Instance.setIsLoading(true);
            flyout.removeAttribute('style');
            flyout.classList.remove('closed');
            flyout.classList.add('open');
            document.body.classList.add('whats-new-rss-is-active');
            // Fix glitch issue that happens when opening drawers.
            if (!!scrollBarWidth) {
                const styleSheet = document.getElementById('whats-new-rss-styles');
                if (styleSheet === null || styleSheet === void 0 ? void 0 : styleSheet.sheet) {
                    styleSheet.sheet.insertRule(`.whats-new-rss-is-active { background-color: yellow; padding-right: ${scrollBarWidth}px; }`, styleSheet.sheet.cssRules.length);
                }
            }
            this.getArgs().flyout.onOpen(this);
            if (!this.isMultiFeedRSS()) {
                injectContents(null);
                return;
            }
            const navBtns = multiFeedNav.querySelectorAll('button');
            navBtns.forEach((navBtn) => {
                this.RSS_View_Instance.setMultiFeedTabNotificationCount(navBtn.dataset.feedKey, this.multiNotificationCount[navBtn.dataset.feedKey]);
                navBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const currentFeedKey = navBtn.dataset.feedKey;
                    this.multiNotificationCount[currentFeedKey] = 0;
                    this.RSS_Fetch_Instance.fetchData()
                        .then((res) => {
                        const currentPostUnixTime = res[currentFeedKey][0].date;
                        this.multiLastPostUnixTime[currentFeedKey] = currentPostUnixTime;
                        if (true === this.multiHasNewFeeds[currentFeedKey]) {
                            if ('function' === typeof this.getArgs().notification.setLastPostUnixTime) {
                                this.getArgs().notification.setLastPostUnixTime(currentPostUnixTime, currentFeedKey);
                            }
                            else {
                                WhatsNewRSSCacheUtils.setLastPostUnixTime(currentPostUnixTime, currentFeedKey);
                            }
                        }
                        this.multiHasNewFeeds[currentFeedKey] = false;
                    })
                        .catch(console.error);
                    navBtns.forEach(navBtn => {
                        navBtn.classList.remove('selected');
                        const feedKey = navBtn.dataset.feedKey;
                        const innerContentClassName = `.inner-content-item-feed-key-${feedKey}`;
                        document.querySelectorAll(innerContentClassName).forEach(item => {
                            if (currentFeedKey !== feedKey) {
                                item.classList.add('hidden');
                            }
                            else {
                                item.classList.remove('hidden');
                            }
                        });
                    });
                    navBtn.classList.add('selected');
                    injectContents(currentFeedKey);
                });
            });
            navBtns[0].click();
        });
        /**
         * Handle events for the closing of the flyout.
         */
        const handleFlyoutClose = () => {
            flyout.classList.add('closed');
            flyout.classList.remove('open');
            flyout.classList.remove('ready');
            document.body.classList.remove('whats-new-rss-is-active');
            if (this.isMultiFeedRSS()) {
                this.RSS_View_Instance.setNotification(Object.values(this.multiNotificationCount).filter(Boolean).length);
            }
            else {
                this.hasNewFeeds = false;
                this.RSS_View_Instance.setNotification(false);
            }
            flyoutInner.innerHTML = '';
            this.getArgs().flyout.onClose(this);
            /**
             * Change focus back to trigger button after flyout close.
             */
            triggerButton.focus();
        };
        if (this.getArgs().flyout.closeOnEsc) {
            document.addEventListener('keydown', function (e) {
                if ('Escape' !== e.key)
                    return;
                if (!flyout.classList.contains('open'))
                    return;
                handleFlyoutClose();
            });
        }
        if (this.getArgs().flyout.closeOnOverlayClick) {
            flyout.querySelector('.whats-new-rss-flyout-overlay').addEventListener('click', handleFlyoutClose);
        }
        flyoutCloseBtn.addEventListener('click', handleFlyoutClose);
    }
}
class WhatsNewRSSCacheUtils {
    static setInstanceID(instanceID) {
        if (!this.instanceID) {
            this.instanceID = instanceID;
        }
    }
    static prefixer(key, prefixKey = '') {
        if (!this.instanceID) {
            throw new Error('Instance ID not set.');
        }
        return !!prefixKey ? `${this.keys[key]}-${this.instanceID}-${prefixKey}` : `${this.keys[key]}-${this.instanceID}`;
    }
    static _setDataExpiry(prefixKey = '') {
        const expiryInSeconds = 86400; // Defaults to 24 hours.
        const now = new Date();
        const expiry = now.getTime() + (expiryInSeconds * 1000);
        sessionStorage.setItem(this.prefixer('SESSION_DATA_EXPIRY', prefixKey), JSON.stringify(expiry));
    }
    static _isDataExpired(prefixKey = '') {
        const key = this.prefixer('SESSION_DATA_EXPIRY', prefixKey);
        const value = window.sessionStorage.getItem(key);
        if (!value) {
            return true;
        }
        const expiry = JSON.parse(value);
        const now = new Date();
        if (now.getTime() > expiry) {
            window.sessionStorage.removeItem(key);
            return true;
        }
        return false;
    }
    static setSessionData(data, prefixKey = '') {
        this._setDataExpiry(prefixKey);
        return window.sessionStorage.setItem(this.prefixer('SESSION', prefixKey), data);
    }
    static getSessionData(prefixKey = '') {
        if (!this._isDataExpired(prefixKey)) {
            return window.sessionStorage.getItem(this.prefixer('SESSION', prefixKey));
        }
        return '{}';
    }
    static setLastPostUnixTime(unixTime, prefixKey = '') {
        return window.localStorage.setItem(this.prefixer('LAST_LATEST_POST', prefixKey), unixTime.toString());
    }
    static getLastPostUnixTime(prefixKey = '') {
        return +window.localStorage.getItem(this.prefixer('LAST_LATEST_POST', prefixKey));
    }
}
WhatsNewRSSCacheUtils.keys = {
    SESSION_DATA_EXPIRY: "whats-new-cache-expiry",
    LAST_LATEST_POST: "whats-new-last-unixtime",
    SESSION: "whats-new-cache"
};
/**
 * Class for handling the data fetching.
 * It also handles the session caching of the fetched data internally.
 */
class WhatsNewRSSFetch {
    constructor(RSS) {
        this.data = {};
        this.RSS = RSS;
        this.RSS.getRSSFeedURLs().forEach((feed) => {
            const sessionCache = JSON.parse(WhatsNewRSSCacheUtils.getSessionData(feed.key));
            if (sessionCache && sessionCache.length) {
                this.data[feed.key] = sessionCache;
            }
        });
    }
    fetchData() {
        return __awaiter(this, void 0, void 0, function* () {
            if (Object.keys(this.data).length) {
                return this.data;
            }
            const fetchPromises = this.RSS.getRSSFeedURLs().map((feed) => __awaiter(this, void 0, void 0, function* () {
                this.data[feed.key] = [];
                const res = yield fetch(feed.url);
                let data = yield res.text();
                /**
                 * There was an issue with the xml content parse
                 * And during parse we were getting "<parsererror>" because of the ‘raquo’ entity.
                 */
                data = data.replace(/&raquo;/g, '&amp;raquo;');
                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(data, 'text/xml');
                const items = xmlDoc.querySelectorAll('item');
                items.forEach(item => {
                    var _a;
                    const title = item.querySelector('title').textContent;
                    const link = item.querySelector('link').textContent;
                    const contentEncoded = item.querySelector('content\\:encoded, encoded');
                    const content = contentEncoded ? contentEncoded.textContent : '';
                    const rssDate = item.querySelector('pubDate').innerHTML;
                    this.data[feed.key].push({
                        title: title,
                        date: !!rssDate ? +new Date(rssDate) : null,
                        postLink: link,
                        description: content.replace(/<a\b((?:(?!target=)[^>])*)>/g, '<a$1 target="_blank">').replace(/<p>\s*<\/p>/g, ''),
                        children: JSON.parse(((_a = item.querySelector('children')) === null || _a === void 0 ? void 0 : _a.innerHTML) || '{}')
                    });
                });
                WhatsNewRSSCacheUtils.setSessionData(JSON.stringify(this.data[feed.key]), feed.key);
            }));
            yield Promise.all(fetchPromises);
            return this.data;
        });
    }
}
/**
 * The class for handling library trigger button and flyout elements.
 * It also provides some necessary methods that can be used during development.
 */
class WhatsNewRSSView {
    constructor(RSS) {
        this.RSS = RSS;
        this.createTriggerButton();
        this.createFlyOut();
    }
    getTriggerButtonID() {
        return `whats-new-rss-btn-${this.RSS.getID()}`;
    }
    getFlyoutID() {
        return `whats-new-rss-flyout-${this.RSS.getID()}`;
    }
    getFlyoutCloseBtnID() {
        return `whats-new-rss-flyout-close-${this.RSS.getID()}`;
    }
    getFlyoutMultiFeedNavID() {
        return `whats-new-rss-flyout-multi-feed-nav-${this.RSS.getID()}`;
    }
    setIsLoading(isLoading = false) {
        const flyoutWrapper = document.getElementById(this.getFlyoutID());
        if (isLoading) {
            flyoutWrapper.classList.add('is-loading');
        }
        else {
            flyoutWrapper.classList.remove('is-loading');
        }
    }
    setNotification(notificationsCount) {
        const notificationBadge = document.querySelector(`#${this.getTriggerButtonID()} .whats-new-rss-notification-badge`);
        if (!!notificationsCount) {
            if (this.RSS.isMultiFeedRSS()) {
                notificationBadge.innerHTML = '';
                notificationBadge.classList.add('is-multi-feed');
            }
            else {
                notificationBadge.innerHTML = notificationsCount > 9 ? "9+" : notificationsCount.toString();
            }
            notificationBadge.classList.remove('hide');
        }
        else {
            notificationBadge.classList.add('hide');
        }
    }
    createTriggerButton() {
        let button = '';
        const label = this.RSS.getArgs().triggerButton.label;
        if (!!label) {
            button = `
			${this.RSS.getArgs().triggerButton.beforeBtn}
			<a class="whats-new-rss-trigger-button has-label" id="${this.getTriggerButtonID()}">
				<div class="icon-badge">
					${this.RSS.getArgs().triggerButton.icon}
					<div class="whats-new-rss-notification-badge hide">0</div>
				</div>
				${label}
			</a>
			${this.RSS.getArgs().triggerButton.afterBtn}
			`;
        }
        else {
            button = `
			${this.RSS.getArgs().triggerButton.beforeBtn}
			<a class="whats-new-rss-trigger-button" id="${this.getTriggerButtonID()}">
				${this.RSS.getArgs().triggerButton.icon}
				<div class="whats-new-rss-notification-badge hide">0</div>
			</a>
			${this.RSS.getArgs().triggerButton.afterBtn}
			`;
        }
        this.RSS.getElement().innerHTML += button;
    }
    createFlyOut() {
        const wrapperClasses = [
            'whats-new-rss-flyout',
            'closed',
        ];
        if (this.RSS.getArgs().flyout.className) {
            wrapperClasses.push(this.RSS.getArgs().flyout.className);
        }
        let multiFeedNav = [];
        if (this.RSS.isMultiFeedRSS()) {
            multiFeedNav.push(`<nav id="${this.getFlyoutMultiFeedNavID()}" class="whats-new-rss-multi-feed-nav">`);
            this.RSS.getRSSFeedURLs().forEach((feed) => {
                multiFeedNav.push(`<button type="button" data-feed-key="${feed.key}">
						${feed.label}
						<div class="new-notification-count"></div>
					</button>
					`);
            });
            multiFeedNav.push('</nav>');
        }
        const flyoutWrapper = document.createElement('div');
        flyoutWrapper.setAttribute('id', this.getFlyoutID());
        flyoutWrapper.setAttribute('class', wrapperClasses.join(' '));
        flyoutWrapper.setAttribute('role', 'dialog');
        flyoutWrapper.setAttribute('style', 'visibility:hidden');
        flyoutWrapper.innerHTML = `
		<div class="whats-new-rss-flyout-contents">

			<div class="whats-new-rss-flyout-inner-header">

				<div class="whats-new-rss-flyout-inner-header__title-icon-wrapper">
					<h3>${this.RSS.getArgs().flyout.title}</h3>

					<span class="whats-new-rss-flyout-inner-header__loading-icon">
					${this.RSS.getArgs().loaderIcon}
					</span>
				</div>

				<button type="button" id="${this.getFlyoutCloseBtnID()}">${this.RSS.getArgs().flyout.closeBtnIcon}</button>
			</div>

			${multiFeedNav.join('')}

			<div class="whats-new-rss-flyout-inner-content">
				<div class="skeleton-container">
					<div class="skeleton-row whats-new-rss-flyout-inner-content-item"></div>
					<div class="skeleton-row whats-new-rss-flyout-inner-content-item"></div>
					<div class="skeleton-row whats-new-rss-flyout-inner-content-item"></div>
				</div>
			</div>

		</div>

		<div class="whats-new-rss-flyout-overlay"></div>
		`;
        document.body.appendChild(flyoutWrapper);
    }
    setMultiFeedTabNotificationCount(key, notificationCount = 0) {
        const tabBtn = document.querySelector(`#${this.getFlyoutMultiFeedNavID()} button[data-feed-key="${key}"]`);
        if (!tabBtn) {
            return;
        }
        const el = tabBtn.querySelector('.new-notification-count');
        if (notificationCount) {
            const _count = notificationCount > 9 ? '9+' : notificationCount;
            el.innerHTML = _count.toString();
        }
        else {
            el.innerHTML = '';
        }
    }
    innerContentWrapper(content, isNewPost = false, additionalClasses = '') {
        const classes = ['whats-new-rss-flyout-inner-content-item'];
        if (isNewPost) {
            classes.push('rss-new-post');
        }
        if (!!additionalClasses) {
            classes.push(additionalClasses);
        }
        return `
		<div class="${classes.join(' ')}">
			${isNewPost ? '<small class="new-post-badge">New ✨</small>' : ''}
			${content}
		</div>
		`;
    }
    createExcerpt(content, readMoreLink, options) {
        const { wordLimit, moreSymbol, readMore } = options;
        if (!wordLimit) {
            return content;
        }
        const plainText = content.replace(/<[^>]*>/g, '');
        const words = plainText.split(/\s+/);
        let rawExcerpt = words.slice(0, wordLimit).join(' ');
        if (moreSymbol) {
            rawExcerpt += moreSymbol;
        }
        if (wordLimit > words.length) {
            return content;
        }
        if (!!readMoreLink && !!(readMore === null || readMore === void 0 ? void 0 : readMore.label)) {
            return `<p>${rawExcerpt} <a href="${readMoreLink}" target="_blank" class="${readMore.className}">${readMore.label}</a></p>`;
        }
        return `<p>${rawExcerpt}</p>`;
    }
    listChildrenPosts(children) {
        const _children = Object.values(children);
        if (!_children.length)
            return '';
        const details = document.createElement('details');
        const summary = document.createElement('summary');
        const itemsWrapper = document.createElement('div');
        _children.forEach((child) => {
            const postContentDoc = new DOMParser().parseFromString(child.post_content, 'text/html');
            const itemDiv = document.createElement('div');
            itemDiv.classList.add('sub-version-item');
            itemDiv.innerHTML = `
				<div class="sub-version-header">
					<h4 class="sub-version-title">${child.post_title}</h4>
					<span class="sub-version-date">${this.formatDate(new Date(child.post_date))}</span>
				</div>
				<div class="sub-version-content">${postContentDoc.documentElement.textContent}</div>
			`;
            itemsWrapper.appendChild(itemDiv);
        });
        summary.innerHTML = '<p class="text-see-more">See More</p><p class="text-see-less">See Less</p>';
        details.appendChild(summary);
        details.appendChild(itemsWrapper);
        itemsWrapper.classList.add('sub-version-items-wrapper');
        details.classList.add('whats-new-rss-sub-version-details');
        return details.outerHTML;
    }
    formatDate(date) {
        if ('function' === typeof this.RSS.getArgs().flyout.formatDate) {
            return this.RSS.getArgs().flyout.formatDate(date);
        }
        const currentDate = new Date();
        const timestamp = date.getTime();
        const currentTimestamp = currentDate.getTime();
        const difference = currentTimestamp - timestamp;
        // Define time intervals in milliseconds
        const minute = 60 * 1000;
        const hour = minute * 60;
        const day = hour * 24;
        const week = day * 7;
        const month = day * 30; // Rough estimate, assuming 30 days in a month
        if (difference < minute) {
            return 'Just now';
        }
        else if (difference < hour) {
            const minutes = Math.floor(difference / minute);
            return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        }
        else if (difference < day) {
            const hours = Math.floor(difference / hour);
            return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        }
        else if (difference < week) {
            const days = Math.floor(difference / day);
            return `${days} day${days > 1 ? 's' : ''} ago`;
        }
        else if (difference < month) {
            const weeks = Math.floor(difference / week);
            return `${weeks} week${weeks > 1 ? 's' : ''} ago`;
        }
        else {
            // Handle months and years accordingly
            // This is a rough estimate and may not be accurate in all cases
            const months = Math.floor(difference / month);
            return `${months} month${months > 1 ? 's' : ''} ago`;
        }
    }
}

async function getCSS() {
    return `@import url(https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&display=swap);.whats-new-rss-is-active{overflow:hidden}.whats-new-rss-trigger-button{display:flex;align-items:center;position:relative;width:auto;padding:0!important;cursor:pointer}.whats-new-rss-trigger-button.has-label{align-items:end;gap:10px}.whats-new-rss-trigger-button.has-label .icon-badge{position:relative;line-height:1}.whats-new-rss-trigger-button.has-label .icon-badge .whats-new-rss-notification-badge{top:-12px;right:-10px}.whats-new-rss-trigger-button .whats-new-rss-notification-badge{position:absolute;top:-8px;right:-9px;width:18px;height:18px;font-size:10px;line-height:20px;text-align:center;border-radius:10px;color:#fff;background:#ff580e;display:flex;align-items:center;justify-content:center;transition:transform .2s ease-in-out}.whats-new-rss-trigger-button .whats-new-rss-notification-badge.is-multi-feed{top:-2px;right:-2px;font-size:0;width:10px;height:10px}.whats-new-rss-trigger-button .whats-new-rss-notification-badge.hide{display:none!important}.whats-new-rss-trigger-button:hover .whats-new-rss-notification-badge{transform:unset}.whats-new-rss-flyout{position:fixed;top:0;right:0;bottom:0;width:100%;z-index:99999;transition:visibility .3s ease-in-out;font-family:Figtree,sans-serif;height:100vh;font-size:15px;font-weight:400;line-height:20px;color:#374151;margin-bottom:5px}.whats-new-rss-flyout *,.whats-new-rss-flyout ::after,.whats-new-rss-flyout ::before{box-sizing:border-box}.whats-new-rss-flyout .lightbox-trigger,.whats-new-rss-flyout.hidden{display:none}.whats-new-rss-flyout.is-loading .whats-new-rss-flyout-inner-header__loading-icon{display:block;margin:auto}.whats-new-rss-flyout.is-loading .whats-new-rss-flyout-inner-header__loading-icon svg{display:block;background:0 0;width:25px;height:25px}.whats-new-rss-flyout p{font-size:14px;font-weight:400;line-height:20px;color:#374151;margin-bottom:15px}.whats-new-rss-flyout p a{color:#1170ff}.whats-new-rss-flyout .wp-block-uagb-buttons{background:#ff6c0a;padding:10px 18px;width:fit-content;color:#fff;border-radius:6px;box-shadow:0 1px 2px rgba(0,0,0,.05);margin-bottom:15px}.whats-new-rss-flyout .wp-block-uagb-buttons .uagb-button__link{color:#fff;font-size:1rem;font-weight:600;line-height:1.5rem}.whats-new-rss-flyout .ast-oembed-container{position:relative;padding-top:56.25%;height:0;overflow:hidden;max-width:100%;height:auto}.whats-new-rss-flyout .wp-has-aspect-ratio iframe{bottom:0;height:100%;left:0;position:absolute;right:0;top:0;width:100%}.whats-new-rss-flyout .whats-new-rss-flyout-contents{position:fixed;display:flex;right:0;width:30%;height:100%;flex-direction:column;background-color:#fff;transition:transform .3s ease-in-out;z-index:9999}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-header{display:flex;justify-content:space-between;align-items:center;padding:16px!important;border-bottom:1px solid #e5e7eb}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-header [id^=whats-new-rss-flyout-close-]{width:24px;height:24px;display:flex;align-items:center;justify-content:center}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-header button{border:none;background:0 0;font-size:30px;cursor:pointer;color:#111827;padding:0;margin:0;box-shadow:none}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-header .whats-new-rss-flyout-inner-header__title-icon-wrapper{display:flex;gap:10px;padding:0 4px}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-header .whats-new-rss-flyout-inner-header__title-icon-wrapper h3{margin:0;padding:0;color:#111827;font-weight:600;font-size:16px;line-height:24px;margin-bottom:0!important}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-multi-feed-nav{display:flex;padding:0 30px;border-bottom:1px solid #e5e7eb;gap:2em;overflow:auto;white-space:nowrap}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-multi-feed-nav button{position:relative;background:0 0;color:inherit;padding:15px 0;border-bottom:2px solid transparent}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-multi-feed-nav button:focus,.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-multi-feed-nav button:hover{border-color:inherit}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-multi-feed-nav button.selected{border-color:#5d5d5d}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-multi-feed-nav button .new-notification-count{position:absolute;top:0;right:-10px;width:20px;font-size:10px;line-height:20px;text-align:center;border-radius:10px;color:#fff;background:red}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content{flex:1;overflow-y:auto}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .skeleton-container{margin:30px}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item{border-bottom:1px solid #d8dfe9;padding:20px;color:#374151;font-weight:400;font-size:14px;line-height:20px;display:flex;flex-direction:column}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item>:nth-child(n+1){margin:8px 0 8px 0}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item>:first-child{margin-top:0}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item>:nth-child(n+1):is(ul){margin-top:0}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item>:nth-child(n+1):is(div):has(figure){margin-bottom:12px}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item figure,.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item img{margin:0!important}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item .wp-smiley{display:inline-block;margin:0;min-height:1em;margin-bottom:4px}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item>*{margin:0}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item h2{font-size:22px;font-weight:600;line-height:1.5em;margin-bottom:15px;padding-top:12px;padding-bottom:4px}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item h3{font-size:20px;font-weight:600;line-height:1.5em;padding-top:12px;padding-bottom:4px}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item h4{font-size:18px;font-weight:600;line-height:1.5em;padding-top:12px;padding-bottom:4px}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item h5{font-size:16px;font-weight:600;line-height:1.5em;margin-bottom:15px;padding-top:12px;padding-bottom:4px}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item h6{font-size:13px;font-weight:600;line-height:1.5em;margin-bottom:15px;padding-top:12px;padding-bottom:4px}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item.skeleton-row{height:25vh;margin:40px 0;background:linear-gradient(90deg,#eee 25%,#ddd 50%,#eee 75%);background-size:200% 100%;animation:shimmer 1.5s infinite}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item.rss-new-post{position:relative}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item.rss-new-post .new-post-badge{background:#ff6c0a;border-radius:4px;top:22px;right:24px;padding:0 4px;color:#fff;position:absolute}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item .rss-content-header{display:flex;flex-direction:column;gap:4px;padding:0}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item .rss-content-header p{color:#6b7280;margin:0;padding:0;font-weight:500;line-height:16px;font-size:12px;margin-bottom:0!important}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item .rss-content-header h2{margin:0;color:#111827;font-weight:600;line-height:30px;font-size:20px;padding:0}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item p{padding:0;font-size:14px}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item a{color:#1170ff;text-decoration:none}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item figure{margin:10px 0;margin-bottom:0}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item figure.wp-block-embed.is-type-video{position:relative;width:100%;height:0;padding-bottom:56.25%}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item figure iframe{position:absolute;top:0;left:0;width:100%;height:100%;border:0;max-width:100%}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item img{box-sizing:border-box;height:auto;max-width:100%;vertical-align:bottom;margin-bottom:15px}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item video{vertical-align:middle;width:100%}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item strong{font-weight:600}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item ol,.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item ul{padding-inline-start:20px;margin-left:10px}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item ol li,.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item ul li{font-size:14px;font-weight:400;line-height:20px;color:#374151;margin:0 0 4px 0}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item ol li:last-child,.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item ul li:last-child{margin-bottom:0}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item ul{list-style-type:disc}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-flyout-inner-content .whats-new-rss-flyout-inner-content-item ol{list-style-type:decimal}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-sub-version-details{line-height:20px;font-size:.9em;margin:10px 0;background:#f9fafb}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-sub-version-details summary{display:flex;justify-content:space-between;cursor:pointer;padding:20px 15px;height:1em;line-height:20px;font-weight:800}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-sub-version-details summary::after{content:"⌃";font-size:20px;transform:rotateX(180deg);font-weight:800}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-sub-version-details summary .text-see-more{display:block}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-sub-version-details summary .text-see-less{display:none}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-sub-version-details[open] summary{border-bottom:1px solid #e2e8f0}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-sub-version-details[open] summary::after{transform:rotateX(45deg)}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-sub-version-details[open] summary .text-see-more{display:none}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-sub-version-details[open] summary .text-see-less{display:block}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-sub-version-details .sub-version-item{position:relative;padding:1em;border-bottom:1px solid #e2e8f0}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-sub-version-details .sub-version-item .sub-version-header{display:flex;justify-content:space-between}.whats-new-rss-flyout .whats-new-rss-flyout-contents .whats-new-rss-sub-version-details .sub-version-item .sub-version-content{margin-top:1em}.whats-new-rss-flyout .whats-new-rss-flyout-overlay{position:fixed;top:0;right:0;bottom:0;left:0;background:rgba(0,0,0,.5);transition:all .3s ease-in-out}.whats-new-rss-flyout.closed{visibility:hidden}.whats-new-rss-flyout.closed .whats-new-rss-flyout-overlay{opacity:0;visibility:hidden}.whats-new-rss-flyout.closed .whats-new-rss-flyout-contents{transform:translateX(100%)}.whats-new-rss-flyout .whats-new-rss-flyout-inner-header__loading-icon{display:none}@keyframes shimmer{0%{background-position:-200% 0}100%{background-position:200% 0}}@media screen and (max-width:1024px){.whats-new-rss-flyout .whats-new-rss-flyout-contents{width:40%}}@media screen and (max-width:768px){.whats-new-rss-flyout .whats-new-rss-flyout-contents{width:85%}}`;
}

async function addStyleIfNotExists() {
    const styleId = 'whats-new-rss-styles';
    if (!document.getElementById(styleId)) {
        const style = document.createElement('style');
        style.id = styleId;
        style.innerHTML = await getCSS();
        document.head.appendChild(style);
    }
}


function createWhatsNewRSSInstance(args) {
    return new WhatsNewRSS(args);
}

function useWhatsNewRSS({ selector, ...rest }) {
    const instanceRef = useRef(null);

    useEffect(() => {
        addStyleIfNotExists();

        if (!instanceRef.current) {
            instanceRef.current = createWhatsNewRSSInstance({ selector, ...rest });
        }

        // Cleanup function
        return () => {
            if (instanceRef.current && typeof instanceRef.current.destroy === 'function') {
                instanceRef.current.destroy();
            }
        };
    }, [selector, ...Object.values(rest)]); // Adjust dependencies as needed

    return instanceRef.current;
}

export default useWhatsNewRSS;

