<?php
if (!class_exists('WC_Payment_Gateway'))
    return;

class Cpsi_Utils {

    protected static $cpsi = null;

    /**
     * @return Cpsi 
     */
    public static function getCpsi($storeId = null, $key = null, $debug = false) {

        if (self::$cpsi) {
            return self::$cpsi;
        }

        if (!($storeId && $key )) {
            $options = get_option(CpsiKeys::OPTIONS_ID);
            $storeId = $options[CpsiKeys::OPTION_CORVUSSTORE_ID];
            $key = $options[CpsiKeys::OPTION_CORVUS_KEY];
            $debug = $options[CpsiKeys::OPTION_DEBUG];
        }

        return self::$cpsi = new Cpsi($storeId, $key, $debug);
    }

}

/**
 * This class provides CorvusPay functionality in Woocommerce administration console.
 * It also provides hooks that enable payment throgh CorvusPay IPG when checkout is requested.
 */
class Cpsi_Gateway extends WC_Payment_Gateway {

    public function __construct() {

        $this->_initClassFields();
        $this->initAdminFormFieldDefinitions();
        $this->init_settings();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'handleAdminFormPost'));
    }

    private function _initClassFields() {
        $this->id = "cpsi";
        $this->title = __("Credit card");
        $this->has_fields = false;
        $this->method_title = __('CorvusPay', 'cpsi');
        $this->icon = plugins_url('assets/PayCardsSupport.png', __FILE__);
    }

    protected function _getOptions() {
        return get_option(CpsiKeys::OPTIONS_ID);
    }

    /**
     *  This method accepts payment request initiated by user, and redirects
     *  him to CorvusPay IPG.
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id) {
        // Load options
        $options = $this->_getOptions();
        $checkout_page_id = $options[CpsiKeys::OPTION_CHECKOUT_PAGE_ID];

        return array(
            'result' => 'success',
            'redirect' => add_query_arg('order_id', $order_id, get_permalink($checkout_page_id))
        );
    }

    /**
     *  This method handles configuration post request initiated by 
     * CorvusPay admin configuration form.
     * @return 
     */
    public function handleAdminFormPost() {
        parent::process_admin_options();


        $this->validate_settings_fields();
        if (count($this->errors) > 0) {
            $this->display_errors();
            return;
        }

        $options = get_option(CpsiKeys::OPTIONS_ID);
        foreach ($this->sanitized_fields as $key => $val) {
            switch ($key) {
                case CpsiKeys::OPTION_CORVUSSTORE_ID:
                    $options[CpsiKeys::OPTION_CORVUSSTORE_ID] = $val;
                    break;
                case CpsiKeys::OPTION_CORVUS_KEY:
                    $options[CpsiKeys::OPTION_CORVUS_KEY] = $val;
                    break;
                case CpsiKeys::OPTION_DEBUG:
                    $options[CpsiKeys::OPTION_DEBUG] = $val == "yes" ? true : false;
                    break;
                case CpsiKeys::OPTION_CHECKOUT_PAGE_ID:
                    $options[CpsiKeys::OPTION_CHECKOUT_PAGE_ID] = $val;
                    break;
                case CpsiKeys::OPTION_SUCCESS_PAGE_ID:
                    $options[CpsiKeys::OPTION_SUCCESS_PAGE_ID] = $val;
                    break;
                case CpsiKeys::OPTION_CANCEL_PAGE_ID:
                    $options[CpsiKeys::OPTION_CANCEL_PAGE_ID] = $val;
                    break;
                case CpsiKeys::OPTION_ENABLE_INSTALLMENTS:
                    $options[CpsiKeys::OPTION_ENABLE_INSTALLMENTS] = $val == "yes" ? true : false;
                    break;
                case CpsiKeys::OPTION_REQUIRE_COMPLETE:
                    $options[CpsiKeys::OPTION_REQUIRE_COMPLETE] = $val == "yes" ? true : false;
                    break;
            }
        }

        update_option(CpsiKeys::OPTIONS_ID, $options);
    }

    /**
     * Define configuration form fields
     */
    public function initAdminFormFieldDefinitions() {
        $options = $this->_getOptions();

        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'cgsi'),
                'type' => 'checkbox',
                'label' => __('Enable CorvusPay Gateway', 'cgsi'),
                'default' => 'no'
            ),
            CpsiKeys::OPTION_CORVUSSTORE_ID => array(
                'title' => __('Store ID', 'cgsi'),
                'type' => 'text',
                'label' => __('CorvusPay Store ID', 'cgsi'),
                'default' => ''
            ),
            CpsiKeys::OPTION_CORVUS_KEY => array(
                'title' => __('CorvusPay Key', 'cgsi'),
                'type' => 'password',
                'label' => __('CorvusPay secret key', 'cgsi'),
                'default' => ''
            ),
            CpsiKeys::OPTION_ENABLE_INSTALLMENTS => array(
                'title' => __('Enable installments', 'cgsi'),
                'type' => 'checkbox',
                'label' => __('', 'cgsi'),
                'default' => $options[CpsiKeys::OPTION_ENABLE_INSTALLMENTS]
            ),
            CpsiKeys::OPTION_REQUIRE_COMPLETE => array(
                'title' => __('Preauthorization', 'cgsi'),
                'type' => 'checkbox',
                'label' => __('If selected client order needs to be completed in Merchant Portal', 'cgsi'),
                'default' => $options[CpsiKeys::OPTION_REQUIRE_COMPLETE]
            ),
            CpsiKeys::OPTION_DEBUG => array(
                'title' => __('Debug mode', 'cgsi'),
                'type' => 'checkbox',
                'label' => __('Can be used to test integration with CorvusPay', 'cgsi'),
                'default' => true
            ),
            CpsiKeys::OPTION_CHECKOUT_PAGE_ID => array(
                'title' => __('Checkout page ID', 'cgsi'),
                'type' => 'text',
                'label' => __('Id of the page that contains \'corvus_pay_checkout\' shortode used to redirect the user to CorvusPay CPS', 'cgsi'),
                'default' => $options[CpsiKeys::OPTION_CHECKOUT_PAGE_ID]
            ),
            CpsiKeys::OPTION_SUCCESS_PAGE_ID => array(
                'title' => __('Success page ID', 'cgsi'),
                'type' => 'text',
                'label' => __('Id of the page that contains \'corvus_pay_process_cancel\' shortode.', 'cgsi'),
                'default' => $options[CpsiKeys::OPTION_SUCCESS_PAGE_ID]
            ),
            CpsiKeys::OPTION_CANCEL_PAGE_ID => array(
                'title' => __('Cancel page ID', 'cgsi'),
                'type' => 'text',
                'label' => __('Id of the page that contains \'corvus_pay_process_cancel\' shortode.', 'cgsi'),
                'default' => $options[CpsiKeys::OPTION_CANCEL_PAGE_ID]
            )
        );
    }

    /**
     * Render administration form fields along with other fields that 
     * define CorvusPay success and cancel post fields.
     * @param array $form_fields
     * @override
     */
    public function generate_settings_html($form_fields = array()) {
        echo parent::generate_settings_html($form_fields);

        $options = $this->_getOptions();
        $cps_recirect_success_page_id = $options[CpsiKeys::OPTION_SUCCESS_PAGE_ID];
        $cps_recirect_cancel_page_id = $options[CpsiKeys::OPTION_CANCEL_PAGE_ID];
        ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row" class="titledesc">
                        <label ><?php echo __('Success redirect URL') ?>:</label>
                    </th>
                    <td class="forminp">
                        <fieldset>
                            <input class="input-text regular-input " type="text"  id="woocommerce_cpsi_corvus_store_id" style="" value="<?php echo get_permalink($cps_recirect_success_page_id) ?>" placeholder="">
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row" class="titledesc">
                        <label ><?php echo __('Cancel redirect URL') ?>:</label>
                    </th>
                    <td class="forminp">
                        <fieldset>
                            <input class="input-text regular-input " type="text"  id="woocommerce_cpsi_corvus_store_id" style="" value="<?php echo get_permalink($cps_recirect_cancel_page_id) ?>" placeholder="">
                        </fieldset>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    public function handleOrderStateChange($order_id, $old_status, $new_status) {
//        do_action( 'woocommerce_order_status_changed', $this->id, $old_status, $new_status );
        // If we need to complete this order payment
        if ($old_status == 'on-hold' && !in_array($new_status, array('failed', 'cancelled', 'refounded'))) {
            Cpsi_Utils::getCpsi()->completeTransaction($order_id);
        }
    }

}

