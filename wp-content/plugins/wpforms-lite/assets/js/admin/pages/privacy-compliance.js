/* global wpforms_pluginlanding, wpforms_admin */

/**
 * @param wpforms_pluginlanding.activated
 * @param wpforms_pluginlanding.activated_pro
 * @param wpforms_pluginlanding.download_now
 * @param wpforms_pluginlanding.error_could_not_activate
 * @param wpforms_pluginlanding.error_could_not_install
 * @param wpforms_pluginlanding.is_activated
 * @param wpforms_pluginlanding.license_level
 * @param wpforms_pluginlanding.plugins_page
 * @param wpforms_pluginlanding.setup_status
 * @param wpforms_pluginlanding.step3_button_url
 * @param wpforms_pluginlanding.wpconsent_manual_activate_url
 * @param wpforms_pluginlanding.wpconsent_manual_install_url
 */

/**
 * Privacy Compliance Subpage.
 *
 * @since 1.9.7.3
 * @deprecated 1.9.8.6 Use WPFormsPagesCommon instead.
 */
const WPFormsPagesPrivacyCompliance = window.WPFormsPagesPrivacyCompliance || ( function( document, window, $ ) {
	/**
	 * Elements.
	 *
	 * @since 1.9.7.3
	 *
	 * @deprecated 1.9.8.6 Use WPFormsPagesCommon instead.
	 *
	 * @type {Object}
	 */
	let el = {};

	/**
	 * Public functions and properties.
	 *
	 * @since 1.9.7.3
	 *
	 * @deprecated 1.9.8.6 Use WPFormsPagesCommon instead.
	 *
	 * @type {Object}
	 */
	const app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.9.7.3
		 *
		 * @deprecated 1.9.8.6 Use WPFormsPagesCommon.init() instead.
		 */
		init: () => {
			console.warn( 'WARNING! Function "WPFormsPagesPrivacyCompliance.init()" has been deprecated, please use the new "WPFormsPagesCommon.init()" function instead!' ); // eslint-disable-line no-console

			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.9.7.3
		 *
		 * @deprecated 1.9.8.6 Use WPFormsPagesCommon.ready() instead.
		 */
		ready: () => {
			console.warn( 'WARNING! Function "WPFormsPagesPrivacyCompliance.ready()" has been deprecated, please use the new "WPFormsPagesCommon.ready()" function instead!' ); // eslint-disable-line no-console

			app.initVars();
			app.events();
		},

		/**
		 * Init variables.
		 *
		 * @since 1.9.7.3
		 *
		 * @deprecated 1.9.8.6 Use WPFormsPagesCommon.initVars() instead.
		 */
		initVars: () => {
			console.warn( 'WARNING! Function "WPFormsPagesPrivacyCompliance.initVars()" has been deprecated, please use the new "WPFormsPagesCommon.initVars()" function instead!' ); // eslint-disable-line no-console

			el = {
				$stepInstall:    $( 'section.step-install' ),
				$stepInstallNum: $( 'section.step-install .num img' ),
				$stepSetup:      $( 'section.step-setup' ),
				$stepSetupNum:   $( 'section.step-setup .num img' ),
				$stepAddon:      $( 'section.step-addon' ),
				$stepAddonNum:   $( 'section.step-addon .num img' ),
			};
		},

		/**
		 * Register JS events.
		 *
		 * @since 1.9.7.3
		 *
		 * @deprecated 1.9.8.6 Use WPFormsPagesCommon.events() instead.
		 */
		events: () => {
			console.warn( 'WARNING! Function "WPFormsPagesPrivacyCompliance.events()" has been deprecated, please use the new "WPFormsPagesCommon.events()" function instead!' ); // eslint-disable-line no-console

			// Step the 'Install' button click.
			el.$stepInstall.on( 'click', 'button', app.stepInstallClick );

			// Step 'Setup' button click.
			el.$stepSetup.on( 'click', 'button', app.gotoURL );

			// Step the 'Addon' button click.
			el.$stepAddon.on( 'click', 'button', app.gotoURL );
		},

		/**
		 * Step the 'Install' button click.
		 *
		 * @since 1.9.7.3
		 *
		 * @deprecated 1.9.8.6 Use WPFormsPagesCommon.stepInstallClick() instead.
		 *
		 * @param {Event} e Event object.
		 */
		stepInstallClick: ( e ) => {
			console.warn( 'WARNING! Function "WPFormsPagesPrivacyCompliance.stepInstallClick()" has been deprecated, please use the new "WPFormsPagesCommon.stepInstallClick()" function instead!' ); // eslint-disable-line no-console

			const $btn = $( e.currentTarget );

			if ( $btn.hasClass( 'disabled' ) ) {
				return;
			}

			const action = $btn.attr( 'data-action' );

			let ajaxAction = '';

			switch ( action ) {
				case 'activate':
					ajaxAction = 'wpforms_activate_addon';
					$btn.text( wpforms_pluginlanding.activating );
					break;

				case 'install':
					ajaxAction = 'wpforms_install_addon';
					$btn.text( wpforms_pluginlanding.installing );
					break;

				case 'goto-url':
					window.location.href = $btn.attr( 'data-url' );
					return;

				default:
					return;
			}

			$btn.addClass( 'disabled' );
			app.showSpinner( el.$stepInstallNum );

			const plugin = $btn.attr( 'data-plugin' );

			const data = {
				action: ajaxAction,
				nonce : wpforms_admin.nonce,
				plugin,
				type  : 'plugin',
			};
			$.post( wpforms_admin.ajax_url, data )
				.done( function( res ) {
					app.stepInstallDone( res, $btn, action );
				} )
				.always( function() {
					app.hideSpinner( el.$stepInstallNum );
				} );
		},

		/**
		 * Done part of the step 'Install'.
		 *
		 * @since 1.9.7.3
		 *
		 * @deprecated 1.9.8.6 Use WPFormsPagesCommon.stepInstallDone() instead.
		 *
		 * @param {Object} res    Result of $.post() query.
		 * @param {jQuery} $btn   Button.
		 * @param {string} action Action (for more info look at the app.stepInstallClick() function).
		 */
		stepInstallDone: ( res, $btn, action ) => {
			console.warn( 'WARNING! Function "WPFormsPagesPrivacyCompliance.stepInstallDone()" has been deprecated, please use the new "WPFormsPagesCommon.stepInstallDone()" function instead!' ); // eslint-disable-line no-console

			const success = 'install' === action ? res.success && res.data.is_activated : res.success;

			if ( success ) {
				el.$stepInstallNum.attr( 'src', el.$stepInstallNum.attr( 'src' ).replace( 'step-1.', 'step-complete.' ) );
				$btn.addClass( 'grey' ).removeClass( 'button-primary' ).text( wpforms_pluginlanding.activated );
				app.stepInstallPluginStatus();
			} else {
				const activationFail = ( 'install' === action && res.success && ! res.data.is_activated ) || 'activate' === action,
					url = ! activationFail ? wpforms_pluginlanding.wpconsent_manual_install_url : wpforms_pluginlanding.wpconsent_manual_activate_url,
					msg = ! activationFail ? wpforms_pluginlanding.error_could_not_install : wpforms_pluginlanding.error_could_not_activate,
					btn = ! activationFail ? wpforms_pluginlanding.download_now : wpforms_pluginlanding.plugins_page;

				$btn.removeClass( 'grey disabled' ).text( btn ).attr( 'data-action', 'goto-url' ).attr( 'data-url', url );
				$btn.after( '<p class="error">' + msg + '</p>' );
			}
		},

		/**
		 * Callback for step 'Install' completion.
		 *
		 * @since 1.9.7.3
		 *
		 * @deprecated 1.9.8.6 Use WPFormsPagesCommon.stepInstallPluginStatus() instead.
		 */
		stepInstallPluginStatus: () => {
			console.warn( 'WARNING! Function "WPFormsPagesPrivacyCompliance.stepInstallPluginStatus()" has been deprecated, please use the new "WPFormsPagesCommon.stepInstallPluginStatus()" function instead!' ); // eslint-disable-line no-console

			const data = {
				action: 'wpforms_privacy_compliance_page_check_plugin_status',
				nonce : wpforms_admin.nonce,
			};
			$.post( wpforms_admin.ajax_url, data ).done( app.stepInstallPluginStatusDone );
		},

		/**
		 * Done part of the callback for step 'Install' completion.
		 *
		 * @since 1.9.7.3
		 *
		 * @deprecated 1.9.8.6 Use WPFormsPagesCommon.stepInstallPluginStatusDone() instead.
		 *
		 * @param {Object} res Result of $.post() query.
		 */
		stepInstallPluginStatusDone: ( res ) => {
			console.warn( 'WARNING! Function "WPFormsPagesPrivacyCompliance.stepInstallPluginStatusDone()" has been deprecated, please use the new "WPFormsPagesCommon.stepInstallPluginStatusDone()" function instead!' ); // eslint-disable-line no-console

			if ( ! res.success ) {
				return;
			}

			el.$stepSetup.removeClass( 'grey' );
			el.$stepSetupBtn = el.$stepSetup.find( 'button' );

			if ( res.data.setup_status > 0 ) {
				el.$stepSetupNum.attr( 'src', el.$stepSetupNum.attr( 'src' ).replace( 'step-2.svg', 'step-complete.svg' ) );
				el.$stepAddon.removeClass( 'grey' );
				el.$stepAddonBtn = el.$stepAddon.find( 'button' );

				if ( res.data.license_level === 'pro' ) {
					el.$stepAddonBtn.text( wpforms_pluginlanding.activated_pro );
					el.$stepAddonNum.attr( 'src', el.$stepAddonNum.attr( 'src' ).replace( 'step-3.svg', 'step-complete.svg' ) );
				} else {
					el.$stepAddonBtn.attr( 'data-url', res.data.step3_button_url );
					el.$stepAddonBtn.removeClass( 'grey disabled' ).addClass( 'button-primary' );
				}
			} else {
				el.$stepSetupBtn.removeClass( 'grey disabled' ).addClass( 'button-primary' );
			}
		},

		/**
		 * Go to URL.
		 *
		 * @since 1.9.7.3
		 *
		 * @deprecated 1.9.8.6 Use WPFormsPagesCommon.gotoURL() instead.
		 *
		 * @param {Event} e Event object.
		 */
		gotoURL: ( e ) => { // eslint-disable-line no-unused-vars
			console.warn( 'WARNING! Function "WPFormsPagesPrivacyCompliance.gotoURL()" has been deprecated, please use the new "WPFormsPagesCommon.gotoURL()" function instead!' ); // eslint-disable-line no-console

			const $btn = $( e.currentTarget ),
				url = $btn.attr( 'data-url' );

			if ( $btn.hasClass( 'disabled' ) || ! url ) {
				return;
			}

			window.location.href = url;
		},

		/**
		 * Show spinner.
		 *
		 * @since 1.9.7.3
		 *
		 * @deprecated 1.9.8.6 Use WPFormsPagesCommon.showSpinner() instead.
		 *
		 * @param {jQuery} $el Element.
		 */
		showSpinner: ( $el ) => {
			console.warn( 'WARNING! Function "WPFormsPagesPrivacyCompliance.showSpinner()" has been deprecated, please use the new "WPFormsPagesCommon.showSpinner()" function instead!' ); // eslint-disable-line no-console

			$el.siblings( 'i.loader' ).removeClass( 'hidden' );
		},

		/**
		 * Hide spinner.
		 *
		 * @since 1.9.7.3
		 *
		 * @deprecated 1.9.8.6 Use WPFormsPagesCommon.hideSpinner() instead.
		 *
		 * @param {jQuery} $el Element.
		 */
		hideSpinner: ( $el ) => {
			console.warn( 'WARNING! Function "WPFormsPagesPrivacyCompliance.hideSpinner()" has been deprecated, please use the new "WPFormsPagesCommon.hideSpinner()" function instead!' ); // eslint-disable-line no-console

			$el.show();
			$el.siblings( 'i.loader' ).addClass( 'hidden' );
		},
	};

	// Provide public access to functions and properties.
	return app;
}( document, window, jQuery ) );

// Initialize.
WPFormsPagesPrivacyCompliance.init();
