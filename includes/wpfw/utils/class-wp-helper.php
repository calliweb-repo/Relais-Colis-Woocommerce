<?php

namespace RelaisColisWoocommerce\WPFw\Utils;

defined( 'ABSPATH' ) or exit;


/**
 *  Helper Class
 *
 * The purpose of this class is to centralize common utility functions that
 * are commonly used in SkyVerge plugins
 *
 * @since 1.0.0
 */
class WP_Helper {


    /** encoding used for mb_*() string functions */
    const MB_ENCODING = 'UTF-8';

    /**
     * Convert weight units to grams (g).
     *
     * This function supports various weight units used in France and worldwide,
     * including those from WooCommerce and potential third-party extensions.
     *
     * Supported units:
     * - Metric system:
     *   'kg'  => Kilograms (1 kg = 1000 g)
     *   'g'   => Grams (1 g = 1 g)
     *   'mg'  => Milligrams (1 mg = 0.001 g)
     *   'cg'  => Centigrams (1 cg = 0.01 g)
     *   'dg'  => Decigrams (1 dg = 0.1 g)
     *   'ton' => Metric ton (1 ton = 1,000,000 g)
     *   'quintal' => Quintal (1 quintal = 100,000 g)
     *
     * - Imperial system:
     *   'lbs' => Pounds (1 lb = 453.592 g)
     *   'oz'  => Ounces (1 oz = 28.3495 g)
     *   'st'  => Stones (1 stone = 6,350.29 g)
     *   'grain' => Grains (1 grain = 0.0648 g)
     *   'dr'  => Drams (1 dram = 1.77185 g)
     *
     * - Troy weight (used for precious metals):
     *   'troy_oz' => Troy ounces (1 troy oz = 31.1035 g)
     *   'troy_lb' => Troy pounds (1 troy lb = 373.242 g)
     *
     * @param float $value The weight value to convert.
     * @param string $unit The unit of measurement (kg, g, mg, lbs, oz, etc.).
     * @return float The converted weight in grams (g), or null if not supported
     */
    public static function convert_to_grams( $value, $unit ) {

        // Conversion rates for different units to grams
        $conversion_rates = [
            // Metric system
            'kg' => 1000,       // 1 kilogram = 1000 grams
            'g' => 1,          // 1 gram = 1 gram
            'mg' => 0.001,     // 1 milligram = 0.001 grams
            'cg' => 0.01,      // 1 centigram = 0.01 grams
            'dg' => 0.1,       // 1 decigram = 0.1 grams
            'ton' => 1000000,  // 1 metric ton = 1,000,000 grams
            'quintal' => 100000, // 1 quintal = 100,000 grams

            // Imperial system
            'lbs' => 453.592,  // 1 pound = 453.592 grams
            'oz' => 28.3495,   // 1 ounce = 28.3495 grams
            'st' => 6350.29,   // 1 stone = 6,350.29 grams
            'grain' => 0.0648, // 1 grain = 0.0648 grams
            'dr' => 1.77185,   // 1 dram = 1.77185 grams

            // Troy weight (for precious metals)
            'troy_oz' => 31.1035,  // 1 troy ounce = 31.1035 grams
            'troy_lb' => 373.242   // 1 troy pound = 373.242 grams
        ];

        // Normalize unit to lowercase for consistency
        $unit = strtolower( trim( $unit ) );

        // Check if the unit exists in our conversion array
        if ( isset( $conversion_rates[ $unit ] ) ) {

            return (float)$value * $conversion_rates[ $unit ];

        } else return null;
    }

    /**
     * Allow to remove method for an hook when, it's a class method used and class don't have global for instanciation !
     */
    public static function remove_filters_with_method_name( $hook_name = '', $method_name = '', $priority = 0 ) {

        global $wp_filter;

        // Take only filters on right hook name and priority
        if ( !isset( $wp_filter[ $hook_name ][ $priority ] ) || !is_array( $wp_filter[ $hook_name ][ $priority ] ) ) {

            return false;
        }

        // Loop on filters registered
        foreach ( (array)$wp_filter[ $hook_name ][ $priority ] as $unique_id => $filter_array ) {

            // Test if filter is an array ! (always for class/method)
            if ( isset( $filter_array[ 'function' ] ) ) {

                if ( is_array( $filter_array[ 'function' ] ) ) {

                    // Test if object is a class and method is equal to param !
                    if ( is_object( $filter_array[ 'function' ][ 0 ] ) && get_class( $filter_array[ 'function' ][ 0 ] ) && $filter_array[ 'function' ][ 1 ] == $method_name ) {

                        // Test for WordPress >= 4.7 WP_Hook class (https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/)
                        if ( is_a( $wp_filter[ $hook_name ], 'WP_Hook' ) ) {

                            unset( $wp_filter[ $hook_name ]->callbacks[ $priority ][ $unique_id ] );
                        } else {

                            unset( $wp_filter[ $hook_name ][ $priority ][ $unique_id ] );
                        }
                    }
                } // Else this is a static call
                else {

                    if ( is_a( $wp_filter[ $hook_name ], 'WP_Hook' ) ) {

                        unset( $wp_filter[ $hook_name ]->callbacks[ $priority ][ $unique_id ] );
                    } else {

                        unset( $wp_filter[ $hook_name ][ $priority ][ $unique_id ] );
                    }
                }
            }

        }

        return false;
    }