/**
 * Shortcode handler
 */
class Cpsi_Shortcodes {

    protected $_options;

    /**
     *
     * @var array Valid shortcodes
     */
    protected static $shortcodes = array(
        'corvus_pay_checkout',
        'corvus_pay_process_success',
        'corvus_pay_process_cancel'
    );

    /**
     * 
     */
    public function __construct() {
        $this->_options = get_option(CpsiKeys::OPTIONS_ID);
    }

    /**
     * 
     * @param string $name
     * @return bool
     */
    public static function isAvailable($name) {
        return in_array($name, self::$shortcodes);
    }

    /**
     * Shortcode handler
     * @param string $name - Shortcode name
     */
    public function runShortcode($name) {
        switch ($name) {
            case 'corvus_pay_checkout':
                $this->corvusPayCheckout();
                break;
            case 'corvus_pay_process_success':
                $this->corvusPayHandleSuccess();
                break;
            case 'corvus_pay_process_cancel':
                $this->corvusPayHandleFailure();
                break;
        }
    }

    /**
     * 
     * @return \Cpsi
     */
    private function _getCpsi() {
        return Cpsi_Utils::getCpsi($this->_options[CpsiKeys::OPTION_CORVUSSTORE_ID], $this->_options[CpsiKeys::OPTION_CORVUS_KEY], $this->_options[CpsiKeys::OPTION_DEBUG]);
    }

