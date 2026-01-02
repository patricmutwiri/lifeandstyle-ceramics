<?php
/**
 * WeeklySummary Class
 *
 * Sends weekly email summary based on stats collected via SureMails logs.
 *
 * @package SureMails\Inc\Controller
 */

namespace SureMails\Inc\Controller;

use SureMails\Inc\DB\EmailLog;
use SureMails\Inc\Settings;
use SureMails\Inc\Traits\Instance;
use SureMails\Inc\Traits\SendEmail;
use SureMails\Inc\Utils\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WeeklySummary
 */
class WeeklySummary {

	use Instance;
	use SendEmail;

	/**
	 * Miscellaneous plugin settings.
	 *
	 * @var array|null
	 */
	private $settings = null;

	/**
	 * Constructor.
	 *
	 * Initializes settings.
	 */
	public function __construct() {
		$this->settings = Settings::instance()->get_misc_settings();
	}

	/**
	 * Called via daily cron. Sends summary only on configured day.
	 *
	 * @return void
	 */
	public function maybe_send_summary(): void {
		$active            = $this->settings['email_summary_active'] ?? 'yes';
		$day               = $this->settings['email_summary_day'] ?? 'monday';
		$today             = strtolower( gmdate( 'l' ) );
		$connections_count = count( Settings::instance()->get_raw_settings()['connections'] ?? [] );

		if ( 'no' === $active || $today !== $day || $connections_count < 1 ) {
			return;
		}

		$this->send_summary_email();
		$index = (int) ( $this->settings['email_summary_index'] ?? 1 ) + 1;

		if ( $index > 12 ) {
			$index = 1;
		}
		Settings::instance()->update_misc_settings( 'email_summary_index', $index );
	}

	/**
	 * Handles logic to compile and send the summary email.
	 *
	 * @return void
	 */
	private function send_summary_email(): void {
		$email = $this->get_email_content();
		if ( empty( $email ) || empty( $email['to'] ) || empty( $email['body'] ) ) {
			return;
		}
		$this->send( $email['to'], $email['subject'], $email['body'], $this->get_html_headers(), [] );
	}

	/**
	 * Builds the subject, body, and recipient for the summary email.
	 *
	 * @return array
	 */
	private function get_email_content(): array {
		$stats = $this->get_statistics();
		$to    = get_option( 'admin_email' );

		$website_name = get_bloginfo( 'name' );
		if ( empty( $website_name ) ) {
			$website_name = __( 'Your Website', 'suremails' );
		}

		// Filter to control whether site name should be included in subject.
		$include_site_name_in_subject = apply_filters( 'suremails_weekly_summary_include_site_name_in_subject', false );

		if ( $include_site_name_in_subject ) {
			$subject = sprintf(
				/* translators: 1: Website name, 2: From date, 3: To date */
				esc_html__( 'Email Summary of last week - %1$s - %2$s to %3$s', 'suremails' ),
				esc_html( $website_name ),
				esc_html( date_i18n( 'F j, Y', strtotime( '-7 days' ) ) ),
				esc_html( date_i18n( 'F j, Y', strtotime( '-1 day' ) ) )
			);
		} else {
			$subject = sprintf(
				/* translators: 1: From date, 2: To date */
				esc_html__( 'Email Summary of last week - %1$s to %2$s', 'suremails' ),
				esc_html( date_i18n( 'F j, Y', strtotime( '-7 days' ) ) ),
				esc_html( date_i18n( 'F j, Y', strtotime( '-1 day' ) ) )
			);
		}

		$body = $this->build_email_header()
			. $this->build_email_body( $stats )
			. $this->build_email_footer();

		return [
			'to'      => $to,
			'subject' => $subject,
			'body'    => $body,
		];
	}

