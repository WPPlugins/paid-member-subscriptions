<?php
/*
 * HTML Output for the basic information page
 */
?>

<div class="wrap pms-wrap pms-info-wrap">

    <div class="pms-badge ">
        <span><?php echo __( 'Version', 'paid-member-subscriptions' ) . ' ' . PMS_VERSION; ?></span>
    </div>

    <h1><?php echo __( '<strong>Paid Member Subscriptions</strong>', 'paid-member-subscriptions' ); ?></h1>
    <p class="pms-info-text"><?php printf( __( 'Accept payments, create subscription plans and restrict content on your website.', 'paid-member-subscriptions' ) ); ?></p>
    <hr />
    <h2 class="pms-callout"><?php _e( 'Membership Made Easy', 'paid-member-subscriptions' ); ?></h2>
    <div class="pms-row pms-3-col">
        <div>
            <h3><?php _e( 'Register', 'paid-member-subscriptions'  ); ?></h3>
            <p><?php printf( __( 'Add basic registration forms where members can sign-up for a subscription plan using the %s shortcode.', 'paid-member-subscriptions' ), '<strong class="nowrap">[pms-register]</strong>' ); ?></p>
        </div>
        <div>
            <h3><?php _e( 'Account', 'paid-member-subscriptions' ); ?></h3>
            <p><?php printf( __( 'Allow members to edit their account information and manage their subscription plans using the %s shortcode.', 'paid-member-subscriptions' ), '<strong class="nowrap">[pms-account]</strong>' ); ?></p>
        </div>
        <div>
            <h3><?php _e( 'Restrict Content', 'pmstxt' ); ?></h3>
            <p><?php printf( __( 'Restrict content using the %s shortcode or directly from individual posts and pages.', 'paid-member-subscriptions' ), '<br/><strong class="nowrap">[pms-restrict subscription_plans="9,10"]</strong> &nbsp;&nbsp;&nbsp; <em>Special content for members subscribed to the subscription plans that have the ID 9 and 10!</em><strong class="nowrap">[/pms-restrict]</strong><br/>' ); ?></p>
        </div>
        <div>
            <h3><?php _e( 'Subscriptions', 'paid-member-subscriptions' ); ?></h3>
            <p><?php printf( __( 'Output subscription plans form anywhere and allow users to sign up for new subscriptions using %s shortcode.', 'paid-member-subscriptions' ), '<strong class="nowrap">[pms-subscriptions]</strong>' ); ?></p>
        </div>
        <div>
            <h3><?php _e( 'Login', 'paid-member-subscriptions' ); ?></h3>
            <p><?php printf( __( 'Allow members to login using %s shortcode.', 'paid-member-subscriptions' ), '<strong class="nowrap">[pms-login]</strong>' ); ?></p>
        </div>
        <div>
            <h3><?php _e( 'Recover Password', 'paid-member-subscriptions' ); ?></h3>
            <p><?php printf( __( 'Add a recover password form for your members using %s shortcode.', 'paid-member-subscriptions' ), '<strong class="nowrap">[pms-recover-password]</strong>' ); ?></p>
        </div>
    </div>

    <hr/>
    <div>
        <h2 class="pms-callout"><?php _e( 'Membership Modules', 'paid-member-subscriptions' );?></h2>
        <p><?php _e( 'Powerful sections that help you manage your members.', 'paid-member-subscriptions' ); ?></p>
    </div>

    <div class="pms-row pms-2-col">
        <div>
            <div class="pms-row pms-2-col">
                <div>
                    <h3><?php _e( 'Subscription Plans', 'paid-member-subscriptions' ); ?></h3>
                    <p><?php _e( 'Create hierarchical subscription plans allowing your members to upgrade from an existing subscription. Shortcode based, offering many options to customize your subscriptions listing.', 'paid-member-subscriptions' ); ?></p>
                </div>
                <div>
                    <h3><?php _e( 'Members', 'paid-member-subscriptions' ); ?></h3>
                    <p><?php _e( 'Overview of all your members and their subscription plans. Easily add/remove members or edit their subscription details. ', 'paid-member-subscriptions' ); ?></p>
                </div>

            </div>
            <div class="pms-row pms-2-col">
                <div>
                    <h3><?php _e( 'Payments', 'paid-member-subscriptions' ); ?></h3>
                    <p><?php _e( 'Keep track of all member payments, payment statuses or purchased subscription plans.', 'paid-member-subscriptions' ); ?></p>
                </div>
                <div>
                    <h3><?php _e( 'Settings', 'paid-member-subscriptions' ); ?></h3>
                    <p><?php _e( 'Set the payment gateway used to accept payments, select messages seen by users when accessing a restricted content page or customize default member emails. Everything is just a few clicks away. ', 'paid-member-subscriptions' ); ?></p>
                </div>
            </div>
        </div>

        <div class="">
            <img src="<?php echo PMS_PLUGIN_DIR_URL; ?>assets/images/pms_members_multiple.png" alt="Paid Member Subscriptions Members Page" />
        </div>
    </div>
    <hr/>

    <div>
        <h2 class="pms-callout"><?php _e( 'WooCommerce Integration', 'paid-member-subscriptions' );?></h2>
        <p><?php _e( 'Integrates beautifully with WooCommerce, for extended functionality.', 'paid-member-subscriptions' ); ?></p>
    </div>

    <div class="pms-row pms-2-col">
        <div>
            <div class="pms-row pms-2-col">
                <div>
                    <h3><?php _e( 'Restrict Product Viewing & Purchasing', 'paid-member-subscriptions' ); ?></h3>
                    <p><?php _e( 'Control who can see or purchase a WooCommerce product based on logged in status and subscription plan. Easily create products available to members only.', 'paid-member-subscriptions' ); ?></p>
                </div>
                <div>
                    <h3><?php _e( 'Offer Membership Discounts', 'paid-member-subscriptions' ); ?></h3>
                    <p><?php _e( 'Offer product discounts to members based on their active subscription. Set discounts globally per subscription plan, or individually per product.', 'paid-member-subscriptions' ); ?></p>
                </div>

            </div>
            <div class="pms-row pms-2-col">
                <div>
                    <h3><?php _e( 'Settings', 'paid-member-subscriptions' ); ?></h3>
                    <p><?php _e( 'Make use of the extra flexibility by setting custom restriction messages per product, excluding products on sale from membership discounts, allowing cumulative discounts & more. ', 'paid-member-subscriptions' ); ?></p>
                </div>
            </div>
        </div>

        <div class="">
            <img src="<?php echo PMS_PLUGIN_DIR_URL; ?>assets/images/pms_woo_member_discount.png" alt="Paid Member Subscriptions WooCommerce Product Member Discount" />
        </div>
    </div>
    <hr/>

    <div>
        <h2 class="pms-callout"><?php _e( 'Featured Add-ons', 'paid-member-subscriptions' );?></h2>
        <p><?php _e( 'Get more functionality by using dedicated Add-ons and tailor Paid Member Subscriptions to your project needs.', 'paid-member-subscriptions' ); ?></p>
    </div>
    <br />
    <div>
        <h3><?php _e( 'Basic Add-ons', 'paid-member-subscriptions' );?></h3>
        <p><?php printf( __( 'These addons extend your WordPress Membership Plugin and are available with the <a href="%s">Hobbyist and PRO</a> versions.', 'paid-member-subscriptions' ), 'https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=basicinfo-addons-basic-link&utm_campaign=PMSFree' ); ?></p>
    </div>
    <div class="pms-row pms-4-col pms-addons">
        <div>
            <h4 class="pms-add-on-name"><?php _e( 'Global Content Restriction', 'paid-member-subscriptions' ); ?></h4>
            <img src="<?php echo PMS_PLUGIN_DIR_URL; ?>assets/images/add-on-global-content-restriction.png" alt="Global Content Restriction" class="pms-addon-image" />
            <p><?php _e( 'Easy way to add global content restriction rules to subscription plans, based on post type, taxonomy and terms.', 'paid-member-subscriptions' ); ?></p>
        </div>
        <div>
            <h4 class="pms-add-on-name"><?php _e( 'Discount Codes', 'paid-member-subscriptions' ); ?></h4>
            <img src="<?php echo PMS_PLUGIN_DIR_URL; ?>assets/images/add-on-discount-codes.png" alt="Discount Codes" class="pms-addon-image" />
            <p><?php _e( 'Friction-less discount code creation for running promotions, making price reductions or simply rewarding your users.', 'paid-member-subscriptions' ); ?></p>
        </div>
        <div>
            <h4 class="pms-add-on-name"><?php _e( 'Email Reminders', 'paid-member-subscriptions' ); ?></h4>
            <img src="<?php echo PMS_PLUGIN_DIR_URL; ?>assets/images/add-on-email-reminders.png" alt="PayPal Pro and PayPal Express" class="pms-addon-image" />
            <p><?php _e( 'Create multiple automated email reminders that are sent to members before or after certain events take place (subscription expires, subscription activated etc.)', 'paid-member-subscriptions' ); ?></p>
        </div>
        <div>
            <h4 class="pms-add-on-name"><?php _e( 'Navigation Menu Filtering', 'paid-member-subscriptions' ); ?></h4>
            <img src="<?php echo PMS_PLUGIN_DIR_URL; ?>assets/images/add-on-navigation-menu-filtering.png" alt="Navigation Menu Filtering" class="pms-addon-image" />
            <p><?php _e( 'Dynamically display menu items based on logged-in status as well as selected subscription plans.', 'paid-member-subscriptions' ); ?></p>
        </div>
    </div>
    <div class="pms-row">
        <p><a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=basicinfo-addons-basic-btn&utm_campaign=PMSFree" class="button-primary pms-cta"><?php _e( 'Get Basic Add-ons', 'paid-member-subscriptions' ); ?></a></p>
    </div>

    <br />

    <div>
        <h3><?php _e( 'Pro Add-ons', 'paid-member-subscriptions' );?></h3>
        <p><?php printf( __( 'These addons extend your WordPress Membership Plugin and are available with the <a href="%s">PRO version</a> only.', 'paid-member-subscriptions' ), 'https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=basicinfo-addons-pro-link&utm_campaign=PMSFree' ); ?></p>
    </div>
    <div class="pms-row pms-4-col pms-addons">
        <div>
            <h4 class="pms-add-on-name"><?php _e( 'Recurring Payments - PayPal Standard', 'paid-member-subscriptions' ); ?></h4>
            <img src="<?php echo PMS_PLUGIN_DIR_URL; ?>assets/images/add-on-paypal-recurring.png" alt="Recurring Payments PayPal Standard" class="pms-addon-image" />
            <p><?php _e( 'Accept recurring payments from your members through PayPal Standard.', 'paid-member-subscriptions' ); ?></p>
        </div>
        <div>
            <h4 class="pms-add-on-name"><?php _e( 'PayPal Pro and PayPal Express', 'paid-member-subscriptions' ); ?></h4>
            <img src="<?php echo PMS_PLUGIN_DIR_URL; ?>assets/images/add-on-paypal-pro.png" alt="PayPal Pro and PayPal Express" class="pms-addon-image" />
            <p><?php _e( 'Accept one time or recurring payments through PayPal Pro (credit card) and/or Express Checkout.', 'paid-member-subscriptions' ); ?></p>
        </div>
        <div>
            <h4 class="pms-add-on-name"><?php _e( 'Stripe', 'paid-member-subscriptions' ); ?></h4>
            <img src="<?php echo PMS_PLUGIN_DIR_URL; ?>assets/images/add-on-stripe.png" alt="Navigation Menu Filtering" class="pms-addon-image" />
            <p><?php _e( 'Accept credit card payments, both one-time and recurring, directly on your website via Stripe.', 'paid-member-subscriptions' ); ?></p>
        </div>
        <div>
            <h4 class="pms-add-on-name"><?php _e( 'Content Dripping', 'paid-member-subscriptions' ); ?></h4>
            <img src="<?php echo PMS_PLUGIN_DIR_URL; ?>assets/images/add-on-content-dripping.png" alt="PayPal Pro and PayPal Express" class="pms-addon-image" />
            <p><?php _e( 'Create schedules for your content, making posts or categories available for your members only after a certain time has passed since they signed up for a subscription plan.', 'paid-member-subscriptions' ); ?></p>
        </div>
        <div>
            <h4 class="pms-add-on-name"><?php _e( 'Multiple Subscriptions / User', 'paid-member-subscriptions' ); ?></h4>
            <img src="<?php echo PMS_PLUGIN_DIR_URL; ?>assets/images/add-on-multiple-subscriptions.png" alt="Multiple Subscriptions per User" class="pms-addon-image" />
            <p><?php _e( 'Setup multiple subscription level blocks and allow members to sign up for more than one subscription plan (one per block).', 'paid-member-subscriptions' ); ?></p>
        </div>
    </div>
    <div class="pms-row">
        <p><a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=basicinfo-addons-pro-btn&utm_campaign=PMSFree" class="button-primary pms-cta"><?php _e( 'Get Pro Add-ons', 'paid-member-subscriptions' ); ?></a></p>
    </div>

    <hr/>
    <p><i><?php printf( __( 'Paid Member Subscriptions comes with an <a href="%s">extensive documentation</a> to assist you.', 'paid-member-subscriptions' ),'http://www.cozmoslabs.com/docs/paid-member-subscriptions/' ); ?></i></p>
</div>