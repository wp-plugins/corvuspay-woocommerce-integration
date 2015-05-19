<?php

class Cpsi {

    /**
     *
     * @var Endpoint for testing purposes
     */
    const TEST_ENDPOINT = "https://testcps.corvus.hr/redirect/";
    const PRODUCTION_ENDPOINT = "https://cps.corvus.hr/redirect/";

    /**
     * Client shop ID
     * @var int
     */
    protected $storeId;

    /**
     * Client private key
     * @var string
     */
    protected $key;

    /**
     * Is debugging enabled
     * @var boolean
     */
    protected $debug;

    /**
     * 
     * @param type $storeId - Store ID
     * @param type $key - Shared secret 
     * @param type $debug - Debug mode flag
     * @throws InvalidArgumentException
     */
    public function __construct($storeId, $key, $debug = false) {

        // Check arguments
        if (!ctype_digit((string) $storeId) || !is_string($key)) {
            throw new InvalidArgumentException();
        }

        // Store arguments
        $this->storeId = (int) $storeId;
        $this->key = $key;
        $this->debug = (bool) $debug;
    }

    public function generateOrderHash($orderNumber, $amount, $currency, $bestBeforeTs = 0) {

        // Validate and filter variables
        $cleanOrderNumber = $this->_filterOrderNumber($orderNumber);
        $cleanAmmount = $this->_filterAmount($amount);
        $cleanCurrency = $this->_filterCurrency($currency);

        if ($bestBeforeTs > 0) {
            $cleanBestBeforeTs = $this->_filterBeforeTimestamp($bestBeforeTs);
        } else
            $cleanBestBeforeTs = 0;

        if ($cleanBestBeforeTs > 0) {
            $hash = sha1($this->key . ":" . $cleanOrderNumber . ":" . $cleanAmmount . ":" . strtoupper($cleanCurrency) . ":" . $cleanBestBeforeTs);
        } else {
            $hash = sha1($this->key . ":" . $cleanOrderNumber . ":" . $cleanAmmount . ":" . strtoupper($cleanCurrency));
        }
        return $hash;
    }

    /**
     * This method will validate if the current request is a success feedback
     * 
     * @param string $url Success URL prefix
     * @return boolean
     */
    public function isFeedbackSuccess() {

        // If order number is not present
        if (null == ( $postOrderNumber = $this->_getPost('order_number') )) {
            return false;
        }

        // Check for hash presence
        if (null == ( $postHash = $this->_getPost('hash'))) {
            return false;
        }

        // Generate hash that should match posted hash
        $validHash = sha1($this->key . $postOrderNumber);
        if ($validHash != $postHash)
            return false;

        return true;
    }

    /**
     * 
     */
    public function isFeedbackFailure() {
        try {
            $this->_filterOrderNumber($this->_getRequest('order_number'));
            $this->_filterLanguage($this->_getRequest('language'));
            return true;

            // Catch validation errors since we need to return boolean
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     *  Verify and filter order number
     * @param string|integer $orderNumber
     * @return string
     * @throws InvalidArgumentException
     */
    private function _filterOrderNumber($orderNumber) {
        if (!is_scalar((string) $orderNumber) || !$orderNumber) {
            throw new InvalidArgumentException(sprintf("Order number must be a numeric value: '%' given", htmlentities($orderNumber)));
        }
        return (string) $orderNumber;
    }

    /**
     * Verify and filter amount
     * @param int|double|float $amount
     * @return float
     * @throws InvalidArgumentException
     */
    private function _filterAmount($amount) {
        if ($amount <= 0) {
            throw new InvalidArgumentException(sprintf("Invalid amount specified: '%s' given", htmlentities($amount)));
        }
        return (float) $amount;
    }

    /**
     * Verify and filter currency
     * @param string $currency
     * @return string
     * @throws InvalidArgumentException
     */
    private function _filterCurrency($currency) {
        if (!is_string($currency) || strlen($currency) != 3) {
            $currency = htmlentities($currency);
            throw new InvalidArgumentException(sprintf("Invalid currency specified: '%s' given", is_scalar($currency) ? $currency : '<object>'));
        }

        return strtoupper($currency);
    }

    private function _filterLanguage($language) {
        if (!is_string($language) || strlen($language) != 2) {
            throw new InvalidArgumentException(sprintf("Provided language is not valid: '%' given", is_scalar($language) ? $language : '<object>'));
        }

        return strtolower($language);
    }

    /**
     * Verify and filter unix timestamp. The timestamp should be greather 
     * than current ts
     * @param int $ts
     * @return int
     */
    private function _filterBeforeTimestamp($ts) {
        if (!ctype_digit((string) $ts)) {
            throw new InvalidArgumentException(spritnf("Provided timestamp value is not valid: '%'", htmlentities($ts)));
        } else if ($ts < time()) {
            throw new InvalidArgumentException(sprintf("Provided timestamp is in the past: '%'", htmlentities($ts)));
        }

        return (int) $ts;
    }

    /**
     * 
     * @return bool
     */
    protected function _isPost() {
        // TODO verify that this check works with all major web servers (apache, nginx, cgi, fcgi, etc...)
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }

    /**
     * 
     * @return bool
     */
    protected function _isGet() {
        // TODO verify that this check works with all major web servers (apache, nginx, cgi, fcgi, etc...)
        return $_SERVER['REQUEST_METHOD'] == 'GET';
    }

    /**
     * 
     * @param string $name
     * @param string $default
     * @return string|null
     */
    protected function _getPost($name, $default = null) {
        if (isset($_POST[$name]))
            return $_POST[$name];
        else
            return $default;
    }

    /**
     * 
     * @param string $name
     * @param string $default
     * @return string|null
     */
    protected function _getGet($name, $default = null) {
        if (isset($_GET[$name]))
            return $_GET[$name];
        else
            return $default;
    }

    protected function _getRequest($name, $default = null) {
        if (isset($_REQUEST[$name]))
            return $_REQUEST[$name];
        else
            return $default;
    }

    /**
     * 
     * @return int
     */
    public function getStoreId() {
        return $this->storeId;
    }

    /**
     * @return bool
     */
    public function isDebug() {
        return $this->debug;
    }

}
