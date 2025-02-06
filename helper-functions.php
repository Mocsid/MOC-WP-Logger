<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists('moc_log') ) {
    /**
     * Helper function for logging with MOC_WP_Logger.
     *
     * @param mixed  $message  The message/data to be logged (string, array, object, etc.).
     * @param string $level    The log level (INFO, ERROR, DEBUG, etc.).
     */
    function moc_log( $message = '', $level = 'INFO' ) {
        if ( class_exists( 'MOC_WP_Logger' ) && method_exists( 'MOC_WP_Logger', 'log_message' ) ) {
            // Match the parameter order in log_message($data, $level).
            MOC_WP_Logger::log_message( $message, $level );
        }
    }
}
