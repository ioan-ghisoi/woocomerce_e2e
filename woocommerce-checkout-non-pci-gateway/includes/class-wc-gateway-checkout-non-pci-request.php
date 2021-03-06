<?php

/**
 * Class WC_Checkout_Non_Pci_Request
 *
 * @version 20160304
 */
class WC_Checkout_Non_Pci_Request
{
    /**
     * Constructor
     *
     * WC_Checkout_Non_Pci_Request constructor.
     * @param WC_Checkout_Non_Pci $gateway
     *
     * @version 20160304
     */
    public function __construct(WC_Checkout_Non_Pci $gateway) {
        $this->gateway = $gateway;
    }

    /**
     * Return Payment Action Type
     *
     * @return string
     *
     * @version 20160304
     */
    private function _isAutoCapture() {
        return $this->gateway->get_option('payment_action') === WC_Checkout_Non_Pci::PAYMENT_ACTION_AUTHORIZE
            ? false : true;
    }

    /**
     * Return Endpoint Mode from configuration
     *
     * @return mixed
     *
     * @version 20160313
     */
    protected function _getEndpointMode(){
        return $this->gateway->get_option('mode');
    }

    /**
     * Return order status for new order
     *
     * @return mixed
     *
     * @version 20160315
     */
    public function getOrderStatus() {
        return $this->gateway->get_option('order_status');
    }

    /**
     * Check if cancel order needed after void
     *
     * @return bool
     *
     * @version 20160316
     */
    public function getVoidOrderStatus() {
        return $this->gateway->get_option('void_status') == 'no' ? false : true;
    }

    /**
     * Capture Charge on Checkout.com
     *
     * @param WC_Order $order
     * @return array
     *
     * @version 20160315
     */
    public function capture(WC_Order $order) {
        include_once('class-wc-gateway-checkout-non-pci-validator.php');

        $Api        = CheckoutApi_Api::getApi(array('mode' => $this->_getEndpointMode()));
        $amount     = $Api->valueToDecimal($order->get_total(), $this->getOrderCurrency($order));
        $response   = array('status' => 'ok', 'message' => __('Checkout.com Capture Charge Approved.', 'woocommerce-checkout-non-pci'));

        $config         = array();
        $orderId        = $order->id;
        $secretKey      = $this->getSecretKey();

        $config['postedParam']['value']         = $amount;
        $config['postedParam']['trackId']       = $orderId;
        $config['postedParam']['description']   = 'capture description';
        $trackIdList                            = get_post_meta($orderId, '_transaction_id');

        $config['authorization']    = $secretKey;
        $config['chargeId']         = end($trackIdList);

        $result = $Api->captureCharge($config);

        if ($Api->getExceptionState()->hasError()) {
            $errorMessage = __('Transaction was not captured. '. $Api->getExceptionState()->getErrorMessage(). ' and try again or contact customer support.',
                'woocommerce-checkout-non-pci');

            WC_Checkout_Non_Pci::log($errorMessage);
            WC_Checkout_Non_Pci::log($Api->getExceptionState());

            $response['status']     = 'error';
            $response['message']    = $errorMessage;

            return $response;
        }

        if (!$result->isValid() || !WC_Checkout_Non_Pci_Validator::responseValidation($result)) {
            $errorMessage = __('Transaction was not captured. Try again or contact customer support.', 'woocommerce-checkout-non-pci');

            WC_Checkout_Non_Pci::log($errorMessage);
            WC_Checkout_Non_Pci::log($Api->getExceptionState());

            $response['status']     = 'error';
            $response['message']    = $errorMessage;

            return $response;
        }

        $entityId = $result->getId();

        update_post_meta($orderId, '_transaction_id', $entityId);

        $order->add_order_note(__("Checkout.com Capture Charge Approved (Transaction ID - {$entityId}, Parent ID - {$config['chargeId']})", 'woocommerce-checkout-non-pci'));

        if (function_exists('WC')) {
            $order->payment_complete();
        } else {
            // Record the sales
            $order->record_product_sales();

            // Increase coupon usage counts
            $order->increase_coupon_usage_counts();

            wp_set_post_terms($order->id, 'processing', 'shop_order_status', false);
            $order->add_order_note(sprintf( __( 'Order status changed from %s to %s.', 'woocommerce' ), __( $order->status, 'woocommerce' ), __('processing', 'woocommerce')));

            do_action('woocommerce_payment_complete', $order->id);

        }

        return $response;
    }

