<?php

/*
 * Plugin Name: YML Turbo Pages for WooCommerce
 * Description: The plugin generates the YML file necessary for Yandex services.
 * Author URI:  http://treustrasse.ru/
 * Author:      Gleb Varganov
 * Version:     1.0
 *
 * Text Domain: woo_yml
 * Domain Path: /languages
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Requires at least: WP 4.1.0
 * Tested up to: WP 5.2
 *
 * WC requires at least: 2.6.0
 * WC tested up to: 3.7
 */

/*
  ini_set('memory_limit', '1024M');
 */

$text_domain = 'woo_yml';

register_activation_hook(__FILE__, 'activate_YML_turbo_pages_plugin_cron'); 
function activate_YML_turbo_pages_plugin_cron() {
	// just in case, we’ll remove all the same cron tasks to add new ones "from scratch"
	// this may be necessary if the same task was connected incorrectly before (without checking that it already exists)
	wp_clear_scheduled_hook( 'update_yml_file' );

	// add a new cron task
	wp_schedule_event( time(), 'hourly', 'update_yml_file');
}

add_action( 'update_yml_file', 'update_cron_YML_turbo_pages_hourly' );
function update_cron_YML_turbo_pages_hourly() {
	generate_YML_file_for_turbo_pages();
}

// When deactivating the plugin, remove cron:
register_deactivation_hook( __FILE__, 'remove_cron_when_deact_YML_turbo_pages_plugin' ); 
function remove_cron_when_deact_YML_turbo_pages_plugin(){
	wp_clear_scheduled_hook( 'update_yml_file' );
}


// Multilingual
add_action('plugins_loaded', 'init_YML_turbo_pages_plugin');
function init_YML_turbo_pages_plugin() {
	global $text_domain;
	load_plugin_textdomain( $text_domain, false, dirname(plugin_basename(__FILE__)).'/languages/' );
}

// Init pl
add_action('admin_menu', 'add_yml_turbo_pages_menu');
function add_yml_turbo_pages_menu() {
	add_menu_page( 'YML Export', 'YML Export', 'manage_options', 'woo-yml-export', 'generate_yml_file_for_tubo_pages', 'dashicons-cart' );
}

// Register plugin settings
function register_yml_turbo_pages_settings() {

	register_setting( 'yml-settings', 'store_name' );
	register_setting( 'yml-settings', 'company_name' );
	register_setting( 'yml-settings', 'yml_store' );
	register_setting( 'yml-settings', 'yml_pickup' );
	register_setting( 'yml-settings', 'yml_delivery' );
	register_setting( 'yml-settings', 'yml_description' );
	register_setting( 'yml-settings', 'yml_currencies' );

}


