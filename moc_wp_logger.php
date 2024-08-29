<?php
/**
 * Plugin Name: MOC WP Logger
 * Description: A simple plugin for logging events in WordPress.
 * Version: 1.0
 * Author: Mocsid
 * Text Domain: moc-wp-logger
 */

if (!defined('ABSPATH')) {
    exit;
}

class MOC_WP_Logger {
    private $log_file;

    public function __construct() {
        // Set the path to the log file.
        $this->log_file = WP_CONTENT_DIR . '/uploads/moc-logs/wp-logger.log';

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('plugins_loaded', function() {
            require_once plugin_dir_path(__FILE__) . 'helper-functions.php';
        }, PHP_INT_MIN);

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_bar_menu', array($this, 'add_toolbar_item'), 100);
    }

    public function activate() {
        $log_dir = WP_CONTENT_DIR . '/uploads/moc-logs/';
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        if (!file_exists($this->log_file)) {
            file_put_contents($this->log_file, ''); // Create an empty file.
        }
    }

    public function deactivate() {
        if (file_exists($this->log_file)) {
            unlink($this->log_file);
        }
    }

    public static function log_message($level = 'INFO', $message) {
        $log_dir = WP_CONTENT_DIR . '/uploads/moc-logs/';
        $log_file = $log_dir . 'wp-logger.log';

        // Ensure the log directory exists
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        // Ensure the log file exists
        if (!file_exists($log_file)) {
            file_put_contents($log_file, ''); // Create an empty file.
        }

        // Convert the message to a JSON string
        if (!is_string($message)) {
            // If message is an object of stdClass, convert it to an array recursively
            $message = json_decode(json_encode($message), true);

            // Use JSON encoding for complex types; ensure it's a string for simple types
            $message = json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            // Check if json_encode failed and fallback to a simple type conversion or notice
            if (json_last_error() !== JSON_ERROR_NONE) {
                $message = 'Log message encoding error: ' . json_last_error_msg();
            }
        }

        $timestamp = current_time('mysql');
        $log_entry = sprintf("[%s] %s: %s\n", $timestamp, esc_html($level), esc_html($message));

        // Write to the log file
        error_log($log_entry, 3, $log_file);
    }

    public function add_admin_menu() {
        add_menu_page(
            esc_html__('MOC WP Logger', 'moc-wp-logger'), // Page title
            esc_html__('MOC Logger', 'moc-wp-logger'), // Menu title
            'manage_options', // Capability
            'moc-wp-logger', // Menu slug
            array($this, 'display_logs_page'), // Function to display the page
            'dashicons-admin-tools', // Icon
            100 // Position
        );
    }

    public function display_logs_page() {
        // Handle the clear logs action
        if (isset($_POST['clear_logs']) && check_admin_referer('clear_logs_action', 'clear_logs_nonce')) {
            $this->clear_logs();
            echo '<div class="updated"><p>' . esc_html__('Logs have been cleared.', 'moc-wp-logger') . '</p></div>';
        }

        // Display the logs
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('MOC WP Logger', 'moc-wp-logger') . '</h1>';

        // Add the clear logs button
        echo '<form method="post">';
        wp_nonce_field('clear_logs_action', 'clear_logs_nonce');
        echo '<input type="submit" name="clear_logs" class="button button-primary" value="' . esc_attr__('Clear Logs', 'moc-wp-logger') . '">';
        echo '</form>';

        // Display the log contents
        if (file_exists($this->log_file)) {
            $logs = file_get_contents($this->log_file);
            echo '<pre>' . esc_html($logs) . '</pre>';
        } else {
            echo '<p>' . esc_html__('No logs found.', 'moc-wp-logger') . '</p>';
        }

        echo '</div>';
    }

    public function clear_logs() {

        // Temporarily set FS_METHOD to 'direct' for this operation
        if (!defined('FS_METHOD')) {
            define('FS_METHOD', 'direct');
        }

        // Include the WordPress filesystem API
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $creds = request_filesystem_credentials(admin_url('admin.php?page=moc-wp-logger'), '', false, false, array());

        if (!WP_Filesystem($creds)) {
            error_log('Failed to initialize WP_Filesystem.');
            return;
        }

        global $wp_filesystem;

        if (!$wp_filesystem || !is_object($wp_filesystem)) {
            error_log('Filesystem not initialized correctly.');
            return;
        }

        // Ensure the log file exists before attempting to clear it
        if (!$wp_filesystem->exists($this->log_file)) {
            $wp_filesystem->put_contents($this->log_file, ''); // Create an empty log file
        }

        $result = $wp_filesystem->put_contents($this->log_file, ''); // Clear the log file
        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>Log file cleared successfully.</p>';
                echo '</div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p>Failed to clear the log file.</p>';
                echo '</div>';
            });
        }
    }

    public function add_toolbar_item($wp_admin_bar) {
        $args = array(
            'id'    => 'moc_wp_logger',
            'title' => esc_html__('View Logs', 'moc-wp-logger'),
            'href'  => admin_url('admin.php?page=moc-wp-logger'),
            'meta'  => array(
                'class' => 'moc-wp-logger-toolbar-item',
                'title' => esc_html__('View MOC WP Logger Logs', 'moc-wp-logger')
            )
        );
        $wp_admin_bar->add_node($args);
    }
}

$moc_wp_logger = new MOC_WP_Logger();