    /**
     * Void Charge on Checkout.com
     *
     * @param WC_Order $order
     * @return array
     *
     * @version 20160316
     */
    public function void(WC_Order $order) {
        include_once('class-wc-gateway-checkout-non-pci-validator.php');

        $response       = array('status' => 'ok', 'message' => __('Checkout.com your transaction has been successfully voided', 'woocommerce-checkout-non-pci'));
        $config         = array();
        $orderId        = $order->id;

        $config['postedParam']['trackId']       = $orderId;
        $config['postedParam']['description']   = 'Void Description';
        $trackIdList                            = get_post_meta($orderId, '_transaction_id');

        $config['authorization']    = $this->getSecretKey();
        $config['chargeId']         = end($trackIdList);

        $Api    = CheckoutApi_Api::getApi(array('mode' => $this->_getEndpointMode()));
        $result = $Api->voidCharge($config);

        if ($Api->getExceptionState()->hasError()) {
            $errorMessage = __('Transaction was not voided. '. $Api->getExceptionState()->getErrorMessage(). ' and try again or contact customer support.',
                'woocommerce-checkout-non-pci');

            WC_Checkout_Non_Pci::log($errorMessage);
            WC_Checkout_Non_Pci::log($Api->getExceptionState());


            $response['status']     = 'error';
            $response['message']    = $errorMessage;

            return $response;
        }

        if (!$result->isValid() || !WC_Checkout_Non_Pci_Validator::responseValidation($result)) {
            $errorMessage = __('Transaction was not voided. Try again or contact customer support.', 'woocommerce-checkout-non-pci');

            WC_Checkout_Non_Pci::log($errorMessage);
            WC_Checkout_Non_Pci::log($Api->getExceptionState());

            $response['status']     = 'error';
            $response['message']    = $errorMessage;

            return $response;
        }

        $entityId = $result->getId();

        update_post_meta($orderId, '_transaction_id', $entityId);

        $successMessage = __("Checkout.com Void Charge Approved (Transaction ID - {$entityId}, Parent ID - {$config['chargeId']})", 'woocommerce-checkout-non-pci');

        if (!$this->getVoidOrderStatus()) {
            $order->add_order_note($successMessage);
        } else {
            if (function_exists('WC')) {
                $order->update_status('cancelled', $successMessage);
            } else {
                $order->decrease_coupon_usage_counts();
                wp_set_post_terms($order->id, 'cancelled', 'shop_order_status', false);
                $order->add_order_note(sprintf( __( 'Order status changed from %s to %s.', 'woocommerce' ), __( $order->status, 'woocommerce' ), __('processing', 'woocommerce')));
            }
        }

        return $response;
    }

