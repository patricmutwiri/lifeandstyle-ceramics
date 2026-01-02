<?php
/**
 * Plugin Name: SureMail
 * Plugin URI: https://suremails.com
 * Description: WordPress emails often go missing or land in spam because web hosts aren’t built for reliable delivery. SureMail fixes this by connecting to trusted SMTP services, so your emails reach inboxes—no more lost messages or frustrated customers.
 * Author: SureMail
 * Author URI: https://suremails.com/
 * Version: 1.9.1
 * License: GPLv2 or later
 * Text Domain: suremails
 * Requires at least: 5.4
 * Requires PHP: 7.4
 *
 * @package suremails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'SUREMAILS_FILE', __FILE__ );
define( 'SUREMAILS_BASE', plugin_basename( SUREMAILS_FILE ) );
define( 'SUREMAILS_DIR', plugin_dir_path( SUREMAILS_FILE ) );
define( 'SUREMAILS_PLUGIN_DIR', plugin_dir_path( __DIR__ ) );
define( 'SUREMAILS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SUREMAILS_VERSION', '1.9.1' );
define( 'SUREMAILS', 'suremail' );
define( 'SUREMAILS_CONNECTIONS', 'suremails_connections' );

require_once SUREMAILS_DIR . 'loader.php'; // Include the Loader file.
require_once SUREMAILS_DIR . 'inc/emails/handler/mail-handler.php';

// Ensure protection files are generated on admin init.
add_action( 'admin_init', 'suremails_ensure_protection_files' );

/**
 * Generate protection files for uploads directory if not already done.
 *
 * This is added temporary. Later on this will be moved to save_file method only.
 *
 * @since 1.9.1
 * @return void
 */
function suremails_ensure_protection_files() {
	$protection_files_generated = get_option( 'suremails_protection_files_generated', false );

	if ( ! $protection_files_generated ) {
		require_once SUREMAILS_DIR . 'inc/emails/handler/uploads.php';
		\SureMails\Inc\Emails\Handler\Uploads::generate_protection_files();
		update_option( 'suremails_protection_files_generated', true, false );
	}
}

// Define wp_mail if it does not exist.
if ( ! function_exists( 'wp_mail' ) ) {
	/**
	 * Override the wp_mail function to use the SureMails MailHandler.
	 *
	 * @param string|array $to          Recipient email address(es).
	 * @param string       $subject     Email subject.
	 * @param string       $message     Email message.
	 * @param string|array $headers     Optional. Additional headers.
	 * @param array        $attachments Optional. Attachments.
	 * @return bool|null Whether the email was sent successfully.
	 */
	function wp_mail( $to, $subject, $message, $headers = '', $attachments = [] ) {
		$atts = apply_filters( 'wp_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments' ) );
		return SureMails\Inc\Emails\Handler\MailHandler::handle_mail( $atts );
	}
}
