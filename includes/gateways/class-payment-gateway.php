<?php
/*
 * Payment Gateways base class
 *
 */

Class PMS_Payment_Gateway {

    /**
     * Payment id
     *
     * @access public
     * @var int
     */
    public $payment_id;

    /**
     * User id
     *
     * @access public
     * @var int
     */
    public $user_id;

    /**
     * User email
     *
     * @access public
     * @var string
     */
    public $user_email;

    /**
     * Subscription plan
     *
     * @access public
     * @var object
     */
    public $subscription_plan;

    /**
     * Subscription plan price currency
     *
     * @access public
     * @var string
     */
    public $currency;

    /**
     * Subscription plan price
     *
     * @access public
     * @var int
     */
    public $amount;

    /**
     * Sign up amount
     *
     * @access public
     * @var int
     */
    public $sign_up_amount;

    /**
     * Recurring payment
     *
     * @access public
     * @var string
     */
    public $recurring;

    /**
     * Redirect URL
     *
     * @access public
     * @var string
     */
    public $redirect_url;

    /**
     * Form location
     *
     * @access public
     * @var string
     */
    public $form_location;

    /**
     * If test mode
     *
     * @access public
     * @var bool
     */
    public $test_mode;


    public function __construct( $payment_data = array() ) {

        if( !empty( $payment_data ) ) {

            $this->payment_id        = ( isset( $payment_data['payment_id'] ) ? $payment_data['payment_id'] : 0 );
            $this->user_id           = ( isset( $payment_data['user_data']['user_id'] ) ? $payment_data['user_data']['user_id'] : 0 );
            $this->user_email        = ( isset( $payment_data['user_data']['user_email'] ) ? $payment_data['user_data']['user_email'] : '' );
            $this->subscription_plan = ( isset( $payment_data['user_data']['subscription'] ) && is_object( $payment_data['user_data']['subscription'] ) ? $payment_data['user_data']['subscription'] : '' );
            $this->currency          = ( isset( $payment_data['currency'] ) ? $payment_data['currency'] : 'USD' );
            $this->amount            = ( isset( $payment_data['amount'] ) ? $payment_data['amount'] : 0 );
            $this->sign_up_amount    = ( isset( $payment_data['sign_up_amount'] ) ? $payment_data['sign_up_amount'] : NULL );
            $this->recurring         = ( isset( $payment_data['recurring'] ) ? $payment_data['recurring'] : 0 );
            $this->redirect_url      = ( isset( $payment_data['redirect_url'] ) ? $payment_data['redirect_url'] : '' );
            $this->form_location     = ( isset( $payment_data['form_location'] ) ? $payment_data['form_location'] : '' );
            $this->test_mode         = pms_is_payment_test_mode();

        }

        $this->init();

        do_action( 'pms_payment_gateway_initialised', $this );

    }

    public function init() {}

    public function process_sign_up() {}

    public function process_webhooks() {}

    public function fields() {}

}