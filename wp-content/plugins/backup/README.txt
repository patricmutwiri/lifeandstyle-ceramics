=== JetBackup - Backup, Restore & Migrate  ===
Plugin Name: JetBackup
Contributors: backupguard, elialum
Author: JetBackup
Donate link: https://www.jetbackup.com/jetbackup-for-wordpress
Tags: backup, restore, remote backup
Requires at least: 6.0
Tested up to: 6.8.1
Requires PHP: 7.4
Stable tag: 3.1.16.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Backup, restore, and migrate WordPress sites fast. Supports TAR, remote backups, multi schedules, and full multisite compatibility.

== Description ==
JetBackup is a powerful, easy-to-use WordPress backup and migration plugin. Create full or partial backups, restore in 1 click, and migrate across domains or hosts. Supports TAR format, cloud storage, and full automation.
Download **JetBackup premium versions** here: [https://www.jetbackup.com/jetbackup-for-wordpress](https://www.jetbackup.com/jetbackup-for-wordpress).
[See JetBackup in Action Here!](https://youtu.be/IEyxa2zFS9o)

### **Free Features**
- **Free Cloud Storage** - 2 GB of free off site [premium cloud storage](https://www.jetbackup.com/jetbackup-storage/)
- **Unlimited backup** - create as many backups as you want, there is no limit
- **Backup files, database or both** - you can backup your database or files, or both
- **Unlimited restore** - restore any backup file whenever needed
- **Download backup** - download your backup files for migration
- **Import backup** - upload your backup file to restore it right away
- **Backup cancellation** - cancel the backup process while it is not finished yet
- **Manage backups** - delete backups, view backup or restore log
- **Backup customization** - you choose which folders you want to backup
- **Live progress** - precise progress of the current backup and restore process
- **Export Backups** - Export backups to hosting control panels (supports cPanel & DirectAdmin)
- **MultiSite** - Multisite support with selective subsite restore.
- **Website migration** - migrate your website from one hosting/domain to another one
- **Scheduled backup** - set the frequency and time to perform automatic backup
- **Multiple scheduled profiles** - schedule your database and files to backup with different frequencies
- **Backup retention** - specify the number of backups you want to keep on your server

### **Premium Features**

- **Off Site Backups** - Support for offsite remote backup destinations: Amazon S3 (and compatible), FTP, SFTP, Google Drive, One Drive, DropBox, Box, pCloud
– **24/7 Premium Support** – Industry-leading support with a **15-minute SLA**.

### **Documentation**
The **documentation** can be found here: [https://www.jetbackup.com/jetbackup-for-wordpress](https://www.jetbackup.com/jetbackup-for-wordpress).

### **Why JetBackup?**
– **No extra libraries required**
– **Supports large websites**
– **Uses TAR archives for efficient storage**
– **Optimized for low-memory & shared hosting**
– **Seamless migration with serialized data refactoring**

### **Minimum Requirements**
– **WordPress**: 6.0 or higher
– **PHP**: 7.4 or higher (requires zlib & cURL)

### **Support**
[https://www.jetbackup.com/jetbackup-for-wordpress/support](https://www.jetbackup.com/jetbackup-for-wordpress/support)

== Installation ==

### **Installation Instructions**
- For **JetBackup Free**, follow [these steps](https://www.jetbackup.com/jetbackup-for-wordpress/doc/install-jetbackup-free).
- For **JetBackup Premium**, follow [these steps](https://www.jetbackup.com/jetbackup-for-wordpress/doc/install-jetbackup-pro).

== Frequently Asked Questions ==

= Why is it important to back up my website? =
Your website is at risk of data loss due to hacking, hardware failure, or accidental deletions. Regular backups ensure you can restore your website anytime.

= How often should I create backups? =
It depends on how frequently your website content changes. Daily backups are ideal for dynamic sites, while weekly backups may be sufficient for static websites.

= What is included in the free version? =
The free version allows unlimited backups and restores, migration capabilities, and local storage management.

= Why upgrade to JetBackup Premium? =
Premium users get cloud storage options, scheduled backups, priority support, and advanced backup management.

= What are the minimum requirements? =
JetBackup requires PHP 7.4+ and a Linux-based server for optimal performance. Windows is supported but may have limitations due to known PHP issues.

= Is JetBackup suitable for migration? =
Yes! JetBackup creates an exact snapshot of your website. When restored, it maintains the same state, making migrations effortless.

== Screenshots ==

1. Advanced Scheduling
2. Cloud Destinations
3. Customized Backups
4. Smart Migration
5. Feature Settings

== Changelog ==

= 3.1.16.1 =
* Fixed A regression in 3.1.15.4 where exclude paths were not applied correctly when using the default data directory location, causing backups to include extra files and grow in size.

= 3.1.15.4 =
* Improved detection of WordPress installation root path.
* Now shows a helpful message when critical PHP functions are missing or disabled.
* Corrected weekly scheduling logic to properly support Sunday as a valid starting day.
* Added verbosity for gzip operations to provide clearer information when an error occurs.
* Added safety check to detect mismatched installations and automatically reset the data folder when needed.

= 3.1.14.16 =
* Added support for environments using SQL mode `ONLY_FULL_GROUP_BY`.
* Added support for using an alternate `wp-config.php` path during restore.
* Fixed an issue where the restore log console could crash the browser when displaying large logs.
* Improved plugin activation checks to ensure required PHP extensions (like PDO) and minimum version requirements are met before activation.

= 3.1.13.2 =
* Added: POSIX availability safeguard to prevent fatal errors on systems without the POSIX extension (Needed for Socket API server level backups).
* Improved Socket API (server-level backups) with built-in safeguards
* Changed: Clarified JetBackup Storage usage description in the UI/help text for better understanding.

= 3.1.12.3 =
* Removed unnecessary front-end REST API entry point
* Fixed issue with migrating schedules from legacy version 2
* Ensured temp folder is cleaned after a failed or aborted queue task
* Disabled JetBackup server-level integration button when Socket API is unavailable to prevent confusion
* Added destination file browser
* Enhanced downloader class for improved performance and reliability
* Limited nonce cookies to the admin area only to improve privacy

= 3.1.11.1 =
* Added ability to translate JetBackup WordPress integrated menu items
* Ensured JetBackup SocketAPI integration is only available when supported by the server to prevent confusion
* Fixed a restore bug causing a fatal error when the `pdo_mysql` driver is not enabled; added a warning in the System Info page
* Fixed an issue causing "Memory limit exhausted" errors during downloads in some edge cases

= 3.1.10.7 =
* Added JetBackup Storage for remote backups - includes free storage tier and available to all users (free & premium)
* Fixed an issue with the front-end REST API scheduler
* Added a 'Backup Now' button in the backup listing page for improved usability
* Database table list is now alphabetically sorted for easier navigation
* Fixed a critical bug where excluding a database table could, in rare cases, lead to data loss
* Fixed an edge case with parsing MySQL login credentials during the restore process
* Added more plugins to the general tables/files default global excludes
* Fixed a bug where adding a license key from the CLI did not work
* Fixed some issues where downloading remote log files didn't work in some edge cases

= 3.1.9.2 =
* Improved the styling of the On/Off button for better visibility
* Fixed an issue when importing backups created on local Windows XAMPP servers
* Added an option to download logs from remote backups
* Added the ability to view logs directly (not just download them)
* Improved handling of language codes in the Moment.js library
* Added an onboarding video for new users

= 3.1.8.3 =
* Fixed a bug to ensure that removing a snapshot no longer leaves behind orphaned items
* Enhanced server-level cache handling for improved performance
* Improved WP-CLI commands and overall usability
* Backup log files are now sent compressed to the destination
* Added support for email alerts with configurable frequency options
* Added an option to define alternate MySQL port
* Added progress to tar extract
* Added an option to view log files instead of only downloading them

= 3.1.7.9 =
* Fully rewritten core, replacing the old BackupGuard plugin with code written by JetBackup team.
* Refreshed design with an improved user experience.
* Native support for TAR archives (replaces SGBP format).
* Integration with JetBackup at the server level—restore backups from your hosting provider.
* Smarter, more resilient system with resumable backup tasks.
* Multi-destination, multi-schedule, and multi-retention support.
* Intelligent scheduling: Prevents duplicate daily & weekly backups.
* Export backups to hosting control panels (supports cPanel & DirectAdmin).
* Full WP-CLI support for backup and restore commands.
* Enhanced GUI security with two-factor authentication (2FA).
* Daily WordPress core file integrity checks with admin notifications.
* Support for background server-level cron jobs for large tasks.
* Optimized for low-resource servers with minimal memory usage.
* Smart exclusions: Automatically excludes known temp files and database data.
* Option to clean up post revisions before each backup.
* Support for four update tiers: Alpha, Edge, Release Candidate, Stable.
* Multisite support with selective subsite restore.
* Advanced queue system manages all backup & restore tasks efficiently.

= 2.0.0 =
* Plugin rebranded as "JetBackup" (formerly BackupGuard).
* Introduced BackupGuard v2 core.

= 1.0 =
* Initial plugin release as "BackupGuard".

== Upgrade Notice ==

= 3.1.5 =
*Important:* This is a fully rewritten core, replacing the old BackupGuard plugin.

== Features ==
### **One-Click Backup**
Perform full or custom backups with a single click.

### **Reliable Restores**
Easily restore backups with a high success rate.

### **Download & Upload**
Download backups or import them via our wizard.

### **Cloud Storage (Pro)**
Automatically store backups on cloud platforms.

### **Automated Backups**
Schedule automatic backups to run at set intervals.

### **Custom Backup Settings**
Select specific files/folders for backup.

### **Server-Level Integration**
Restore backups created by your hosting provider via JetBackup.

### **Background Mode**
Run backups in low-priority mode for better performance.

### **Email Notifications**
Receive alerts on backup success or failure.

== Documentation ==
**Perform manual backup**
If you want to create a backup manually, follow these steps:

1. Go to "Backup jobs" page
2. Click "Run now" on the default backup job.

**Import backups from local computer**
If you have an exported backup file in your PC and you want to import it into your website, follow these steps:

1. Go to "Backups" page
2. Click on "Import & Restore" and select your file from your local computer
3. Go to the kitchen, prepare a cup of coffee and watch the screen :)

**Restore**
Restoring is as easy as backing up. Just follow these instructions:

1. Go to "Backups" page
2. Choose your backup and click on the "Restore" button.

[Full documentation](https://www.jetbackup.com/jetbackup-for-wordpress/doc)