// Main plugin settings
function generate_yml_file_for_tubo_pages() {

	global $text_domain;

	// Update options
	if( isset( $_POST['submit'] ) ) {
		
		// Make sure the request is not expired
		// On error, it will display a message and interrupt PHP.
		check_admin_referer( 'yml_nounce_turbo_pages_action', 'yml_tp_nounce' );

		/* Update options */
		
		// Store name
		if(isset( $_POST['store_name'] )) {
			$store_name_db = sanitize_text_field( $_POST['store_name'] );
		} else {
			$store_name_db = '';
		}
		update_option( 'store_name', $store_name_db );
		
		// Company name
		if(isset( $_POST['company_name'] )) {
			$company_name_db = sanitize_text_field( $_POST['company_name'] );
		} else {
			$company_name_db = '';
		}
		update_option( 'company_name', $company_name_db );

		// Store
		if(isset( $_POST['yml_store'] )) {
			$yml_store_db = sanitize_text_field( $_POST['yml_store'] );
		} else {
			// Use by default (if something went wrong)
			$yml_store_db = 'true';
		}
		update_option( 'yml_store', $yml_store_db );
		
		// Pickup
		if(isset( $_POST['yml_pickup'] )) {
			$yml_pickup_db = sanitize_text_field( $_POST['yml_pickup'] );
		} else {
			// Use by default (if something went wrong)
			$yml_pickup_db = 'true';
		}
		update_option( 'yml_pickup', $yml_pickup_db );
		
		// Delivery
		if(isset( $_POST['yml_delivery'] )) {
			$yml_delivery_db = sanitize_text_field( $_POST['yml_delivery'] );
		} else {
			// Use by default (if something went wrong)
			$yml_delivery_db = 'true';
		}
		update_option( 'yml_delivery', $yml_delivery_db );
		
		// Description
		if(isset( $_POST['yml_description'] )) {
			$yml_description_db = sanitize_text_field( $_POST['yml_description'] );
		} else {
			// Use by default (if something went wrong)
			$yml_description_db = 'post_content';
		}
		update_option( 'yml_description', $yml_description_db );
		
		// Currencies
		if(isset( $_POST['yml_currencies'] )) {
			$yml_currencies_db = sanitize_text_field( $_POST['yml_currencies'] );
		} else {
			// Use by default (if something went wrong)
			$yml_currencies_db = 'RUR';
		}
		update_option( 'yml_currencies', $yml_currencies_db );
		

		// Generate YML
		$result = generate_YML_file_for_turbo_pages();
	}


	// Check woocommerce
	if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		$woo_active = array(
			'status' => 'success',
			'message' => '',
		);
	}

	else {
		$woo_active =  array(
			'status' => 'error',
			'message' => '<div class="notice notice-error"> <p>'.__('For export you need to install <a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a> plugin.').'</p></div>',
		);
	}


	// Generate form
	echo '<div id="woo_yml_wrap" class="wrap">';
	echo '<h1 class="wp-heading-inline">'.__('YML Turbo Pages for WooCommerce', $text_domain).'</h1>';

	echo $woo_active['message'];

	if(isset($result['message'])) {
		echo $result['message'];
	}

	// If plugin not activated - hide form
	if( $woo_active['status'] == 'success' ) {

		echo '<form method="post" enctype="multipart/form-data">';
			echo '<table class="form-table">';
				echo '<tbody>';

					// Store name
					echo '<tr valign="top">';
						echo '<th scope="row"><label for="store_name">'.__('Store name', $text_domain).'</label></th>';
						echo '<td><input type="text" id="store_name" name="store_name" class="regular-text" placeholder="'.__('Store name', $text_domain).'" value="'.esc_attr( get_option('store_name') ).'" /><p class="description">'.__('Short name of the store. Max 20 characters.', $text_domain).'</p></td>';
					echo '</tr>';

					// Company name
					echo '<tr valign="top">';
						echo '<th scope="row"><label for="company_name">'.__('Company name', $text_domain).'</label></th>';
						echo '<td><input type="text" id="company_name" name="company_name" class="regular-text" placeholder="'.__('Company name', $text_domain).'" value="'.esc_attr( get_option('company_name') ).'" /><p class="description">'.__('Full name of the company owning the store. Not published, used for internal identification.', $text_domain).'</p></td>';
					echo '</tr>';

					// Description
					echo '<tr valign="top">';
						echo '<th scope="row"><label for="yml_description">'.__('Description', $text_domain).'</label></th>';
						echo '<td><select id="yml_description" name="yml_description">'.get_select_options_description_for_yml_turbo_pages(  get_option('yml_description') ).'</select><p class="description">'.__('If there is no description, a title will be inserted.', $text_domain).'</p></td>';
					echo '</tr>';

					// Store
					echo '<tr valign="top">';
						echo '<th scope="row"><label for="yml_store">'.__('Store', $text_domain).'</label></th>';
						echo '<td><select id="yml_store" name="yml_store">'.get_select_true_false_option_for_yml_turbo_pages(  get_option('yml_store') ).'</select><p class="description">'.__('The possibility of buying goods without reservation in the sales area.', $text_domain).'</p></td>';
					echo '</tr>';

					// Pickup
					echo '<tr valign="top">';
						echo '<th scope="row"><label for="yml_pickup">'.__('Pickup', $text_domain).'</label></th>';
						echo '<td><select id="yml_pickup" name="yml_pickup">'.get_select_true_false_option_for_yml_turbo_pages(  get_option('yml_pickup') ).'</select><p class="description">'.__('Possibility of self-delivery of goods from points of delivery (in all regions to which the store delivers).', $text_domain).'</p></td>';
					echo '</tr>';

					// Delivery
					echo '<tr valign="top">';
						echo '<th scope="row"><label for="yml_delivery">'.__('Delivery', $text_domain).'</label></th>';
						echo '<td><select id="yml_delivery" name="yml_delivery">'.get_select_true_false_option_for_yml_turbo_pages(  get_option('yml_delivery') ).'</select><p class="description">'.__('Possibility of courier delivery of goods (in all regions to which the store delivers).', $text_domain).'</p></td>';
					echo '</tr>';

					// Currencies
					echo '<tr valign="top">';
						echo '<th scope="row"><label for="yml_currencies">'.__('Currency', $text_domain).'</label></th>';
						echo '<td><select id="yml_currencies" name="yml_currencies">'.get_select_options_currency_for_yml_turbo_pages(  get_option('yml_currencies') ).'</select><p class="description">'.__('Currency of the store.', $text_domain).'</p></td>';
					echo '</tr>';
				echo '</tbody>';
			echo '</table>';
			
			// Nonces
			wp_nonce_field('yml_nounce_turbo_pages_action','yml_tp_nounce');
			
			submit_button( __('Export', $text_domain) );
		echo '</form>';

	}

	echo '</div>';

}

