<?php
/**
 * Plugin Name: MOC WP Logger
 * Description: A simple plugin for logging events in WordPress.
 * Version: 1.0
 * Author: Mocsid
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MOC_WP_Logger {
    private $log_file;

    public function __construct() {
        // Set the path to the log file.
        $this->log_file = WP_CONTENT_DIR . '/moc-logs/wp-logger.log';

        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        add_action('plugins_loaded', function() {
            require_once plugin_dir_path( __FILE__ ) . 'helper-functions.php';
        }, PHP_INT_MIN);
    }

    public function activate() {
        $log_dir = WP_CONTENT_DIR . '/moc-logs/';
        if ( ! file_exists( $log_dir ) ) {
            wp_mkdir_p( $log_dir );
        }

        if ( ! file_exists( $this->log_file ) ) {
            file_put_contents( $this->log_file, '' ); // Create an empty file.
        }
    }

    public function deactivate() {
        if ( file_exists( $this->log_file ) ) {
            unlink( $this->log_file );
        }
    }

    public static function log_message( $level = 'INFO', $message ) {
        $log_dir = WP_CONTENT_DIR . '/moc-logs/';
        $log_file = $log_dir . 'wp-logger.log';

        // Ensure the log directory exists
        if ( ! file_exists( $log_dir ) ) {
            wp_mkdir_p( $log_dir );
        }

        // Convert the message to a JSON string
        if ( ! is_string( $message ) ) {
            // Use JSON encoding for complex types; ensure it's a string for simple types
            $message = json_encode( $message, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
            
            // Check if json_encode failed and fallback to a simple type conversion or notice
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                $message = 'Log message encoding error: ' . json_last_error_msg();
            }
        }

        $timestamp = current_time( 'mysql' );
        $log_entry = sprintf( "[%s] %s: %s\n", $timestamp, strtoupper( $level ), $message );

        // Write to the log file
        error_log( $log_entry, 3, $log_file );
    }
}

$moc_wp_logger = new MOC_WP_Logger();