	/**
	 * Build email header HTML.
	 *
	 * @return string
	 */
	private function build_email_header(): string {
		ob_start();
		?>
		<!DOCTYPE html>
		<html lang="en">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<title><?php esc_html_e( 'Weekly Summary', 'suremails' ); ?></title>
			<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
		</head>
		<body style="font-family:Figtree,Arial,sans-serif;background-color:#F1F5F9;margin:0;padding:32px;">
			<div style="max-width:640px;margin:0 auto;">
				<div style="margin-bottom:24px;text-align:left;">
					<img src="<?php echo esc_url( 'https://suremails.com/wp-content/uploads/2025/10/suremail-logo-og-scaled.png' ); ?>"
						alt="<?php esc_attr_e( 'SureMail Logo', 'suremails' ); ?>" width="162" height="32"
						style="display:inline-block;">
				</div>
				<div style="background-color:#FFFFFF;padding-bottom:40px;">
					<?php
					$content = ob_get_clean();
					return $content !== false ? $content : '';
	}

	/**
	 * Generate logs URL with date range for the past 7 days.
	 *
	 * @return string
	 */
	private function get_logs_url(): string {
		$from_date = gmdate( 'Y-m-d', strtotime( '-7 days' ) );
		$to_date   = gmdate( 'Y-m-d', strtotime( '-1 day' ) );

		return Utils::get_admin_url( "/logs?from={$from_date}&to={$to_date}" );
	}

	/**
	 * Build email body with statistics.
	 *
	 * @param array $stats Email statistics.
	 * @return string
	 */
	private function build_email_body( array $stats ): string {
		$logs_url = $this->get_logs_url();

		ob_start();
		?>
					<div style="padding:24px;">
						<p style="font-size:18px;font-weight:600;color:#111827;margin:0 0 8px;">
							<?php esc_html_e( 'Hey There,', 'suremails' ); ?>
						</p>
						<p style="font-size:14px;color:#4B5563;margin:0 0 16px;">
							<?php
							$site_url  = home_url();
							$site_name = get_bloginfo( 'name' );

							if ( ! empty( $site_name ) ) {
								$link_text = $site_name;
							} else {
								$link_text = str_replace( [ 'https://', 'http://' ], '', $site_url );
							}

							printf(
								/* translators: %s: Website name or URL link */
								esc_html__( 'Here is your SureMail report for the last 7 days of %s', 'suremails' ),
								'<a href="' . esc_url( $site_url ) . '" style="color:#2563EB;text-decoration:none;font-weight:400;" target="_blank" rel="noopener noreferrer"><strong>' . esc_html( $link_text ) . '</strong></a>'
							);
							?>
						</p>

						<?php echo wp_kses_post( $this->build_statistics_table( $stats ) ); ?>

						<a href="<?php echo esc_url( $logs_url ); ?>"
							style="display:inline-block;background-color:#2563EB;color:#FFFFFF;padding:8px 12px;border-radius:4px;text-decoration:none;font-size:12px;font-weight:600;margin-top:16px;">
							<?php esc_html_e( 'View Email Logs', 'suremails' ); ?>
						</a>
					</div>
					<?php
					$content = ob_get_clean();
					return $content !== false ? $content : '';
	}

