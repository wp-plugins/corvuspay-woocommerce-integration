<?php

abstract class Cpsi_Form_Abstract {

    /**
     * Form field constants
     */
    const FORM_FIELD_TARGET = 'target';
    const FORM_FIELD_MODE = 'mode';
    const FORM_FIELD_STORE_ID = 'store_id';
    const FORM_FIELD_ORDER_NUMBER = 'order_number';
    const FORM_FIELD_LANGUAGE = 'language';
    const FORM_FIELD_CURRENCY = 'currency';
    const FORM_FIELD_AMOUNT = 'amount';
    const FORM_FIELD_CART = 'cart';
    const FORM_FIELD_HASH = 'hash';
    const FORM_FIELD_REQUIRE_COMPLETE = 'require_complete';
    const FORM_FIELD_CARDHOLDER_NAME = 'cardholder_name';
    const FORM_FIELD_CARDHOLDER_SURNAME = 'cardholder_surname';
    const FORM_FIELD_CARDHOLDER_ADDRESS = 'cardholder_address';
    const FORM_FIELD_CARDHOLDER_CITY = 'cardholder_city';
    const FORM_FIELD_CARDHOLDER_ZIP_CODE = 'cardholder_zip_code';
    const FORM_FIELD_CARDHOLDER_COUNTRY = 'cardholder_country';
    const FORM_FIELD_CARDHOLDER_PHONE = 'cardholder_phone';
    const FORM_FIELD_CARDHOLDER_EMAIL = 'cardholder_email';
    const FORM_FIELD_SUBSCRIPTION = 'subscription';
    const FORM_FIELD_PAYMENT_NUMBER = 'payment_number';
    const FORM_FIELD_PAYMENT_ALL = 'payment_all';

    private $_defaultFormLabels = array(
        self::FORM_FIELD_TARGET => 'target',
        self::FORM_FIELD_MODE => 'mode',
        self::FORM_FIELD_STORE_ID => 'store_id',
        self::FORM_FIELD_ORDER_NUMBER => 'order_number',
        self::FORM_FIELD_LANGUAGE => 'language',
        self::FORM_FIELD_CURRENCY => 'currency',
        self::FORM_FIELD_AMOUNT => 'amount',
        self::FORM_FIELD_CART => 'cart',
        self::FORM_FIELD_HASH => 'hash',
        self::FORM_FIELD_REQUIRE_COMPLETE => 'require_complete',
        self::FORM_FIELD_CARDHOLDER_NAME => 'cardholder_name',
        self::FORM_FIELD_CARDHOLDER_SURNAME => 'cardholder_surname',
        self::FORM_FIELD_CARDHOLDER_ADDRESS => 'cardholder_address',
        self::FORM_FIELD_CARDHOLDER_CITY => 'cardholder_city',
        self::FORM_FIELD_CARDHOLDER_ZIP_CODE => 'cardholder_zip_code',
        self::FORM_FIELD_CARDHOLDER_COUNTRY => 'cardholder_country',
        self::FORM_FIELD_CARDHOLDER_PHONE => 'cardholder_phone',
        self::FORM_FIELD_CARDHOLDER_EMAIL => 'cardholder_email',
        self::FORM_FIELD_SUBSCRIPTION => 'subscription',
        self::FORM_FIELD_PAYMENT_NUMBER => 'payment_number',
        self::FORM_FIELD_PAYMENT_ALL => 'payment_all'
    );

    /**
     *
     * @var Cpsi
     */
    protected $cpsi;
    protected $target;
    protected $mode;
    protected $orderNumber;
    protected $language;
    protected $currency;
    protected $amount;
    protected $cart;
    protected $requireComplete;
    protected $bestBeforeTs;
    protected $hash;
    protected $storeId;
    protected $_validLanguages = array(
        'hr', 'en', 'it', 'de', 'fr', 'es', 'cz', 'hu', 'pl', 'ro', 'sk', 'sl',
        'ru', 'se', 'nl'
    );
    protected $_validCurrencies = array(
        'AUD', 'CAD', 'CZK', 'DKK', 'HUF', 'JPY', 'NOK', 'SKK', 'SEK',
        'CHF', 'GBP', 'USD', 'EUR', 'PLN', 'HRK'
    );

