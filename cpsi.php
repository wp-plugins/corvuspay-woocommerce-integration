<?php

/*
  Plugin Name: WP-CPIS
  Description: CorvusPay payment plugin for WooCommerce
 */

class CpsiKeys {

    const OPTIONS_ID = 'cpsi_gateway';
    const OPTION_CHECKOUT_PAGE_ID = 'checkout_page_id';
    const OPTION_CORVUSSTORE_ID = 'corvus_store_id';
    const OPTION_CORVUS_KEY = 'corvus_key';
    const OPTION_SUCCESS_PAGE_ID = 'cps_success_page_id';
    const OPTION_CANCEL_PAGE_ID = 'cps_cancel_page_id';
    const OPTION_DEBUG = 'cps_debug';
    const OPTION_CERTFILE = 'cps_certfile';
    const OPTION_CERTFILE_PASSWORD = 'cps_certfile_password';

}

class Cpsi_Installer {

    public function installAndActivate() {
        $this->install();
        $this->activate();
    }

    public function install() {

        $options = get_option(CpsiKeys::OPTIONS_ID);
        if ($options === false) {
            $post_id = $this->_insertCheckoutShortCode();
            $success_reply_id = $this->_insertCpsSuccessReplyShortCode();
            $cancel_reply_id = $this->_insertCpsCancelReplyShort();
            $this->_registerOptionsKeys($post_id, $success_reply_id, $cancel_reply_id);
        }
    }

    public function activate() {
        $options = get_option(CpsiKeys::OPTIONS_ID);
        $this->_updateOptionKeys($options);
    }

    public function deactivate() {
        assert(true);
    }

    public function uninstall() {
        $options = get_option(CpsiKeys::OPTIONS_ID);

        if (is_array($options)) {
            if (!empty($options[CpsiKeys::OPTION_CANCEL_PAGE_ID])) {
                wp_delete_post($options[CpsiKeys::OPTION_CANCEL_PAGE_ID]);
            }
        }
        if (is_array($options)) {
            if (!empty($options[CpsiKeys::OPTION_CHECKOUT_PAGE_ID])) {
                wp_delete_post($options[CpsiKeys::OPTION_CHECKOUT_PAGE_ID]);
            }
        }
        if (is_array($options)) {
            if (!empty($options[CpsiKeys::OPTION_CORVUSSTORE_ID])) {
                wp_delete_post($options[CpsiKeys::OPTION_CORVUSSTORE_ID]);
            }
        }
        if (is_array($options)) {
            if (!empty($options[CpsiKeys::OPTION_SUCCESS_PAGE_ID])) {
                wp_delete_post($options[CpsiKeys::OPTION_SUCCESS_PAGE_ID]);
            }
        }

        delete_option(CpsiKeys::OPTIONS_ID);
    }

    private function _registerOptionsKeys($checkout_id = 0, $success_id = 0, $cancel_id = 0) {
        add_option(CpsiKeys::OPTIONS_ID, array(
            CpsiKeys::OPTION_CHECKOUT_PAGE_ID => $checkout_id,
            CpsiKeys::OPTION_SUCCESS_PAGE_ID => $success_id,
            CpsiKeys::OPTION_CANCEL_PAGE_ID => $cancel_id,
            CpsiKeys::OPTION_DEBUG => true
        ));
    }

    private function _updateOptionKeys($options) {
        if (empty($options[CpsiKeys::OPTION_CORVUSSTORE_ID])) {
            $options[CpsiKeys::OPTION_CORVUSSTORE_ID] = '';
        }

        if (empty($options[CpsiKeys::OPTION_CORVUS_KEY])) {
            $options[CpsiKeys::OPTION_CORVUS_KEY] = '';
        }

        if (empty($options[CpsiKeys::OPTION_SUCCESS_PAGE_ID])) {
            $options[CpsiKeys::OPTION_SUCCESS_PAGE_ID] = '';
        }

        if (empty($options[CpsiKeys::OPTION_CANCEL_PAGE_ID])) {
            $options[CpsiKeys::OPTION_CANCEL_PAGE_ID] = '';
        }

        if (empty($options[CpsiKeys::OPTION_DEBUG])) {
            $options[CpsiKeys::OPTION_DEBUG] = true;
        }

        update_option(CpsiKeys::OPTIONS_ID, $options);
    }

