<?php
/**
 * Email Reports
 *
 * The main module for scheduling and sending email reports.
 *
 * @package SureRank\Inc\Modules\EmailReports
 * @since 1.6.0
 */

namespace SureRank\Inc\Modules\EmailReports;

use SureRank\Inc\Frontend\Image;
use SureRank\Inc\Traits\Get_Instance;
use SureRank\Inc\GoogleSearchConsole\Controller as  GSC_Controller;
use SureRank\Inc\GoogleSearchConsole\Auth as GSC_Auth;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email Reports Controller.
 *
 * @since 1.6.0
 */
class Controller {



	use Get_Instance;

	/**
	 * Cron event name for email reports.
	 *
	 * @since 1.6.0
	 */
	public const EMAIL_REPORTS_CRON_EVENT = 'surerank_send_email_report';

	/**
	 * Email Reports Controller.
	 *
	 * @since 1.6.0
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize the Email Reports module.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	public function init() {
		add_action( self::EMAIL_REPORTS_CRON_EVENT, [ $this, 'send_email_report' ] );
		add_action( 'init', [ $this, 'maybe_send_email_fallback' ] );
		add_action( 'update_option_surerank_email_reports_settings', [ $this, 'on_settings_updated' ], 10, 2 );
		add_filter( 'cron_schedules', [ $this, 'cron_schedules' ] );
		add_action( 'wp_loaded', [ $this, 'check_and_schedule' ] );
	}

	/**
	 * Check if scheduling is needed and run it.
	 * This runs on every wp_loaded but exits early if cron exists.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	public function check_and_schedule() {
		// If cron is already scheduled, no need to check further.
		if ( wp_next_scheduled( self::EMAIL_REPORTS_CRON_EVENT ) ) {
			return;
		}

		// Cron doesn't exist - try to schedule it.
		$this->maybe_schedule_email_report();
	}

	/**
	 * Check if email report cron is already scheduled.
	 * 
	 * @since 1.6.0
	 * @return bool True if scheduled, false otherwise.
	 */
	private function has_scheduled() {
		$timestamp = wp_next_scheduled( self::EMAIL_REPORTS_CRON_EVENT );
		return $timestamp !== false;
	}

