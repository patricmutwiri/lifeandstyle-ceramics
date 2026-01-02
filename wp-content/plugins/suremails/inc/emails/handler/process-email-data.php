<?php
/**
 * ProcessEmailData.php
 *
 * Handles processing of email data components such as recipients, headers, attachments, message, and subject.
 *
 * @package SureEmails\Inc\Emails\Handler
 */

namespace SureMails\Inc\Emails\Handler;

use PHPMailer\PHPMailer\Exception;
use SureMails\Inc\ConnectionManager;
use SureMails\Inc\Emails\ProviderHelper;
use SureMails\Inc\Traits\Instance;
use SureMails\Inc\Utils\LogError;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class ProcessEmailData
 *
 * Provides methods to process different components of an email.
 */
class ProcessEmailData {

	use Instance;

	/**
	 * Array of primary recipients with 'name' and 'email'.
	 *
	 * @var array
	 */
	private $to = [];

	/**
	 * Associative array containing 'name' and 'email' for the sender.
	 *
	 * @var array
	 */
	private $from = [
		'name'  => '',
		'email' => '',
	];

	/**
	 * Array of CC recipients with 'name' and 'email'.
	 *
	 * @var array
	 */
	private $cc = [];

	/**
	 * Array of BCC recipients with 'name' and 'email'.
	 *
	 * @var array
	 */
	private $bcc = [];

	/**
	 * Array of Reply-To addresses with 'name' and 'email'.
	 *
	 * @var array
	 */
	private $reply_to = [];

	/**
	 * Content type of the email (e.g., 'text/plain' or 'text/html').
	 *
	 * @var string
	 */
	private $content_type = 'text/plain';

	/**
	 * Character set of the email (e.g., 'UTF-8').
	 *
	 * @var string
	 */
	private $charset = 'UTF-8';

	/**
	 * Boundary string used in multipart emails.
	 *
	 * @var string
	 */
	private $boundary = '';

	/**
	 * Mailer used to send the email.
	 *
	 * @var string
	 */
	private $x_mailer;

	/**
	 * Associative array for any additional headers.
	 *
	 * @var array
	 */
	private $extra_headers = [];

	/**
	 * The email body content.
	 *
	 * @var string
	 */
	private $message = '';

	/**
	 * Array of file paths to be attached.
	 *
	 * @var array
	 */
	private $attachments = [];

	/**
	 * The email subject.
	 *
	 * @var string
	 */
	private $subject = '';

	/**
	 * Indicates if the email is a resend.
	 *
	 * @var bool
	 */
	private $is_resend = false;

	/**
	 * ProcessEmailData constructor.
	 *
	 * Initializes default values for properties.
	 */
	public function __construct() {
		$this->x_mailer  = 'WordPress/' . get_bloginfo( 'version' );
		$this->is_resend = ConnectionManager::instance()->get_is_resend();
	}

	/**
	 * Process the recipients ($to).
	 *
	 * @param string|array $to Recipients as a comma-separated string or an array.
	 * @return array Array of recipients with 'name' and 'email'.
	 */
	public function process_to( $to ) {
		$recipients = [];

		// If $to is a string, split it by commas.
		if ( ! is_array( $to ) ) {
			$to = explode( ',', $to );
		}

		foreach ( (array) $to as $recipient ) {
			$recipient = trim( $recipient );
			if ( empty( $recipient ) ) {
				continue;
			}

			// Check if recipient is in the format "Name <email@example.com>".
			if ( preg_match( '/^(.*)<(.+)>$/', $recipient, $matches ) ) {
				$name  = trim( $matches[1] ); // Remove surrounding quotes and whitespace.
				$email = sanitize_email( trim( $matches[2] ) );
			} else {
				$name  = '';
				$email = sanitize_email( $recipient );
			}

			if ( is_email( $email ) ) {
				$recipients[] = [
					'name'  => $name,
					'email' => $email,
				];
			}
		}

		$this->set_to( $recipients );

		return $recipients;
	}

