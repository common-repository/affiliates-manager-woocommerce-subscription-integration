<?php

add_action('wpam_after_main_admin_menu', 'wpam_wc_subscription_do_admin_menu');

function wpam_wc_subscription_do_admin_menu($menu_parent_slug) {
    add_submenu_page($menu_parent_slug, __("WooCommerce Subscription", 'wpam'), __("Woo Subscription", 'wpam'), 'manage_options', 'wpam-wc-subscription', 'wpam_wc_subscription_settings');
}

function wpam_wc_subscription_settings() {
    echo '<div class="wrap">';   
    echo '<h2>Affiliates Manager WooCommerce Subscription Integration</h2>';
    echo '<div id="poststuff"><div id="post-body">';
    if (isset($_POST['wpam_wc_subscription_save_settings'])) {
        
        $first_commission_only = isset($_POST['first_commission_only']) ? '1':'';
        
        $options = array(
            'first_commission_only' => $first_commission_only,
        );
        update_option('wpam_wc_subscription_settings', $options);
        echo '<div id="message" class="updated fade">';
        echo '<p>WooCommerce Subscription Settings Saved!</p>';
        echo '</div>';
    }
    $options = get_option('wpam_wc_subscription_settings');
    ?>

    <p style="background: #fff6d5; border: 1px solid #d1b655; color: #3f2502; margin: 10px 0;  padding: 5px 5px 5px 10px;">
        Read the <a href="https://wpaffiliatemanager.com/affiliates-manager-woocommerce-subscription-integration/" target="_blank">usage documentation</a> to learn how to use the WooCommerce Subscription integration addon
    </p>
    <form action="" method="POST">

        <div class="postbox">
            <h3 class="hndle"><label for="title">WooCommerce Subscription Integration Settings</label></h3>
            <div class="inside">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">First Commission Only</th>
                        <td>
                            <input name="first_commission_only" type="checkbox"<?php if(isset($options['first_commission_only']) && $options['first_commission_only'] != '') echo ' checked="checked"'; ?> value="1"/>                            
                            <p class="description">Check this if you only want to reward a commission on the first payment (by default, a commission is rewarded on all future payments of a subscription).</p>
                        </td>
                    </tr>                  
                </table>
            </div>
        </div>
        <input type="submit" name="wpam_wc_subscription_save_settings" value="Save" class="button-primary" />

    </form>


    <?php
    echo '</div></div>'; //end of poststuff and post-body
    echo '</div>'; //end of wrap    
}
