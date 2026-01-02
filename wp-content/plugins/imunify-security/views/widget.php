<?php
/**
 * Widget view.
 *
 * @var array $data Template data.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! is_array( $data ) || ! array_key_exists( 'scanData', $data ) || ! array_key_exists( 'pluginUrl', $data ) || ! array_key_exists( 'features', $data ) ) {
	return;
}

/**
 * Template data.
 *
 * @var \CloudLinux\Imunify\App\Model\ScanData $scanData
 * @var \CloudLinux\Imunify\App\Model\Feature[] $features
 * @var \CloudLinux\Imunify\App\Model\MalwareItem[] $malwareItems
 * @var int $totalItemsCount
 * @var bool $showMoreButton
 * @var string $showMoreUrl
 * @var bool $showUpgradeButton
 * @var string $upgradeUrl
 * @var string $statusTitle
 * @var string $statusIcon
 */
$scanData          = $data['scanData'];
$pluginUrl         = $data['pluginUrl'];
$features          = $data['features'];
$malwareItems      = $data['malwareItems'];
$totalItemsCount   = $data['totalItemsCount'];
$showMoreButton    = $data['showMoreButton'];
$showMoreUrl       = $data['showMoreUrl'];
$showUpgradeButton = isset( $data['showUpgradeButton'] ) && $data['showUpgradeButton'];
$upgradeUrl        = $data['upgradeUrl'];
$statusTitle       = $data['statusTitle'];
$statusIcon        = $data['statusIcon'];

$lastScanTime = $scanData->getLastScanTimestamp();
$nextScanTime = $scanData->getNextScanTimestamp();

use CloudLinux\Imunify\App\Helpers\DateTimeFormatter;
use CloudLinux\Imunify\App\Helpers\PathFormatter;
?>
<div class="imunify-security__widget">
	<div class="imunify-security__overview">
		<div class="imunify-security__overview-main">
			<div class="imunify-security__status">
				<div class="imunify-security__status-icon">
					<img src="<?php echo esc_url( $pluginUrl . 'assets/images/' . $statusIcon ); ?>" alt="Protected status" width="80" height="80">
				</div>
				<div class="imunify-security__status-title"><?php echo esc_html( $statusTitle ); ?></div>
			</div>
			<?php if ( $showUpgradeButton ) : ?>
			<div class="imunify-security__get-protected">
				<a href="<?php echo esc_url( $upgradeUrl ); ?>" class="button button-primary">
					<?php esc_html_e( 'Get protected', 'imunify-security' ); ?>
				</a>
			</div>
			<?php endif; ?>
		</div>

		<div class="imunify-security__overview-details">
			<div class="imunify-security__overview-rows">
				<?php foreach ( $features as $feature ) : ?>
				<div class="imunify-security__overview-row imunify-security__overview-row--feature">
					<span class="imunify-security__overview-label">
						<a href="<?php echo esc_url( $feature->getUrl() ); ?>" target="_blank">
							<?php echo esc_html( $feature->getName() ); ?>
						</a>
					</span>
					<span class="imunify-security__overview-value <?php echo esc_attr( 'imunify-security__overview-value--' . strtolower( $feature->getStatus() ) ); ?>">
						<?php echo esc_html( $feature->getStatusLabel() ); ?>
					</span>
				</div>
				<?php endforeach; ?>
				<div class="imunify-security__overview-row imunify-security__overview-row--scan imunify-security__overview-row--separator">
					<span class="imunify-security__overview-label"><?php esc_html_e( 'Last scan:', 'imunify-security' ); ?></span>
					<span class="imunify-security__overview-value">
					<?php
					if ( $lastScanTime > 0 ) {
						echo esc_html( DateTimeFormatter::formatScanTime( $lastScanTime ) );
					} else {
						esc_html_e( 'never', 'imunify-security' );
					}
					?>
					</span>
				</div>
				<div class="imunify-security__overview-row imunify-security__overview-row--scan">
					<span class="imunify-security__overview-label"><?php esc_html_e( 'Next scan:', 'imunify-security' ); ?></span>
					<span class="imunify-security__overview-value">
					<?php
					if ( $nextScanTime > 0 ) {
						echo esc_html( DateTimeFormatter::formatScanTime( $nextScanTime ) );
					} else {
						esc_html_e( 'not scheduled', 'imunify-security' );
					}
					?>
					</span>
				</div>
			</div>
			<?php if ( empty( $malwareItems ) ) : ?>
			<div class="imunify-security__no-malware">
				<?php esc_html_e( 'No malware found', 'imunify-security' ); ?>
			</div>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( ! empty( $malwareItems ) ) : ?>
	<div class="imunify-security__malware">
		<div class="imunify-security__malware-list">
			<div class="imunify-security__malware-row imunify-security__malware-header">
				<strong>
					<?php
					/* translators: %d: number of malware items found */
					echo esc_html( sprintf( _n( '%d malware found', '%d malware found', $totalItemsCount, 'imunify-security' ), $totalItemsCount ) );
					?>
				</strong>
			</div>
			<?php foreach ( $malwareItems as $malware ) : ?>
			<div class="imunify-security__malware-row">
				<div class="imunify-security__malware-header">
					<div class="imunify-security__malware-path"><?php echo esc_html( PathFormatter::formatLongPath( $malware->getPath() ) ); ?></div>
					<span class="imunify-security__malware-status <?php echo esc_attr( $malware->getStatusExtraCssClass( 'imunify-security__malware-status' ) ); ?>"><?php echo esc_html( $malware->getStatusLabel() ); ?></span>
				</div>
				<div class="imunify-security__malware-details">
					<span class="imunify-security__malware-signature"><?php echo esc_html( $malware->getSignature() ); ?></span>
					<span class="imunify-security__malware-detected"><?php echo esc_html( DateTimeFormatter::formatDetectionDate( $malware->getLastActionDate() ) ); ?></span>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
		<div class="imunify-security__malware-actions">
			<?php if ( $showMoreButton ) : ?>
			<a href="<?php echo esc_url( $showMoreUrl ); ?>" class="imunify-security__action-link js-show-more"><?php esc_html_e( 'Show more results', 'imunify-security' ); ?></a>
			<span class="imunify-security__action-separator">|</span>
			<?php endif; ?>
			<a href="#" class="imunify-security__action-link js-hide-notifications"><?php esc_html_e( 'Hide notifications', 'imunify-security' ); ?></a>
		</div>
	</div>
	<?php endif; ?>
</div>
<div class="imunify-security__snooze-panel" style="display: none;">
	<form class="imunify-security__snooze-form">
		<label for="imunify-snooze-weeks"><?php esc_html_e( 'Snooze for:', 'imunify-security' ); ?></label>
		<select id="imunify-snooze-weeks" name="weeks">
			<?php for ( $i = 1; $i <= 4; $i++ ) : ?>
				<option value="<?php echo esc_attr( $i ); ?>">
					<?php
					/* translators: %d: number of weeks */
					echo esc_html( sprintf( _n( '%d week', '%d weeks', $i, 'imunify-security' ), $i ) );
					?>
				</option>
			<?php endfor; ?>
		</select>
		<button type="submit" class="button"><?php esc_html_e( 'Snooze', 'imunify-security' ); ?></button>
	</form>
</div>