    /**
     * Refund Charge on Checkout.com
     *
     * @param WC_Order $order
     * @param $amount
     * @param $message
     * @return array
     *
     * @version 20160316
     */
    public function refund(WC_Order $order, $amount, $message) {
        include_once('class-wc-gateway-checkout-non-pci-validator.php');

        $response = array('status' => 'ok', 'message' => __('Checkout.com your transaction has been successfully refunded', 'woocommerce-checkout-non-pci'));

        $Api    = CheckoutApi_Api::getApi(array('mode' => $this->_getEndpointMode()));
        $totalAmount = $order->get_total();
        $totalAmount = $Api->valueToDecimal($totalAmount, $this->getOrderCurrency($order));

        $amount = empty($amount) ? $order->get_total() : $amount;
        $amount = $Api->valueToDecimal($amount, $this->getOrderCurrency($order));

        $config         = array();
        $orderId        = $order->id;

        $config['postedParam']['trackId']       = $orderId;
        $config['postedParam']['description']   = (string)$message;
        $config['postedParam']['value']         = $amount;
        $trackIdList                            = get_post_meta($orderId, '_transaction_id');

        $config['authorization']    = $this->getSecretKey();
        $config['chargeId']         = end($trackIdList);

        $result = $Api->refundCharge($config);

        if ($Api->getExceptionState()->hasError()) {
            $errorMessage = __('Transaction was not refunded. '. $Api->getExceptionState()->getErrorMessage(). ' and try again or contact customer support.',
                'woocommerce-checkout-non-pci');

            WC_Checkout_Non_Pci::log($errorMessage);
            WC_Checkout_Non_Pci::log($Api->getExceptionState());

            $response['status']     = 'error';
            $response['message']    = $errorMessage;

            return $response;
        }

        if (!$result->isValid() || !WC_Checkout_Non_Pci_Validator::responseValidation($result)) {
            $errorMessage = __('Transaction was not refunded. Try again or contact customer support.', 'woocommerce-checkout-non-pci');

            WC_Checkout_Non_Pci::log($errorMessage);
            WC_Checkout_Non_Pci::log($Api->getExceptionState());

            $response['status']     = 'error';
            $response['message']    = $errorMessage;

            return $response;
        }

        $entityId = $result->getId();

        update_post_meta($orderId, '_transaction_id', $entityId);

        $successMessage = __("Checkout.com Refund Charge Approved (Transaction ID - {$entityId}, Parent ID - {$config['chargeId']})", 'woocommerce-checkout-non-pci');

        if($totalAmount == $amount){
            $order->update_status('refunded', $successMessage);
        } else {
            $order->add_order_note( sprintf($successMessage) );
        }

        return $response;
    }

    /**
     * Check if order can be capture
     *
     * @param WC_Order $order
     * @return bool
     *
     * @version 20160314
     */
    public function canCapture(WC_Order $order) {
        $paymentMethod  = (string)get_post_meta($order->id, '_payment_method', true);

        if ($paymentMethod !== WC_Checkout_Non_Pci::PAYMENT_METHOD_CODE) {
            return false;
        }

        return true;
    }

    /**
     * Check payment method code
     *
     * @param WC_Order $order
     * @return bool
     *
     * @version 20160316
     */
    public function canVoid(WC_Order $order) {
        $paymentMethod  = (string)get_post_meta($order->id, '_payment_method', true);

        if ($paymentMethod !== WC_Checkout_Non_Pci::PAYMENT_METHOD_CODE) {
            return false;
        }

        return true;
    }

    /**
     * Verify Charge on Checkout.com
     *
     * @param $paymentToken
     * @return array
     *
     * @version 20160317
     */
    public function verifyCharge($paymentToken) {
        include_once('class-wc-gateway-checkout-non-pci-validator.php');

        global $woocommerce;

        $Api            = CheckoutApi_Api::getApi(array('mode' => $this->_getEndpointMode()));
        $verifyParams   = array('paymentToken' => $paymentToken, 'authorization' => $this->getSecretKey());
        $result         = $Api->verifyChargePaymentToken($verifyParams);
        $response       = array('status' => 'ok', 'message' => '', 'object' => array());

        if ($Api->getExceptionState()->hasError()) {
            $errorMessage = __('Your payment was not completed.'. $Api->getExceptionState()->getErrorMessage(). ' and try again or contact customer support.', 'woocommerce-checkout-non-pci');

            WC_Checkout_Non_Pci::log($errorMessage);
            WC_Checkout_Non_Pci::log($Api->getExceptionState());

            $response['status']     = 'error';
            $response['message']    = $errorMessage;

            return $response;
        }

        if (!$result->isValid() || !WC_Checkout_Non_Pci_Validator::responseValidation($result)) {
            $errorMessage = __('Please check you card details and try again. Thank you.', 'woocommerce-checkout-non-pci');

            WC_Checkout_Non_Pci::log($errorMessage);
            WC_Checkout_Non_Pci::log($Api->getExceptionState());

            $response['status']     = 'error';
            $response['message']    = $errorMessage;

            return $response;
        }

        $entityId   = $result->getId();
        $orderId    = $result->getTrackId();
        $order      = new WC_Order($orderId);

        if (!is_object($order) || !$order) {
            $errorMessage = 'Empty order data.';

            WC_Checkout_Non_Pci::log($errorMessage);

            $response['status']     = 'error';
            $response['message']    = $errorMessage;

            return $response;
        }

        $order->update_status($this->getOrderStatus(), __("Checkout.com Charge Approved (Transaction ID - {$entityId}", 'woocommerce-checkout-non-pci'));
        $order->reduce_order_stock();
        $woocommerce->cart->empty_cart();

        add_post_meta($orderId, '_transaction_id', $entityId, true);

        $response['object'] = $order;

        return $response;
    }

