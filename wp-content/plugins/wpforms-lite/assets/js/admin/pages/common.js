/* global wpforms_pluginlanding, wpforms_admin */

/**
 * @param wpforms_pluginlanding.activated
 * @param wpforms_pluginlanding.activated_pro
 * @param wpforms_pluginlanding.download_now
 * @param wpforms_pluginlanding.error_could_not_activate
 * @param wpforms_pluginlanding.error_could_not_install
 * @param wpforms_pluginlanding.is_activated
 * @param wpforms_pluginlanding.license_level
 * @param wpforms_pluginlanding.result_status
 * @param wpforms_pluginlanding.plugins_page
 * @param wpforms_pluginlanding.setup_status
 * @param wpforms_pluginlanding.step3_button_url
 * @param wpforms_pluginlanding.manual_activate_url
 * @param wpforms_pluginlanding.manual_install_url
 */

/**
 * Landing Pages Common.
 *
 * @since 1.9.8.6
 */
const WPFormsPagesCommon = window.WPFormsPagesCommon || ( function( document, window, $ ) {
	/**
	 * Elements.
	 *
	 * @since 1.9.8.6
	 *
	 * @type {Object}
	 */
	let el = {};

	/**
	 * Public functions and properties.
	 *
	 * @since 1.9.8.6
	 *
	 * @type {Object}
	 */
	const app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.9.8.6
		 */
		init: () => {
			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.9.8.6
		 */
		ready: () => {
			app.initVars();
			app.events();
		},

		/**
		 * Init variables.
		 *
		 * @since 1.9.8.6
		 */
		initVars: () => {
			el = {
				$stepInstall:    $( 'section.step-install' ),
				$stepInstallNum: $( 'section.step-install .num img' ),
				$stepSetup:      $( 'section.step-setup' ),
				$stepSetupNum:   $( 'section.step-setup .num img' ),
				$stepResult:      $( 'section.step-result' ),
				$stepResultNum:   $( 'section.step-result .num img' ),
			};
		},

		/**
		 * Register JS events.
		 *
		 * @since 1.9.8.6
		 */
		events: () => {
			// Step the 'Install' button click.
			el.$stepInstall.on( 'click', 'button', app.stepInstallClick );

			// Step 'Setup' button click.
			el.$stepSetup.on( 'click', 'button', app.gotoURL );

			// Step the 'Addon' button click.
			el.$stepResult.on( 'click', 'button', app.gotoURL );
		},

		/**
		 * Step the 'Install' button click.
		 *
		 * @since 1.9.8.6
		 *
		 * @param {Event} e Event object.
		 */
		stepInstallClick: ( e ) => {
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
		 * @since 1.9.8.6
		 *
		 * @param {Object} res    Result of $.post() query.
		 * @param {jQuery} $btn   Button.
		 * @param {string} action Action (for more info look at the app.stepInstallClick() function).
		 */
		stepInstallDone: ( res, $btn, action ) => { // eslint-disable-line complexity
			const success = 'install' === action ? res.success && res.data.is_activated : res.success,
				provider = $btn.data( 'provider' );

			if ( success ) {
				el.$stepInstallNum.attr( 'src', el.$stepInstallNum.attr( 'src' ).replace( 'step-1.', 'step-complete.' ) );
				$btn.addClass( 'grey' ).removeClass( 'button-primary' ).text( wpforms_pluginlanding.activated );
				app.stepInstallPluginStatus( provider );
			} else {
				const activationFail = ( 'install' === action && res.success && ! res.data.is_activated ) || 'activate' === action,
					installUrl = wpforms_pluginlanding[ provider + '_manual_install_url' ] || '',
					activateUrl = wpforms_pluginlanding[ provider + '_manual_activate_url' ] || '',
					url = ! activationFail ? installUrl : activateUrl,
					msg = ! activationFail ? wpforms_pluginlanding.error_could_not_install : wpforms_pluginlanding.error_could_not_activate,
					btn = ! activationFail ? wpforms_pluginlanding.download_now : wpforms_pluginlanding.plugins_page;

				$btn.removeClass( 'grey disabled' ).text( btn ).attr( 'data-action', 'goto-url' ).attr( 'data-url', url );
				$btn.after( '<p class="error">' + msg + '</p>' );
			}
		},

		/**
		 * Callback for step 'Install' completion.
		 *
		 * @since 1.9.8.6
		 *
		 * @param {string} plugin Plugin name.
		 */
		stepInstallPluginStatus: ( plugin ) => {
			const data = {
				action: 'wpforms_page_check_' + plugin + '_status',
				nonce: wpforms_admin.nonce,
				provider: plugin,
			};
			$.post( wpforms_admin.ajax_url, data ).done( app.stepInstallPluginStatusDone );
		},

		/**
		 * Done part of the callback for step 'Install' completion.
		 *
		 * @since 1.9.8.6
		 *
		 * @param {Object} res Result of $.post() query.
		 */
		stepInstallPluginStatusDone: ( res ) => {
			if ( ! res.success ) {
				return;
			}

			el.$stepSetup.removeClass( 'grey' );
			el.$stepSetupBtn = el.$stepSetup.find( 'button' );

			if ( res.data.setup_status > 0 ) {
				el.$stepSetupNum.attr( 'src', el.$stepSetupNum.attr( 'src' ).replace( 'step-2.svg', 'step-complete.svg' ) );
				el.$stepResult.removeClass( 'grey' );
				el.$stepResultBtn = el.$stepResult.find( 'button' );

				if ( res.data.license_level === 'pro' && res.data.result_status === true ) {
					el.$stepResultBtn.text( wpforms_pluginlanding.activated_pro );
					el.$stepResultNum.attr( 'src', el.$stepResultNum.attr( 'src' ).replace( 'step-3.svg', 'step-complete.svg' ) );
				} else {
					el.$stepResultBtn.attr( 'data-url', res.data.step3_button_url );
					el.$stepResultBtn.removeClass( 'grey disabled' ).addClass( 'button-primary' );
				}
			} else {
				el.$stepSetupBtn.removeClass( 'grey disabled' ).addClass( 'button-primary' );
			}
		},

		/**
		 * Go to URL.
		 *
		 * @since 1.9.8.6
		 *
		 * @param {Event} e Event object.
		 */
		gotoURL: ( e ) => { // eslint-disable-line no-unused-vars
			const $btn = $( e.currentTarget ),
				url = $btn.attr( 'data-url' );

			if ( $btn.hasClass( 'disabled' ) || ! url ) {
				return;
			}

			window.open( url, '_blank' );
		},

		/**
		 * Show spinner.
		 *
		 * @since 1.9.8.6
		 *
		 * @param {jQuery} $el Element.
		 */
		showSpinner: ( $el ) => {
			$el.siblings( 'i.loader' ).removeClass( 'hidden' );
		},

		/**
		 * Hide spinner.
		 *
		 * @since 1.9.8.6
		 *
		 * @param {jQuery} $el Element.
		 */
		hideSpinner: ( $el ) => {
			$el.show();
			$el.siblings( 'i.loader' ).addClass( 'hidden' );
		},
	};

	// Provide public access to functions and properties.
	return app;
}( document, window, jQuery ) );

// Initialize.
WPFormsPagesCommon.init();
