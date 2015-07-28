<?php
// trapit_settings_page() displays the page content for the Test settings submenu
function trapit_plugin_options() { //settings_page() {
    
    //must check that the user has the required capability
    if (!current_user_can('manage_options')) {
        wp_die( __('You do not have sufficient permissions to access this page.') );
    }
    
    // variables for the field and option names
    $options = array('email', 'password');
    
    $opt_vals = array();
    foreach ($options as $option) {
        $opt_name = "trapit_$option";
        $opt_vals[$opt_name] = get_option( $opt_name );
    }
    
    $hidden_field_name = 'trapit_submit_hidden';
    
    // Read in existing option value from database
    //$opt_val = get_option( $opt_name );
    
    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if ( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
        // Read their posted values
        foreach ($options as $option) {
            $opt_name = "trapit_$option";
            $opt_val = sanitize_text_field($_POST[$opt_name]);
            
            // Save the posted value to the database
            update_option( $opt_name, $opt_val );
            $opt_vals[$opt_name] = $opt_val;
        }

        // Attempt authenticating with the new credentials
        $creds = trapit_universal_authenticate($opt_vals['trapit_email'], $opt_vals['trapit_password']);
        foreach ($creds as $key => $opt_val) {
            $opt_name = sanitize_text_field("trapit_$key");
            $opt_val = sanitize_text_field($opt_val);

            // Save the received credentials to the database
            update_option( $opt_name, $opt_val );
            $opt_vals[$opt_name] = $opt_val;
        }
        
        // Put a settings updated message on the screen
        
        ?>
        <div class="updated"><p><strong><?php _e('settings saved.', 'trapit-wordpress' ); ?></strong></p></div>
<?php
        
    }
        
    
    // Now display the settings editing screen
    
    echo '<div class="wrap">';
    
    // header
    
    echo "<h2>" . __( 'Trapit Login', 'trapit-wordpress' ) . "</h2>";
    
    // settings form
    
    ?>
    
    <form name="form1" method="post" action="">
    <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
    
<?php
    foreach ($options as $option) {
        ?><p><?php
        $label = ucfirst(str_replace('_', ' ', $option));
        _e( "$label:", 'trapit-wordpress' );
        $opt_name = "trapit_$option";
        $opt_val = $opt_vals[ $opt_name ];
        ?>
        <input type="text" name="<?php echo $opt_name; ?>" value="<?php echo $opt_val; ?>" size="20">
        </p><hr />
<?php
    }
    ?>
    
    <p class="submit">
    <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
    </p>
    
    </form>
    </div>
    
<?php
    
}

function trapit_universal_authenticate($email, $password) {
    $url = 'https://trap.it/api/v4/auth/';
    $body = array('email' => $email, 'password' => $password);
    
    // Universal auth endpoint usage
    // POST /api/v4/auth/
    // {"email": EMAIL, "password": PWD}
    
    // Response:
    // {'slug': org_slug,
    // 'hostname': host_name,
    // 'user_id': user_id (hex),
    // 'api_key': api_key (hex)}

    // Actual Response:
    //
    // Array
    // (
    //     [api_key] => 099aae482c204cec88ad0fbb8223a472
    //     [hostname] => st1.staging.trap.it
    //     [user_id] => 877f28c798ea4099a977041fc2f6673e
    //     [slug] => st
    // )
    
    $args = array(
        'timeout'     => 35,
        'redirection' => 5,
        'httpversion' => '1.0',
        'user-agent'  => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ),
        'blocking'    => true,
        'headers'     => array(),
        'cookies'     => array(),
        'compress'    => false,
        'decompress'  => true,
        'sslverify'   => true,
        'stream'      => false,
        'filename'    => null,
        'body'        => json_encode($body)
    );
    
    $response = wp_remote_post($url, $args);
    
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        wp_die(__("Error authenticating. Is your email and password correct? Error message: $error_message"));
    }
    $json_body = wp_remote_retrieve_body( $response );
    $body = json_decode($json_body, $assoc_array=true);

    // check JSON response for error messages, die if present
    if (isset($body['errors'])) {
        $errors = $body['errors'];
        array_unshift($errors, 'Error verifying credentials:');
        $err_msg = implode('<br/>', $errors);
        wp_die($err_msg);
    }

    return $body;
}
