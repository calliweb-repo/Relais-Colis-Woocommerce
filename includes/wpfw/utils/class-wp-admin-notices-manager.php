<?php

namespace RelaisColisWoocommerce\WPFw\Utils;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;

/**
 * Manages admin notices
 *
 * @since 1.0.0
 */
class WP_Admin_Notices_Manager {

    // Use Trait Singleton
    use Singleton;

    const TYPE_ERROR = 'error';
    const TYPE_NOTICE = 'notice';
    const TYPE_UPDATED = 'updated';
    const TYPE_SAVED = 'saved';
    const TYPE_RESET = 'reset';

    const LOCATION_TOP = 'top';
    const LOCATION_BELOW_H2 = 'below-h2';

    // The admin notices to add
    private $notices = array();

    /**
     * Default init method called when instance created
     * This method can be overridden if needed.
     *
     * @since 1.0.0
     * @access public
     */
    public function init() {

        //  Wordpress / admin_notices: Prints admin screen notices.
        add_action( 'admin_notices', array( $this, 'action_admin_notices' ), 10 );
    }

    /**
     * Adds an admin notice to be displayed.
     *
     * @param string $slug message slug
     * @param string $class CSS classes
     * @param string $message notice message
     * @since 1.0.0
     *
     */
    public function add_admin_notice( $slug, $class, $message ) {

        $this->notices[] = array(
            'slug' => $slug,
            'class' => $class,
            'message' => $message
        );
    }

    /**
     * Simply format a notice
     */
    public function format_notice( $message, $type = self::TYPE_UPDATED, $location = self::LOCATION_TOP ) {

        if ( $location != self::LOCATION_TOP ) {

            $location = self::LOCATION_BELOW_H2;
        }

        if ( $type == self::TYPE_SAVED || $type == self::TYPE_RESET ) {

            $message = '<strong>'.$message.'</strong>';
            $type = self::TYPE_UPDATED;
        }

        return "<div class='$type $location'><p>{$message}</p></div>";
    }

    /**
     *          ===============
     *      =======================
     *  ============ HOOKS ===========
     *      =======================
     *          ===============
     */

    /**
     * Display all admin notices
     */
    public function action_admin_notices() {

        foreach ( (array)$this->notices as $notice_key => $notice ) {

            if ( $notice[ 'slug' ] !== '' ) {

                $full_message = $notice[ 'slug' ].' - '.$notice[ 'message' ];
            } else {

                $full_message = $notice[ 'message' ];
            }

            echo "<div class='".esc_attr( $notice[ 'class' ] )."'><p>";
            echo wp_kses( $full_message, array( 'a' => array( 'href' => array() ) ) );
            echo "</p></div>";
        }
    }
}