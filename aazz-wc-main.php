<?php
/*
Plugin Name: WooCommerce Table Rate Shipping
Plugin URI:  https://aazztech.com/product/easy-table-rate-shipping-pro-for-woocommerce/
Description: It allows you to calculate WooCommerce shipping cost based on total price or weight.
Version:     1.0.2
Author:      AazzTech
Author URI:  https://aazztech.com
License:     GPL2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages/
Text Domain: easy-table-rate-shipping-for-woocommerce
WC requires at least: 3.0
WC tested up to: 3.5.0
*/
defined( 'ABSPATH' ) || die( 'Cheating, huh? Direct access to this file is not allowed !!!!' );

class Aazztech_Wc_Table_Rate_Shipping {

    public function __construct() {
        $this->aazz_define_all_constants();

        if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
            //If woocommerce is not install then it show admin nottice and not activate
            add_action( 'admin_notices', array($this, 'aazz_wc_admin_notice') );
        }
        //load text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        //include css and js files for admin
        add_action('admin_enqueue_scripts',array($this,'aazz_wc_admin_enqueue'));
        add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array($this, 'aazz_wc_settings') );
        require_once AAZZ_WC_DIR . 'inc/aazz-wc-shipping-method.php';
    }

    //define all constant
    public function aazz_define_all_constants() {

        if ( ! defined( 'AAZZ_WC_DIR' ) ) { define( 'AAZZ_WC_DIR', plugin_dir_path( __FILE__ ) ); }
        if ( ! defined( 'AAZZ_WC_URL' ) ) { define( 'AAZZ_WC_URL', plugin_dir_url( __FILE__ ) ); }
        if ( ! defined('AAZZ_WC_BASE') ) { define('AAZZ_WC_BASE', plugin_basename( __FILE__ )); }
        if ( ! defined( 'AAZZ_WC_TEXTDOMAIN' ) ) define( 'AAZZ_WC_TEXTDOMAIN', 'easy-table-rate-shipping-for-woocommerce' );
        if ( ! defined( 'AAZZ_WC_BASENAME' ) ) define( 'AAZZ_WC_BASENAME', plugin_basename(__file__) );

    }

    //woocommerce notice
    public function aazz_wc_admin_notice() {
        ?>
        <div class="error">
            <p>
                <?php
                printf('%s <strong>%s</strong>', esc_html__('WooCommerce plugin is not activated. Please install and activate it to use', AAZZ_WC_TEXTDOMAIN), esc_html__('Easy Table Rate Shipping For Woommerce Plugin', AAZZ_WC_TEXTDOMAIN) );
                ?>
            </p>
        </div>
        <?php
        deactivate_plugins( AAZZ_WC_BASE );
    }

    public function aazz_wc_admin_enqueue($hook) {
        if ($hook == 'woocommerce_page_wc-settings') {
            wp_enqueue_style('table-css',PLUGINS_URL('assets/shipping.css',__FILE__));
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('checkall',PLUGINS_URL('assets/shipping.js',__FILE__),array('jquery'));
        }

    }


    public function aazz_wc_settings( $links ) {
        $links[] = '<a href="admin.php?page=wc-settings&tab=shipping">Settings</a>';
        $links[] .= '<a href="https://aazztech.com/product/easy-table-rate-shipping-pro-for-woocommerce/">Pro</a>';
        return $links;
    }

    //load plugin text domain
    function load_textdomain() {
        load_plugin_textdomain( AAZZ_WC_TEXTDOMAIN, false, dirname( plugin_basename(__FILE__) ) . '/languages/' );
    }


    public static function aazz_wc_activate() {
        flush_rewrite_rules();
    }

    public static function aazz_wc_deactivate() {
        flush_rewrite_rules();
    }

} //end class

register_activation_hook(__FILE__, array('Aazztech_Wc_Table_Rate_Shipping','aazz_wc_activate'));
register_deactivation_hook(__FILE__, array('Aazztech_Wc_Table_Rate_Shipping','aazz_wc_deactivate'));

new Aazztech_Wc_Table_Rate_Shipping();