	/**
	 * Process the headers ($headers).
	 *
	 * @param string|array $headers Headers as a string or an array.
	 * @return array Associative array of processed headers.
	 */
	public function process_headers( $headers ) {
		$processed_headers = [
			'bcc'           => [],
			'cc'            => [],
			'reply_to'      => [],
			'content_type'  => 'text/plain',
			'charset'       => get_bloginfo( 'charset' ),
			'boundary'      => '',
			'x_mailer'      => $this->x_mailer,
			'extra_headers' => [],
			'from'          => [
				'name'  => '',
				'email' => '',
			],
		];

		// If headers are a string, split them by newlines.
		if ( ! is_array( $headers ) ) {
			$headers = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
		}

		foreach ( (array) $headers as $header ) {
			$header = trim( $header );
			if ( empty( $header ) ) {
				continue;
			}

			// Ensure the header contains a colon.
			if ( strpos( $header, ':' ) === false ) {
				// Handle headers like "boundary=..." without a colon.
				if ( stripos( $header, 'boundary=' ) !== false ) {
					$boundary_parts = preg_split( '/boundary=/i', $header );
					if ( isset( $boundary_parts[1] ) ) {
						$processed_headers['boundary'] = trim( str_replace( [ '"', "'" ], '', $boundary_parts[1] ) );
					}
				}
				continue;
			}

			[$name, $content] = explode( ':', $header, 2 );
			$name             = strtolower( trim( $name ) );
			$content          = trim( $content );

			switch ( $name ) {
				case 'bcc':
					$processed_headers['bcc'] = array_merge( $processed_headers['bcc'], $this->parse_emails( $content ) );
					break;
				case 'cc':
					$processed_headers['cc'] = array_merge( $processed_headers['cc'], $this->parse_emails( $content ) );
					break;
				case 'reply-to':
					$processed_headers['reply_to'] = array_merge( $processed_headers['reply_to'], $this->parse_emails( $content ) );
					break;
				case 'content-type':
					// Split content-type and charset if available.
					if ( strpos( $content, ';' ) !== false ) {
						[$type, $params]                   = explode( ';', $content, 2 );
						$processed_headers['content_type'] = trim( $type );

						// Parse parameters.
						foreach ( explode( ';', $params ) as $param ) {
							if ( stripos( $param, 'charset=' ) !== false ) {
								$charset                      = trim( str_replace( [ 'charset=', '"' ], '', $param ) );
								$processed_headers['charset'] = sanitize_text_field( $charset );
							} elseif ( stripos( $param, 'boundary=' ) !== false ) {
								$boundary                      = trim( str_replace( [ 'boundary=', '"' ], '', $param ) );
								$processed_headers['boundary'] = sanitize_text_field( $boundary );
							}
						}
					} else {
						$processed_headers['content_type'] = sanitize_text_field( $content );
					}
					break;
				case 'x-mailer':
					$processed_headers['x_mailer'] = sanitize_text_field( $content );
					break;
				case 'from':
					// Parse "From" header.
					if ( preg_match( '/^(.*)<(.+)>$/', $content, $matches ) ) {
						$name  = trim( $matches[1] ); // Remove surrounding quotes and whitespace.
						$email = sanitize_email( trim( $matches[2] ) );
						if ( is_email( $email ) ) {
							$processed_headers['from'] = [
								'name'  => $name,
								'email' => $email,
							];
						}
					} else {
						$email = sanitize_email( trim( $content ) );
						if ( is_email( $email ) ) {
							$processed_headers['from'] = [
								'name'  => '',
								'email' => $email,
							];
						}
					}
					break;
				default:
					// Any other headers.
					$processed_headers['extra_headers'][ $name ] = sanitize_text_field( $content );
					break;
			}
		}

		// Apply WordPress filters if 'from' is not set via headers.
		if ( empty( $processed_headers['from']['email'] ) ) {

			$from_email = apply_filters( 'wp_mail_from', '' );
			$from_name  = apply_filters( 'wp_mail_from_name', '' );

			if ( is_email( $from_email ) ) {
				$processed_headers['from'] = [
					'name'  => sanitize_text_field( $from_name ),
					'email' => $from_email,
				];
			}
		}

		// Apply WordPress filters for content type and charset.
		$processed_headers['content_type'] = apply_filters( 'wp_mail_content_type', $processed_headers['content_type'] );
		$processed_headers['charset']      = apply_filters( 'wp_mail_charset', $processed_headers['charset'] );

		// Set the processed headers to class properties.
		$this->set_from( $processed_headers['from'] );
		$this->set_cc( $processed_headers['cc'] );
		$this->set_bcc( $processed_headers['bcc'] );
		$this->set_reply_to( $processed_headers['reply_to'] );
		$this->set_content_type( $processed_headers['content_type'] );
		$this->set_charset( $processed_headers['charset'] );
		$this->set_boundary( $processed_headers['boundary'] );
		$this->set_x_mailer( $processed_headers['x_mailer'] );
		$this->set_extra_headers( $processed_headers['extra_headers'] );

		return $processed_headers;
	}