    /**
     * Allow to remove method for an hook when, it's a class method used and class don't have variable, but you know the class name :)
     */
    public static function remove_filters_for_anonymous_class( $hook_name = '', $class_name = '', $method_name = '', $priority = 0 ) {

        global $wp_filter;

        // Take only filters on right hook name and priority
        if ( ! isset( $wp_filter[ $hook_name ][ $priority ] ) || ! is_array( $wp_filter[ $hook_name ][ $priority ] ) ) {

            return false;
        }

        // Loop on filters registered
        foreach ( (array) $wp_filter[ $hook_name ][ $priority ] as $unique_id => $filter_array ) {

            // Test if filter is an array ! (always for class/method)
            if ( isset( $filter_array['function'] ) && is_array( $filter_array['function'] ) ) {

                // Test if object is a class, class and method is equal to param !
                if ( is_object( $filter_array['function'][0] ) && get_class( $filter_array['function'][0] ) && get_class( $filter_array['function'][0] ) == $class_name && $filter_array['function'][1] == $method_name ) {

                    // Test for WordPress >= 4.7 WP_Hook class (https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/)
                    if ( is_a( $wp_filter[ $hook_name ], 'WP_Hook' ) ) {

                        unset( $wp_filter[ $hook_name ]->callbacks[ $priority ][ $unique_id ] );
                    } else {

                        unset( $wp_filter[ $hook_name ][ $priority ][ $unique_id ] );
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get a french representation of the datetime
     *
     * @param $datetime
     * @param $format french format, default %A %d %B %Y / %Hh%M
     * @return string representation
     */
    public static function get_datetime_fr_format( $datetime, $format='%A %d %B %Y / %Hh%M' ) {

        return utf8_encode(strftime($format, $datetime->getTimestamp()));
    }

    /**
     * Replace accent characters with non accentued ones
     * Eg. é becomes e
     * @param $string
     * @return array|string|string[]
     */
    public static function replace_accents( $string ) {

        $search  = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'à', 'á', 'â', 'ã', 'ä', 'å', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ð', 'ò', 'ó', 'ô', 'õ', 'ö', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ');
        $replace = array('A', 'A', 'A', 'A', 'A', 'A', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 'a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y');
        $string = str_replace($search, $replace, $string);
        return $string;
    }

    /**
     * Determines whether a plugin is active, and keep active plugins as instance array
     *
     * @since 1.0.0
     *
     * @param string $plugin_name plugin name corresponding with <plugin_name>/<plugin_name>.php or something/<plugin_name>.php or <plugin_name>/something.php
     * @return boolean true if the named plugin is installed and active, otherwise false
     */
    public static function is_plugin_active( $plugin_name ) {

        $is_active = false;

        $active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

        if ( is_multisite() ) {
            $active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) );
        }

        foreach ( $active_plugins as $active_plugin ) {

            if ( WP_Helper::str_exists( $active_plugin, $plugin_name ) ) {
                return true;
            }
        }

        return false;
    }


    /** String manipulation functions (all multi-byte safe) ***************/

    /**
     * Returns true if the haystack string starts with needle
     *
     * Note: case-sensitive
     *
     * @since 1.0.0
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function str_starts_with( $haystack, $needle ) {

        if ( self::multibyte_loaded() ) {

            if ( '' === $needle ) {
                return true;
            }

            return 0 === mb_strpos( $haystack, $needle, 0, self::MB_ENCODING );

        } else {

            $needle = self::str_to_ascii( $needle );

            if ( '' === $needle ) {
                return true;
            }

            return 0 === strpos( self::str_to_ascii( $haystack ), self::str_to_ascii( $needle ) );
        }
    }

    /**
     * Get a media ID from its filename, including extensions or not
     * @param type $filename
     * @return type
     */
    public static function get_media_id_from_filename( $filename ) {
        
        $ext = array(".png", ".jpg", ".gif", ".jpeg");
        $filename = str_replace($ext, "", $filename);
        $clean_filename = trim(html_entity_decode(sanitize_title($filename)));
        $page = get_page_by_title($clean_filename, OBJECT, 'attachment');
        return $page->ID;
    }

    /**
     * Check if a image already exist in media
     * @global type $wpdb
     * @param type $filename
     * @return type
     */
    public static function media_file_already_exists( $filename, $boolean_response=true ) {

        // Must consider a -1.jpeg or -2.jpeg in file name, for example
        $exts = array(".png", ".jpg", ".gif", ".jpeg");
        foreach ( $exts as $ext ) {

            if ( FALSE !== strpos( $filename, $ext ) ) {

                $filename = str_replace($ext, "", $filename);
            }
        }
        require_once( ABSPATH . 'wp-admin/includes/post.php' );
        $image_exists = post_exists( $filename, '', '', 'attachment' );
        if ( $boolean_response ) return ( $image_exists > 0 );
        else return $image_exists;
    }


    /**
     * Function which can be used in array_walk_recursive as callback to reduced huge string values in an array
     *
     * @param $string
     * @param $index
     * @return void
     */
    public static function reduce_array_value ( &$string, $index ) {

        if ( is_string( $string ) && ( strlen( $string ) > 2048 ) ) {

            $string = substr( $string, 0, 4800 ).' [HUGE STRING REDUCED...]';
        }
    }

    /**
     * Get the filename from the URL, without that after ?
     *
     * @param type $url the given URL
     */
    public static function get_url_strict_basename( $url ) {

        $e = explode( "?",basename( $url ) );
        return $e[0];
    }

    /**
     * Return true if the haystack string ends with needle
     *
     * Note: case-sensitive
     *
     * @since 1.0.0
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function str_ends_with( $haystack, $needle ) {

        if ( '' === $needle ) {
            return true;
        }

        if ( self::multibyte_loaded() ) {

            return mb_substr( $haystack, -mb_strlen( $needle, self::MB_ENCODING ), null, self::MB_ENCODING ) === $needle;

        } else {

            $haystack = self::str_to_ascii( $haystack );
            $needle   = self::str_to_ascii( $needle );

            return substr( $haystack, -strlen( $needle ) ) === $needle;
        }
    }


    /**
     * Returns true if the needle exists in haystack
     *
     * Note: case-sensitive
     *
     * @since 1.0.0
     * @param string $haystack the string to search in
     * @param string $needle the string to search for
     * @return bool
     */
    public static function str_exists( $haystack, $needle ) {

        if ( self::multibyte_loaded() ) {

            if ( '' === $needle ) {
                return false;
            }

            return false !== mb_strpos( $haystack, $needle, 0, self::MB_ENCODING );

        } else {

            $needle = self::str_to_ascii( $needle );

            if ( '' === $needle ) {
                return false;
            }

            return false !== strpos( self::str_to_ascii( $haystack ), self::str_to_ascii( $needle ) );
        }
    }


    /**
     * Truncates a given $string after a given $length if string is longer than
     * $length. The last characters will be replaced with the $omission string
     * for a total length not exceeding $length
     *
     * @since 1.0.0
     * @param string $string text to truncate
     * @param int $length total desired length of string, including omission
     * @param string $omission omission text, defaults to '...'
     * @return string
     */
    public static function str_truncate( $string, $length, $omission = '...' ) {

        if ( self::multibyte_loaded() ) {

            // bail if string doesn't need to be truncated
            if ( mb_strlen( $string, self::MB_ENCODING ) <= $length ) {
                return $string;
            }

            $length -= mb_strlen( $omission, self::MB_ENCODING );

            return mb_substr( $string, 0, $length, self::MB_ENCODING ) . $omission;

        } else {

            $string = self::str_to_ascii( $string );

            // bail if string doesn't need to be truncated
            if ( strlen( $string ) <= $length ) {
                return $string;
            }

            $length -= strlen( $omission );

            return substr( $string, 0, $length ) . $omission;
        }
    }

    /**
     * Helper method to check if the multibyte extension is loaded, which
     * indicates it's safe to use the mb_*() string methods
     *
     * @since 1.0.0
     * @return bool
     */
    protected static function multibyte_loaded() {

        return extension_loaded( 'mbstring' );
    }


    /**
     * Returns a string with all non-ASCII characters removed. This is useful
     * for any string functions that expect only ASCII chars and can't
     * safely handle UTF-8. Note this only allows ASCII chars in the range
     * 33-126 (newlines/carriage returns are stripped)
     *
     * @since 1.0.0
     * @param string $string string to make ASCII
     * @return string
     */
    public static function str_to_ascii( $string ) {

        // strip ASCII chars 32 and under
        $string = filter_var( $string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW );

        // strip ASCII chars 127 and higher
        return filter_var( $string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH );
    }


    /**
     * Return a string with insane UTF-8 characters removed, like invisible
     * characters, unused code points, and other weirdness. It should
     * accept the common types of characters defined in Unicode.
     *
     * The following are allowed characters:
     *
     * p{L} - any kind of letter from any language
     * p{Mn} - a character intended to be combined with another character without taking up extra space (e.g. accents, umlauts, etc.)
     * p{Mc} - a character intended to be combined with another character that takes up extra space (vowel signs in many Eastern languages)
     * p{Nd} - a digit zero through nine in any script except ideographic scripts
     * p{Zs} - a whitespace character that is invisible, but does take up space
     * p{P} - any kind of punctuation character
     * p{Sm} - any mathematical symbol
     * p{Sc} - any currency sign
     *
     * pattern definitions from http://www.regular-expressions.info/unicode.html
     *
     * @since 4.0.0
     *
     * @param string $string
     * @return string
     */
    public static function str_to_sane_utf8( $string ) {

        $sane_string = preg_replace( '/[^\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Zs}\p{P}\p{Sm}\p{Sc}]/u', '', $string );

        // preg_replace with the /u modifier can return null or false on failure
        return ( is_null( $sane_string ) || false === $sane_string ) ? $string : $sane_string;
    }

    /**
     * Safely gets a variable value from $_GET, $_POST, $_REQUEST, $_COOKIE.
     * Check and sanitize
     *
     * @since 1.0.0
     * @param array $gprc_array one of $_GET, $_POST, $_REQUEST, $_COOKIE
     * @param string $key data key
     * @param int|float|array|bool|null|string $default default data type to return (default empty string)
     * @return int|float|array|bool|null|string data value if key found, or default
     */
    public static function get_gprc_value( $key, $gprc_array, $default = null ) {

        // Initialize value with the default
        $value = $default;

        // Check if the requested key exists in the request
        if ( is_array( $gprc_array ) && isset( $gprc_array[ $key ] ) ) {

            // Sanitize the value based on its type
            if ( is_string( $gprc_array[ $key ] ) ) {

                $value = sanitize_text_field( wp_unslash( $gprc_array[ $key ] ) );
            }
            elseif ( is_int( $gprc_array[ $key ] ) ) {

                $value = absint( $gprc_array[ $key ] );
            }
            else {

                $value = $gprc_array[ $key ];
            }
        }

        // Return the sanitized or default value
        return $value;
    }


    /**
     * Get the count of notices added, either for all notices (default) or for one
     * particular notice type specified by $notice_type.
     *
     * WC notice functions are not available in the admin
     *
     * @since 1.0.0
     * @param string $notice_type The name of the notice type - either error, success or notice. [optional]
     * @return int
     */
    public static function wc_notice_count( $notice_type = '' ) {

        if ( function_exists( 'wc_notice_count' ) ) {
            return wc_notice_count( $notice_type );
        }

        return 0;
    }


    /**
     * Add and store a notice.
     *
     * WC notice functions are not available in the admin
     *
     * @since 1.0.0
     * @param string $message The text to display in the notice.
     * @param string $notice_type The singular name of the notice type - either error, success or notice. [optional]
     */
    public static function wc_add_notice( $message, $notice_type = 'success' ) {

        if ( function_exists( 'wc_add_notice' ) ) {
            wc_add_notice( $message, $notice_type );
        }
    }


    /**
     * Print a single notice immediately
     *
     * WC notice functions are not available in the admin
     *
     * @since 1.0.0
     * @param string $message The text to display in the notice.
     * @param string $notice_type The singular name of the notice type - either error, success or notice. [optional]
     */
    public static function wc_print_notice( $message, $notice_type = 'success' ) {

        if ( function_exists( 'wc_print_notice' ) ) {
            wc_print_notice( $message, $notice_type );
        }
    }
}
