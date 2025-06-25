<?php

namespace RelaisColisWoocommerce\WPFw\Utils;

use Psr\Log\LogLevel;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;

defined( 'ABSPATH' ) or exit;

/**
 * The logger class.
 * Class name is shortened in order to simplify uses of logger
 *
 * @since 1.0.0
 */
class WP_Log {

    use Singleton;

    const WP_SUKELLOS_FW_LOGGER_LEVEL_OPTION_PREFIX = 'wp_sukellos_fw_logger_level_';

    /**
     * @var array an <text_domain> => Loggers
     */
    public static $loggers = array();
    private static $default_text_domain = null;

    public static $error_level = 'error';

    /**
     * Default init method called when instance created
     * This method MUST be overridden in child
     *
     * @since 1.0.0
     * @access protected
     */
    public function init() {
    }

    /**
     * @return array the loggers: <text_domain> => Loggers
     */
    public function get_loggers() {
        return self::$loggers;
    }

    /**
     * Init logging for a text domain
     */
    public function register_text_domain( $text_domain, $default=false ) {
        // Setting default logger text domain... may simplify calls
        if ( $default ) {
            self::$default_text_domain = $text_domain;
        }

        // Get logging level
        $error_log_level = $this->get_error_log_level( $text_domain );
        self::$loggers[$text_domain] = $error_log_level;
    }

    /**
     * Get logging level
     * Must return one of levels defined in WordPress error_log
     * error - Runtime errors
     * warning - Exceptional occurrences that are not errors
     * notice - Uncommon events
     * info - Interesting events
     * debug - Detailed debug information
     */
    public function get_error_log_level( $text_domain ) {
        // Get logger level from options
        $logger_level = \get_option( self::WP_SUKELLOS_FW_LOGGER_LEVEL_OPTION_PREFIX.$text_domain, 'error' );

        switch ( $logger_level ) {
            case 'logger_level_emergency':
            case 'logger_level_alert':
            case 'logger_level_critical':
            case 'logger_level_error':
                WP_Log::$error_level = 'error';
                break;
            case 'logger_level_warning':
                WP_Log::$error_level = 'warning';
                break;
            case 'logger_level_notice':
                WP_Log::$error_level = 'notice';
                break;
            case 'logger_level_info':
                WP_Log::$error_level = 'info';
                break;
            case 'logger_level_debug':
                WP_Log::$error_level = 'debug';
                break;
            default:
                WP_Log::$error_level = 'error';
                break;
        }
        return WP_Log::$error_level;
    }

    /**
     * Adds a log record at an arbitrary level for a logger attached to a text domain
     *
     * @param $text_domain   The log text domain
     * @param $level   The log level
     * @param $message The log message
     * @param $context The log context
     */
    public static function log( $level, $message, array $context = [], $text_domain=null ) {
        if ( is_null( $text_domain ) && !is_null( self::$default_text_domain ) ) {
            $text_domain = self::$default_text_domain;
        }

        if ( !is_null( $text_domain ) && array_key_exists( $text_domain, self::$loggers ) ) {
            // Message is prefixed with text domain
            $message = '> '.$text_domain.' > '.$message;

            // Convert Monolog levels to WordPress levels
            $wp_level = 'error';
            switch ($level) {
                case 'emergency':
                case 'alert':
                case 'critical':
                case 'error':
                    $wp_level = 'error';
                    break;
                case 'warning':
                    $wp_level = 'warning';
                    break;
                case 'notice':
                    $wp_level = 'notice';
                    break;
                case 'info':
                    $wp_level = 'info';
                    break;
                case 'debug':
                    $wp_level = 'debug';
                    break;
            }

            // Add context to message if present
            if (!empty($context)) {
                $message .= ' ' . json_encode($context);
            }

            // Use WordPress error_log function
            \error_log($message, 0);
        }
    }

    public static function debug( $message, array $context = [], $text_domain=null ) {
        self::log( 'debug', $message, $context, $text_domain );
    }

    public static function info( $message, array $context = [], $text_domain=null ) {
        self::log( 'info', $message, $context, $text_domain );
    }

    public static function notice( $message, array $context = [], $text_domain=null ) {
        self::log( 'notice', $message, $context, $text_domain );
    }

    public static function warning( $message, array $context = [], $text_domain=null ) {
        self::log( 'warning', $message, $context, $text_domain );
    }

    public static function error( $message, array $context = [], $text_domain=null ) {
        self::log( 'error', $message, $context, $text_domain );
    }

    public static function critical( $message, array $context = [], $text_domain=null ) {
        self::log( 'critical', $message, $context, $text_domain );
    }

    public static function alert( $message, array $context = [], $text_domain=null ) {
        self::log( 'alert', $message, $context, $text_domain );
    }

    public static function emergency( $message, array $context = [], $text_domain=null ) {
        self::log( 'emergency', $message, $context, $text_domain );
    }

    /**
     * Called on plugin deactivation to clean options
     */
    public function deactivate() {
        foreach ( self::$loggers as $text_domain => $logger ) {
            \delete_option( self::WP_SUKELLOS_FW_LOGGER_LEVEL_OPTION_PREFIX.$text_domain );
        }
    }
}