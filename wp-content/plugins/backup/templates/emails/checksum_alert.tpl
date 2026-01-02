<h1>Checksum verification notice</h1>
<p>Hello,</p>
<p>This is your JetBackup agent!</p>
<p>This is an automated notification to inform you that a checksum verification has failed for your WordPress installation. This could indicate that a core file has been modified, corrupted, or compromised.</p>
<p>Details:</p>
<p><strong>Domain:</strong> {$backup_domain}</p>
<ul>
    {foreach $checksums as $checksum}
        <li>
            <strong>File:</strong> {$checksum.file}<br>
            <strong>Expected Checksum:</strong> {$checksum.api_checksum}<br>
            <strong>Actual Checksum:</strong> {$checksum.local_checksum}
        </li>
    {/foreach}
</ul>

<p>We recommend reviewing this file immediately to ensure the integrity of your WordPress site. If you did not make this change intentionally, it could be a sign of a security issue.</p>
<p>For detailed steps on how to handle this, please refer to the WordPress Codex or consider contacting a security professional.</p>

<p>Best regards,</p>
<p>JetBackup For WordPress</p>