    /**
     * Return stored secret key
     *
     * @return mixed
     *
     * @version 20160321
     */
    public function getSecretKey() {
        return $this->gateway->get_option('secret_key');
    }

    /**
     * Get stored public key
     * @return mixed
     *
     * @version 20160321
     */
    public function getPublicKey(){
        return $this->gateway->get_option('public_key');
    }

    /**
     * Get stored private shared key
     *
     * @return mixed
     *
     * @version 20160415
     */
    public function getPrivateSharedKey(){
        return $this->gateway->get_option('private_shared_key');
    }

    /**
     * Get stored 3d mode
     *
     * @return mixed
     *
     * @version 20160322
     */
    public function getChargeMode() {
        return $this->gateway->get_option('is_3d');
    }

     /**
     * Get stored autoCapTime
     *
     * @return mixed
     *
     * @version 20171127
     */
    public function getAutoCapTime() {
        $autoCapTime = $this->gateway->get_option('auto_cap_time');
        
        if(empty($autoCapTime)){
            $autoCapTime = 0.02;
        }

        if (strpos($autoCapTime, ',') !== false) { 
           $autoCapTime = str_replace(',', '.', $autoCapTime);
        }

        return $autoCapTime;
    }

    /**
     * Get stored reactivate_cancel
     *
     * @return mixed
     *
     * @version 20180221
     */
    public function getRecurringSetting() {
        return $this->gateway->get_option('reactivate_cancel');
    }


    /**
     * Create Payment Token
     *
     * @param $amount
     * @param $currency
     * @return bool
     *
     * @version 20160322
     */
    public function createPaymentToken($amount, $currency) { 
        $Api            = CheckoutApi_Api::getApi(array('mode' => $this->_getEndpointMode()));
        $amount         = $Api->valueToDecimal($amount, $currency);;
        $autoCapture    = $this->_isAutoCapture();
        $result         = array();

        $tokenParams    = array(
            'authorization' => $this->getSecretKey(),
            'postedParam'   => array(
                'value'                 => $amount,
                'currency'              => $currency,
                'chargeMode'            => $this->getChargeMode(),
                'transactionIndicator'  => WC_Checkout_Non_Pci::TRANSACTION_INDICATOR_REGULAR,
                'customerIp'            => $this->get_ip_address(),
                'autoCapTime'           => $this->getAutoCapTime(),
                'autoCapture'           => $autoCapture ? CheckoutApi_Client_Constant::AUTOCAPUTURE_CAPTURE : CheckoutApi_Client_Constant::AUTOCAPUTURE_AUTH
            )
        );

        $paymentTokenCharge = $Api->getPaymentToken($tokenParams);

        if ($Api->getExceptionState()->hasError()) {
            $errorMessage = __('Your payment was not completed.'. $Api->getExceptionState()->getErrorMessage(). ' and try again or contact customer support.',
                'woocommerce-checkout-non-pci');

            WC_Checkout_Non_Pci::log($errorMessage);
            WC_Checkout_Non_Pci::log($Api->getExceptionState());

            return $result;
        }

        if (!$paymentTokenCharge->isValid()) {
            if($paymentTokenCharge->getEventId()) {
                $eventCode = $paymentTokenCharge->getEventId();
            }else {
                $eventCode = $paymentTokenCharge->getErrorCode();
            }

            $errorMessage = __($paymentTokenCharge->getExceptionState()->getErrorMessage().
                ' ( '.$eventCode.')', 'woocommerce-checkout-non-pci');

            WC_Checkout_Non_Pci::log($errorMessage);
            WC_Checkout_Non_Pci::log($Api->getExceptionState());

            return $result;
        } 

        $result = array(
            'token'     => $paymentTokenCharge->getId(),
            'amount'    => $amount,
            'currency'  => $currency
        );

        return $result;
    }