	/**
	 * Build statistics table HTML.
	 *
	 * @param array $stats Email statistics.
	 * @return string
	 */
	private function build_statistics_table( array $stats ): string {
		$stats_labels = [
			'sent'    => esc_html__( 'Emails Sent Successfully', 'suremails' ),
			'failed'  => esc_html__( 'Emails Failed to Send', 'suremails' ),
			'blocked' => esc_html__( 'Emails Blocked by Reputation Shield', 'suremails' ),
		];

		ob_start();
		?>
					<table
						style="border:1px solid #E5E7EB;border-radius:8px;box-shadow:0 1px 1px rgba(0,0,0,0.05);margin-top:16px;width:100%;border-collapse:separate;border-spacing:0;">
						<thead>
							<tr style="background-color:#F9FAFB;">
								<th
									style="padding:8px 12px;font-size:14px;font-weight:500;color:#111827;text-align:left;border-top-left-radius:8px;">
									<?php esc_html_e( 'Emails', 'suremails' ); ?>
								</th>
								<th
									style="padding:8px 12px;font-size:14px;font-weight:500;color:#111827;text-align:right;width:146px;border-top-right-radius:8px;">
									<?php esc_html_e( 'Last Week', 'suremails' ); ?>
								</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$row_index   = 0;
							$stats_count = count( $stats_labels );

							foreach ( $stats_labels as $key => $label ) {
								$bg_color            = $row_index % 2 === 0 ? '#FFFFFF' : '#F9FAFB';
								$is_last             = $row_index === $stats_count - 1;
								$border_top          = $row_index > 0 ? '0.5px solid #E5E7EB' : 'none';
								$border_radius_left  = $is_last ? 'border-bottom-left-radius:8px;' : '';
								$border_radius_right = $is_last ? 'border-bottom-right-radius:8px;' : '';
								?>
								<tr style="background-color:<?php echo esc_attr( $bg_color ); ?>;">
									<td
										style="padding:12px;font-size:14px;color:#4B5563;border-top:<?php echo esc_attr( $border_top ); ?>;<?php echo esc_attr( $border_radius_left ); ?>">
										<?php echo esc_html( $label ); ?>
									</td>
									<td
										style="padding:12px;font-size:14px;color:#4B5563;text-align:right;width:146px;border-top:<?php echo esc_attr( $border_top ); ?>;<?php echo esc_attr( $border_radius_right ); ?>">
										<?php echo esc_html( $stats[ $key ] ?? 0 ); ?>
									</td>
								</tr>
								<?php
								$row_index++;
							}
							?>
						</tbody>
					</table>
					<?php
					$content = ob_get_clean();
					return $content !== false ? $content : '';
	}