    /**
     *
     * @var array
     */
    protected $_optionalFormFieldValueParis = array();

    /**
     * 
     * @param Cpsi $cpsi
     * @param int $orderNumber
     * @param string $language
     * @param string $currency
     * @param float $amount
     * @param string $cart
     * @param string $requireComplete
     * @param int $bestBeforeTs
     */
    public function __construct($cpsi, $orderNumber, $language, $currency, $amount, $cart, $requireComplete, $bestBeforeTs = 0) {
        $this->cpsi = $cpsi;
        $this->orderNumber = $orderNumber;
        $this->language = $language;
        $this->currency = $currency;
        $this->amount = $amount;
        $this->cart = $cart;
        $this->requireComplete = ( (is_bool($requireComplete) && $requireComplete) || (is_string($requireComplete) && strtolower($requireComplete) == 'true')) ? 'true' : 'false';
        $this->bestBeforeTs = $bestBeforeTs;
    }

    /**
     * 
     * @return mixed
     */
    public function constructForm() {
        $this->_initFormFieldValues();
        $fieldDefinitions = $this->_constructFormFieldDefinitions();
        $finalFormFieldDefinitions = $this->_addOptionalFormFieldDefinitions($fieldDefinitions, $this->_getOptionalFormFieldValuePairs());
        return $this->_constructFormFromDefinition($finalFormFieldDefinitions);
    }

    /**
     * 
     * @param bool $enable
     */
    public function setEnableInstallments($enable = true) {
        if ($enable) {
            $this->_optionalFormFieldValueParis[self::FORM_FIELD_PAYMENT_ALL] = 'Y0299';
        } else {
            unset($this->_optionalFormFieldValueParis[self::FORM_FIELD_PAYMENT_ALL]);
        }
    }

    /**
     * Generate form
     */
    protected function _constructFormFromDefinition(array $formFields) {
        ?>

        <form id="corvus-autosubmit" method="POST" action="<?php echo $this->getAction() ?>">
            <?php foreach ($formFields as $name => $field) : ?>
                <input type="hidden" name="<?php echo $name ?>" 
                       value="<?= $field['value'] ?>"/>
                   <?php endforeach; ?>

            <script type="text/javascript">
                document.forms['corvus-autosubmit'].submit();
            </script>
        </form>
        <?php
    }

    /**
     * 
     * @param array $pairs
     */
    public function setOptionalFormFieldValuePairs(array $pairs) {
        $this->_optionalFormFieldValueParis = $pairs;
    }

    private function _getOptionalFormFieldValuePairs() {
        return $this->_optionalFormFieldValueParis;
    }

    protected function _initFormFieldValues() {

        $this->target = '_top';
        $this->mode = 'form';
        $this->storeId = $this->cpsi->getStoreId();
        $this->language = in_array($this->language, $this->_validLanguages) ?
                $this->language : 'hr';
        if (!in_array($this->currency, $this->_validCurrencies)) {
            throw new RuntimeException('Invalid currency specified');
        }
        if ($this->bestBeforeTs > 0) {
            $this->hash = $this->cpsi->generateOrderHash($this->orderNumber, $this->amount, $this->currency);
        } else {
            $this->hash = $this->cpsi->generateOrderHash($this->orderNumber, $this->amount, $this->currency, $this->bestBeforeTs);
        }
    }

    /**
     * Get form action URl
     * @return string
     */
    public function getAction() {
        if ($this->cpsi->isDebug()) {
            return Cpsi::TEST_ENDPOINT;
        } else {
            return Cpsi::PRODUCTION_ENDPOINT;
        }
    }

