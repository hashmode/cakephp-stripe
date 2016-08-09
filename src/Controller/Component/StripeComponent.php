<?php
namespace CakephpStripe\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Error\Card;
use Stripe\Error\InvalidRequest;
use Stripe\Error\Authentication;
use Stripe\Error\ApiConnection;
use Stripe\Error\Base;
use Stripe\Charge;
use Stripe\Plan;
use Stripe\Coupon;
use Stripe\Event;

/**
 * Stripe Component
 *
 */
class StripeComponent extends Component
{
    /**
     * Stripe mode, can be 'Live' or 'Test'
     *
     * @var string
     */
    public $mode = 'Test';
    
    
    /**
     * Stripe API Secret key
     *
     * @var string
     */
    public $key = null;
    
    
    /**
     * Stripe currency, default is 'usd'
     *
     * @var string
     */
    public $currency = 'usd';
    
    
    /**
     *
     * If provided, statuses will be saved in that file, default is false
     * if enabled, log should be added in bootstrap, e.g. if log file should be in tmp/logs/stripe.log
     *
     * @var string
     */
    public $logFile = false;
    
    
    /**
     * Can be 'both', 'success' or 'error', to what results to save, default is 'error'
     *
     * @var string
     */
    public $logType = 'error';
    
    /**
     *  For saving the reflection class, to use in the loop
     *
     * @var array
     */
    protected $reflectionClass = [];


    public function initialize(array $config)
    {
        parent::initialize($config);
        
        // if mode is not set in bootstrap, defaults to 'Test'
        $mode = Configure::read('Stripe.mode');
        if ($mode) {
            $this->mode = $mode;
        }

        // set the Stripe API key
        $this->key = Configure::read('Stripe.' . $this->mode . 'Secret');
        if (!$this->key) {
            throw new Exception('Stripe API Secret key is not set');
        }
        
        // if currency is not set, defaults to 'usd'
        $currency = Configure::read('Stripe.currency');
        if ($currency) {
            $this->currency = strtolower($currency);
        }
    }
    
    

    
    
    
    
    
    
    
    
    
    
    
    
    
    /**
     * getCents method
     * returns price in cents
     * 
     * @param number $price
     * @return number
     */
    public function getCents($price) {
        return $price*100;
    }
    
    
    /**
     * getUsd method
     * 
     * @param string $price
     * @param boolean $currency
     * @return string
     */
    public function getUsd($price, $currency = false) {
        return number_format($price/100, 2).($currency ? ' '.strtoupper($this->currency) : '');
    }
    
    
    
    
    
    
    /**
     * charge method
     * Charges the given credit card(card id, array or token) or customer
     *
     * @param array $data
     * @param string $customerId[optional]
     * @return array
     *
     * @link https://stripe.com/docs/api#create_charge
     */
    public function charge($data = null, $customerId = null) {
        if (!$customerId && empty($data['card']) && empty($data['source'])) {
            throw new Exception(__('Customer Id or Card is required'));
        }
    
        if ($customerId) {
            $data['customer'] = $customerId;
        }
    
        // set default currency
        if (!isset($data['currency'])) {
            $data['currency'] = $this->currency;
        }
    
        return $this->request(__FUNCTION__, $data);
    }
    