	/**
	 * Build email footer HTML.
	 *
	 * @return string
	 */
	private function build_email_footer(): string {
		$all_products = [
			'astra'            => [
				'url'           => 'https://wpastra.com?utm_medium=suremails-email-summary',
				'title'         => esc_html__( 'Build Faster with Astra', 'suremails' ),
				'description'   => esc_html__( 'Fast, lightweight & customizable WordPress theme for website builders.', 'suremails' ),
				'image'         => 'https://suremails.com/wp-content/uploads/2025/10/astra.png',
				'explore_title' => 'Astra',
			],
			'surecart'         => [
				'url'           => 'https://surecart.com?utm_medium=suremails-email-summary',
				'title'         => esc_html__( 'Boost Sales with SureCart', 'suremails' ),
				'description'   => esc_html__( 'Powerful WordPress e-commerce plugin for selling digital and physical products.', 'suremails' ),
				'image'         => 'https://suremails.com/wp-content/uploads/2025/10/surecart.png',
				'explore_title' => 'SureCart',
			],
			'sureforms'        => [
				'url'           => 'https://sureforms.com?utm_medium=suremails-email-summary',
				'title'         => esc_html__( 'Grow Your List with SureForms', 'suremails' ),
				'description'   => esc_html__( 'Creating beautiful, functional forms has never been easier with this AI form builder.', 'suremails' ),
				'image'         => 'https://suremails.com/wp-content/uploads/2025/10/sureforms.png',
				'explore_title' => 'SureForms',
			],
			'prestoplayer'     => [
				'url'           => 'https://prestoplayer.com/?utm_medium=suremails-email-summary',
				'title'         => esc_html__( 'Engage Viewers with Presto Player', 'suremails' ),
				'description'   => esc_html__( 'The best video player plugin for WordPress with modern video experience.', 'suremails' ),
				'image'         => 'https://suremails.com/wp-content/uploads/2025/10/prestoplayer.png',
				'explore_title' => 'Presto Player',
			],
			'suredash'         => [
				'url'           => 'https://suredash.com?utm_medium=suremails-email-summary',
				'title'         => esc_html__( 'Impress Clients with SureDash', 'suremails' ),
				'description'   => esc_html__( 'All-in-one solution to connect, engage, grow, and scale your community.', 'suremails' ),
				'image'         => 'https://suremails.com/wp-content/uploads/2025/10/suredash.png',
				'explore_title' => 'SureDash',
			],
			'cartflows'        => [
				'url'           => 'https://cartflows.com?utm_medium=suremails-email-summary',
				'title'         => esc_html__( 'Increase Conversions with CartFlows', 'suremails' ),
				'description'   => esc_html__( 'One click sales funnel builder for WordPress with conversion optimized templates.', 'suremails' ),
				'image'         => 'https://suremails.com/wp-content/uploads/2025/10/cartflows.png',
				'explore_title' => 'CartFlows',
			],
			'suremembers'      => [
				'url'           => 'https://suremembers.com?utm_medium=suremails-email-summary',
				'title'         => esc_html__( 'Monetize Content with SureMembers', 'suremails' ),
				'description'   => esc_html__( 'Top-rated WordPress membership plugin for managing member access and content.', 'suremails' ),
				'image'         => 'https://suremails.com/wp-content/uploads/2025/10/suremembers.png',
				'explore_title' => 'SureMembers',
			],
			'startertemplates' => [
				'url'           => 'https://startertemplates.com/?utm_medium=suremails-email-summary',
				'title'         => esc_html__( 'Launch Sites with Starter Templates', 'suremails' ),
				'description'   => esc_html__( 'Build beautiful websites in minutes with 600+ AI-powered templates for Elementor and Gutenberg.', 'suremails' ),
				'image'         => 'https://suremails.com/wp-content/uploads/2025/10/startertemplates.png',
				'explore_title' => 'Starter Templates',
			],
			'zipwp'            => [
				'url'           => 'https://zipwp.com?utm_medium=suremails-email-summary',
				'title'         => esc_html__( 'Create Sites Instantly with ZipWP', 'suremails' ),
				'description'   => esc_html__( 'Plan, build, and host stunning WordPress websites instantly. No setup. No mess.', 'suremails' ),
				'image'         => 'https://suremails.com/wp-content/uploads/2025/10/zipwp.png',
				'explore_title' => 'ZipWP',
			],
			'ottokit'          => [
				'url'           => 'https://ottokit.com?utm_medium=suremails-email-summary',
				'title'         => esc_html__( 'Work Smarter with OttoKit', 'suremails' ),
				'description'   => esc_html__( 'No-code AI automation tool for creating automated workflows without technical skills.', 'suremails' ),
				'image'         => 'https://suremails.com/wp-content/uploads/2025/10/ottokit.png',
				'explore_title' => 'OttoKit',
			],
			'surefeedback'     => [
				'url'           => 'https://surefeedback.com?utm_medium=suremails-email-summary',
				'title'         => esc_html__( 'Collaborate Better with SureFeedback', 'suremails' ),
				'description'   => esc_html__( 'Get design feedback and client approval using WordPress with visual collaboration.', 'suremails' ),
				'image'         => 'https://suremails.com/wp-content/uploads/2025/10/surefeedback.png',
				'explore_title' => 'SureFeedback',
			],
			'surerank'         => [
				'url'           => 'https://surerank.com?utm_medium=suremails-email-summary',
				'title'         => esc_html__( 'Rank Higher with SureRank', 'suremails' ),
				'description'   => esc_html__( 'Modern SEO without the bloat - simple, lightweight SEO assistant for better rankings.', 'suremails' ),
				'image'         => 'https://suremails.com/wp-content/uploads/2025/10/surerank-icon.png',
				'explore_title' => 'SureRank',
			],
		];

		$index        = isset( $this->settings['email_summary_index'] ) ? (int) $this->settings['email_summary_index'] : 1;
		$idx          = max( 1, $index );
		$product_keys = array_keys( $all_products );
		$pick_index   = ( $idx - 1 ) % count( $product_keys );
		$product_slug = $product_keys[ $pick_index ];
		$product_data = $all_products[ $product_slug ];

		ob_start();
		?>
					<hr style="margin:24px 24px 16px;border:none;border-top:1px solid #E5E7EB;">
					<div style="padding:0 24px 24px;">
						<table role="presentation" cellpadding="0" cellspacing="0"
							style="width:100%;margin:0;background-color:#FFFFFF;border:0.5px solid #E5E7EB;border-radius:8px;border-collapse:separate;">
							<tr>
								<td style="padding:16px;">
									<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;">
										<!-- Logo Row -->
										<tr>
											<td style="text-align:left;padding-bottom:4px;">
												<img src="<?php echo esc_url( $product_data['image'] ); ?>"
													alt="
													<?php
													echo esc_attr(
														// translators: %s: Product name.
														sprintf( esc_attr__( '%s logo', 'suremails' ), ucfirst( $product_slug ) )
													);
													?>
														"
													width="20" height="20" style="display:block;">
											</td>
										</tr>
										<!-- Content Row -->
										<tr>
											<td style="text-align:left;">
												<h3
													style="font-size:14px;line-height:20px;font-weight:600;color:#111827;margin:0 0 2px 0;font-family:Figtree,Arial,sans-serif;">
													<?php echo esc_html( $product_data['title'] ); ?>
												</h3>
												<p
													style="font-size:12px;line-height:16px;font-weight:400;color:#6B7280;margin:0 0 4px 0;font-family:Figtree,Arial,sans-serif;">
													<?php echo esc_html( $product_data['description'] ); ?>
												</p>
												<a href="<?php echo esc_url( $product_data['url'] ); ?>"
													style="font-size:12px;line-height:16px;font-weight:600;color:#2563EB;text-decoration:none;font-family:Figtree,Arial,sans-serif;display:inline-block;"
													target="_blank" rel="noopener noreferrer">
													<?php
													/* translators: %s: Product name */
													printf( esc_html__( 'Explore %s →', 'suremails' ), esc_html( $product_data['explore_title'] ) );
													?>
												</a>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</div>
					<hr style="margin:0 24px 24px;border:none;border-top:1px solid #E5E7EB;">
					<div>
						<p
							style="font-size:12px;color:#9CA3AF;text-align:center;margin:16px 0;font-family:Figtree,Arial,sans-serif;">
							<a href="<?php echo esc_url( Utils::get_admin_url( '/settings' ) ); ?>"
								style="color:#9CA3AF;text-decoration:none;">
								<?php esc_html_e( 'Manage Email Summaries from your website settings', 'suremails' ); ?>
							</a>
						</p>
						<hr style="border:none;border-top:1px solid #E5E7EB;margin:0 24px;">
						<div style="text-align:center;margin-top:16px;">
							<a href="<?php echo esc_url( 'https://suremails.com' ); ?>" target="_blank" rel="noopener noreferrer">
								<img src="<?php echo esc_url( 'https://suremails.com/wp-content/uploads/2025/10/suremail-logo-og-scaled.png' ); ?>"
									alt="<?php esc_attr_e( 'SureMail Logo', 'suremails' ); ?>" height="20"
									style="display:block;margin:0 auto;">
							</a>
						</div>
					</div>
				</div>
			</div>
		</body>

		</html>
		<?php
		$content = ob_get_clean();
		return $content !== false ? $content : '';
	}

	/**
	 * Get email statistics for the past 7 days.
	 *
	 * @return array
	 */
	private function get_statistics(): array {
		$email_log  = EmailLog::instance();
		$start_date = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );
		$end_date   = gmdate( 'Y-m-d H:i:s', strtotime( '-1 day 23:59:59' ) );

		$results = $email_log->get(
			[
				'select'   => 'status, COUNT(*) as count',
				'where'    => [
					'updated_at >=' => $start_date,
					'updated_at <=' => $end_date,
				],
				'group_by' => 'status',
			]
		);

		$counts = [
			'sent'    => 0,
			'failed'  => 0,
			'blocked' => 0,
		];

		if ( is_array( $results ) && ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$status = $row['status'];
				$count  = (int) $row['count'];
				if ( isset( $counts[ $status ] ) ) {
					$counts[ $status ] = $count;
				}
			}
		}

		return $counts;
	}
}