    /**
     * 
     * @param type $var
     * @return WC_Order
     */
    private function _getOrderFromRequestVar($var = 'order_id') {
        if (!filter_input(INPUT_POST, $var, FILTER_VALIDATE_INT) && !filter_input(INPUT_GET, $var, FILTER_VALIDATE_INT)) {
            return false;
        } else {
            $orderId = filter_input(INPUT_POST, $var, FILTER_SANITIZE_NUMBER_INT);
            if (!$orderId) {
                $orderId = filter_input(INPUT_GET, $var, FILTER_SANITIZE_NUMBER_INT);
            }
        }

        if (null == ($order = $this->getOrder($orderId))) {
            return false;
        }

        $userId = get_current_user_id();

        if ($order->get_user_id() != $userId) {
            return false;
        }

        return $order;
    }

    /**
     * 
     * @param \WC_Order $order
     * @return boolean
     */
    private function _canForwardOrderToPaymentGw(Wc_Order $order) {
        $status = $order->get_status();
        if ($status != 'pending' && $status != 'on-hold') {
            $this->redirectToHome();
            return false;
        }
        return true;
    }

    private function corvusPayCheckout() {

        if (false === ($order = $this->_getOrderFromRequestVar())) {
            return $this->redirectToHome();
        }

        if (!$this->_canForwardOrderToPaymentGw($order)) {
            return $this->redirectToHome();
        }

        echo $this->_buildRedirectToPaymentGwForm($order);
    }

