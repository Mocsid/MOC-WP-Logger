<?php
/**
 * Plugin Name: MOC WP Logger
 * Description: A more advanced plugin for logging raw data (strings, arrays, objects, etc.) in WordPress without sanitization.
 * Version: 1.2
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
        $log_dir = trailingslashit(WP_CONTENT_DIR) . 'uploads/moc-logs/';
        $this->log_file = $log_dir . 'wp-logger.log';

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'load_dependencies'), 1);

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_bar_menu', array($this, 'add_toolbar_item'), 100);
    }

    /**
     * Load helper files or additional dependencies if required.
     */
    public function load_dependencies() {
        // Load helper functions (if you have them).
        require_once plugin_dir_path(__FILE__) . 'helper-functions.php';
    }

    /**
     * Plugin activation routine.
     * Ensures the logging directory and file are created.
     */
    public function activate() {
        $log_dir = trailingslashit(WP_CONTENT_DIR) . 'uploads/moc-logs/';

        // Ensure the log directory exists
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir); // Create the directory if it doesn't exist
        }

        // Ensure the log file exists or is created
        if (!file_exists($this->log_file)) {
            file_put_contents($this->log_file, '');
        }
    }

    /**
     * Plugin deactivation routine.
     * Attempts to remove the log file upon deactivation.
     */
    public function deactivate() {
        if (file_exists($this->log_file)) {
            if (!unlink($this->log_file)) {
                error_log('Failed to delete log file: ' . $this->log_file);
            }
        }
    }

    /**
     * Logs various data types directly to the file (raw).
     *
     * @param mixed  $data  The data to be logged (string, array, object, JSON string, etc.).
     * @param string $level The log level (INFO, ERROR, etc.).
     */
    public static function log_message($data, $level = 'INFO') {
        $log_dir = trailingslashit(WP_CONTENT_DIR) . 'uploads/moc-logs/';
        $log_file = $log_dir . 'wp-logger.log';

        // Ensure the log directory exists
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        // Ensure the log file exists
        if (!file_exists($log_file)) {
            file_put_contents($log_file, '');
        }

        // Ensure the log file is writable
        if (!is_writable($log_file)) {
            error_log('Log file is not writable: ' . $log_file);
            return;
        }

        // Prepare the data string
        $formattedData = '';

        if (is_string($data)) {
            // Attempt to decode if it's JSON
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // Valid JSON string
                $formattedData = "JSON String => Decoded:\n" . print_r($decoded, true);
            } else {
                // Plain string
                $formattedData = "String:\n" . $data;
            }
        } elseif (is_array($data) || is_object($data)) {
            // Array or object -> use print_r for a deeper look
            $formattedData = "Array/Object:\n" . print_r($data, true);
        } else {
            // Numbers, booleans, etc.
            $formattedData = "Scalar (" . gettype($data) . "): " . var_export($data, true);
        }

        $timestamp = current_time('mysql');
        // We remove esc_html() so the raw data is not altered in the log file
        $log_entry = sprintf("[%s] %s: %s\n\n", $timestamp, $level, $formattedData);

        // Write to the log file
        error_log($log_entry, 3, $log_file);
    }

    /**
     * Adds an admin menu page to view and clear logs.
     */
    public function add_admin_menu() {
        add_menu_page(
            __('MOC WP Logger', 'moc-wp-logger'), // Page title
            __('MOC Logger', 'moc-wp-logger'),    // Menu title
            'manage_options',                     // Capability
            'moc-wp-logger',                      // Menu slug
            array($this, 'display_logs_page'),    // Callback
            'dashicons-admin-tools',              // Icon
            100
        );
    }

    /**
     * Render the logs page in the WordPress admin.
     * We do not re-escape logs to preserve exact output for debugging.
     */
    public function display_logs_page() {
        // Handle the clear logs action
        if (isset($_POST['clear_logs']) && check_admin_referer('clear_logs_action', 'clear_logs_nonce')) {
            $this->clear_logs();
            echo '<div class="updated"><p>' . __('Logs have been cleared.', 'moc-wp-logger') . '</p></div>';
        }

        // Display the logs
        echo '<div class="wrap">';
        echo '<h1>' . __('MOC WP Logger', 'moc-wp-logger') . '</h1>';

        // Add the clear logs button
        echo '<form method="post">';
        wp_nonce_field('clear_logs_action', 'clear_logs_nonce');
        echo '<input type="submit" name="clear_logs" class="button button-primary" value="' . esc_attr__('Clear Logs', 'moc-wp-logger') . '">';
        echo '</form>';

        // Display the log contents (raw). Use <pre> for formatting.
        if (file_exists($this->log_file)) {
            $logs = file_get_contents($this->log_file);
            echo '<pre style="background: #f3f3f3; border: 1px solid #ccc; padding: 1em;">';
            // Show raw logs without sanitizing
            echo $logs ? $logs : __('No logs found.', 'moc-wp-logger');
            echo '</pre>';
        } else {
            echo '<p>' . __('No logs found.', 'moc-wp-logger') . '</p>';
        }

        echo '</div>';
    }

    /**
     * Clears the content of the log file using the WordPress filesystem APIs.
     */
    public function clear_logs() {
        // Temporarily set FS_METHOD to 'direct' for this operation
        if (!defined('FS_METHOD')) {
            define('FS_METHOD', 'direct');
        }

        // Include the WP filesystem API
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

        // Ensure the log file exists before clearing
        if (!$wp_filesystem->exists($this->log_file)) {
            $wp_filesystem->put_contents($this->log_file, '');
        }

        $result = $wp_filesystem->put_contents($this->log_file, '');
        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . __('Log file cleared successfully.', 'moc-wp-logger') . '</p>';
                echo '</div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p>' . __('Failed to clear the log file.', 'moc-wp-logger') . '</p>';
                echo '</div>';
            });
        }
    }

    /**
     * Adds a shortcut in the admin toolbar to quickly view logs.
     */
    public function add_toolbar_item($wp_admin_bar) {
        $args = array(
            'id'    => 'moc_wp_logger',
            'title' => __('View Logs', 'moc-wp-logger'),
            'href'  => admin_url('admin.php?page=moc-wp-logger'),
            'meta'  => array(
                'class' => 'moc-wp-logger-toolbar-item',
                'title' => __('View MOC WP Logger Logs', 'moc-wp-logger')
            )
        );
        $wp_admin_bar->add_node($args);
    }
}

// Instantiate
$moc_wp_logger = new MOC_WP_Logger();