    /**
     * Create Charge on Checkout.com
     *
     * @param WC_Order $order
     * @param $chargeToken
     * @param $savedCardData
     * @return array
     *
     * @version 20160323
     */
    public function createCharge(WC_Order $order, $chargeToken, $savedCardData) { 

        $amount     = $order->get_total();
        $Api        = CheckoutApi_Api::getApi(array('mode' => $this->_getEndpointMode()));
        $amount     = $Api->valueToDecimal($amount, $this->getOrderCurrency($order));
        $chargeData = $this->_getChargeData($order, $chargeToken, $amount, $savedCardData);

        $result     = $Api->createCharge($chargeData);

        if ($Api->getExceptionState()->hasError()) {
            WC_Checkout_Non_Pci::log('-Your payment was not completed.');
            WC_Checkout_Non_Pci::log($Api->getExceptionState());

            $errorMessage = '-Your payment was not completed. Try again or contact customer support.';

            WC_Checkout_Non_Pci::log($errorMessage. '-'.$errorCode);
            WC_Checkout_Non_Pci::log($Api->getExceptionState()->getErrorMessage());
            return array('error' => $errorMessage);
        }

        if (!$result->isValid() || !WC_Checkout_Non_Pci_Validator::responseValidation($result)) {
            $errorMessage = "Please check you card details and try again. Thank you.";
            
            WC_Checkout_Non_Pci::log($errorMessage. '-' .$responseCode);
            WC_Checkout_Non_Pci::log($result->getResponseCode());
            return array('error' => $errorMessage);
        }

        return $result;
    }

    /**
     * Verify charge by payment token
     *
     * @param WC_Order $order
     * @param $paymentToken
     * @return array
     */
    public function verifyChargePaymentToken(WC_Order $order, $paymentToken) { 
        
        $Api            = CheckoutApi_Api::getApi(array('mode' => $this->_getEndpointMode()));
        $verifyParams   = array('paymentToken' => $paymentToken, 'authorization' => $this->getSecretKey());
        $result         = $Api->verifyChargePaymentToken($verifyParams);

        if ($Api->getExceptionState()->hasError()) {
            $errorMessage = 'Your payment was not completed.'. $Api->getExceptionState()->getErrorMessage(). ' and try again or contact customer support.';

            WC_Checkout_Non_Pci::log($errorMessage);
            WC_Checkout_Non_Pci::log($Api->getExceptionState());
            return array('error' => $errorMessage);
        }

        if (!$result->isValid() || !WC_Checkout_Non_Pci_Validator::responseValidation($result)) {
            $errorMessage = "Please check you card details and try again. Thank you. Response Code - {$result->getResponseCode()}";

            WC_Checkout_Non_Pci::log($errorMessage);
            WC_Checkout_Non_Pci::log($Api->getExceptionState());
            return array('error' => $errorMessage);
        }

        $Api->updateTrackId($result, $order->id);

        return $result;
    }

