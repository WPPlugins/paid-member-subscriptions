<?php

Class PMS_Subscription_Plan {

    /**
     * Subscription plan id
     *
     * @access public
     * @var int
     */
    public $id;

    /**
     * Subscription plan name
     *
     * @access public
     * @var string
     */
    public $name;

    /**
     * Subscription plan description
     *
     * @access public
     * @var string
     */
    public $description;

    /**
     * Subscription plan price
     *
     * @access public
     * @var int
     */
    public $price;

    /**
     * Subscription plan status
     *
     * @access public
     * @var string
     */
    public $status;

    /**
     * Subscription plan duration
     *
     * @access public
     * @var int
     */
    public $duration;

    /**
     * Subscription plan duration unit
     *
     * @access public
     * @var string
     */
    public $duration_unit;

    /**
     * Subscription plan user role
     *
     * @access public
     * @var string
     */
    public $user_role;

    /**
     * Parent subscription plan
     *
     * @access public
     * @var string
     */
    public $top_parent;


    public function __construct( $id_or_post ) {

        if( !is_object( $id_or_post ) )
            $id_or_post = (int)$id_or_post;

        // Abort if id is not an integer
        if( !is_object( $id_or_post ) && !is_int( $id_or_post ) )
            return;

        $this->init( $id_or_post );

    }


    public function init( $id_or_post ) {

        /*
         * Set subscription plan data from the post itself
         *
         */
        if( is_object( $id_or_post ) ) {

            $id = $id_or_post->ID;
            $post_subscription = $id_or_post;

        } else {

            $id = $id_or_post;
            $post_subscription = get_post( $id );

        }


        if( !$post_subscription )
            return null;

        $this->id   = (int)$post_subscription->ID;
        $this->name = $post_subscription->post_title;


        /*
         * Set subscription plan data from the post meta data
         *
         */
        $post_meta_subscription = get_post_meta( $id );

        // Subscription plan description
        $this->description =  isset( $post_meta_subscription['pms_subscription_plan_description'] ) ? esc_attr( $post_meta_subscription['pms_subscription_plan_description'][0] ) : '';

        // Subscription plan price
        $this->price =  isset( $post_meta_subscription['pms_subscription_plan_price'] ) ? $post_meta_subscription['pms_subscription_plan_price'][0] : 0;

        // Subscription plan status
        $this->status =  isset( $post_meta_subscription['pms_subscription_plan_status'] ) ? $post_meta_subscription['pms_subscription_plan_status'][0] : '';

        // Subscription plan duration and duration unit
        $this->duration = ( isset( $post_meta_subscription['pms_subscription_plan_duration'] ) && !empty( $post_meta_subscription['pms_subscription_plan_duration'][0] ) ) ? $post_meta_subscription['pms_subscription_plan_duration'][0] : 0;
        $this->duration_unit = isset( $post_meta_subscription['pms_subscription_plan_duration_unit'] ) ? $post_meta_subscription['pms_subscription_plan_duration_unit'][0] : '';

        // Set default user role
        $this->user_role = ( isset( $post_meta_subscription['pms_subscription_plan_user_role'] ) && !empty( $post_meta_subscription['pms_subscription_plan_user_role'][0] ) ) ? $post_meta_subscription['pms_subscription_plan_user_role'][0] : '';

        // Set top parent of the group
        $this->top_parent = isset( $post_meta_subscription['pms_subscription_plan_top_parent'] ) ? $post_meta_subscription['pms_subscription_plan_top_parent'][0] : '';

    }


    /*
     * Method that checks if the subscription plan is active
     *
     */
    public function is_active() {

        if( $this->status == 'active' )
            return true;
        elseif( $this->status == 'inactive' )
            return false;

    }


    /*
     * Activate the subscription plan
     *
     * @param $post_id
     *
     */
    public static function activate( $post_id ) {

        if( !is_int( $post_id ) )
            return;

        update_post_meta( $post_id, 'pms_subscription_plan_status', 'active' );

        // Change the post status to "active" as well
        $post = array(
            'ID'          => $post_id,
            'post_status' => 'active',
        );
        wp_update_post( $post );

    }


    /*
     * Deactivate the subscription plan
     *
     * @param $post_id
     *
     */
    public static function deactivate( $post_id ) {

        if( !is_int( $post_id ) )
            return;

        update_post_meta( $post_id, 'pms_subscription_plan_status', 'inactive' );

        // Change the post status to "inactive" as well
        $post = array(
            'ID'          => $post_id,
            'post_status' => 'inactive',
        );
        wp_update_post( $post );

    }


    /*
     * Delete subscription plan
     *
     * @param $post_id
     *
     */
    public static function remove( $post_id ) {

        $subscription_plan_post = get_post( $post_id );

        // If the post doesn't exist just skip everything
        if( !$subscription_plan_post )
            return;


        wp_delete_post( $post_id );

    }


    /*
     * Check to see if subscription plan exists
     *
     */
    public function is_valid() {

        if( empty($this->id) )
            return false;
        else
            return true;

    }


    /*
     * Method that returns the expiration date of the subscription plan
     *
     */
    public function get_expiration_date( $timestamp = false ) {

        if( $this->duration != 0 ) {
            $duration = $this->duration;
            $duration_unit = $this->duration_unit;
        } else {
            $duration = 10;
            $duration_unit = 'year';
        }

        if( $timestamp )
            return strtotime( "+" . $duration . ' ' . $duration_unit );
        else
            return date( 'Y-m-d 23:59:59', strtotime( "+" . $duration . ' ' . $duration_unit ) );

    }


    /*
     * Returns the user role associated with the subscription plan
     *
     */
    public function get_user_role() {

        $user_role = get_post_meta( $this->id, 'pms_subscription_plan_user_role', true );

        if( empty($user_role) )
            $user_role = 'subscriber';

        return $user_role;

    }

}