function generate_YML_file_for_turbo_pages() {

	global $text_domain;

	// Variables
	$wye_site_url = get_site_url();
	$wye_platform = 'WooCommerce';
	$wye_version = get_option( 'woocommerce_version', '' );


	$product_cats_args = array(
		'taxonomy' => 'product_cat',
		'orderby' => 'name',
		'hide_empty' => 1,
		'hierarchical' => 1,
		'number' => 0,
	);

	$all_product_cats = get_categories( $product_cats_args );

	$products_args = array(
		'post_type' => 'product',
		'post_status' => 'publish',
		'posts_per_page'   => -1
	);

	$the_query = new WP_Query();
	$the_query = $the_query->query( $products_args );
	$products = '';


	if( count($the_query) > 0 ) {
		$products .= '<offers>';
		foreach ($the_query as $post) {

			$product = new WC_product( $post->ID );
				$attachment_ids = $product->get_gallery_attachment_ids();

			$cat_id = ''; 

			$get_price = $product->get_price();
			$get_regular_price = $product->get_regular_price();
			$get_sale_price = $product->get_sale_price();

			$products .= '<offer id="'.$post->ID.'">';

				// Name
				$products .= '<name>'.htmlspecialchars( $post->post_title, ENT_XML1, 'UTF-8' ).'</name>';
				
				// Url
				$products .= '<url>'.get_permalink( $post->ID ).'</url>';
				
				// Price
				if( $get_price == $get_sale_price ) {
					$products .= '<price>'.$get_sale_price.'</price>';
					$products .= '<oldprice>'.$get_price.'</oldprice>';
				} else {
					$products .= '<price>'.$get_price.'</price>';
				}
				$products .= '<currencyId>'.get_option('yml_currencies').'</currencyId>';

				// Category
				$term_list = wp_get_post_terms( $post->ID,'product_cat',array('fields'=>'ids') );
				$cat_id = (int)$term_list[0];
				if(!empty($cat_id)) {
					$products .= '<categoryId>'.$cat_id.'</categoryId>';
				}

				// Thumbnail
				$thumb = get_post_thumbnail_id( $post->ID );
				if(!empty($thumb)) {
					$products .= '<picture>'.wp_get_attachment_url( $thumb ).'</picture>';
				}
				// Gallery
				foreach( $attachment_ids as $attachment_id ) {
					$products .= '<picture>'.wp_get_attachment_url( $attachment_id ).'</picture>';
				}
				
				// Store
				$products .= '<store>'.get_option('yml_store').'</store>';

				// Pickup
				$products .= '<pickup>'.get_option('yml_pickup').'</pickup>';

				// Delivery
				$products .= '<delivery>'.get_option('yml_delivery').'</delivery>';

				// Description
				if( get_option('yml_description') == 'post_content' ) {
					$description = $post->post_content;
				} else {
					$description = $post->post_excerpt;
				}
				if(empty($description)) {
					$description = $post->post_title;
				}
				$products .= '<description><![CDATA['.$description.']]></description>';

				// Attributes
				$attributes = $product->get_attributes();

				if ( $attributes ) {
					foreach ($attributes as $attribute) {
						if(isset($attribute['name'])) {
							$term = get_term_by( 'id', $attribute['options'][0], $attribute['name'] );
							if(!empty($term)) {
								$products .= '<param name="'.wc_attribute_label( $attribute['name'] ).'">'.$term->name.'</param>';
							}
						}
					}
				}

				
			$products .= '</offer>';
		}
		$products .= '</offers>';

	}

	$wye_xml = '<?xml version="1.0" encoding="UTF-8"?>';
	$wye_xml .= '<yml_catalog date="'.date("Y-m-d H:i").'">';

		$wye_xml .= '<shop>';

			$wye_xml .= '<name>'.get_option('store_name').'</name>';
			$wye_xml .= '<company>'.get_option('company_name').'</company>';
			$wye_xml .= '<url>'.$wye_site_url.'</url>';
			$wye_xml .= '<platform>'.$wye_platform.'</platform>';
			$wye_xml .= '<version>'.$wye_version.'</version>';
			$wye_xml .= '<currencies>';
				$wye_xml .= '<currency id="'.get_option('yml_currencies').'" rate="1"/>';
			$wye_xml .= '</currencies>';

			if(!empty($all_product_cats)) {
				$wye_xml .= '<categories>';

					foreach( $all_product_cats as $category ){
						if($category->parent == 0) {
							$wye_xml .= '<category id="'.$category->term_id.'">'.$category->name.'</category>';
						} else {
							$wye_xml .= '<category id="'.$category->term_id.'" parentId="'.$category->parent.'">'.$category->name.'</category>';
						}
					}

				$wye_xml .= '</categories>';
			}
			if(!empty($products)) {
				$wye_xml .= $products;
			}

		$wye_xml .= '</shop>';

	$wye_xml .= '</yml_catalog>';

	$file_name = 'yandex-yml.xml';

	$fp = fopen( ABSPATH . $file_name, 'w' );
    fwrite( $fp, $wye_xml );
    fclose( $fp );

	$filename = ABSPATH . $file_name;

	if (file_exists($filename)) {

		$yml_link = get_option('siteurl').'/'.$file_name;
	    $out = array(
	    	'status' => 'success',
	    	'message' => '<div class="updated notice is-dismissible"><p>'.__( 'YML is successfully generated', $text_domain).'. <a href="'.$yml_link.'" target="_blank">'.$yml_link.'</a></p></div>',
	    );

	} else {

	    $out = array(
	    	'status' => 'error',
	    	'message' => '<div class="notice-error is-dismissible"><p>'.__( 'Something went wrong.', $text_domain).'</p></div>',
	    );

	}

	return $out;

}