    /**
     * Return decorated data for create charge request
     *
     * @param WC_Order $order
     * @param $chargeToken
     * @param $amount
     * @param $savedCardData
     * @return mixed
     *
     * @version 20160323
     */
    private function _getChargeData(WC_Order $order, $chargeToken, $amount, $savedCardData) { 
        global $woocommerce;
        $cart = WC()->cart;

        $secretKey = $this->getSecretKey();
        $Api = CheckoutApi_Api::getApi(array('mode' => $this->_getEndpointMode()));
        $config = array();
        $autoCapture = $this->_isAutoCapture();
        $integrationType = $this->gateway->get_option('integration_type');

        /* START: Prepare data */
        $billingAddressConfig = array (
            'addressLine1'  => $order->billing_address_1,
            'addressLine2'  => $order->billing_address_2,
            'postcode'      => $order->billing_postcode,
            'country'       => $order->billing_country,
            'city'          => $order->billing_city,
            'state'         => $order->billing_state,
            'phone'         => array('number' => $order->billing_phone)
        );

        $products       = array();
        $productFactory = new WC_Product_Factory();

        foreach ($order->get_items() as $item) {
            $product        = $productFactory->get_product($item['product_id']);

            $productPrice = $product->get_price();
            if(is_null($productPrice)){
                $productPrice = 0;
            }

            $products[] = array(
                'description'   => (string)$product->post->post_content,
                'name'          => $item['name'],
                'price'         => $productPrice,
                'quantity'      => $item['qty'],
                'sku'           => $product->get_sku()
            );
        }

        /* END: Prepare data */

        $config['autoCapTime']  = $this->getAutoCapTime();
        $config['autoCapture']  = $autoCapture ? CheckoutApi_Client_Constant::AUTOCAPUTURE_CAPTURE : CheckoutApi_Client_Constant::AUTOCAPUTURE_AUTH;
        $config['chargeMode']   = $this->getChargeMode();
        $config['value']                = $amount;
        $config['currency']             = $this->getOrderCurrency($order);
        $config['trackId']              = $order->id;
        $config['customerName']         = $order->billing_first_name . ' ' . $order->billing_last_name;
        $config['transactionIndicator'] = WC_Checkout_Non_Pci::TRANSACTION_INDICATOR_REGULAR;
        $config['customerIp']           = $this->get_ip_address();

        if (!empty($savedCardData)) {
            $config['cardId'] = $savedCardData->card_id;

            global $wpdb;
            $tableName = $wpdb->prefix. 'checkout_customer_cards';
            $sql        = $wpdb->prepare("SELECT * FROM {$tableName} WHERE card_id = '%s';", $savedCardData->card_id);

            $result = $wpdb->get_results($sql);

            foreach ($result as $row) {
                $results = $row;
            }

            if($results->cko_customer_id){
                $config['customerId'] = $results->cko_customer_id;
            } else {
                $config['email'] = $order->billing_email;
            }
        } else {
            $config['cardToken'] = $chargeToken;
            $config['email'] = $order->billing_email;
        }

        $config['shippingDetails']  = $billingAddressConfig;
        $config['products']         = $products;

        /* Meta */
        $config['metadata'] = array(
            'server'            => get_site_url(),
            'quote_id'          => $order->id,
            'woo_version'       => property_exists($woocommerce, 'version') ? $woocommerce->version : '2.0',
            'plugin_version'    => WC_Checkout_Non_Pci::VERSION,
            'lib_version'       => CheckoutApi_Client_Constant::LIB_VERSION,
            'integration_type'  => $integrationType,
            'time'              => date('Y-m-d H:i:s')
        );

        // Check if order contains subscription
        if (class_exists('WC_Subscriptions_Order')) {
            $isSubscription = WC_Subscriptions_Order::order_contains_subscription($order);

            if ($isSubscription) {
                $recurringAmt = number_format(WC_Subscriptions_Order::get_recurring_total($order, $product_id = ''), 2, '.', '');
                $recurringAmount = $Api->valueToDecimal($recurringAmt, $this->getOrderCurrency($order));
                $intervalType = $this->getRecurringData()->intervalType;
                $recurringStartDate = $this->getRecurringData()->recurringStartDate;
                $interval = $this->getRecurringData()->interval;
                $recurringCount = $this->getRecurringData()->recurringCount;
                // Additional param to process recurring charge
                $config['paymentPlans'] = array(array(
                    'name' => $order->billing_first_name . '_' . $order->id,
                    'planTrackId' => $order->id,
                    'value' => $recurringAmount,
                    'cycle' => $interval . '' . $intervalType,
                    'recurringCount' => $recurringCount,
                    'autoCapTime' => $this->getAutoCapTime(),
                    'startDate' => $recurringStartDate //Next day of initial payment
                ));
            }
        }

        $result['authorization']    = $secretKey;
        $result['postedParam']      = $config;

        return $result;
    }