    /**
     * retrieveCharge method
     * Retrieves the details of a charge that has previously been created
     *
     * @param string $chargeId
     * @return array
     *
     * @link https://stripe.com/docs/api#retrieve_charge
     */
    public function retrieveCharge($chargeId = null) {
        if (!$chargeId) {
            throw new Exception(__('Charge Id is required'));
        }
    
        $data = [
            'charge_id' => $chargeId
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    
    /**
     * updateCharge method
     * Updates the specified charge
     *
     * @param string $chargeId
     * @param array $data
     * @return array
     *
     * @link https://stripe.com/docs/api#update_charge
     */
    public function updateCharge($chargeId = null, $data = []) {
        if (!$chargeId) {
            throw new Exception(__('Charge Id is not provided'));
        }
    
        if (empty($data)) {
            throw new Exception(__('No data is provided to updates the card'));
        }
    
        $data = [
            'charge_id' => $chargeId,
            'fields' => $data,
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    
    /**
     * refundCharge method
     * Refunds a charge that has previously been created but not yet refunded
     *
     * @param string $chargeId
     * @param array $data
     * @return array
     *
     * @link https://stripe.com/docs/api#refund_charge
     */
    public function refundCharge($chargeId = null, $data = []) {
        if (!$chargeId) {
            throw new Exception(__('Charge Id is not provided'));
        }
    
        $data['charge_id'] = $chargeId;
    
        return $this->request(__FUNCTION__, $data);
    }
    
    
    /**
     * captureCharge method
     * Capture the payment of an existing, uncaptured, charge.
     *
     * @param string $chargeId
     * @param array $data
     * @return array
     *
     * @link https://stripe.com/docs/api#charge_capture
     */
    public function captureCharge($chargeId = null, $data = []) {
        if (!$chargeId) {
            throw new Exception(__('Charge Id is not provided'));
        }
    
        $data['charge_id'] = $chargeId;
    
        return $this->request(__FUNCTION__, $data);
    }
    
    
    /**
     * listCharges method
     * Returns a list of charges you've previously created
     *
     * @param array $data
     * @return array
     *
     * @link https://stripe.com/docs/api#list_charges
     */
    public function listCharges($data = []) {
        $data = array(
            'options' => $data
        );
    
        return $this->request(__FUNCTION__, $data);
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    /**
     * createCustomer method
     * Creates a new customer
     *
     * @param array	$data - according to customer object
     * @return array
     *
     * @link  https://stripe.com/docs/api/php#create_customers
     */
    public function createCustomer($data) {
        if (empty($data) || !is_array($data)) {
            throw new Exception(__('Data is empty or is not an array'));
        }
    
        return $this->request(__FUNCTION__, $data);
    }
    
    
    /**
     * retrieveCustomer method
     * Retrives the customer information
     *
     * @param string $customerId
     * @return array
     *
     * @link https://stripe.com/docs/api/php#retrieve_customer
     */
    public function retrieveCustomer($customerId = null) {
        if (!$customerId) {
            throw new Exception(__('Customer Id is not provided'));
        }
    
        $data = [
            'customer_id' => $customerId
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    
    /**
     * updateCustomer method
     * Updates the customer info
     *
     * @param string $customerId
     * @param array $fields - fields to be updated
     * @return array
     *
     * @link https://stripe.com/docs/api/php#update_customer
     */
    public function updateCustomer($customerId = null, $fields = []) {
        if (!$customerId) {
            throw new Exception(__('Customer Id is not provided'));
        }
    
        if (empty($fields)) {
            throw new Exception(__('Update fields are empty'));
        }
    
        $data = [
            'customer_id' => $customerId,
            'fields' => $fields
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    
    /**
     * deleteCustomer method
     * Deletes the customer
     *
     * @param string $customerId
     * @return array
     *
     * @link https://stripe.com/docs/api/php#delete_customer
     */
    public function deleteCustomer($customerId = null) {
        if (!$customerId) {
            throw new Exception(__('Customer Id is not provided'));
        }
    
        $data = [
            'customer_id' => $customerId
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    /**
     * listCustomers method
     * Returns array with customers
     *
     * As this is an expensive call(Reflection class is used to convert objects to arrays) use limit wisely
     *
     * @param array $data
     * @param array $cards - default is false, if true each customers cards will be returned as array
     * @param array $subscriptions - default is false, if true each customers subscriptions will be returned as array
     * @return array
     *
     * @link https://stripe.com/docs/api/php#list_customers
     */
    public function listCustomers($data = []) {
        $data = [
            'options' => $data
        ];
        
        return $this->request(__FUNCTION__, $data);
    }
    
    
    
    
    /**
     * createCard method
     * Creates a new card for the given customer
     *
     * @param string $customerId
     * @param mixed $data - card data, token or array
     * @return array
     *
     * @link https://stripe.com/docs/api/php#create_card
     */
    public function createCard($customerId = null, $card = null) {
        if (!$customerId) {
            throw new Exception(__('Customer Id is not provided'));
        }
    
        if (!$card) {
            throw new Exception(__('Card data is not provided'));
        }
    
        $metadata = [];
        if (!empty($card['metadata'])) {
            $metadata = $card['metadata'];
            unset($card['metadata']);
        }
    
        $card['object'] = 'card';
        $data = [
            'customer_id' => $customerId,
            'source' => $card
        ];
    
        if (!empty($metadata)) {
            $data['metadata'] = $metadata;
        }
    
        return $this->request(__FUNCTION__, $data);
    }
    
    /**
     * retrieveCard method
     * Retrives an existing card for the given customer
     *
     * @param string $customerId
     * @param string $cardId
     * @return array
     *
     * @link https://stripe.com/docs/api/php#retrieve_card
     */
    public function retrieveCard($customerId = null, $cardId = null) {
        if (!$customerId) {
            throw new Exception(__('Customer Id is not provided'));
        }
    
        if (!$cardId) {
            throw new Exception(__('Card Id is not provided'));
        }
    
        $data = [
            'customer_id' => $customerId,
            'card_id' => $cardId
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    /**
     * updateCard method
     * Updates an existing card for the given customer
     *
     * @param string $customerId
     * @param string $cardId
     * @param array $data
     * @return array
     *
     * @link https://stripe.com/docs/api/php#update_card
     */
    public function updateCard($customerId = null, $cardId = null, $data = []) {
        if (!$customerId) {
            throw new Exception(__('Customer Id is not provided'));
        }
    
        if (!$cardId) {
            throw new Exception(__('Card Id is not provided'));
        }
    
        if (empty($data)) {
            throw new Exception(__('No data is provided to updates the card'));
        }
    
        $data = [
            'customer_id' => $customerId,
            'card_id' => $cardId,
            'fields' => $data,
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    /**
     * deleteCard method
     * Deletes an existing card for the given customer
     *
     * @param string $customerId
     * @param string $cardId
     * @return array
     *
     * @link https://stripe.com/docs/api/php#delete_card
     */
    public function deleteCard($customerId = null, $cardId = null) {
        if (!$customerId) {
            throw new Exception(__('Customer Id is not provided'));
        }
    
        if (!$cardId) {
            throw new Exception(__('Card Id is not provided'));
        }
    
        $data = [
            'customer_id' => $customerId,
            'card_id' => $cardId
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    /**
     * listCards method
     * Returs cards for the given customer
     *
     * @param string $customerId
     * @param string $cardId
     * @return array
     *
     * @link https://stripe.com/docs/api/php#list_cards
     */
    public function listCards($customerId = null, $data = []) {
        if (!$customerId) {
            throw new Exception(__('Customer Id is not provided'));
        }
    
        $data = [
            'customer_id' => $customerId,
            'card_id' => $cardId
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    
    
    
    
    
    
    
    
    
    /**
     * createSubscription method
     * Creates a new subscription for the given customer
     *
     * @param int $customerId
     * @param array $data - subscription data, token or array
     * @return array
     *
     * @link https://stripe.com/docs/api/php#create_subscription
     */
    public function createSubscription($customerId = null, $subscription = null) {
        if (!$customerId) {
            throw new Exception(__('Customer Id is not provided'));
        }
    
        if (!$subscription) {
            throw new Exception(__('Subscription data is not provided'));
        }
    
        $data = [
            'customer_id' => $customerId,
            'subscription' => $subscription
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    /**
     * retrieveSubscription method
     * Retrives an existing subscription for the given customer
     *
     * @param string $customerId
     * @param string $subscriptionId
     * @return array
     *
     * @link https://stripe.com/docs/api/php#retrieve_subscription
     */
    public function retrieveSubscription($customerId = null, $subscriptionId = null) {
        if (!$customerId) {
            throw new Exception(__('Customer Id is not provided'));
        }
    
        if (!$subscriptionId) {
            throw new Exception(__('Subscription Id is not provided'));
        }
    
        $data = [
            'customer_id' => $customerId,
            'subscription_id' => $subscriptionId
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    /**
     * updateSubscription method
     * Updates an existing subscription for the given customer
     *
     * @param string $customerId
     * @param string $subscriptionId
     * @param array $data
     * @return array
     *
     * @link https://stripe.com/docs/api/php#update_subscription
     */
    public function updateSubscription($customerId = null, $subscriptionId = null, $data = []) {
        if (!$customerId) {
            throw new Exception(__('Customer Id is not provided'));
        }
    
        if (!$subscriptionId) {
            throw new Exception(__('Subscription Id is not provided'));
        }
    
        if (empty($data)) {
            throw new Exception(__('No data is provided to update the subscription'));
        }
    
        $data = [
            'customer_id' => $customerId,
            'subscription_id' => $subscriptionId,
            'fields' => $data,
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    /**
     * cancelSubscription method
     * Cancels an existing subscription for the given customer
     *
     * @param string $customerId
     * @param string $subscriptionId
     * @return array
     *
     * @link https://stripe.com/docs/api/php#cancel_subscription
     */
    public function cancelSubscription($customerId = null, $subscriptionId = null, $atPeriodEnd = false) {
        if (!$customerId) {
            throw new Exception(__('Customer Id is not provided'));
        }
    
        if (!$subscriptionId) {
            throw new Exception(__('Subscription Id is not provided'));
        }
    
        $data = [
            'at_period_end' => $atPeriodEnd,
            'customer_id' => $customerId,
            'subscription_id' => $subscriptionId
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    /**
     * listSubscriptions method
     * Returs subscriptions for the given customer
     *
     * @param string $customerId
     * @param string $subscriptionId
     * @return array
     *
     * @link https://stripe.com/docs/api/php#list_subscriptions
     */
    public function listSubscriptions($customerId = null, $data = []) {
        if (!$customerId) {
            throw new Exception(__('Customer Id is not provided'));
        }
    
        $data = [
            'customer_id' => $customerId,
            'options' => $data
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    
    
    
    
    
    
    
    
    
    
    /**
     * createPlan method
     * Creates a new subscription plan
     *
     * @param array $data
     * @return array
     *
     * @link https://stripe.com/docs/api/php#create_plan
     */
    public function createPlan($data = array()) {
        if (empty($data) || !is_array($data)) {
            throw new Exception(__('Data is empty or is not an array'));
        }
    
        // set default currency
        if (!isset($data['currency'])) {
            $data['currency'] = $this->currency;
        }
    
        return $this->request(__FUNCTION__, $data);
    }
    
    
    /**
     * retrievePlan method
     * Retrieves the existing subscription plan
     *
     * @param string $planId
     * @return array
     *
     * @link https://stripe.com/docs/api/php#retrieve_plan
     */
    public function retrievePlan($planId = null) {
        if (!$planId) {
            throw new Exception(__('Plan Id is required'));
        }
    
        $data = [
            'plan_id' => $planId
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    /**
     * updatePlan method
     * Updates the existing subscription plan
     *
     * @param string $planId
     * @return array
     *
     * @link https://stripe.com/docs/api/php#update_plan
     */
    public function updatePlan($planId = null, $data = array()) {
        if (!$planId) {
            throw new Exception(__('Plan Id is required'));
        }
    
        if (empty($data)) {
            throw new Exception(__('No data is provided to updates the plan'));
        }
    
        $data = [
            'plan_id' => $planId,
            'fields' => $data
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    /**
     * deletePlan method
     * Deletes the existing subscription plan
     *
     * @param string $planId
     * @return array
     *
     * @link https://stripe.com/docs/api/php#delete_plan
     */
    public function deletePlan($planId = null) {
        if (!$planId) {
            throw new Exception(__('Plan Id is required'));
        }
    
        $data = [
            'plan_id' => $planId
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    /**
     * listPlans method
     * Returns all the plans
     *
     * @param array $data
     * @return array
     *
     * @link https://stripe.com/docs/api/php#list_plans
     */
    public function listPlans($data = array()) {
        $data = [
            'options' => $data
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    /**
     * createCoupon method
     * Creates a new coupon
     *
     * @param array $data
     * @return array
     *
     * @link https://stripe.com/docs/api/php#create_coupon
     */
    public function createCoupon($data = array()) {
        if (empty($data) || !is_array($data)) {
            throw new Exception(__('Data is empty or is not an array'));
        }
    
        // set default currency
        if (!isset($data['currency'])) {
            $data['currency'] = $this->currency;
        }
    
        return $this->request(__FUNCTION__, $data);
    }
    
    
    /**
     * retrieveCoupon method
     * Retrieves the existing coupon
     *
     * @param string $couponId
     * @return array
     *
     * @link https://stripe.com/docs/api/php#retrieve_coupon
     */
    public function retrieveCoupon($couponId = null) {
        if (!$couponId) {
            throw new Exception(__('Coupon Id is required'));
        }
    
        $data = [
            'coupon_id' => $couponId
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    /**
     * deleteCoupon method
     * Deletes the existing coupon
     *
     * @param string $couponId
     * @return array
     *
     * @link https://stripe.com/docs/api/php#delete_coupon
     */
    public function deleteCoupon($couponId = null) {
        if (!$couponId) {
            throw new Exception(__('Coupon Id is required'));
        }
    
        $data = [
            'coupon_id' => $couponId,
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    /**
     * listCoupons method
     * Returns all the plans
     *
     * @param array $data
     * @return array
     *
     * @link https://stripe.com/docs/api/php#list_coupons
     */
    public function listCoupons($data = array()) {
        $data = [
            'options' => $data
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    
    /**
     * deleteCustomerDiscount method
     * Removes the currently applied discount on a customer
     *
     * @param string $customerId
     * @return array
     *
     * @link https://stripe.com/docs/api/php#delete_discount
     */
    public function deleteCustomerDiscount($customerId = null) {
        if (!$customerId) {
            throw new Exception(__('Customer Id is not provided'));
        }
    
        $data = [
            'customer_id' => $customerId
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    
    
    
    
    
    /**
     * retrievePlan method
     * Retrieves the details of an event
     *
     * @param string $eventId
     * @return array
     *
     * @link https://stripe.com/docs/api#retrieve_event
     */
    public function retrieveEvent($eventId = null) {
        if (!$eventId) {
            throw new Exception(__('Event Id is required'));
        }
    
        $data = [
            'event_id' => $eventId
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    /**
     * listEvents method
     * List events, going back up to 30 days.
     *
     * @param array $data
     * @return array
     *
     * @link https://stripe.com/docs/api#list_events
     */
    public function listEvents($data = array()) {
        $data = [
            'options' => $data,
        ];
    
        return $this->request(__FUNCTION__, $data);
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    /**
     * request method
     *
     * @param string $method
     * @param array $data
     *
     * @return array - containing 'status', 'message' and 'data' keys
     * 					if response was successful, keys will be 'success', 'Success' and the stripe response as associated array respectively,
     *   				if request failed, keys will be 'error', the card error message if it was card_error, boolen false otherwise, and
     *   								error data as an array respectively
     */
    private function request($method = null, $data = null) {
        if (!$method) {
            throw new Exception(__('Request method is missing'));
        }
        if (is_null($data)) {
            throw new Exception(__('Request Data is not provided'));
        }
    
        Stripe::setApiKey($this->key);
    
        $success = null;
        $error = null;
        $message = false;
        $log = null;
        
        try {
            switch ($method) {
                /**
                 *
                 * 		CHARGES
                 *
                 */
                case 'charge':
                    $success = $this->fetch(Charge::create($data));
                    break;
                case 'retrieveCharge':
                    $success = $this->fetch(Charge::retrieve($data['charge_id']));

                    if (!empty($success['refunds'])) {
                        foreach ($success['refunds'] as &$refund) {
                            $refund = $this->fetch($refund);
                        }
                    }
                
                    break;
                case 'updateCharge':
                    $charge = Charge::retrieve($data['charge_id']);
                    	
                    foreach ($data['fields'] as $field => $value) {
                        $charge->$field = $value;
                    }
                
                    $success = $this->fetch($charge->save());
                    break;
                case 'refundCharge':
                    $charge = Charge::retrieve($data['charge_id']);
                
                    // to prevent unknown param error
                    unset($data['charge_id']);
                    $success = $this->fetch($charge->refund($data));
                
                    foreach ($success['refunds']['data'] as &$refund) {
                        $refund = $this->fetch($refund);
                    }
                    break;
                case 'captureCharge':
                    $charge = Charge::retrieve($data['charge_id']);
                
                    unset($data['charge_id']);
                    $success = $this->fetch($charge->capture($data));
                
                    if (!empty($success['refunds']['data'])) {
                        foreach ($success['refunds']['data'] as &$refund) {
                            $refund = $this->fetch($refund);
                        }
                    }
                
                    break;
                case 'listCharges':
                    $charges = Charge::all();
                    $success = $this->fetch($charges);
                    	
                    foreach ($success['data'] as &$charge) {
                        $charge = $this->fetch($charge);
                        	
                        if (isset($charge['refunds']['data']) && !empty($charge['refunds']['data'])) {
                            foreach ($charge['refunds']['data'] as &$refund) {
                                $refund = $this->fetch($refund);
                            }
                            unset($refund);
                        }
                    }
                    	
                    break;
                
                
                
                
                
                
                
                    /**
                     * 		CUSTOMERS
                     */
                case 'createCustomer':
                    $customer = Customer::create($data);
                    $success = $this->fetch($customer);
                    	
                    if (!empty($success['cards']['data'])) {
                        foreach ($success['cards']['data'] as &$card) {
                            $card = $this->fetch($card);
                        }
                        unset($card);
                    }
                
                    if (!empty($success['subscriptions']['data'])) {
                        foreach ($success['subscriptions']['data'] as &$subscription) {
                            $subscription = $this->fetch($subscription);
                        }
                        unset($subscription);
                    }
                    	
                    break;
                case 'retrieveCustomer':
                    $customer = Customer::retrieve($data['customer_id']);
                    $success = $this->fetch($customer);
                    	
                    if (!empty($success['cards']['data'])) {
                        foreach ($success['cards']['data'] as &$card) {
                            $card = $this->fetch($card);
                        }
                        unset($card);
                    }
                    	
                    if (!empty($success['subscriptions']['data'])) {
                        foreach ($success['subscriptions']['data'] as &$subscription) {
                            $subscription = $this->fetch($subscription);
                        }
                        unset($subscription);
                    }
                
                    break;
                case 'updateCustomer':
                    $cu = Customer::retrieve($data['customer_id']);
                    	
                    foreach ($data['fields'] as $field => $value) {
                        $cu->$field = $value;
                    }
                    	
                    $success = $this->fetch($cu->save());
                    	
                    if (!empty($success['cards']['data'])) {
                        foreach ($success['cards']['data'] as &$card) {
                            $card = $this->fetch($card);
                        }
                        unset($card);
                    }
                    	
                    if (!empty($success['subscriptions']['data'])) {
                        foreach ($success['subscriptions']['data'] as &$subscription) {
                            $subscription = $this->fetch($subscription);
                        }
                        unset($subscription);
                    }
                    	
                    break;
                case 'deleteCustomer':
                    $cu = Customer::retrieve($data['customer_id']);
                    $success = $this->fetch($cu->delete());
                    	
                    break;
                case 'listCustomers':
                    $customers = Customer::all($data['options']);
                    $success = $this->fetch($customers);
                    	
                    foreach ($success['data'] as &$customer) {
                        $customer = $this->fetch($customer);
                
                        if (!empty($customer['cards']['data'])) {
                            foreach ($customer['cards']['data'] as &$card) {
                                $card = $this->fetch($card);
                            }
                            unset($card);
                        }
                
                        if (!empty($customer['subscriptions']['data'])) {
                            foreach ($customer['subscriptions']['data'] as &$subscription) {
                                $subscription = $this->fetch($subscription);
                            }
                            unset($subscription);
                        }
                    }
                    	
                    break;
                    	
                    	
                    /**
                     * 		CARDS
                     *
                     */
                case 'createCard':
                    $cu = Customer::retrieve($data['customer_id']);
                
                    $validCardFields = [
                        'object',
                        'address_zip',
                        'address_city',
                        'address_state',
                        'address_country',
                        'address_line1',
                        'address_line2',
                        'number',
                        'exp_month',
                        'exp_year',
                        'cvc',
                        'name',
                        'metadata'
                    ];
                    	
                    // unset not valid keys to prevent unknown parameter stripe error
                    unset($data['customer_id']);
                    foreach ($data['source'] as $k => $v) {
                        if (!in_array($k, $validCardFields)) {
                            unset($data['source'][$k]);
                        }
                    }

                    $card = $cu->sources->create($data);
                    $success = $this->fetch($card);

                    break;
                case 'retrieveCard':
                    $cu = Customer::retrieve($data['customer_id']);
                    $card = $cu->sources->retrieve($data['card_id']);
                
                    $success = $this->fetch($card);
                    break;
                case 'updateCard':
                    $cu = Customer::retrieve($data['customer_id']);
                    $cuCard = $cu->sources->retrieve($data['card_id']);
                
                    foreach ($data['fields'] as $field => $value) {
                        $cuCard->$field = $value;
                    }
                    	
                    $card = $cuCard->save();
                    	
                    $success = $this->fetch($card);
                    break;
                case 'deleteCard':
                    $cu = Customer::retrieve($data['customer_id']);
                    $card = $cu->sources->retrieve($data['card_id'])->delete();
                    	
                    $success = $this->fetch($card);
                    break;
                case 'listCards':
                    $cu = Customer::retrieve($data['customer_id']);
                    $cards = $cu->sources->all($data['options']);
                    $success = $this->fetch($cards);
                
                    foreach ($success['data'] as &$card) {
                        $card = $this->fetch($card);
                    }
                    	
                    break;
                    	
                
                    /**
                     * 		SUBSCRIPTIONS
                     *
                     */
                case 'createSubscription':
                    $cu = Customer::retrieve($data['customer_id']);
                    	
                    // unset customer_id to prevent unknown parameter stripe error
                    unset($data['customer_id']);
                    $subscription = $cu->subscriptions->create($data['subscription']);
                
                    $success = $this->fetch($subscription);
                    break;
                case 'retrieveSubscription':
                    $cu = Customer::retrieve($data['customer_id']);
                    $subscription = $cu->subscriptions->retrieve($data['subscription_id']);
                
                    $success = $this->fetch($subscription);
                    break;
                case 'updateSubscription':
                    $cu = Customer::retrieve($data['customer_id']);
                    $cuSubscription = $cu->subscriptions->retrieve($data['subscription_id']);
                
                    foreach ($data['fields'] as $field => $value) {
                        $cuSubscription->$field = $value;
                    }
                    	
                    $subscription = $cuSubscription->save();
                    	
                    $success = $this->fetch($subscription);
                    break;
                case 'cancelSubscription':
                    $cu = Customer::retrieve($data['customer_id']);
                    $subscription = $cu->subscriptions->retrieve($data['subscription_id'])->cancel($data['at_period_end']);
                    	
                    $success = $this->fetch($subscription);
                    break;
                case 'listSubscriptions':
                    $cu = Customer::retrieve($data['customer_id']);
                    $subscriptions = $cu->subscriptions->all($data['options']);
                    $success = $this->fetch($subscriptions);
                
                    foreach ($success['data'] as &$subscription) {
                        $subscription = $this->fetch($subscription);
                    }
                    	
                    break;
                    
                    
                /**
                 * 		PLANS
                 *
                 */
                case 'createPlan':
                    $plan = Plan::create($data);
                    $success = $this->fetch($plan);
                    break;
                case 'retrievePlan':
                    $plan = Plan::retrieve($data['plan_id']);
                    $success = $this->fetch($plan);
                    break;
                case 'updatePlan':
                    $p = Plan::retrieve($data['plan_id']);
                    	
                    foreach ($data['fields'] as $field => $value) {
                        $p->$field = $value;
                    }

                    $plan = $p->save();
                    $success = $this->fetch($plan);
                    break;
                case 'deletePlan':
                    $p = Plan::retrieve($data['plan_id']);
                    $plan = $p->delete();
                    	
                    $success = $this->fetch($plan);
                    break;
                case 'listPlans':
                    $plans = Plan::all($data['options']);
                    $success = $this->fetch($plans);
                    	
                    foreach ($success['data'] as &$plan) {
                        $plan = $this->fetch($plan);
                    }
                    break;
                    	
                    	
                    /**
                     * 	 	COUPONS
                     *
                     */
                case 'createCoupon':
                    $coupon = Coupon::create($data);
                    $success = $this->fetch($coupon);
                    break;
                case 'retrieveCoupon':
                    $coupon = Coupon::retrieve($data['coupon_id']);
                    $success = $this->fetch($coupon);
                    break;
                case 'deleteCoupon':
                    $c = Coupon::retrieve($data['coupon_id']);
                    $coupon = $c->delete();
                
                    $success = $this->fetch($coupon);
                    break;
                case 'listCoupons':
                    $coupons = Coupon::all($data['options']);
                    $success = $this->fetch($coupons);
                
                    foreach ($success['data'] as &$coupon) {
                        $coupon = $this->fetch($coupon);
                    }
                    break;
                    
                /**
                 *
                 *  	EVENTS
                 *
                 */
                case 'retrieveEvent':
                    $event = Event::retrieve($data['event_id']);
                    $success = $this->fetch($event);
                    	
                    // cards
                    if (isset($success['data']['object']['cards']['data']) && !empty($success['data']['object']['cards']['data'])) {
                        foreach ($success['data']['object']['cards']['data'] as &$card) {
                            $card = $this->fetch($card);
                        }
                        unset($refund);
                    }
                    	
                    break;
                case 'listEvents':
                    $events = Event::all($data['options']);
                    $success = $this->fetch($events);
                
                    foreach ($success['data'] as &$event) {
                        $event = $this->fetch($event);
                
                        // refunds
                        if (isset($event['data']['object']['refunds']) && !empty($event['data']['object']['refunds'])) {
                            foreach ($event['data']['object']['refunds'] as &$refund) {
                                $refund = $this->fetch($refund);
                            }
                            unset($refund);
                        }
                
                        // cards
                        if (isset($event['data']['object']['cards']['data']) && !empty($event['data']['object']['cards']['data'])) {
                            foreach ($event['data']['object']['cards']['data'] as &$card) {
                                $card = $this->fetch($card);
                            }
                            unset($refund);
                        }
                
                    }
                    break;
                    
                    
                
                
            }
        } catch(Card $e) {
            $body = $e->getJsonBody();
            $error = $body['error'];
            $error['http_status'] = $e->getHttpStatus();
        
            $message = $error['message'];
        } catch (InvalidRequest $e) {
            $body = $e->getJsonBody();
            $error = $body['error'];
            $error['http_status'] = $e->getHttpStatus();
        } catch (Authentication $e) {
            $error = $e->getJsonBody();
            $error['http_status'] = $e->getHttpStatus();
        } catch (ApiConnection $e) {
            $body = $e->getJsonBody();
            $error['http_status'] = $e->getHttpStatus();
        } catch (Base $e) {
            $body = $e->getJsonBody();
            $error['http_status'] = $e->getHttpStatus();
        } catch (\Exception $e) {
            $body = $e->getJsonBody();
            $error['http_status'] = $e->getHttpStatus();
        }
        
        if ($success) {
//             if ($this->logFile && in_array($this->logType, ['both', 'success'])) {
//                 CakeLog::write('Success', $method, $this->logFile);
//             }
            	
            return [
                'status' => 'success',
                'message' => 'Success',
                'response' => $success
            ];
        }
        
        $str = '';
        $str .= $method.", type:". (!empty($error['type']) ? $error['type'] : '');
        $str .= ", type:". (!empty($error['type']) ? $error['type'] : '');
        $str .= ", http_status:". (!empty($error['http_status']) ? $error['http_status'] : '');
        $str .= ", param:". (!empty($error['param']) ? $error['param'] : '');
        $str .= ", message:". (!empty($error['message']) ? $error['message'] : '');

//         if ($this->logFile && in_array($this->logType, array('both', 'error'))) {
//             CakeLog::write('Error', $str, $this->logFile );
//         }

        return [
            'status' => 'error',
            'message' => $message,
            'response' => $error
        ];
    }
    
    
    
    /**
     * fetch method
     * Converts object to array - checking also one level nested objects
     *
     * @param object $object
     * @return array
     */
    private function fetch($object) {
        $objectClass = get_class($object);
        if (!isset($this->reflectionClass[$objectClass])) {
            $this->reflectionClass[$objectClass] = new \ReflectionClass($objectClass);
        }
    
        $array = [];
    
        foreach ($this->reflectionClass[$objectClass]->getProperties() as $property) {
            $property->setAccessible(true);
            $array[$property->getName()] = $property->getValue($object);
            $property->setAccessible(false);
        }
    
        foreach ($array['_values'] as $k => $value) {
            if (is_object($value)) {
                $array['_values'][$k] = $this->fetch($value);
            }
        }
    
        return $array['_values'];
    }
    
    
}
