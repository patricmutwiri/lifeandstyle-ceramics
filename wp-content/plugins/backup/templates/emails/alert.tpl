<h1>JetBackup WordPress Alert</h1>
<p>Hello,</p>
<p>This is your JetBackup agent!</p>
<p>This is an automated notification to inform you that an alert has been triggered</p>
<p>Details:</p>
<p><strong>Domain:</strong> {$backup_domain}</p>

<ul>
    {foreach $alerts as $alert}
        <li>
            <strong>Title:</strong> {$alert.title}<br>
            <strong>Message:</strong> {$alert.message}<br>
            <strong>Level:</strong> {$alert.level}<br>
            <strong>Date:</strong> {$alert.date}
        </li>
    {/foreach}
</ul>

<p>Best regards,</p>
<p>JetBackup For WordPress</p>
<p>*Alert Frequency settings: {$notification_frequency}</p>

