var JetBackup = function(options) { this.init(options); };
JetBackup.prototype = {
	_lang: '',
	_mfa: {},
	_lang_default: 'en_US',
	_path: {
		api: './admin-ajax.php?action=jetbackup_api',
		download: './admin.php?page=jetbackup',
		media: '',
		location: '',
		lang_cdn_release: '_e0', // _e0 is the latest
		lang_cdn_project: '_a7213103036961125313e9d0a2cf',
		lang_cdn: 'https://delivery.localazy.com'
	},
	_account: {},
	_language_ns: [],
	_language_cdn: false,
	_info: {},
	_lib_files: [
		"/libraries/moment-with-locales.js",
		//"/libraries/bootstrap/js/bootstrap.bundle.min.js",
		"/libraries/angularjs/1.8.2/angular.min.js",
		"/libraries/angularjs/1.8.2/angular-aria.min.js",
		"/libraries/angularjs/1.8.2/angular-route.min.js",
		"/libraries/angularjs/1.8.2/angular-animate.min.js",
		"/libraries/angularjs/1.8.2/angular-sanitize.min.js",
		"/libraries/angularjs-material/1.1.6/angular-material.min.js",
		"/libraries/angular-moment-picker/angular-moment-picker.min.js",
		"/libraries/angularjs-i18next/i18next.js",
		"/libraries/angularjs-i18next/i18nextHttpBackend.js",
		"/libraries/angularjs-i18next/i18nextSprintfPostProcessor.js",
		"/libraries/angularjs-i18next/ng-i18next.js",
		"/libraries/ui-bootstrap/ui-bootstrap-tpls-2.5.0.min.js",
		"/libraries/angular-loading-bar/loading-bar.min.js",
		"/libraries/requirejs/requirejs.min.js",
	],
	_loadLang: function(callback) {
		if(callback === undefined) callback = function() {};
		if(this._language_cdn) this._loadLangCDN(callback);
		else this._loadLangLocal(callback);
	},
	_loadLangCDN: function (callback) {
		if(callback === undefined) callback = function() {};

		var self = this;

		var oReq = new XMLHttpRequest();
		oReq.addEventListener("load", function () {
			
			if(this.status !== 200) {
				self._loadLangLocal(callback);
				return;
			}

			var files = JSON.parse(this.responseText).files;
			var cdn_files_map = {};
			for(var key in files) {
				var filename = files[key].file;
				// remove the .json suffix 
				filename = filename.substring(-5, filename.length-5);
				cdn_files_map[filename] = {key: key, languages:[]};
				var locales = files[key].locales;
				for(var locale in locales) {
					var language = locales[locale].language;
					if(locales[locale].region) language += '_' + locales[locale].region;
					language = language.replace('-', '_').toLowerCase();
					cdn_files_map[filename].languages[language] = locales[locale].uri;
				}
			}

			self._loadLangLocal(callback, cdn_files_map);
		});
		oReq.open("GET", self._path.lang_cdn + '/' + self._path.lang_cdn_project + '/' + self._path.lang_cdn_release + '.v2.json');
		oReq.send();
	},
	_loadLangLocal: function(callback, cdn_files_map) {

		if(callback === undefined) callback = function() {};
		if(cdn_files_map === undefined) cdn_files_map = {};

		if (!window.i18next) return;

		var self = this;
		
		window.i18next
		.use(window.i18nextHttpBackend)
		.use(window.i18nextSprintfPostProcessor);

		var currentLang = this._lang ? this._lang : 'en_US';
		var fallbackLng = [];

		if(currentLang !== 'en_US') fallbackLng.push(currentLang);
		fallbackLng.push('en_US');

		window.i18next.init({
			debug: false,
			lng: currentLang,
			fallbackLng: fallbackLng,
			backend: {
				loadPath: function (lang, ns) {
					
					lang = lang[0];
					ns = ns[0];

					var version = (self._info.development ? self._randomHash(12) : self._info.version);
					
					// if the language isn't supported or the file not found in our CDN, try to use the local language files
					if(cdn_files_map[ns] === undefined || cdn_files_map[ns].languages[lang.toLowerCase()] === undefined) 
						return self._path.media + '/lang/' + lang + '/{{ns}}.json?v=' + version;
					
					return self._path.lang_cdn + cdn_files_map[ns].languages[lang.toLowerCase()] + '?v=' + version;
				}
			},
			ns: self._language_ns,
			defaultNS: 'common',
			fallbackNS: 'common',
			keySeparator: "|.|",
			nsSeparator: "|:|",
			pluralSeparator: "|_|",
			contextSeparator: "|_|",
			useCookie: false,
			useLocalStorage: false,
			load: 'currentOnly',
			postProcess: [ 'sprintf' ]
		}, function (err, t) {
			callback();
		});
	},
	_api: function(func, callback) {
		var oReq = new XMLHttpRequest();
		oReq.addEventListener("load", function () {

			if(this.status != 200 || /^</.test(this.responseText)) {
				var message = "";
				if(this.status != 200) message = "API call failed with response code " + this.status;
				else message = "API call returned invalid response. Error: " + this.responseText;
				callback({ success: 0, message: message, data: {} });
				return;
			}

			callback(JSON.parse(this.responseText));
		});
		oReq.open("POST", this._path.api + "&nonce=" + window.PAGE.nonce + "&actionType=" + func);
		oReq.send();
	},
	_loadMain: function(){
		var self = this;
		require.config({
			baseUrl: self._path.media,
			waitSeconds: 0,
			urlArgs: 'v=' + self._info.version
		});

		self._loadLang(function() { self._loadJS("/main" + (self._info.development ? '' : '.min') +  ".js"); });
	},
	_loadLibCount: 0,
	_loadLibRetries: 0,
	_rand: null,
	_randomHash :function(length) {

		if(this._rand) return this._rand;

		this._rand           = '';
		var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		var charactersLength = characters.length;
		for ( var i = 0; i < length; i++ ) {
			this._rand += characters.charAt(Math.floor(Math.random() * charactersLength));
		}
		return this._rand;
	},
	_loadLib: function() {
		var self = this;

		self._api('panelPreload', function (response) {
			self._loadLibRetries++;
			if(!response.success) {
				var message = "Failed loading panel preload information. Error: " + response.message;
				if(self._loadLibRetries > 15) self._addLoaderError(message);
				setTimeout(function () { self._loadLib(); }, 2000);
				throw Error(message);
			}

			var error = document.getElementById('JetBackupLoadingError');
			if(error !== null) error.innerHTML = '';

			self._info = response.data.info;
			self._info.version = response.system.version;
			self._account = response.data.account;
			self._configuration = response.data.configuration;
			self._integration = response.data.integration;
			self._mfa = response.data.mfa;

			self._language_ns = response.data.language_ns;
			self._language_cdn = response.data.language_cdn;

			window.PAGE.info = self._info;
			window.PAGE.system = response.system;
			window.PAGE.account = self._account;
			window.PAGE.configuration = self._configuration;
			window.PAGE.integration = self._integration;
			window.PAGE.mfa = self._mfa;

			window.PAGE.license = response.data.license;
			window.PAGE.nonce = response.system.nonce;

			for (var j=0; self._lib_files.length>j; j++) {
				self._loadJS(self._lib_files[j], function () {
					if (++self._loadLibCount >= self._lib_files.length) self._loadMain();
				});
			}
		});

	},
	_loadJS: function(file, onload) {
		if(onload === undefined || typeof onload != 'function') onload = function () {};
		var script = document.createElement('script');
		script.src = this._path.media + file + '?v=' + (this._info.development ? this._randomHash(12) : this._info.version);
		script.type = 'text/javascript';
		script.async = false;
		script.addEventListener('load', onload);
		document.head.appendChild(script);
	},
	_addLoaderError: function(message) {
		var error = document.getElementById('JetBackupLoadingError');
		if(error === null) return false;
		if(!message) return true;
		error.innerHTML = '<div style="margin-top: 30px;">' + message + '</div>';
		return true;

	},
	_buildLoader: function() {

		var html = '';
		html += "<div style='text-align: center; padding: 30px; 0; color: #ffffff; margin-top: 150px;'>";
		html += "<img src='" + this._path.media + "/images/logo-loader.png' class='pounding-logo' alt='JetBackup' />";
		html += "<div id='JetBackupLoadingError'></div>";
		html += "</div>";

		var main = document.getElementById('JetBackup');
		if(main === null) return;
		main.innerHTML = html;

	},
	init: function (options) {

		this._lang = options.language;
		
		if(!window.PAGE) window.PAGE = {};
		window.PAGE.lang = this._lang;
		window.PAGE.convertedToMomentLocales = window.PAGE.lang.split('_')[0]
		window.PAGE.nonce = options.nonce;
		window.PAGE.dateFormat =  options.dateFormat;
		window.PAGE.timeFormat =  options.timeFormat;

		this._path.location = window.location.pathname;
		this._path.media = options.plugin_path;

		// Adjust API path only if "/network/" is found
		if (window.location.pathname.includes('/network/')) {
			const basePath = window.location.pathname.replace('/network/', '/');
			this._path.api = window.location.origin + basePath.split('/wp-admin/')[0] + '/wp-admin/admin-ajax.php?action=jetbackup_api';
		}

		window.PAGE.path = this._path;
		
		this._buildLoader();
		this._loadLib();
	}
};