    /**
     * 
     * @param WC_Order $order
     * @return string
     */
    private function _buildRedirectToPaymentGwForm($order) {
        $orderId = $order->id;
        $amount = $order->get_total();
        $currency = $order->get_order_currency();
        $language = 'hr';
        $cartDescription = $this->getItemDescription($order);
        $cpsi = $this->_getCpsi();
        $handle_installments = $this->_options[CpsiKeys::OPTION_ENABLE_INSTALLMENTS];
        $require_complete = $this->_options[CpsiKeys::OPTION_REQUIRE_COMPLETE];

        // $cpsi, $orderNumber, $language, $currency, $amount, $cart, $requireComplete, $bestBeforeTs = 0
        $form = new Cpsi_Form_Woolcommmerce($cpsi, $orderId, $language, $currency, $amount, $cartDescription, $require_complete);
        $this->_setOptionalFormFields($form, $order);
        $form->setEnableInstallments($handle_installments);

        echo $form->constructForm();
    }

    /**
     * 
     * @param Cpsi_Form_Woolcommmerce $form
     * @param WC_Order $order
     */
    private function _setOptionalFormFields($form, $order) {
        $form->setOptionalFormFieldValuePairs(array(
            Cpsi_Form_Woolcommmerce::FORM_FIELD_CARDHOLDER_NAME => $order->billing_first_name,
            Cpsi_Form_Woolcommmerce::FORM_FIELD_CARDHOLDER_SURNAME => $order->billing_last_name,
            Cpsi_Form_Woolcommmerce::FORM_FIELD_CARDHOLDER_COUNTRY => $order->billing_country,
            Cpsi_Form_Woolcommmerce::FORM_FIELD_CARDHOLDER_EMAIL => $order->billing_email,
            Cpsi_Form_Woolcommmerce::FORM_FIELD_CARDHOLDER_PHONE => $order->billing_phone,
            Cpsi_Form_Woolcommmerce::FORM_FIELD_CARDHOLDER_CITY => $order->billing_city,
            Cpsi_Form_Woolcommmerce::FORM_FIELD_CARDHOLDER_ADDRESS => $order->billing_address_1,
            Cpsi_Form_Woolcommmerce::FORM_FIELD_CARDHOLDER_ZIP_CODE => $order->billing_postcode
        ));
    }

    private function jsRedirect($url) {
        ?>
        <script type="text/javascript">
            var redirectUrl = "<?php echo $url ?>";
            if (String(window.location) != redirectUrl && String(window.location) != (redirectUrl + '/'))
                window.location = redirectUrl;
        </script>
        <noscript>
        <a href="<?php echo $url ?>">Click here to redirect</a>
        </noscript>
        <?php
    }

    /**
     * 
     * @param WC_Order $order
     */
    private function _isOrderPreauthorized($order) {
        $isProcessed = !in_array($order->get_status(), array('pending'));
        return $isProcessed;
    }

    private function corvusPayHandleSuccess() {
        global $woocommerce;

        $cpsi = $this->_getCpsi();
        if ($cpsi->isFeedbackSuccess()) {
            $order = $this->_getOrderFromRequestVar('order_number');
            if (!$order || $this->_isOrderPreauthorized($order)) {
                // If order is already completed, prevent any modification to order.
                $this->redirectToHome();
            }
            $woocommerce->cart->empty_cart();
            $order->payment_complete();

            if ($this->_options[CpsiKeys::OPTION_REQUIRE_COMPLETE]) {
                $order->update_status('on-hold');
            } else {
                $order->update_status('processing');
            }
        } else {
            $this->redirectToHome();
        }
    }

    private function corvusPayHandleFailure() {
        $cpsi = $this->_getCpsi();
        if (!$cpsi->isFeedbackFailure()) {
            $order_id = filter_input(INPUT_POST, 'order_number', FILTER_SANITIZE_NUMBER_INT);
            $order = $this->getOrder($order_id);
            $order->update_status('cancelled');
        } else {
            $this->redirectToHome();
        }
    }

    private function redirectToHome() {
        $url = home_url();
        $this->jsRedirect($url);
    }

    /**
     * 
     * @param numberic $orderId
     * @return \WC_Order
     */
    private function getOrder($orderId) {
        return new WC_Order($orderId);
    }

    private function getItemDescription(WC_Order $order) {
        $description = "";
        foreach ($order->get_items() as $item) {
            $description.=$item['name'] . " X " . $item['qty'] . "\n";
        }

        return $description;
    }

}
