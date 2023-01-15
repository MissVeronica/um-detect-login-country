<?php
/**
 * Plugin Name:     Ultimate Member - Detect Login Country
 * Description:     Extension to Ultimate Member to detect logins from unexpected countries.
 * Version:         1.1.0 
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
        if ( isset( $um_countries[$userInfo->country->isoCode] )) {
            $country_name = $um_countries[$userInfo->country->isoCode];
        } else {
            $country_name = '';
        }

        $mail_from_addr = UM()->options()->get( 'mail_from_addr' ); 
        if ( empty( $mail_from_addr ) ) $mail_from_addr = get_option( 'admin_email' );

        $site_name = UM()->options()->get( 'site_name' );
        if ( empty( $site_name )) $site_name = '';

        $blog_name = get_bloginfo( 'name' );
        if ( empty( $blog_name )) $blog_name = '';
        
        $date_time = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), current_time( 'timestamp' ));

        if ( ! empty( get_option( 'admin_email' ) )) {

            $to      = get_option( 'admin_email' );
            $subject = __( 'Detect Login Country', 'ultimate-member' );

            $body = sprintf( __( 'Site Name %s',                'ultimate-member' ), $site_name ) . '<br>' .
                    sprintf( __( 'Blog Name %s',                'ultimate-member' ), $blog_name ) . '<br>' .
                    sprintf( __( 'Date and Time %s',            'ultimate-member' ), $date_time ) . '<br>' .
                    sprintf( __( 'Login Country Code %s',       'ultimate-member' ), $userInfo->country->isoCode ) . '<br>' .
                    sprintf( __( 'Login Country Name %s',       'ultimate-member' ), $country_name ) . '<br>' . 
                    sprintf( __( 'Login City %s',               'ultimate-member' ), $userInfo->city->name ) . '<br>' . 
                    sprintf( __( 'User Client IP Address %s',   'ultimate-member' ), geoip_detect2_get_client_ip()) . '<br>' .
                    sprintf( __( 'Submitted UM Username %s',    'ultimate-member' ), $args['submitted']['username'] ) . '<br>' . 
                    sprintf( __( 'Allowed Country Codes %s',    'ultimate-member' ), UM()->options()->get( 'um_detect_login_country' ));

            $headers = array( 'Content-Type: text/html; charset=UTF-8',
                              'From: ' . stripslashes( $site_name ) . ' <' . $mail_from_addr . '>' );

            wp_mail( $to, $subject, $body, $headers, array( '' ) );
        }
    }

}

function um_settings_structure_detect_login_country( $settings_structure ) {

    $settings_structure['access']['sections']['other']['fields'][] = array(
            'id'            => 'um_detect_login_country',
            'type'          => 'text',
            'label'         => __( 'Detect Login Country - Country Codes accepted', 'ultimate-member' ),
            'size'          => 'medium',
            'tooltip'       => __( 'Comma separated two character capital letter country codes (ISO 3166) which are accepted. Other country codes will send an admin email.', 'ultimate-member' )
            );

    return $settings_structure;
}