	/**
	 * Process the attachments ($attachments).
	 *
	 * @param string|array $attachments Attachments as a string or an array.
	 * @return array Array of sanitized attachment file paths.
	 */
	public function process_attachments( $attachments ) {
		$processed_attachments          = [];
		$processed_uploaded_attachments = [];

		// If attachments are a string, split them by newlines.
		if ( ! is_array( $attachments ) ) {
			$attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );
		}
		$upload = Uploads::instance();

		// Process each attachment.
		$uploaded_attachments = $upload->handle_attachments( $attachments );
		foreach ( (array) $attachments as $attachment ) {
			$attachment = trim( $attachment );
			if ( empty( $attachment ) ) {
				continue;
			}
			if ( $this->is_resend === true ) {
				$path = Uploads::get_suremails_base_dir();
				if ( ! is_wp_error( $path ) && isset( $path['path'] ) ) {
					$attachment = $path['path'] . '/attachments/' . $attachment;
				}
			}
			// Validate the attachment path.
			$attachment = sanitize_text_field( $attachment );
			if ( is_readable( $attachment ) ) {
				$processed_attachments[] = $attachment;
			}
		}

		foreach ( (array) $uploaded_attachments as $attachment ) {
			$attachment = trim( $attachment );
			if ( empty( $attachment ) ) {
				continue;
			}

			// Validate the attachment path.
			$attachment = sanitize_text_field( $attachment );
			if ( file_exists( $attachment ) && is_readable( $attachment ) ) {
				$processed_uploaded_attachments[] = $attachment;
			}
		}

		$this->set_attachments( $processed_attachments );

