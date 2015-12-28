<?php
/*
Plugin Name: Purge KeyCDN Zone 
Text Domain: cdn
Description: Adds button to Adminbar to easily purge the cache of one defined KeyCDN zone.
Author: TimoM
Author URI: https://timom.de
License: GPLv2 or later
Version: 0.5.0
*/



/* Check & Quit */
defined('ABSPATH') OR exit;

require 'inc/KeyCDN.php';


add_action('admin_bar_menu','cdn_admin_links',90);

function cdn_admin_links($wp_admin_bar) {

        // check user role
        if ( ! is_admin_bar_showing()  ) {
            return;
        }

        // add admin purge link
        $wp_admin_bar->add_menu(
            array(
                'id'     => 'purge-cache',
                'href'   => wp_nonce_url( add_query_arg('_cdn_purge', 'clear'), '_cdn_purge__clear_nonce'),
                'parent' => 'top-secondary',
                'title'  => '<span class="ab-item">Purge Zone Cache</span>',
                'meta'   => array( 'title' => esc_html__('purge Cache', 'cache') )
            )
        );

    }
add_action('init','process_purge_request');

function process_purge_request($data) {

    // check if clear request
    if ( empty($_GET['_cdn_purge']) OR $_GET['_cdn_purge'] !== 'clear' ) {
        return;
    }

    // validate nonce
    if ( empty($_GET['_wpnonce']) OR ! wp_verify_nonce($_GET['_wpnonce'], '_cdn_purge__clear_nonce') ) {
        return;
    }

    // check user role
    if ( ! is_admin_bar_showing() ) {
        return;
    }

    // load if network
    if ( ! function_exists('is_plugin_active_for_network') ) {
        require_once( ABSPATH. 'wp-admin/includes/plugin.php' );
    }


        // clear cache
    	$options1 = get_option( 'cdn_api' );
       	$options2 = get_option( 'zone_id' );
        $keycdn_api = new KeyCDN($options1['cdn_api']);
       	$output = $keycdn_api->get('zones/purge/'.$options2['zone_id'].'.json');

        // clear notice
        if ( is_admin() ) {

            add_action('admin_notices','purge_notice',10,1);
            do_action('admin_notices',$output);
        }

    if ( ! is_admin() ) {
        wp_safe_redirect(
            remove_query_arg(
                '_cdn_purge',
                wp_get_referer()
            )
        );

        exit();
    }
}

function purge_notice($arg1) {

    // check if admin
    if ( ! is_admin_bar_showing() OR ! $arg1 ) {
        return false;
    }

    $message = json_decode($arg1);

    echo sprintf(
        '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
        esc_html__($message->description, 'cache')
    );
}



/**
 * Add an admin submenu link under Settings
 */
function keycdn_add_options_submenu_page() {
     add_submenu_page(
          'options-general.php',          // admin page slug
          __( 'Purge KeyCDN', 'keycdn' ), // page title
          __( 'Purge KeyCDN', 'keycdn' ), // menu title
          'manage_options',               // capability required to see the page
          'keycdn_options',                // admin page slug, e.g. options-general.php?page=keycdn_options
          'keycdn_options_page'            // callback function to display the options page
     );
}
add_action( 'admin_menu', 'keycdn_add_options_submenu_page' );
 
/**
 * Register the settings
 */
function keycdn_register_settings() {
     register_setting(
          'keycdn_options',  // settings section
          'cdn_api' // setting name
     );

     register_setting(
          'keycdn_options',  // settings section
          'zone_id' // setting name
     );
}
add_action( 'admin_init', 'keycdn_register_settings' );
 
/**
 * Build the options page
 */
function keycdn_options_page() {
     if ( ! isset( $_REQUEST['settings-updated'] ) )
          $_REQUEST['settings-updated'] = false; ?>
 
     <div class="wrap">
 
          <?php if ( false !== $_REQUEST['settings-updated'] ) : ?>
               <div class="updated fade"><p><strong><?php _e( 'keycdn Options saved!', 'keycdn' ); ?></strong></p></div>
          <?php endif; ?>
           
          <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
           
          <div id="poststuff">
               <div id="post-body">
                    <div id="post-body-content">
                         <form method="post" action="options.php">
                              <?php settings_fields( 'keycdn_options' ); ?>
                              <?php $options1 = get_option( 'cdn_api' ); ?>
                              <?php $options2 = get_option( 'zone_id' ); ?>
                              <table class="form-table">
                                   <tr valign="top"><th scope="row"><?php _e( 'KeyCDN API Key', 'keycdn' ); ?></th>
                                        <td>
                                           

											<label for="cdn_api">
												<input type="text" name="cdn_api[cdn_api]" id="cdn_api" value="<?php echo $options1['cdn_api']; ?>" size="64" class="regular-text code" />
												
											</label>


                                        </td>
                                   </tr>
                                   <tr valign="top"><th scope="row"><?php _e( 'Zone ID', 'keycdn' ); ?></th>
                                        <td>
                                           

											<label for="zone_id">
												<input type="text" name="zone_id[zone_id]" id="zone_id" value="<?php echo $options2['zone_id']; ?>" size="64" class="regular-text code" />
												
											</label>


                                        </td>
                                   </tr>
                                   <tr><td><?php submit_button() ?></td></tr>
                              </table>
                         </form>
                    </div> <!-- end post-body-content -->
               </div> <!-- end post-body -->
          </div> <!-- end poststuff -->
     </div>
<?php
}