	/**
	 * Add custom cron schedules.
	 *
	 * @since 1.6.0
	 * @param array<string,array<string,int|string>> $schedules Array of cron schedules.
	 * @return array<string,array<string,int|string>> Modified array of cron schedules.
	 */
	public function cron_schedules( $schedules ) {
		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = [
				'interval' => 604800, // 7 days
				'display'  => __( 'Once Weekly', 'surerank' ),
			];
		}
		return $schedules;
	}

	/**
	 * Schedule email report based on settings.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	public function maybe_schedule_email_report() {
		$utils    = Utils::get_instance();
		$settings = $utils->get_settings();

		// If email reports are disabled, unschedule any existing cron.
		if ( empty( $settings['enabled'] ) ) {
			$this->unschedule_email_report();
			return;
		}

		// Validate recipient email.
		if ( empty( $settings['recipientEmail'] ) ) {
			return;
		}

		// Check if cron is available.
		if ( ! $this->is_cron_available() ) {
			return;
		}

		// Return if not connected to search console.
		if ( ! $this->is_gsc_ready() ) {
			if ( $this->has_scheduled() ) {
				$this->unschedule_email_report();
			}
			return;
		}

		// Schedule the cron if not already scheduled.
		$this->schedule_email_report( $settings['scheduledOn'] );
	}

	/**
	 * Schedule the email report cron job.
	 *
	 * @since 1.6.0
	 * @param string $day_of_week Day of the week to send the report.
	 * @return void
	 */
	private function schedule_email_report( $day_of_week ) {
		// Check if already scheduled.
		if ( $this->has_scheduled() ) {
			return;
		}

		// Get the next occurrence of the scheduled day.
		$next_run = $this->get_next_scheduled_time( $day_of_week );


		// Schedule new event using wp_schedule_event.
		wp_schedule_event( $next_run, 'weekly', self::EMAIL_REPORTS_CRON_EVENT );
	}

	/**
	 * Unschedule the email report cron job.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	private function unschedule_email_report() {
		$timestamp = wp_next_scheduled( self::EMAIL_REPORTS_CRON_EVENT );
		if ( $timestamp === false ) {
			return;
		}
		wp_unschedule_event( $timestamp, self::EMAIL_REPORTS_CRON_EVENT );
	}

	/**
	 * Get the next scheduled time for a given day of the week.
	 *
	 * @since 1.6.0
	 * @param string $day_of_week Day of the week (e.g., 'monday', 'sunday').
	 * @return int Timestamp of the next occurrence.
	 */
	private function get_next_scheduled_time( $day_of_week ) {
		// Get current time in UTC (WordPress cron uses UTC).
		$current_time = time();
		$target_day   = ucfirst( strtolower( $day_of_week ) );

		// Get current day name in UTC.
		$current_day  = gmdate( 'l', $current_time );
		$current_hour = (int) gmdate( 'H', $current_time );

		// If today is the target day and it's before 9 AM UTC, schedule for today at 9 AM.
		if ( $current_day === $target_day && $current_hour < 9 ) {
			$next_run = strtotime( gmdate( 'Y-m-d', $current_time ) . ' 09:00:00 UTC' );
		} else {
			// Otherwise, schedule for next occurrence of the target day at 9 AM UTC.
			$next_run = strtotime( "next {$target_day} 09:00:00 UTC" );
		}

		return $next_run !== false ? $next_run : time();
	}

	/**
	 * Check if WP-Cron is available.
	 *
	 * @since 1.6.0
	 * @return bool True if cron is available, false otherwise.
	 */
	private function is_cron_available() {
		// Check if DISABLE_WP_CRON is defined and true.
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			return false;
		}

		// Check if cron test has been performed.
		$cron_test = get_option( 'surerank_cron_test_ok' );
		if ( false !== $cron_test ) {
			return (bool) $cron_test;
		}

		return true; // Assume cron is available by default.
	}

	/**
	 * Fallback to send email via init action if cron is not available.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	public function maybe_send_email_fallback() {
		// Only run if cron is not available and if GSC is not ready.
		if ( $this->is_cron_available() || ! $this->is_gsc_ready() ) {
			return;
		}

		$utils    = Utils::get_instance();
		$settings = $utils->get_settings();

		// Check if email reports are enabled.
		if ( empty( $settings['enabled'] ) ) {
			return;
		}

		// Check if today is the scheduled day.
		$current_day = strtolower( gmdate( 'l' ) );
		if ( $current_day !== $settings['scheduledOn'] ) {
			return;
		}

		// Check if email was already sent today.
		$last_sent = get_option( 'surerank_email_report_last_sent' );
		$today     = gmdate( 'Y-m-d' );

		if ( $last_sent === $today ) {
			return;
		}

		// Send the email.
		$this->send_email_report();
	}

	/**
	 * Send the email report.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	public function send_email_report() {
		$utils    = Utils::get_instance();
		$settings = $utils->get_settings();

		// Validate settings.
		if ( empty( $settings['enabled'] ) || empty( $settings['recipientEmail'] ) ) {
			return;
		}

		// Check if Google Search Console is authenticated and site is selected.
		if ( ! $this->is_gsc_ready() ) {
			return;
		}

		// Send email to configured recipient.
		$sent = $this->send_email_to( $settings['recipientEmail'] );

		if ( $sent ) {
			update_option( 'surerank_email_report_last_sent', gmdate( 'Y-m-d' ), false );
		}
	}

	/**
	 * Send email report to a specific recipient.
	 *
	 * @since 1.6.0
	 * @param string $recipient_email The recipient email address.
	 * @return bool True if email was sent successfully, false otherwise.
	 */
	public function send_email_to( $recipient_email ) {
		// Validate email.
		if ( empty( $recipient_email ) || ! is_email( $recipient_email ) ) {
			return false;
		}

		$subject = $this->get_email_subject();
		$message = $this->get_email_template();
		$headers = $this->get_email_headers();

		// Send the email.
        // @phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail
		return wp_mail( $recipient_email, $subject, $message, $headers );
	}

	/**
	 * Get email subject.
	 *
	 * @since 1.6.0
	 * @return string Email subject.
	 */
	public function get_email_subject() {
		$end_date   = gmdate( 'M j, Y', strtotime( '-2 days' ) );
		$start_date = gmdate( 'M j, Y', strtotime( '-9 days' ) );

		/* translators: 1: Start date, 2: End date */
		return sprintf( __( 'SEO Summary of last week - %1$s to %2$s', 'surerank' ), $start_date, $end_date );
	}

	/**
	 * Get email headers.
	 *
	 * @since 1.6.0
	 * @return array<string> Email headers.
	 */
	public function get_email_headers() {
		return [
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
		];
	}

	/**
	 * Get email template with inline styles.
	 *
	 * @since 1.6.0
	 * @return string HTML email template.
	 */
	public function get_email_template() {
		$site_url         = GSC_Auth::get_instance()->get_credentials( 'site_url' );
		$admin_url        = admin_url( 'admin.php?page=surerank#/search-console' );
		$default_logo_url = 'https://surerank.com/wp-content/uploads/2025/11/surerank-logo.png';
		$end_date         = gmdate( 'jS F Y', strtotime( '-2 days' ) );
		$start_date       = gmdate( 'jS F Y', strtotime( '-9 days' ) );
		$date_range       = $start_date . ' to ' . $end_date;
		$gsc_data         = $this->get_gsc_data();

		$logo_url    = apply_filters( 'surerank_email_reports_brand_logo', $default_logo_url );
		$logo_url    = $this->validate_logo_url( $logo_url, $default_logo_url );
		$brand_color = apply_filters( 'surerank_email_reports_brand_color', '#4330D2' );
		ob_start();
		?>
		<!DOCTYPE html>
		<html lang="en">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<title><?php echo esc_html( $this->get_email_subject() ); ?></title>
			<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
			<style type="text/css">
				@media screen and (max-width: 840px) {
					.metric-cell-left, 
					.metric-cell-right, 
					.metric-cell-center {
						display: block;
						width: 100% !important;
						padding: 0 !important;
						margin-bottom: 1rem;
					}
				}
			</style>
		</head>
		<body style="font-family: 'Figtree', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; margin: 0; padding: 24px; background-color: #FFFFFF;">
			<table role="presentation" style="width: 100%; max-width: 700px; margin: 0 auto; border-collapse: collapse; background-color: #FFFFFF; padding: 24px;">
				<tr>
					<td>
						<?php echo $this->get_email_header( $logo_url, $site_url, $date_range, $brand_color ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo $this->get_email_body( $gsc_data, $admin_url, $brand_color ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo $this->get_email_promotion(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo $this->get_email_footer( $logo_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</td>
				</tr>
			</table>
		</body>
		</html>
		<?php
		$content = ob_get_clean();
		return $content === false ? '' : $content;
	}

	/**
	 * Get email header section.
	 *
	 * @since 1.6.0
	 * @param string $logo_url Logo URL.
	 * @param string $site_url Site URL.
	 * @param string $date_range Date range text.
	 * @param string $brand_color Brand color hex code.
	 * @return string Header HTML.
	 */
	private function get_email_header( $logo_url, $site_url, $date_range, $brand_color ) {
		// Convert brand color to lighter background (add opacity/lightness).

		$header_bg = apply_filters( 'surerank_email_reports_lighten_color', '#EEF2FF', $brand_color, 90 );

		// Get contrasting text color for the site URL against the header background.
		$site_url_color = apply_filters( 'surerank_email_reports_site_url_color', $brand_color, $header_bg );

		// Get logo style (allows Pro to customize based on custom vs default logo).
		// Default to original fixed dimensions for SureRank logo.
		$default_logo_style = 'display: block; width: 139px; height: 22px; margin: 0 auto 16px auto;';
		$logo_style         = apply_filters( 'surerank_email_reports_header_logo_style', $default_logo_style, $logo_url );

		$site_name = get_bloginfo( 'name' );
		/* translators: %s: Site name */
		$logo_alt = sprintf( __( '%s Logo', 'surerank' ), $site_name );
		ob_start();
		?>
		<table role="presentation" style="width: 100%; max-width: 660px; margin: 0 auto; border-collapse: collapse; background-color: <?php echo esc_attr( $header_bg ); ?>; border-radius: 8px; padding: 20px 24px;">
			<tr>
				<td style="text-align: center; background: <?php echo esc_attr( $header_bg ); ?>; padding: 20px 24px;">
					<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $logo_alt ); ?>" style="<?php echo esc_attr( $logo_style ); ?>" width="139" height="22" />
					<h1 style="margin: 0 0 0 0; font-size: 24px; font-weight: 600; line-height: 1.33; letter-spacing: -0.006em; color: #0A0A0A;"><?php esc_html_e( 'SEO Weekly Summary of Your Website', 'surerank' ); ?></h1>
					<p class="site-url" style="margin: 12px 0 12px 0; font-size: 14px; line-height: 1.43; color: <?php echo esc_attr( $site_url_color ); ?>; font-weight: 400; text-decoration: none;"><?php echo esc_url( $site_url ); ?></p>
					<p style="margin: 0; font-size: 14px; line-height: 1.43; color: #4B5563; font-weight: 500;"><?php echo esc_html( $date_range ); ?></p>
				</td>
			</tr>
		</table>
		<?php
		$content = ob_get_clean();
		return $content === false ? '' : $content;
	}

	/**
	 * Get email body section.
	 *
	 * @since 1.6.0
	 * @param array<string, mixed> $gsc_data GSC performance data.
	 * @param string               $admin_url Admin URL.
	 * @param string               $brand_color Brand color hex code.
	 * @return string Body HTML.
	 */
	private function get_email_body( $gsc_data, $admin_url, $brand_color ) {
		ob_start();
		?>
		<table role="presentation" style="width: 100%; max-width: 660px; margin: 32px auto 0; border-collapse: collapse;">
			<tr>
				<td>
					<div style="margin-bottom: 20px;">
						<h2 style="margin: 0; font-size: 16px; font-weight: 600; line-height: 1.5; color: #111827;"><?php esc_html_e( 'Your Website Performance (Last 7 Days)', 'surerank' ); ?></h2>
					</div>
					<?php echo $this->get_metrics_section( $gsc_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo $this->get_top_pages_section( $gsc_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo $this->get_cta_section( $admin_url, $brand_color ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</td>
			</tr>
		</table>
		<?php
		$content = ob_get_clean();
		return $content === false ? '' : $content;
	}

	/**
	 * Get metrics section.
	 *
	 * @since 1.6.0
	 * @param array<string, mixed> $gsc_data GSC performance data.
	 * @return string|string Metrics HTML.
	 */
	private function get_metrics_section( $gsc_data ) {
		ob_start();
		?>
		<table role="presentation" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
			<tr>
				<?php echo $this->get_metric_card( __( 'Site Clicks', 'surerank' ), $gsc_data['clicks'], 'left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo $this->get_metric_card( __( 'Total Impressions', 'surerank' ), $gsc_data['impressions'], 'center' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo $this->get_metric_card( __( 'Average Position', 'surerank' ), $gsc_data['avg_position'], 'right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</tr>
		</table>
		<?php
		$content = ob_get_clean();
		return $content === false ? '' : $content;
	}

	/**
	 * Get individual metric card.
	 *
	 * @since 1.6.0
	 * @param string               $label Metric label.
	 * @param array<string, mixed> $data Metric data.
	 * @param string               $position Card position (left, center, right).
	 * @return string Metric card HTML.
	 */
	private function get_metric_card( $label, $data, $position = 'left' ) {
		$padding_style     = '';
		$metric_cell_class = 'metric-cell-' . $position;
		if ( 'left' === $position ) {
			$padding_style = 'width: 33.33%; padding-right: 10px; vertical-align: top;';
		} elseif ( 'center' === $position ) {
			$padding_style = 'width: 33.33%; padding: 1px 5px; vertical-align: top;';
		} else {
			$padding_style = 'width: 33.33%; padding-left: 10px; vertical-align: top;';
		}
		$value      = Utils::format_number( $data['current'] );
		$percentage = $data['percentage'] ?? 0;

		ob_start();
		?>
		<td class="<?php echo esc_attr( $metric_cell_class ); ?>" style="<?php echo esc_attr( $padding_style ); ?>">
			<div style="padding: 16px; background: #F9FAFB; border-bottom: 2px solid #0000000D; border-radius: 6px;">
				<p style="padding: 4px; margin: 0 0 16px 0; font-size: 16px; font-weight: 500; line-height: 1.5; color: #111827;"><?php echo esc_html( $label ); ?></p>
				<div style="display: flex; align-items: center; justify-content: space-between; padding: 4px;">
					<h3 style="display: inline-block; margin: 0; font-size: 24px; font-weight: 600; line-height: 1.33; letter-spacing: -0.006em; color: #111827;"><?php echo esc_html( $value ); ?></h3>
					<?php if ( $percentage != 0 ) : ?>
						<?php
						$change_color = $percentage > 0 ? '#16A34A' : '#DC2626';
						$arrow        = $percentage > 0 ? '↑' : '↓';
						?>
						<div style="display: inline-flex; align-items: center; margin-top: 4px; margin-left: auto;">
							<span style="font-size: 14px; font-weight: 500; line-height: 1.43; color: <?php echo esc_attr( $change_color ); ?>;"><?php echo esc_html( $arrow ) . ' ' . esc_html( (string) abs( $percentage ) ); ?>%</span>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</td>
		<?php
		$content = ob_get_clean();
		return $content === false ? '' : $content;
	}

	/**
	 * Get top pages section.
	 *
	 * @since 1.6.0
	 * @param array<string, mixed> $gsc_data GSC performance data.
	 * @return string Top pages HTML.
	 */
	private function get_top_pages_section( $gsc_data ) {
		if ( empty( $gsc_data['top_pages'] ) ) {
			return '';
		}

		$total_pages = count( $gsc_data['top_pages'] );

		ob_start();
		?>
		<div style="border-radius: 8px; padding: 12px; margin-bottom: 20px;">
			<h3 style="margin: 0 0 12px 0; font-size: 16px; font-weight: 600; line-height: 1.5; color: #111827;"><?php esc_html_e( 'Top Ranked Posts', 'surerank' ); ?></h3>
			<table role="presentation" style="width: 100%; border-collapse: separate; border-spacing: 0; background-color: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; overflow: hidden;">
				<thead>
					<tr style="background-color: #F9FAFB;">
						<th style="padding: 8px 12px; text-align: left; font-size: 14px; font-weight: 500; line-height: 1.43; color: #111827; border-bottom: 1px solid #E5E7EB; border-top-left-radius: 8px;"><?php esc_html_e( 'Posts', 'surerank' ); ?></th>
						<th style="padding: 8px 12px; text-align: left; font-size: 14px; font-weight: 500; line-height: 1.43; color: #111827; border-bottom: 1px solid #E5E7EB;"><?php esc_html_e( 'Clicks', 'surerank' ); ?></th>
						<th style="padding: 8px 12px; text-align: left; font-size: 14px; font-weight: 500; line-height: 1.43; color: #111827; border-bottom: 1px solid #E5E7EB;"><?php esc_html_e( 'Avg. Position', 'surerank' ); ?></th>
						<th style="padding: 8px 12px; text-align: left; font-size: 14px; font-weight: 500; line-height: 1.43; color: #111827; border-bottom: 1px solid #E5E7EB; border-top-right-radius: 8px;"><?php esc_html_e( 'Impressions', 'surerank' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $gsc_data['top_pages'] as $index => $page ) : ?>
						<?php
						$page_url     = $page['url'] ?? '';
						$page_title   = $this->get_page_title_from_url( $page_url );
						$border_style = $index < $total_pages - 1 ? 'border-bottom: 1px solid #E5E7EB;' : '';
						?>
						<tr>
							<td style="padding: 12px; font-size: 14px; font-weight: 500; line-height: 1.43; color: #4B5563; <?php echo esc_attr( $border_style ); ?>"><?php echo esc_html( $page_title ); ?></td>
							<td style="padding: 12px; font-size: 12px; font-weight: 500; line-height: 1.33; color: #4B5563; <?php echo esc_attr( $border_style ); ?>"><?php echo esc_html( Utils::format_number( $page['clicks'] ?? 0 ) ); ?></td>
							<td style="padding: 12px; font-size: 12px; font-weight: 500; line-height: 1.33; color: #4B5563; <?php echo esc_attr( $border_style ); ?>"><?php echo esc_html( (string) round( $page['position'] ?? 0, 1 ) ); ?></td>
							<td style="padding: 12px; font-size: 12px; font-weight: 500; line-height: 1.33; color: #4B5563; <?php echo esc_attr( $border_style ); ?>"><?php echo esc_html( Utils::format_number( $page['impressions'] ?? 0 ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		$content = ob_get_clean();
		return $content === false ? '' : $content;
	}

	/**
	 * Get CTA section.
	 *
	 * @since 1.6.0
	 * @param string $admin_url Admin URL.
	 * @param string $brand_color Brand color hex code.
	 * @return string CTA HTML.
	 */
	private function get_cta_section( $admin_url, $brand_color ) {

		$text_color = apply_filters( 'surerank_email_reports_contrasting_text_color', '#FFFFFF', $brand_color );

		$border_color = ( strtoupper( $brand_color ) === '#FFFFFF' ) ? '#000000' : $brand_color;

		ob_start();
		?>
		<div style="text-align: center;">
			<a href="<?php echo esc_url( $admin_url ); ?>" style="display: inline-block; padding: 10px 16px; background-color: <?php echo esc_attr( $brand_color ); ?>; color: <?php echo esc_attr( $text_color ); ?>; border: 1px solid <?php echo esc_attr( $border_color ); ?>; border-radius: 6px; font-size: 14px; font-weight: 600; line-height: 1.43; text-decoration: none;"><?php esc_html_e( 'View Full Analysis', 'surerank' ); ?></a>
		</div>
		<?php
		$content = ob_get_clean();
		return $content === false ? '' : $content;
	}

	/**
	 * All product promotions.
	 * 
	 * @since 1.6.0
	 * @return array<string, array<string, string>> Product promotions.
	 */
	private function get_product_promotions() {
		return [
			'suredash' => [
				'title'         => __( 'Want to see SEO + traffic + sales in one dashboard?', 'surerank' ),
				'text'          => __( 'See the data that matters, all in one clean, customizable dashboard. Track your site\'s traffic, sales, and form activity without the clutter.', 'surerank' ),
				'link_url'      => 'https://suredash.com',
				'link_text'     => __( 'Explore', 'surerank' ) . ' SureDash →',
				'logo_url'      => 'https://suremails.com/wp-content/uploads/2025/10/suredash.png',
				'product_title' => 'SureDash',

			],
		];
	}

	/**
	 * Get email promotion section.
	 *
	 * @since 1.6.0
	 *
	 * @return string Promotion HTML.
	 */
	private function get_email_promotion() {
		// Don't show promotions if Pro is active - user has already paid.
		$nudges_utils = \SureRank\Inc\Modules\Nudges\Utils::get_instance();
		if ( $nudges_utils->is_pro_active() ) {
			return '';
		}

		$all_promotions = $this->get_product_promotions();
		if ( empty( $all_promotions ) ) {
			return '';
		}
		$product       = $all_promotions['suredash'];
		$title         = $product['title'];
		$text          = $product['text'];
		$link_url      = $product['link_url'];
		$link_text     = $product['link_text'];
		$logo_url      = $product['logo_url'];
		$product_title = $product['product_title'];

		ob_start();
		?>
		<div style="height: 1px; background-color: #E5E7EB; margin: 32px 0;"></div>
		<div style="background-color: #EEF2FF; padding: 20px 24px; text-align: left;">
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $product_title ); ?>" style="text-align: left;" width="20" height="20" />
			<h3 style="margin: 0 0 2px 0; font-size: 14px; font-weight: 600; line-height: 1.43; color: #141338;"><?php echo esc_html( $title ); ?></h3>
			<p style="margin: 0 0 4px 0; font-size: 12px; font-weight: 400; line-height: 1.33; color: #4F4E7C;"><?php echo esc_html( $text ); ?></p>
			<a href="<?php echo esc_url( $link_url . '?utm_medium=surerank-seo-weekly-report' ); ?>" style="display: inline-block; padding: 0; color: #4338CA; font-size: 12px; font-weight: 600; line-height: 1.33; text-decoration: none; border-radius: 4px;"><?php echo esc_html( $link_text ); ?></a>
		</div>
		<?php
		$content = ob_get_clean();
		return $content === false ? '' : $content;
	}

	/**
	 * Get email footer section.
	 *
	 * @since 1.6.0
	 * @param string $logo_url Logo URL.
	 * @return string Footer HTML.
	 */
	private function get_email_footer( $logo_url ) {
		// Get logo style (allows Pro to customize based on custom vs default logo).
		// Default to original fixed height for SureRank logo.
		$default_logo_style = 'display: inline-block; width: auto; height: 16px;';
		$logo_style         = apply_filters( 'surerank_email_reports_footer_logo_style', $default_logo_style, $logo_url );

		$site_name = get_bloginfo( 'name' );
		/* translators: %s: Site name */
		$logo_alt = sprintf( __( '%s Logo', 'surerank' ), $site_name );
		ob_start();
		?>
		<div style="height: 1px; width: 80%; background-color: #E5E7EB; margin: 32px auto;"></div>
		<div style="text-align: center;">
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $logo_alt ); ?>" style="<?php echo esc_attr( $logo_style ); ?>" width="auto" height="16" />
		</div>
		<?php
		$content = ob_get_clean();
		return $content === false ? '' : $content;
	}

	/**
	 * Handle settings update to reschedule cron.
	 *
	 * @since 1.6.0
	 * @param mixed $old_value Old settings value.
	 * @param mixed $new_value New settings value.
	 * @return void
	 */
	public function on_settings_updated( $old_value, $new_value ) {
		// Unschedule and reschedule based on new settings.
		$this->unschedule_email_report();
		$this->maybe_schedule_email_report();
	}

	/**
	 * Get GSC performance data for email report.
	 *
	 * @since 1.6.0
	 * @return array<string, mixed> GSC performance data.
	 */
	private function get_gsc_data() {
		try {
			$gsc_controller = GSC_Controller::get_instance();

			$start_date = gmdate( 'Y-m-d', strtotime( '-9 days' ) );
			$end_date   = gmdate( 'Y-m-d', strtotime( '-2 days' ) );

			// Get clicks and impressions data using the new method.
			$clicks_impressions_data = $gsc_controller->get_clicks_and_impressions_data( $start_date, $end_date );
			$clicks_data             = $clicks_impressions_data['data']['clicks'] ?? [
				'current'    => 0,
				'percentage' => 0,
			];
			$impressions_data        = $clicks_impressions_data['data']['impressions'] ?? [
				'current'    => 0,
				'percentage' => 0,
			];

			// Get content performance (top pages) - this includes position data for current and previous periods.
			$content_performance_response = $gsc_controller->get_content_performance_data( $start_date, $end_date );
			$content_performance_data     = $content_performance_response['data'] ?? [];

			// Calculate current and previous average positions from content performance data.
			$current_avg_position  = 0;
			$previous_avg_position = 0;
			$position_count        = 0;

			foreach ( $content_performance_data as $page ) {
				if ( isset( $page['current']['position'] ) && $page['current']['position'] > 0 ) {
					$current_avg_position += $page['current']['position'];
					++$position_count;
				}
			}

			// Calculate previous period average position.
			// Note: We need to calculate previous position from the changes data.
			$previous_position_count = 0;
			foreach ( $content_performance_data as $page ) {
				if ( isset( $page['current']['position'] ) && $page['current']['position'] > 0 && isset( $page['changes']['position'] ) ) {
					// Calculate previous position from current and percentage change.
					$current_pos            = $page['current']['position'];
					$change_pct             = $page['changes']['position'];
					$previous_pos           = $change_pct != 0 ? $current_pos / ( 1 + ( $change_pct / 100 ) ) : $current_pos;
					$previous_avg_position += $previous_pos;
					++$previous_position_count;
				}
			}

			$current_avg_position  = $position_count > 0 ? round( $current_avg_position / $position_count, 1 ) : 0;
			$previous_avg_position = $previous_position_count > 0 ? round( $previous_avg_position / $previous_position_count, 1 ) : 0;

			// Calculate percentage change for average position.
			// Note: For position, lower is better, so we invert the calculation.
			$position_percentage = 0;
			if ( $previous_avg_position > 0 ) {
				$position_percentage = round( ( ( $previous_avg_position - $current_avg_position ) / $previous_avg_position ) * 100, 2 );
			} elseif ( $current_avg_position > 0 ) {
				$position_percentage = -100;
			}

			// Get top 3 pages and format them for the email.
			$top_pages = array_slice( $content_performance_data, 0, 3 );
			$top_pages = array_map(
				function( $page ) {
					return [
						'url'         => $page['url'] ?? '',
						'clicks'      => $page['current']['clicks'] ?? 0,
						'impressions' => $page['current']['impressions'] ?? 0,
						'position'    => $page['current']['position'] ?? 0,
					];
				},
				$top_pages
			);

			return [
				'clicks'       => $clicks_data,
				'impressions'  => $impressions_data,
				'avg_position' => [
					'current'    => $current_avg_position,
					'percentage' => $position_percentage,
				],
				'top_pages'    => $top_pages,
			];
		} catch ( \Exception $e ) {
			// Return default data if GSC fails.
			return $this->get_default_gsc_data();
		}
	}

	/**
	 * Get default GSC data structure.
	 *
	 * @since 1.6.0
	 * @return array<string, mixed> Default GSC data.
	 */
	private function get_default_gsc_data() {
		return [
			'clicks'       => [
				'current'    => 0,
				'percentage' => 0,
			],
			'impressions'  => [
				'current'    => 0,
				'percentage' => 0,
			],
			'avg_position' => [
				'current'    => 0,
				'percentage' => 0,
			],
			'top_pages'    => [],
		];
	}

	/**
	 * Check if Google Search Console is ready (authenticated and site selected).
	 *
	 * @since 1.6.0
	 * @return bool True if GSC is authenticated and site is selected, false otherwise.
	 */
	private function is_gsc_ready() {
		$auth = GSC_Auth::get_instance();

		// Check if user is authenticated.
		if ( ! $auth->auth_check() ) {
			return false;
		}

		// Check if site is selected.
		$site_url = $auth->get_credentials( 'site_url' );
		if ( empty( $site_url ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get page title from URL.
	 *
	 * Tries to get the post title if available, otherwise returns formatted URL.
	 *
	 * @since 1.6.0
	 * @param string $url Page URL.
	 * @return string Page title or formatted URL.
	 */
	private function get_page_title_from_url( $url ) {
		// Try to get post ID from URL.
		$post_id = url_to_postid( $url ); // @phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.url_to_postid_url_to_postid

		// If we found a post, get its title.
		if ( $post_id > 0 ) {
			$title = get_the_title( $post_id );
			if ( ! empty( $title ) ) {
				return $title;
			}
		}

		// Fallback: Format the URL path as a readable title.
		$parsed_path = wp_parse_url( $url, PHP_URL_PATH );
		$page_title  = basename( is_string( $parsed_path ) ? $parsed_path : '' );

		// Clean up the slug to make it readable.
		$page_title = str_replace( [ '-', '_' ], ' ', $page_title );
		$page_title = ucwords( $page_title );

		if ( empty( $page_title ) || $page_title === '/' ) {
			return __( 'Home', 'surerank' );
		}

		return $page_title;
	}

	/**
	 * Validate logo URL and fallback to default if invalid.
	 *
	 * @since 1.6.0
	 * @param string $logo_url Logo URL to validate.
	 * @param string $default_logo_url Default logo URL to use as fallback.
	 * @return string Valid logo URL or default logo URL.
	 */
	public function validate_logo_url( $logo_url, $default_logo_url ) {

		if ( empty( $logo_url ) ) {
			return esc_url( $default_logo_url );
		}

		if ( ! filter_var( $logo_url, FILTER_VALIDATE_URL ) ) {
			return esc_url( $default_logo_url );
		}

		// Get URL path and validate it's a string.
		$url_path = wp_parse_url( $logo_url, PHP_URL_PATH );
		if ( ! is_string( $url_path ) || empty( $url_path ) ) {
			return esc_url( $default_logo_url );
		}

		// Use the existing image extension validator.
		$image_instance = Image::get_instance();
		if ( ! $image_instance->is_valid_image_extension( $logo_url ) ) {
			return esc_url( $default_logo_url );
		}

		return esc_url( $logo_url );
	}

}