		return $processed_uploaded_attachments;
	}

	/**
	 * Process the message ($message).
	 *
	 * @param string $message The email message.
	 * @param bool   $is_html Whether the message is HTML.
	 * @return string The sanitized message.
	 */
	public function process_message( string $message, bool $is_html = false ) {

		$this->set_message( $message );

		return $message;
	}

	/**
	 * Sanitize and process the subject ($subject).
	 *
	 * @param string $subject The email subject.
	 * @return string The sanitized subject.
	 */
	public function process_subject( string $subject ) {

		$this->set_subject( $subject );

		return $subject;
	}

	/**
	 * Formats the processed headers into a suitable format for sending.
	 *
	 * @param array $headers Processed headers.
	 * @return array Formatted headers.
	 */
	public function format_processed_headers( array $headers ) {
		$formatted_headers = [];

		// From.
		if ( ! empty( $headers['from']['email'] ) ) {
			$from = $headers['from']['email'];
			if ( ! empty( $headers['from']['name'] ) ) {
				$from = $headers['from']['name'] . ' <' . $headers['from']['email'] . '>';
			}
			$formatted_headers[] = 'From: ' . $from;
		}

		// CC.
		if ( ! empty( $headers['cc'] ) ) {
			$cc_emails           = array_map(
				static function ( $cc ) {
					return ! empty( $cc['name'] ) ? "{$cc['name']} <{$cc['email']}>" : $cc['email'];
				},
				$headers['cc']
			);
			$formatted_headers[] = 'Cc: ' . implode( ', ', $cc_emails );
		}

		// BCC.
		if ( ! empty( $headers['bcc'] ) ) {
			$bcc_emails          = array_map(
				static function ( $bcc ) {
					return ! empty( $bcc['name'] ) ? "{$bcc['name']} <{$bcc['email']}>" : $bcc['email'];
				},
				$headers['bcc']
			);
			$formatted_headers[] = 'Bcc: ' . implode( ', ', $bcc_emails );
		}

		// Reply-To.
		if ( ! empty( $headers['reply_to'] ) ) {
			$reply_to_emails     = array_map(
				static function ( $reply_to ) {
					return ! empty( $reply_to['name'] ) ? "{$reply_to['name']} <{$reply_to['email']}>" : $reply_to['email'];
				},
				$headers['reply_to']
			);
			$formatted_headers[] = 'Reply-To: ' . implode( ', ', $reply_to_emails );
		}

		// Content-Type.
		if ( ! empty( $headers['content_type'] ) ) {
			$content_type_header = 'Content-Type: ' . $headers['content_type'];
			if ( ! empty( $headers['charset'] ) ) {
				$content_type_header .= '; charset=' . $headers['charset'];
			}
			if ( ! empty( $headers['boundary'] ) ) {
				$content_type_header .= '; boundary="' . $headers['boundary'] . '"';
			}
			$formatted_headers[] = $content_type_header;
		}

		// X-Mailer.
		if ( ! empty( $headers['x_mailer'] ) ) {
			$formatted_headers[] = 'X-Mailer: ' . $headers['x_mailer'];
		}

		// Extra Headers.
		if ( ! empty( $headers['extra_headers'] ) && is_array( $headers['extra_headers'] ) ) {
			foreach ( $headers['extra_headers'] as $name => $content ) {
				$formatted_headers[] = "{$name}: {$content}";
			}
		}

		// Return as array for consistency.
		return $formatted_headers;
	}

	/**
	 * Formats email recipients for logging.
	 *
	 * @param array|string $recipients The email recipients.
	 * @return string Formatted recipients.
	 */
	public function format_email_recipients( $recipients ) {
		$formatted = [];

		if ( ! is_array( $recipients ) ) {
			$recipients = array_map( 'trim', explode( ',', $recipients ) );
		}

		foreach ( $recipients as $recipient ) {

			if ( ! empty( $recipient['name'] ) ) {
				$formatted[] = "{$recipient['name']} <{$recipient['email']}>";
			} elseif ( ! empty( $recipient['email'] ) ) {
				$formatted[] = $recipient['email'];
			}
		}
		return implode( ', ', $formatted );
	}

	/**
	 * Comprehensive processing of all email components.
	 *
	 * @param string|array $to          Recipients as a comma-separated string or an array.
	 * @param string|array $headers     Headers as a string or an array.
	 * @param string       $message     The email message.
	 * @param string|array $attachments Attachments as a string or an array.
	 * @param string       $subject     The email subject.
	 * @return array Processed email data.
	 */
	public function process_all( $to, $headers, $message, $attachments, $subject ) {
		// Process each component.
		$processed_to          = $this->process_to( $to );
		$processed_headers     = $this->process_headers( $headers );
		$processed_message     = $this->process_message( $message, true );
		$processed_attachments = $this->process_attachments( $attachments );
		$processed_subject     = $this->process_subject( $subject );

		$mail_data = compact( 'to', 'headers', 'message', 'attachments', 'subject', );
		// Structure the processed data.
		$processed_data = [
			'to'                   => $this->get_to(),
			'headers'              => [
				'from'          => $this->get_from(),          // Array of name and email.
				'cc'            => $this->get_cc(),            // Array of name and email.
				'bcc'           => $this->get_bcc(),           // Array of name and email.
				'reply_to'      => $this->get_reply_to(),      // Array of name and email.
				'content_type'  => $this->get_content_type(),
				'charset'       => $this->get_charset(),
				'boundary'      => $this->get_boundary(),
				'x_mailer'      => $this->get_x_mailer(),
				'extra_headers' => $this->get_extra_headers(), // Associative array.
			],
			'message'              => $this->get_message(),
			'attachments'          => $this->get_attachments(),
			'subject'              => $this->get_subject(),
			'uploaded_attachments' => $processed_attachments,
		];

		$phpmailer = ConnectionManager::instance()->get_phpmailer();
		//phpcs:disable
		$phpmailer->clearAllRecipients();
		$phpmailer->clearAttachments();
		$phpmailer->clearCustomHeaders();
		$phpmailer->clearReplyTos();
		$phpmailer->Body    = '';
		$phpmailer->AltBody = '';

		// Populate PHPMailer with processed data.
		try {
			// Set From.
			$from       = $processed_data['headers']['from'];
			$from_email = $from['email'];
			$from_name  = $from['name'];

			if( ! empty( $from_email ) ) {
				$phpmailer->setFrom( $from_email, $from_name );
			}

			// Add To recipients.
			foreach ( $processed_data['to'] as $recipient ) {
				$phpmailer->addAddress( $recipient['email'], $recipient['name'] );
			}

			// Add CC recipients.
			if ( ! empty( $processed_data['headers']['cc'] ) ) {
				foreach ( $processed_data['headers']['cc'] as $cc ) {
					$phpmailer->addCC( $cc['email'], $cc['name'] );
				}
			}

			// Add BCC recipients.
			if ( ! empty( $processed_data['headers']['bcc'] ) ) {
				foreach ( $processed_data['headers']['bcc'] as $bcc ) {
					$phpmailer->addBCC( $bcc['email'], $bcc['name'] );
				}
			}

			// Add Reply-To addresses.
			if ( ! empty( $processed_data['headers']['reply_to'] ) ) {
				foreach ( $processed_data['headers']['reply_to'] as $reply_to ) {
					$phpmailer->addReplyTo( $reply_to['email'], $reply_to['name'] );
				}
			}

			// Set Subject.
			$phpmailer->Subject = $processed_data['subject'];

			// Set Body.
			$is_html = ProviderHelper::is_html( $processed_data['headers']['content_type'] );
			if( $is_html ) {
				$phpmailer->Body = $processed_data['message'];
				$phpmailer->AltBody = wp_strip_all_tags($processed_data['message']);
			} else {
				$phpmailer->Body = $processed_data['message'];
			}

			// Add Attachments.
			if ( ! empty( $processed_data['attachments'] ) ) {
				foreach ( $processed_data['attachments'] as $attachment ) {
					$file_name = $this->get_attachment_name( $attachment );
					$phpmailer->addAttachment($attachment, $file_name);
				}
			}

			// Set Content-Type and Charset.
			$phpmailer->isHTML( $is_html );
			$phpmailer->CharSet = $processed_data['headers']['charset'];
			//phpcs:enable

		} catch ( Exception $e ) {
			// Handle exceptions during PHPMailer setup.
			// translators: %s is the error message.
			LogError::instance()->log_error( sprintf( __( 'PHPMailer Exception: %s', 'suremails' ), $e->getMessage() ) );
			do_action( 'wp_mail_failed', new \WP_Error( 'phpmailer_exception', $e->getMessage(), $mail_data ) );
		}

		return $processed_data;
	}

	/**
	 * Get the attachment name.
	 *
	 * @param string $attachment The attachment path.
	 * @return string The attachment name.
	 */
	public function get_attachment_name( $attachment ) {

		if ( $this->is_resend === false ) {
			return basename( $attachment );
		}

		$base_name = basename( $attachment );

		// New format: {16-md5}-{12-random}-{name} = 30 char prefix.
		// Old format: {16-md5}-{name} = 17 char prefix.
		// Detection: check if position 29 is a dash (0-indexed: 16 + 1 + 12 = 29).
		if ( strlen( $base_name ) > 29 && $base_name[29] === '-' ) {
			return substr( $base_name, 30 );
		}

		// Backward compatibility: old format <= 1.9.0.
		$dash_pos = strpos( $base_name, '-' );
		return $dash_pos !== false ? substr( $base_name, $dash_pos + 1 ) : $base_name;
	}

	/**
	 * Get HTML headers.
	 *
	 * @return string
	 */
	public static function get_html_headers() {
		return 'Content-Type: text/html; charset=UTF-8.';
	}

	/**
	 * Get plain text headers.
	 *
	 * @return string
	 */
	public static function get_text_headers() {
		return 'Content-Type: text/plain; charset=UTF-8.';
	}

	/* ==================== Getter and Setter Methods ==================== */

	/**
	 * Get the primary recipients.
	 *
	 * @return array Array of recipients with 'name' and 'email'.
	 */
	public function get_to() {
		return $this->to;
	}

	/**
	 * Set the primary recipients.
	 *
	 * @param array $to Array of recipients with 'name' and 'email'.
	 * @return void
	 */
	public function set_to( array $to ) {
		$this->to = $to;
	}

	/**
	 * Get the 'from' details.
	 *
	 * @return array Associative array with 'name' and 'email'.
	 */
	public function get_from() {
		return $this->from;
	}

	/**
	 * Set the 'from' details.
	 *
	 * @param array $from Associative array with 'name' and 'email'.
	 * @return void
	 */
	public function set_from( array $from ) {
		$this->from = $from;
	}

	/**
	 * Get the CC recipients.
	 *
	 * @return array Array of CC recipients with 'name' and 'email'.
	 */
	public function get_cc() {
		return $this->cc;
	}

	/**
	 * Set the CC recipients.
	 *
	 * @param array $cc Array of CC recipients with 'name' and 'email'.
	 * @return void
	 */
	public function set_cc( array $cc ) {
		$this->cc = $cc;
	}

	/**
	 * Get the BCC recipients.
	 *
	 * @return array Array of BCC recipients with 'name' and 'email'.
	 */
	public function get_bcc() {
		return $this->bcc;
	}

	/**
	 * Set the BCC recipients.
	 *
	 * @param array $bcc Array of BCC recipients with 'name' and 'email'.
	 * @return void
	 */
	public function set_bcc( array $bcc ) {
		$this->bcc = $bcc;
	}

	/**
	 * Get the Reply-To addresses.
	 *
	 * @return array Array of Reply-To addresses with 'name' and 'email'.
	 */
	public function get_reply_to() {
		return $this->reply_to;
	}

	/**
	 * Set the Reply-To addresses.
	 *
	 * @param array $reply_to Array of Reply-To addresses with 'name' and 'email'.
	 * @return void
	 */
	public function set_reply_to( array $reply_to ) {
		$this->reply_to = $reply_to;
	}

	/**
	 * Get the content type.
	 *
	 * @return string Content type of the email.
	 */
	public function get_content_type() {
		return $this->content_type;
	}

	/**
	 * Set the content type.
	 *
	 * @param string $content_type Content type of the email.
	 * @return void
	 */
	public function set_content_type( string $content_type ) {
		$this->content_type = $content_type;
	}

	/**
	 * Get the charset.
	 *
	 * @return string Character set of the email.
	 */
	public function get_charset() {
		return $this->charset;
	}

	/**
	 * Set the charset.
	 *
	 * @param string $charset Character set of the email.
	 * @return void
	 */
	public function set_charset( string $charset ) {
		$this->charset = $charset;
	}

	/**
	 * Get the boundary.
	 *
	 * @return string Boundary string used in multipart emails.
	 */
	public function get_boundary() {
		return $this->boundary;
	}

	/**
	 * Set the boundary.
	 *
	 * @param string $boundary Boundary string used in multipart emails.
	 * @return void
	 */
	public function set_boundary( string $boundary ) {
		$this->boundary = $boundary;
	}

	/**
	 * Get the X-Mailer.
	 *
	 * @return string Mailer used to send the email.
	 */
	public function get_x_mailer() {
		return $this->x_mailer;
	}

	/**
	 * Set the X-Mailer.
	 *
	 * @param string $x_mailer Mailer used to send the email.
	 * @return void
	 */
	public function set_x_mailer( string $x_mailer ) {
		$this->x_mailer = $x_mailer;
	}

	/**
	 * Get the extra headers.
	 *
	 * @return array Associative array of extra headers.
	 */
	public function get_extra_headers() {
		return $this->extra_headers;
	}

	/**
	 * Set the extra headers.
	 *
	 * @param array $extra_headers Associative array of extra headers.
	 * @return void
	 */
	public function set_extra_headers( array $extra_headers ) {
		$this->extra_headers = $extra_headers;
	}

	/**
	 * Get the message.
	 *
	 * @return string The email body content.
	 */
	public function get_message() {
		return $this->message;
	}

	/**
	 * Set the message.
	 *
	 * @param string $message The email body content.
	 * @return void
	 */
	public function set_message( string $message ) {
		$this->message = $message;
	}

	/**
	 * Get the attachments.
	 *
	 * @return array Array of attachment file paths.
	 */
	public function get_attachments() {
		return $this->attachments;
	}

	/**
	 * Set the attachments.
	 *
	 * @param array $attachments Array of attachment file paths.
	 * @return void
	 */
	public function set_attachments( array $attachments ) {
		$this->attachments = $attachments;
	}

	/**
	 * Get the subject.
	 *
	 * @return string The email subject.
	 */
	public function get_subject() {
		return $this->subject;
	}

	/**
	 * Set the subject.
	 *
	 * @param string $subject The email subject.
	 * @return void
	 */
	public function set_subject( string $subject ) {
		$this->subject = $subject;
	}

	/**
	 * Parse a string of email addresses into an array.
	 *
	 * @param string $emails Comma-separated email addresses.
	 * @return array Array of sanitized email addresses.
	 */
	private function parse_emails( string $emails ) {
		$parsed_emails = [];

		$emails = explode( ',', $emails );

		foreach ( $emails as $email ) {
			$email = trim( $email );
			if ( empty( $email ) ) {
				continue;
			}

			// Check if email is in "Name <email@example.com>" format.
			if ( preg_match( '/^(.*)<(.+)>$/', $email, $matches ) ) {
				$name  = trim( $matches[1] );
				$email = sanitize_email( trim( $matches[2] ) );
			} else {
				$name  = '';
				$email = sanitize_email( $email );
			}

			if ( is_email( $email ) ) {
				$parsed_emails[] = [
					'name'  => $name,
					'email' => $email,
				];
			}
		}

		return $parsed_emails;
	}
}