    /**
     * Generage abstract form structure used by specific form generator
     * 
     * @return array
     */
    protected function _constructFormFieldDefinitions() {
        return array(
            self::FORM_FIELD_TARGET => array(
                'value' => $this->target,
                'label' => $this->_getFormLabel(self::FORM_FIELD_TARGET),
                'type' => 'hidden'
            ),
            self::FORM_FIELD_MODE => array(
                'value' => $this->mode,
                'label' => $this->_getFormLabel(self::FORM_FIELD_MODE),
                'type' => 'hidden'
            ),
            self::FORM_FIELD_STORE_ID => array(
                'value' => $this->storeId,
                'label' => $this->_getFormLabel(self::FORM_FIELD_STORE_ID),
                'type' => 'hidden'
            ),
            self::FORM_FIELD_ORDER_NUMBER => array(
                'value' => $this->orderNumber,
                'label' => $this->_getFormLabel(self::FORM_FIELD_ORDER_NUMBER),
                'type' => 'hidden'
            ),
            self::FORM_FIELD_LANGUAGE => array(
                'value' => $this->language,
                'label' => $this->_getFormLabel(self::FORM_FIELD_LANGUAGE),
                'type' => 'hidden'
            ),
            self::FORM_FIELD_CURRENCY => array(
                'value' => $this->currency,
                'label' => $this->_getFormLabel(self::FORM_FIELD_CURRENCY),
                'type' => 'hidden'
            ),
            self::FORM_FIELD_AMOUNT => array(
                'value' => $this->amount,
                'label' => $this->_getFormLabel(self::FORM_FIELD_AMOUNT),
                'type' => 'hidden'
            ),
            self::FORM_FIELD_CART => array(
                'value' => $this->cart,
                'label' => $this->_getFormLabel(self::FORM_FIELD_CART),
                'type' => 'hidden'
            ),
            self::FORM_FIELD_HASH => array(
                'value' => $this->hash,
                'label' => $this->_getFormLabel(self::FORM_FIELD_HASH),
                'type' => 'hidden'
            ),
            self::FORM_FIELD_REQUIRE_COMPLETE => array(
                'value' => $this->requireComplete,
                'label' => $this->_getFormLabel(self::FORM_FIELD_REQUIRE_COMPLETE),
                'type' => 'hidden'
            )
        );
    }

    /**
     * 
     * @param string $value
     * @param string $label
     * @param string $type
     * @return type
     */
    private function _makeFormFieldDefinition($value, $label, $type = 'hidden') {
        return array(
            'value' => $value,
            'label' => $label,
            'type' => $type
        );
    }

    /**
     * 
     * @param string $fieldName
     * @return string
     */
    private function _getFormLabel($fieldName) {
        if (!in_array($fieldName, array_keys($this->_defaultFormLabels))) {
            return $fieldName;
        }

        return $this->_defaultFormLabels[$fieldName];
    }

    /**
     * 
     * @param array $formFieldDefinitions
     * @param array $formValues
     * @return array
     */
    protected function _addOptionalFormFieldDefinitions(array $formFieldDefinitions, array $formValues) {
        $optionalFormFieldDefinitions = array();

        foreach ($formValues as $key => $value) {
            switch ($key) {
                case self::FORM_FIELD_CARDHOLDER_NAME:
                case self::FORM_FIELD_CARDHOLDER_SURNAME:
                case self::FORM_FIELD_CARDHOLDER_ADDRESS:
                case self::FORM_FIELD_CARDHOLDER_CITY:
                case self::FORM_FIELD_CARDHOLDER_ZIP_CODE:
                case self::FORM_FIELD_CARDHOLDER_COUNTRY:
                case self::FORM_FIELD_CARDHOLDER_PHONE:
                case self::FORM_FIELD_CARDHOLDER_EMAIL:
                case self::FORM_FIELD_SUBSCRIPTION:
                case self::FORM_FIELD_PAYMENT_NUMBER:
                case self::FORM_FIELD_PAYMENT_ALL:
                    $optionalFormFieldDefinitions[$key] = $this->_makeFormFieldDefinition($value, $key);
                    break;
                default:
                    continue;
            }
        }

        return $formFieldDefinitions + $optionalFormFieldDefinitions;
    }

}
