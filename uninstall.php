<?php
// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$log_dir = WP_CONTENT_DIR . '/moc-logs/';
$log_file = $log_dir . 'wp-logger.log';

// Delete log file
if ( file_exists( $log_file ) ) {
    unlink( $log_file );
}

// Remove log directory if empty
if ( is_dir( $log_dir ) && count( scandir( $log_dir ) ) == 2 ) {
    rmdir( $log_dir );
}
