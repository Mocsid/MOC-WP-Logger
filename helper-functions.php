<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists('moc_log') ) {

    function moc_log( $message, $level = 'INFO' ) {
        if ( class_exists( 'MOC_WP_Logger' ) && method_exists( 'MOC_WP_Logger', 'log_message' ) ) {
            MOC_WP_Logger::log_message( $message, $level );
        }
    }

}