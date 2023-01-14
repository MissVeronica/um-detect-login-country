<?php
/**
 * Plugin Name:     Ultimate Member - Detect Login Country
 * Description:     Extension to Ultimate Member to detect logins from unexpected countries.
 * Version:         1.0.0 
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica?tab=repositories
 * Text Domain:     ultimate-member
 * Domain Path:     /languages
 * UM version:      2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;
if ( ! function_exists( 'geoip_detect2_get_info_from_current_ip' )) return;


add_action( 'um_user_login',         'um_detect_login_country', 10, 1 );
add_filter( 'um_settings_structure', 'um_settings_structure_detect_login_country', 10, 1 );

function um_detect_login_country( $args ) {

    $userInfo = geoip_detect2_get_info_from_current_ip();
    
    $accepted_country_codes = explode( ',', UM()->options()->get( 'um_detect_login_country' ) );

    if ( ! in_array( $userInfo->country->isoCode, $accepted_country_codes )) {

        $um_countries = UM()->builtin()->get( 'countries' );
        if( isset( $um_countries[$userInfo->country->isoCode] )) {
            $country_name = $um_countries[$userInfo->country->isoCode];
        } else {
            $country_name = '';
        }

        $to      = get_option( 'admin_email' );
        $subject = __( 'Detect Login Country', 'ultimate-member' );

        $body    = sprintf( __( 'Login Country Code %s', 'ultimate-member' ), $userInfo->country->isoCode ) . '<br>' .
                   sprintf( __( 'Login Country Name %s', 'ultimate-member' ), $country_name ) . '<br>' . 
                   sprintf( __( 'Login City %s', 'ultimate-member' ), $userInfo->city->name ) . '<br>' . 
                   sprintf( __( 'User IP Address %s', 'ultimate-member' ), geoip_detect2_get_client_ip()) . '<br>' .
                   sprintf( __( 'Submitted UM Username %s', 'ultimate-member' ), $args['submitted']['username'] ) . '<br>' . 
                   sprintf( __( 'Allowed Country Codes %s', 'ultimate-member' ), UM()->options()->get( 'um_detect_login_country' ));

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        wp_mail( $to, $subject, $body, $headers, array( '' ) );
    }

}

function um_settings_structure_detect_login_country( $settings_structure ) {

    $settings_structure['access']['sections']['other']['fields'][] = array(
            'id'            => 'um_detect_login_country',
            'type'          => 'text',
            'label'         => __( 'Detect Login Country - Country Codes accepted', 'ultimate-member' ),
            'size'          => 'medium',
            'tooltip'       => __( 'Comma separated two character capital letter country codes which are accepted.', 'ultimate-member' )
            );

    return $settings_structure;
}
