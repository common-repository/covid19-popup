<?php
/*
 * Plugin Name: Coronavirus (COVID-19) Live Update Popup
 * Description: Display a popup to visitors about COVID-19. Use this shortcode [covid19_popup] and display COVID19 popup where you want.
 * Version: 2.3
 * Author: Abu Omama
 * Requires at least: 5.1
 * Tested up to: 5.4
 *
 * Text Domain: covid-popup
 *
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit;


register_activation_hook( __FILE__, 'covid_popup_check_activation_hook' );
function covid_popup_check_activation_hook() {
  set_transient( 'covid-popup-admin-notice-activation', true, 5 );
  add_filter('widget_text', 'shortcode_unautop');
  add_filter('widget_text', 'do_shortcode');
}

add_action( 'admin_notices', 'covid_popup_check_activation_notice' );
function covid_popup_check_activation_notice(){
     if( get_transient( 'covid-popup-admin-notice-activation' ) ){
        ?>
        <div class="updated notice is-dismissible">
            <p>Use This shortcode [covid19_popup] to display global data and [covid19_popup country="india"] to display specific country data.</p>
        </div>
        <?php
        delete_transient( 'covid-popup-admin-notice-activation' );
    }
}


register_deactivation_hook( __FILE__, 'covid_popup_deactivation_function' );
function covid_popup_deactivation_function(){

}


function covid_popup_covid19_func( $atts = [] ) {
  $atts = shortcode_atts($default=array(
    'country' => ''
  ), $atts);
  $country_slug = $atts['country'];
  $country_name = '';
  $response = wp_remote_get( 'https://api.covid19api.com/summary' );
  $body = wp_remote_retrieve_body( $response );
  $obj = json_decode($body,true);
  if(!empty($country_slug)){
    $countries = $obj[ 'Countries' ];
    $key = array_search( $country_slug, array_column( $countries, 'Slug' ) );
    $country = $countries[ $key ];
    $country_name = $country[ 'Country' ];
  }else{
    $country = $obj['Global'];
  }
  $confirmed_cases = number_format($country[ 'TotalConfirmed' ]);
  $recovered_cases = number_format($country[ 'TotalRecovered' ]);
  $deaths_cases = number_format($country[ 'TotalDeaths' ]);
  $output = '<div class="covid19-popup-box">';
  $output .= '<span class="covid19-popup-close">x</span>';
  $output .= '<div class="covid19-popup-head">';
  $output .= '<h4>COVID-19</h4>';
  $output .= '</div>';
  $output .= '<div class="covid19-popup-body">';
  $output .= '<h6>'.($country_name?$country_name:'World').'</h6>';
  $output .= '<span>Confirmed: '.$confirmed_cases.'</span>';
  $output .= '<span>Deaths: '.$deaths_cases.'</span>';
  $output .= '</div>';
  $output .= '</div>';
  return $output;
  
}
add_shortcode( 'covid19_popup', 'covid_popup_covid19_func' );


add_action('wp_enqueue_scripts', 'covid_popup_callback_for_setting_up_scripts');
function covid_popup_callback_for_setting_up_scripts() {
    wp_register_style( 'covid-popup-style', plugins_url( 'assets/css/style.css', __FILE__ ) );
    wp_enqueue_style( 'covid-popup-style' );
    wp_enqueue_script( 'covid-popup-script', plugins_url( 'assets/js/script.js', __FILE__ ), array( 'jquery' ) );
}

add_action('wp_footer', 'covid_popup_footer_html'); 
function covid_popup_footer_html(){
  $add_to_whole_site = get_field('add_to_whole_site','option');
  $select_country = get_field('select_country','option');
  $all_countries_data = get_field('all_countries_data','option');
  if($add_to_whole_site && $select_country):
    echo do_shortcode('[covid19_popup country="'.$select_country.'"]');
  endif;
  if($add_to_whole_site && $all_countries_data):
    echo do_shortcode('[covid19_popup country=""]');
  endif;
}

add_action('wp_head', 'covid_popup_custom_style'); 
function covid_popup_custom_style(){
  $header = get_field('header','option');
  $body = get_field('body','option');
  if($header && $body):
    echo "<style>";
      if($header){
        echo ".covid19-popup-head{background-color:".$header['background_color'].";}";
        echo ".covid19-popup-head > h4{color:".$header['text_color'].";}";
      }
      if($body){
        echo ".covid19-popup-box{background-color:".$body['background_color'].";}";
        echo ".covid19-popup-body > h4,.covid19-popup-body > span{color:".$body['text_color'].";}";
      }
    echo "</style>";
  endif;
}

// Define path and URL to the ACF plugin.
define( 'covid_popup_ACF_PATH', plugin_dir_url( __FILE__ ) . 'includes/acf/' );
define( 'covid_popup_ACF_URL', plugins_url('includes/acf/', __FILE__ ) );

// Include the ACF plugin.
include_once( 'includes/acf/acf.php' );
include_once( 'includes/option-fields.php' );

// Customize the url setting to fix incorrect asset URLs.
add_filter('acf/settings/url', 'covid_popup_acf_settings_url');
function covid_popup_acf_settings_url( $url ) {
    return covid_popup_ACF_URL;
}

// (Optional) Hide the ACF admin menu item.
add_filter('acf/settings/show_admin', 'covid_popup_acf_settings_show_admin');
function covid_popup_acf_settings_show_admin( $show_admin ) {
    return false;
}
if(is_admin()){
if(function_exists('acf_add_options_page')){
    acf_add_options_page(array(
        'page_title'    =>  __('Covid19 Popup','nhc'),
        'menu_title'    =>  __('Covid19 Popup','nhc'),
        'menu_slug'     =>  'theme-general-settings',
        'capability'    =>  'edit_posts',
        'redirect'      =>  false
    ));
}

function covid_popup_load_color_field_choices( $field ) {
    // reset choices
    $field['choices'] = array();
    $response = wp_remote_get( 'https://api.covid19api.com/summary' );
    $body = wp_remote_retrieve_body( $response );
    $obj = json_decode($body,true);
    $choices = array_merge(array(array('Slug'=>'Global','Country'=>'All Countries')), $obj[ 'Countries' ]);
    // loop through array and add to field 'choices'
    if( is_array($choices) ) {
        foreach( $choices as $choice ) {
            $field['choices'][ $choice['Slug'] ] = $choice['Country'];
        }
    }
    // return the field
    return $field;
}
add_filter('acf/load_field/name=select_country', 'covid_popup_load_color_field_choices');
}
?>