function get_select_true_false_option_for_yml_turbo_pages( $selected="" ) {

	global $text_domain;

	$arr = array(
		'true' => 'Yes',
		'false' => 'No',
	);

	$out = '';

	foreach ($arr as $key => $value) {
		if($selected == $key) {
			$out .= '<option value="'.$key.'" selected="selected">'.__( $value, $text_domain).'</option>';
		} else {
			$out .= '<option value="'.$key.'">'.__( $value, $text_domain).'</option>';
		}
	}

	return $out;

}

function get_select_options_description_for_yml_turbo_pages( $selected='' ) {

	global $text_domain;

	$arr = array(
		'post_content' => 'Post content',
		'post_excerpt' => 'Excerpt',
	);

	$out = '';

	foreach ($arr as $key => $value) {
		if($selected == $key) {
			$out .= '<option value="'.$key.'" selected="selected">'.__( $value, $text_domain).'</option>';
		} else {
			$out .= '<option value="'.$key.'">'.__( $value, $text_domain).'</option>';
		}
	}

	return $out;

}

function get_select_options_currency_for_yml_turbo_pages( $selected='' ) {

	global $text_domain;

	$arr = array(
		'RUR' => 'Russian ruble',
		'UAH' => 'Ukrainian hryvnia',
		'BYN' => 'Belarusian ruble',
	);

	$out = '';

	foreach ($arr as $key => $value) {
		if($selected == $key) {
			$out .= '<option value="'.$key.'" selected="selected">'.__( $value, $text_domain).'</option>';
		} else {
			$out .= '<option value="'.$key.'">'.__( $value, $text_domain).'</option>';
		}
	}

	return $out;

}