    /**
     * This method will insert wp post page that will be used as a jump page
     * to CorvusPay Payment Gateway
     * @return int
     */
    protected function _insertCheckoutShortCode() {

        $checkout_post = array(
            'post_author' => 1,
            'post_title' => __('CorvusPay Checkout'),
            'post_content' => '[corvus_pay_checkout]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed',
            'post_name' => 'process-corvus-payment'
        );

        $post_id = wp_insert_post($checkout_post);

        return $post_id;
    }

    /**
     * 
     * @return int
     */
    protected function _insertCpsSuccessReplyShortCode() {

        $checkout_cps_reply_success_post = array(
            'post_author' => 1,
            'post_title' => __('Your order has been successfully completed!'),
            'post_content' => '[corvus_pay_process_success]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed',
            'post_name' => 'process-corvus-payment'
        );

        $postId = wp_insert_post($checkout_cps_reply_success_post);
        return $postId;
    }

    /*
     * @return int
     */

    protected function _insertCpsCancelReplyShort() {
        $checkout_cps_reply_cancel_post = array(
            'post_author' => 1,
            'post_title' => __('Canceling order'),
            'post_content' => '[corvus_pay_process_cancel]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed',
            'post_name' => 'process-corvus-payment'
        );

        return wp_insert_post($checkout_cps_reply_cancel_post);
    }

}

/**
 * If woocommerce is not present, for now exit
 */
class Cpsi_Init {

    protected $classesAreLoaded = false;

    /**
     * CpsiInit constructor 
     */
    public function __construct() {
        // Register hook handlers
        register_activation_hook(__FILE__, array($this, 'activation'));
        register_deactivation_hook(__FILE__, array($this, 'deactivation'));
        register_uninstall_hook(__FILE__, array($this, 'uninstall'));
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'on_plugins_loaded'));
    }

    /**
     * @return Cpsi_Installer
     */
    private function _getInstaller() {
        return new Cpsi_Installer();
    }

    /**
     * This method handles activation of the plugin. 
     * If this is first activation, it will execute plugin installation code
     */
    public function activation() {
        $this->_getInstaller()->installAndActivate();
    }

    /**
     * This method handles deactivation of the plugin
     */
    public function deactivation() {
        $this->_getInstaller()->deactivate();
    }

    /**
     * This method handles plugin deactivation
     */
    public function uninstall() {
        $this->_getInstaller()->uninstall();
    }

    /**
     * This method will handle initialization of this plugin
     */
    public function init() {
        add_shortcode('corvus_pay_checkout', array($this, 'executeShortcode'));
        add_shortcode('corvus_pay_process_success', array($this, 'executeShortcode'));
        add_shortcode('corvus_pay_process_cancel', array($this, 'executeShortcode'));
    }

    /**
     * This method will initialize and run shortcode handler identified
     * by name argument
     * @param string $name
     */
    public function executeShortcode($arg1, $arg2, $shortcodeName) {
        $this->_loadClasses();
        $shortcodesHandler = new Cpsi_Shortcodes();
        $shortcodesHandler->runShortcode($shortcodeName);
    }

    /**
     * Load required classes
     * 
     */
    protected function _loadClasses() {
        if ($this->classesAreLoaded) {
            return;
        }

        $this->classesAreLoaded = true;
        require_once dirname(__FILE__) . "/classes.php";
        require_once dirname(__FILE__) . '/includes/Cpsi/Cpsi.php';
        require_once dirname(__FILE__) . '/includes/Cpsi/Form/Abstract.php';
        require_once dirname(__FILE__) . '/includes/Cpsi/Form/Woocommerce.php';
    }

    /**
     * 
     */
    public function on_plugins_loaded() {
        add_action('woocommerce_payment_gateways', array($this, 'on_woocommerce_payment_gateways'));
    }

    /**
     *  This method is called by woocommerce and it will register 
     *  CorvusPay admin console.
     * @param array $methods
     * @return array
     */
    public function on_woocommerce_payment_gateways($methods) {
        if (class_exists('WC_Payment_Gateway')) {
            $this->_loadClasses();
        }

        $methods[] = 'Cpsi_Gateway';
        return $methods;
    }

}

new Cpsi_Init();