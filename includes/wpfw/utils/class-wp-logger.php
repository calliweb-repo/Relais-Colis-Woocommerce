<?php

namespace RelaisColisWoocommerce\WPFw\Utils;

// Monolog
use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Formatter\LineFormatter;
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

    public static $error_level = Logger::EMERGENCY;

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

        // First get logging level for error_log
        $this->init_error_log( $text_domain, $default );
    }

    /**
     * Init error logging
     */
    private function init_error_log( $text_domain, $default=false ) {

        // Setting default logger text domain... may simplify calls
        if ( $default ) {

            self::$default_text_domain = $text_domain;
        }

        // First get logging level for error_log
        $error_log_level = $this->get_error_log_level( $text_domain );

//        self::$error_log = new Logger( $text_domain.'-error-log' );
        $logger = new Logger( $text_domain );

        // Introspection processor (add info about class, line, method...)
        // Only for DEBUG mode
        if ($this->activate_introspection()) {

            $introspection_processor = new IntrospectionProcessor( $error_log_level );
            $logger->pushProcessor( $introspection_processor );
        }

        // Line formatter
        //
        // Output format example: "%datetime% > %level_name% > %message% %context% %extra%\n";
        // Depending on level mode
        $date_format = "Y n j, g:i a";
        $output = "%level_name% %message% %context%";
        if ( Logger::DEBUG == $error_log_level ) {

            $output = "%extra% %level_name% %message% %context%";
        }
        $line_formatter = new LineFormatter( $output, $date_format, false, true );

        // Error log handler
        $error_log_handler = new ErrorLogHandler( ErrorLogHandler::OPERATING_SYSTEM, $error_log_level, true, false );
        $error_log_handler->setFormatter( $line_formatter );
        $logger->pushHandler( $error_log_handler );

        self::$loggers[$text_domain] = $logger;
    }

    /**
     * Set logging level for error_log
     * Must return one of levels defined in Monolog Logger; eg. Logger::DEBUG
     * DEBUG - Detailed debug information
     * INFO - Interesting events. Examples: User logs in, SQL logs.
     * NOTICE - Uncommon events
     * WARNING - Exceptional occurrences that are not errors. Examples: Use of deprecated APIs, poor use of an API,
     * ERROR - Runtime errors
     * CRITICAL - Critical conditions - Example: Application component unavailable, unexpected exception.
     * ALERT - Action must be taken immediately. Example: Entire website down, database unavailable, etc.
     * EMERGENCY - Urgent alert
     */
    public function get_error_log_level( $text_domain ) {

        // Get logger level from Titan settings, directly using get_options (no need for Titan admin visual composer)
        $logger_level = get_option( self::WP_SUKELLOS_FW_LOGGER_LEVEL_OPTION_PREFIX.$text_domain, Logger::EMERGENCY );

        switch ( $logger_level ) {

            case 'logger_level_emergency':
                WP_Log::$error_level = Logger::EMERGENCY;
                break;
            case 'logger_level_alert':
                WP_Log::$error_level = Logger::ALERT;
                break;
            case 'logger_level_critical':
                WP_Log::$error_level = Logger::CRITICAL;
                break;
            case 'logger_level_error':
                WP_Log::$error_level = Logger::ERROR;
                break;
            case 'logger_level_warning':
                WP_Log::$error_level = Logger::WARNING;
                break;
            case 'logger_level_notice':
                WP_Log::$error_level = Logger::NOTICE;
                break;
            case 'logger_level_info':
                WP_Log::$error_level = Logger::INFO;
                break;
            case 'logger_level_debug':
                WP_Log::$error_level = Logger::DEBUG;
                break;
            default:
                WP_Log::$error_level = Logger::EMERGENCY;
                break;

        }
        return WP_Log::$error_level;
    }

    /**
     * Adds a log record at an arbitrary level for a logger atached to a text domain
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

            $clogger = self::$loggers[''.$text_domain];
            $clogger->log( $level, $message, $context );
        }
    }
    public static function debug( $message, array $context = [], $text_domain=null ) {
        self::log( Logger::DEBUG, $message, $context, $text_domain );
    }
    public static function info( $message, array $context = [], $text_domain=null ) {
        self::log( Logger::INFO, $message, $context, $text_domain );
    }
    public static function notice( $message, array $context = [], $text_domain=null ) {
        self::log( Logger::NOTICE, $message, $context, $text_domain );
    }
    public static function warning( $message, array $context = [], $text_domain=null ) {
        self::log( Logger::WARNING, $message, $context, $text_domain );
    }
    public static function error( $message, array $context = [], $text_domain=null ) {
        self::log( Logger::ERROR, $message, $context, $text_domain );
    }
    public static function critical( $message, array $context = [], $text_domain=null ) {
        self::log( Logger::CRITICAL, $message, $context, $text_domain );
    }
    public static function alert( $message, array $context = [], $text_domain=null ) {
        self::log( Logger::ALERT, $message, $context, $text_domain );
    }
    public static function emergency( $message, array $context = [], $text_domain=null ) {
        self::log( Logger::EMERGENCY, $message, $context, $text_domain );
    }

    /**
     * Activate verbose info in log
     * See Monolog Introspection
     */
    public function activate_introspection() {
        return false;
    }

    /**
     * Called on plugin deactivation to clean options
     */
    public function deactivate() {

        foreach ( self::$loggers as $text_domain => $logger ) {

            delete_option( self::WP_SUKELLOS_FW_LOGGER_LEVEL_OPTION_PREFIX.$text_domain );
        }
    }
}