    public function getRecurringData(){

        // Get recurring data
        $intervalType = WC_Subscriptions_Cart::get_cart_subscription_period();
        $interval = WC_Subscriptions_Cart::get_cart_subscription_interval();
        $subscriptionLength = WC_Subscriptions_Cart::get_cart_subscription_length();

        if($subscriptionLength == 0){
            switch($intervalType)
            {
                case 'month';
                    $subscriptionLength = 83;
                    break;

                case 'day';
                    $subscriptionLength = 6993;
                    break;

                case 'week';
                     $subscriptionLength = 999;
                     break;

                case 'year';
                     $subscriptionLength = 19;
                     break;
            }
        }

        $recurringCount = $subscriptionLength - 1;
        // Get trial Info
        $trialLength = WC_Subscriptions_Cart::get_cart_subscription_trial_length();
        $trialPeriod = WC_Subscriptions_Cart::get_cart_subscription_trial_period();

        $cyclePeriod = 1;
        $transactionDate = time();

        switch($intervalType)
        {
            case 'month';
                $intervalType = 'm';
                $datetime = strtotime("+{$cyclePeriod} Month",$transactionDate);
                $recurringStartDate =  date('Y-m-d',$datetime);
                break;

            case 'day';
                $intervalType = 'd';
                $datetime = strtotime("+{$cyclePeriod} DAY",$transactionDate);
                $recurringStartDate =  date('Y-m-d',$datetime);
                break;

            case 'week';
                $intervalType = 'w';
                $datetime = strtotime("+{$cyclePeriod} week",$transactionDate);
                $recurringStartDate =  date('Y-m-d',$datetime);
                break;

            case 'year';
                $intervalType = 'y';
                $datetime = strtotime("+{$cyclePeriod} year",$transactionDate);
                $recurringStartDate =  date('Y-m-d',$datetime);
                break;
        }

        // get trial end period
        // d or m or y
        // Trial end day > $recurringStartDate, the $recurringStartDate = Trial end day

        $obj = (object) array(
            'intervalType' => $intervalType,
            'recurringStartDate' => $recurringStartDate,
            'interval' => $interval,
            'recurringCount' => $recurringCount
        );

        return $obj;
    }

    /**
     * Get current user IP Address.
     * @return string
     */
    public function get_ip_address() {
        if ( isset( $_SERVER['X-Real-IP'] ) ) {
            return $_SERVER['X-Real-IP'];
        } elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            // Proxy servers can send through this header like this: X-Forwarded-For: client1, proxy1, proxy2
            // Make sure we always only send through the first IP in the list which should always be the client IP.
            return trim( current( explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
        } elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return '';
    }

    /**
     * For old version
     *
     * @param WC_Order $order
     * @return string
     */
    public function getOrderCurrency(WC_Order $order) {
        if (method_exists($order, 'get_order_currency')) {
            return $order->get_order_currency();
        }

        if (property_exists($order, 'order_custom_fields') && !empty($order->order_custom_fields['_order_currency'])) {
            return $order->order_custom_fields['_order_currency'][0];
        }

        return get_woocommerce_currency();
    }
}