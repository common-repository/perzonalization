<?php
/**
 * Plugin Name: Perzonalization - AI Powered Personalization That Sells
 * Plugin URI: perzonalization.com
 * Version:  1.6.0
 * Description: Personalizing the shopping experience of more than 19 million shoppers every month, we have learned that understanding visitor individual tastes is key. That is why we not only analyse behaviours around products but also take into account shopper individual preferences and preferences of those similar to them. Requires activated WooCommerce REST API.
 * Author: Perzonalization
 * Author URI: http://www.perzonalization.com/woocommerce-plugin/
 *
 * @package woocommerce-perzonalization.
 */


if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    global $description, $store_name;
    $description = 'Perzonalization plugin';

    //store name
    $blog_info = get_bloginfo('wpurl');
    if (substr($blog_info, 0, 7) == "http://") {
        $store_name = str_replace('http://', '', $blog_info);
    } elseif (substr($blog_info, 0, 8) == "https://") {
        $store_name = str_replace('https://', '', $blog_info);
    }

    // ACTIVATING
    register_activation_hook(__FILE__, function () {
        global $wpdb, $description, $store_name;
        $table = $wpdb->prefix . 'woocommerce_api_keys';
        $result = $wpdb->get_var("SELECT key_id FROM $table WHERE description = '" . $description . "'");
        if (!$result) {
            $consumer_key = 'ck_' . wc_rand_hash();
            $consumer_secret = 'cs_' . wc_rand_hash();

            $data = array(
                'user_id' => get_current_user_id(),
                'description' => $description,
                'permissions' => 'read',
                'consumer_key' => wc_api_hash($consumer_key),
                'consumer_secret' => $consumer_secret,
                'truncated_key' => substr($consumer_key, -7)
            );

            $result = $wpdb->insert($table, $data);

            if ($result) {
                // add guid in options table
                $guid = get_option('perzonalization_guid');
                if ($guid === false) {
                    $guid = strtolower(guid_woocommerce());
                    add_option('perzonalization_guid', $guid);
                }
                if ($guid == '') {
                    $guid = strtolower(guid_woocommerce());
                    update_option('perzonalization_guid', $guid);
                }

                if (!function_exists('get_plugin_data')) {
                    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
                }
                $plugin_version = get_plugin_data(__FILE__);


                //woocommerce version
                $woo_version = get_option('woocommerce_version');

                //country
                $country = wc_get_base_location();
                $country = $country['country'];
				$admin_url = admin_url();
                $data_post = array();
                //accessToken - not applicable
                $data_post['apiConsumerKey'] = $consumer_key;
                $data_post['apiConsumerSecret'] = $data['consumer_secret'];
                //city - not applicable
                $data_post['country'] = $country;
                $data_post['currency'] = get_woocommerce_currency();
                $data_post['displayName'] = get_bloginfo('name');
                $data_post['nameAPI'] = get_bloginfo('wpurl');
                $data_post['email'] = get_option('admin_email');
                $data_post['language'] = get_bloginfo('language');
				$data_post['isUpdate'] = "false";
                //owner - not applicable
                //phone - not applicable
                $data_post['platformVersion'] = $woo_version;
                $data_post['pluginVersion'] = $plugin_version['Version'];
                $data_post['url'] = $store_name;
                $data_post['adminUrl'] = $admin_url;
                $data_post_send = '';
                foreach ($data_post as $key => $value) {
                    $data_post_send .= $key . '=' . $value . '&';
                }
                $headers = array(
                    "Accept-Encoding: gzip, deflate",
                    "Accept: */*",
                    "Content-Type: application/x-www-form-urlencoded"
                );
                //curl to create the Perzonalization API page
                $ch = curl_init();
                $url = "http://api.perzonalization.com/stores/woocommerce." . $guid;
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_post_send);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $test = curl_exec($ch);
                if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 201) {
                    // all is good
                } else {
                    // error
                }
                curl_close($ch);
            }
        }
    });
	
	function wp_upe_upgrade_completed( $upgrader_object, $options ) {
	 $our_plugin = plugin_basename( __FILE__ );
	 if( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
	  foreach( $options['plugins'] as $plugin ) {
	   if( $plugin == $our_plugin ) {
		   //notify backend about update
			$guid = get_option('perzonalization_guid');
			if (!function_exists('get_plugin_data')) {
				require_once(ABSPATH . 'wp-admin/includes/plugin.php');
			}
			$plugin_version = get_plugin_data(__FILE__);
			$woo_version = get_option('woocommerce_version');
			$data_post = array();
			$data_post['isUpdate'] = "true";
			$data_post['platformVersion'] = $woo_version;
			$data_post['pluginVersion'] = $plugin_version['Version'];
			$data_post_send = '';
			foreach ($data_post as $key => $value) {
				$data_post_send .= $key . '=' . $value . '&';
			}
			$headers = array(
				"Accept-Encoding: gzip, deflate",
				"Accept: */*",
				"Content-Type: application/x-www-form-urlencoded"
			);
			
			$ch = curl_init();
			$url = "http://api.perzonalization.com/stores/woocommerce." . $guid;
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_post_send);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$test = curl_exec($ch);
			if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 201) {
				// all is good
			} else {
				// error
			}
			curl_close($ch);
	   }
	  }
	 }
	}
	add_action( 'upgrader_process_complete', 'wp_upe_upgrade_completed', 10, 2 );

    // DEACTIVATING
    register_deactivation_hook(__FILE__, function () {
        global $wpdb, $description, $store_name;
        $table = $wpdb->prefix . 'woocommerce_api_keys';
        $result = $wpdb->get_results("SELECT consumer_secret FROM $table WHERE description = '" . $description . "'");
        $guid = get_option('perzonalization_guid');
        if ($result) {
            $wpdb->delete($table, array('description' => $description));
            // curl to delete Perzonalization API page
            $url = 'http://api.perzonalization.com/stores/woocommerce.' . $guid;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_POSTFIELDS, '');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
        }
    });

    // Submenu into a wooCommerce
    add_action('admin_menu', function () {
        add_submenu_page('woocommerce', __('Perzonalization', 'woocommerce-perzonalization'), __('Perzonalization', 'woocommerce-perzonalization'), 'manage_options', 'perzonalization', 'display_options_page');
    });
    
	function getConsumerSecret() {
		global $wpdb, $description;
        $table = $wpdb->prefix . 'woocommerce_api_keys';
        $result = $wpdb->get_row("SELECT * FROM $table WHERE description = '" . $description . "'");
        if ($result) {
			return $result->consumer_secret;
		}
		else{
			return null;
		}
	}
	
	function display_options_page() {
        $guid = get_option('perzonalization_guid');
		$accessToken = getConsumerSecret();
        ?>
        <iframe src="//my.perzonalization.com/stores/woocommerce.<?php echo $guid; ?>/config?at=<?php echo $accessToken; ?>" width="100%"
                height="1080px" align="left"></iframe>
    <?php
    }
	
	//iframe height was 813px

    //add_action('init', 'manufacturer_taxonomy', 0);

//  Manufacturer taxonomy

    //function manufacturer_taxonomy() {
    //    $labels = array(
    //        'name' => _x('Manufacturer', 'taxonomy general name'),
    //        'singular_name' => _x('Manufacturer', 'taxonomy singular name'),
    //        'search_items' => __('Search Manufacturer'),
    //        'all_items' => __('All Manufacturers'),
    //        'parent_item' => __('Parent Manufacturer'),
    //        'parent_item_colon' => __('Parent Manufacturer:'),
    //        'edit_item' => __('Edit Manufacturer'),
    //        'update_item' => __('Update Manufacturer'),
    //        'add_new_item' => __('Add New Manufacturer'),
    //        'new_item_name' => __('New Genre Manufacturer'),
    //        'menu_name' => __('Manufacturer'),
    //    );
	//
    //    $args = array(
    //        'hierarchical' => true,
    //        'labels' => $labels,
    //        'show_ui' => true,
    //        'show_admin_column' => true,
    //        'query_var' => true,
    //        'rewrite' => array('slug' => 'manufacturer'),
    //    );
	//
    //    register_taxonomy('manufacturer', array('product'), $args);
    //}


    // Add a main script on pages

    add_action('woocommerce_thankyou', 'prz_custom_tracking');
	
	function prz_get_attribute_options( $product_id, $attribute ) {
		if ( isset( $attribute['is_taxonomy'] ) && $attribute['is_taxonomy'] ) {
			  return wc_get_product_terms( $product_id, $attribute['name'], array( 'fields' => 'names' ) );
			} elseif ( isset( $attribute['value'] ) ) {
			  return array_map( 'trim', explode( '|', $attribute['value'] ) );
		}

		return array();
  }
  
    function prz_get_variation_attributes($variation){
		$attributes = array();
		foreach ( $variation->get_variation_attributes() as $attribute_name => $attribute ) {

				// taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`
				$attributes[] = array(
					'name'   => ucwords( str_replace( 'attribute_', '', wc_attribute_taxonomy_slug( $attribute_name ) ) ),
					'option' => $attribute,
				);
			}
		return $attributes;
	}
  
	function prz_get_product_variations_with_attributes($product){
		$variations = array();

		foreach ( $product->get_children() as $child_id ) {
			$variation = wc_get_product( $child_id );

			if ( ! $variation || ! $variation->exists() ) {
				continue;
			}
		
			$variations[] = array(
				'id' => $child_id,
				'attributes' => prz_get_variation_attributes($variation)
			);
		}
		
		return $variations;
	}

    function prz_custom_tracking($order_id)
    {
        $user_id = get_current_user_id();
        $order = wc_get_order($order_id);
        $line_items = $order->get_items();
        $prod_data = '';
//        $info = array();
        foreach ($line_items as $item) {
            $product = $order->get_product_from_item($item);
            $product_id = $item['product_id'];
            $price = $product->get_price();
            $qty = $item['qty'];
            $prod = wc_get_product($product_id);
            $attrs = $prod->get_attributes();
            if ((array_key_exists('size', $attrs)) and ($prod->product_type == 'variable')) {
                $size = $item['size'];
                $prod_data .= "{id: '$product_id', price: '$price', quantity: '$qty', size: '$size'},";
            }
            elseif ((!($prod->product_type == 'variable')) and (array_key_exists('size', $attrs))) {
                $size = $attrs['size']['value'];
                $prod_data .= "{id: '$product_id', price: '$price', quantity: '$qty', size: '$size'},";
            }
            else {
                $prod_data .= "{id: '$product_id', price: '$price', quantity: '$qty'},";
            }
        }
//        $prod_data = substr($prod_data, 0, -1);
//        $info2 = json_encode($info);
        echo "<script type='text/javascript'>
				 var purchaseDetailsForPrz = {
                       transactionId: '{$order_id}',
                        userId: '{$user_id}',
                          productData: [
                              $prod_data
                          ]};
                        var _przq = _przq || []; _przq.push(purchaseDetailsForPrz);</script>";
    }


    add_action('wp_head', function () {
        $guid = get_option('perzonalization_guid');
		echo "<!--PERZONALIZATION-START Do not modify or delete this comment-->
		";
        if (is_order_received_page()) {
			$user_id = get_current_user_id();
			$language = get_bloginfo('language');
			$currency = get_woocommerce_currency();
            echo "<script type='text/javascript'>
				  var detailsForPrz = {
					instanceGuid: '{$guid}',
					pageType: 'sales',
					userId: '{$user_id}',
					language: '{$language}',
					currency: '{$currency}'
				  };</script>";
        }

        if (is_product()) {

            global $post;
            $product_id = $post->ID;

            $product = wc_get_product($product_id);
            $permalink = get_permalink();
            // get product tags
            $product_tags = get_the_terms($post->ID, 'product_tag');
            if ($product_tags == false) {
                $tags = '';
            } else {
                $tags = '';
                foreach ($product_tags as $product_tag) {
                    $tags .= "{'name': 'tag', 'value': '" . clean_string($product_tag->name) . "'},"; 
                }
                $tags = substr($tags, 0, -1);
				// $tags = str_replace('"', "'", $tags); // This causes a bug for categories with quotes in string
            }

            // get manufacturer
            //$manufacturer_tax = get_the_terms($post->ID, 'manufacturer');
            //if (!($manufacturer_tax == false)) {
            //    $manufacturer = $manufacturer_tax[0]->name;
            //} else {
            //    $manufacturer = '';
            //}
			
            // get product categories
            $product_cats = get_the_terms($post->ID, 'product_cat');
            if (!empty($product_cats))  {
                $cats = '';
                foreach ($product_cats as $product_cat) {
                    $cats .= '"' . clean_string($product_cat->name) . '",';
                }
                $cats = substr($cats, 0, -1);
                // $cats = str_replace('"', "'", $cats); // This causes a bug for categories with quotes in string
            } else {$cats = '';}
            // product image
            $product_img = wp_get_attachment_url(get_post_thumbnail_id());
			$product_img2 = wp_get_attachment_image_url($product->get_image_id(), 'full');
            $product_attributes = $product->get_attributes();
			
			$isFirst = true;
            // get product attributes
            if (!empty($product_attributes)) {
                foreach ($product_attributes as $product_attribute => $val) {
					if($isFirst == true && empty($tags) == false){
						$tags .= ',';
						$isFirst = false;
					}
					$the_options = prz_get_attribute_options($product->get_id(), $val);
					
					// taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`
					
					if ($val['variation'] == true) 
					{
						$attr_sanitized_name = str_replace('attribute_', "", str_replace('pa_', "", strtolower($val['name'])));
						
						$the_variations = prz_get_product_variations_with_attributes($product);
						
						$variation = null;
						foreach ($the_variations as $temp_variation => $val1) 
						{
							foreach ($val1['attributes'] as $temp_attribute => $val11)
							{
								if (str_replace('attribute_', "", str_replace('pa_', "", strtolower($val11['name']))) == $attr_sanitized_name) {
									$variation = $val1;//TODO: should select default variation instead of first occurrence
									break;
								}
							}
							if ($variation != null) {
								break;
							}
						}
						
						if ($variation != null) {
							$variation_attributes = $variation['attributes']; 
							
							foreach ($variation_attributes as $variation_attribute => $val2) 
							{
								$variation_attr_sanitized_name = str_replace('attribute_', "", str_replace('pa_', "", strtolower($val2['name'])));
								if ($attr_sanitized_name == $variation_attr_sanitized_name)
								{
									$tags .= '{"name":' . "'" .  $variation_attr_sanitized_name . "'" . ',"value":' . "'" . clean_string($val2['option']) . "'" . '},';
								}
							}							
						}
					}
					else {
						foreach ($the_options as $option => $val3) {
							$tags .= '{"name":' . "'" .  str_replace('attribute_', "", str_replace('pa_', "", strtolower($val['name']))) . "'" . ',"value":' . "'" . clean_string($val3) . "'" . '},';
						}						
					}

					//if (strpos($val['value'], '|') !== false) {
						//multiple values
					//	$values = explode('|', $val['value']);
					//	  foreach ($values as $singleValue) {
					//		  $tags .= '{"name":' . "'" . $product_attribute . "'" . ',"value":' . "'" . clean_string($singleValue) . "'" . '},';						  
					//	  }
					//}
					//else {
					//	$tags .= '{"name":' . "'" . $product_attribute . "'" . ',"value":' . "'" . clean_string($val['value']) . "'" . '},';
					//}
                }
                $tags = substr($tags, 0, -1);
                $tags = str_replace('"', "'", $tags);
            }

            $post_title = addslashes($product->get_title());
            //$post_content = clean_string($product->post->post_content);
            $in_stock = $product->is_in_stock();

            $regular_price = $product->get_regular_price();
            $sale_price = $product->get_price();
            if ($sale_price == $regular_price ) {
                $regular_price = null;
            }

            if ($product->product_type == 'variable') {

                    //$variations1 = $product->get_available_variations();
					$variations2 = prz_get_product_variations_with_attributes($product);
                    //$regular_price = $variations1[0]['display_regular_price'];
                    $var_onsale = '';
                    foreach ($variations2 as $variation) {
                        foreach ($variation['attributes'] as $key => $value) {
                            $key = substr($key, 10);
                            //$var_onsale .= "{'name': '" . $key . "', 'value': '" . $value . "', 'variantId': '" . $variation['variation_id'] . "'},";
							$var_onsale .= "{'name': '" . str_replace('attribute_', "", str_replace('pa_', "", strtolower($value['name']))) . "', 'value': '" . $value['option'] . "', 'variantId': '" . $variation['id'] . "'},";
                        }
                    }

                    $var_onsale = substr($var_onsale, 0, -1);

            } else {
                $var_onsale = null;
            }

			//description: '{$post_content}', - this occasionally breaks json when it has html content
			//manufacturer: '{$manufacturer}',
            echo "<script type='text/javascript'>
				  var productDetailsForPrz  = {
                    attributes: [$tags],
					campaign: null,
					canonicalUrl: '{$permalink}',
                    categories: [$cats],
					id: '{$product_id}',
					name: '{$post_title}',
					onSale: '{$in_stock}',
					regularPrice : '{$regular_price}',
					salesPrice : '{$sale_price}',
					thumbnailUrl : '{$product_img}',
					thumbnailUrl2 : '{$product_img2}',
                    variantsOnSale: [
                        {$var_onsale}
                    ]
				  }</script>";

            $user_id = get_current_user_id();
			$language = get_bloginfo('language');
			$currency = get_woocommerce_currency();
            echo "<script type='text/javascript'>
				  var detailsForPrz = {
					instanceGuid: '{$guid}',
					pageType: 'product',
					userId: '{$user_id}',
					language: '{$language}',
					currency: '{$currency}'
				  };</script>";
        }

        if (is_shop()) {

            $user_id = get_current_user_id();

            $taxonomy     = 'product_cat';
            $orderby      = 'name';
            $show_count   = 0;
            $pad_counts   = 0;
            $hierarchical = 1;
            $title        = '';
            $empty        = 0;
            $args = array(
                'taxonomy'     => $taxonomy,
                'orderby'      => $orderby,
                'show_count'   => $show_count,
                'pad_counts'   => $pad_counts,
                'hierarchical' => $hierarchical,
                'title_li'     => $title,
                'hide_empty'   => $empty
            );
            $all_categories = get_categories( $args );
            $every_cat = '';
            foreach ($all_categories as $category) {
                $every_cat .= "'".clean_string($category->name)."',";
            }
            $every_cat = substr($every_cat, 0, -1);


            echo "<script type='text/javascript'>
				var filterDetailsForPrz = {
                     categories: [$every_cat]
                     }
                     </script>";
					 
			$language = get_bloginfo('language');
			$currency = get_woocommerce_currency();
            echo "<script type='text/javascript'>
                var detailsForPrz = {
					instanceGuid: '{$guid}',
					pageType: 'filter',
					userId: '{$user_id}',
					language: '{$language}',
					currency: '{$currency}'
                };</script>";
        }

        if (is_search()) {
            $user_id = get_current_user_id();

            global $wp_query;
            $search_ids = '';
            foreach ($wp_query->posts as $queried_post) {
                if ($queried_post->post_type == 'product') {
                    $search_ids .= "'".$queried_post->ID."'".",";
                }
                else {
                    $search_ids .= ' ';
                }
            }
            $search_ids = substr($search_ids, 0, -1);

            echo "<script type='text/javascript'>
                      var searchDetailsForPrz = {
                        ids: [$search_ids]
                      };</script>";

			$language = get_bloginfo('language');
			$currency = get_woocommerce_currency();
            echo "<script type='text/javascript'>
				  var detailsForPrz = {
					instanceGuid: '{$guid}',
					pageType: 'search',
					userId: '{$user_id}',
					language: '{$language}',
					currency: '{$currency}'
				  };</script>";
        }

        if (is_product_category()) {

            $user_id = get_current_user_id();
            $cat_id = get_queried_object()->term_id;
            $categories = get_all_parents($cat_id);
            $cat_r = array_reverse($categories, true);
            $cat = array();
            foreach ($cat_r as $category) {
                $cat[] = $category->name;
            }
            $cat = json_encode($cat);
            // $cat_s = str_replace('"', "'", $cat); // This causes a bug for categories with quotes in string

            $args = array(
                'post_type'             => 'product',
                'post_status'           => 'publish',
                'ignore_sticky_posts'   => 1,
                'posts_per_page'        => '12',
                'meta_query'            => array(
                    array(
                        'key'           => '_visibility',
                        'value'         => array('catalog', 'visible'),
                        'compare'       => 'IN'
                    )
                ),
                'tax_query'             => array(
                    array(
                        'taxonomy'      => 'product_cat',
                        'field' => 'term_id', //This is optional, as it defaults to 'term_id'
                        'terms'         => $cat_id,
                        'operator'      => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
                    )
                )
            );
//            $products = new WP_Query($args);
            $products = get_posts( $args );
            $ids_str = '';
            foreach ($products as $product) {
                $ids_str .= "'".$product->ID."'".",";
            }
            $ids_str = substr($ids_str, 0, -1);

            echo "<script type='text/javascript'>
var filterDetailsForPrz = {
  categories: $cat,
  ids: [$ids_str]
}
</script>";

			$language = get_bloginfo('language');
			$currency = get_woocommerce_currency();
            echo "<script type='text/javascript'>
				  var detailsForPrz = {
					instanceGuid: '{$guid}',
					pageType: 'filter',
					userId: '{$user_id}',
					language: '{$language}',
					currency: '{$currency}'
				  };</script>";
        }

        if (is_front_page()) {
            $user_id = get_current_user_id();
			$language = get_bloginfo('language');
			$currency = get_woocommerce_currency();
            echo "<script type='text/javascript'>
				  var detailsForPrz = {
					instanceGuid: '{$guid}',
					pageType: 'home',
					userId: '{$user_id}',
					language: '{$language}',
					currency: '{$currency}'
				  };</script>";
        }
		 
		if (is_cart()) {
            $user_id = get_current_user_id();
			$language = get_bloginfo('language');
			$currency = get_woocommerce_currency();
            echo "<script type='text/javascript'>
				  var detailsForPrz = {
					instanceGuid: '{$guid}',
					pageType: 'basket',
					userId: '{$user_id}',
					language: '{$language}',
					currency: '{$currency}'
				  };</script>";
        }
		
		if (is_404()) {
            $user_id = get_current_user_id();
			$language = get_bloginfo('language');
			$currency = get_woocommerce_currency();
            echo "<script type='text/javascript'>
				  var detailsForPrz = {
					instanceGuid: '{$guid}',
					pageType: '404',
					userId: '{$user_id}',
					language: '{$language}',
					currency: '{$currency}'
				  };</script>";
        }
		
        if (is_woocommerce() || is_search() || is_checkout() || is_cart() || is_404() || is_front_page() || is_order_received_page()) {

            global $woocommerce;
            $items = $woocommerce->cart->get_cart();
            $info = '';
            foreach ($items as $item) {

                $product = wc_get_product($item['product_id']);
                $product_attributes = $product->get_attributes();
                $product_id = $item['product_id'];
                $price = $item['line_total'];
                if ((!empty($product_attributes)) and (!empty($item['variation'])) and (array_key_exists('attribute_size', $item['variation'])) ) {
                    $size = $item['variation']['attribute_size'];
                        $info .= "{id: '$product_id', size: '$size', price: '$price'},";
                }
                elseif ((!empty($product_attributes)) and (empty($item['variation'])) and (array_key_exists('size', $product_attributes))) {
                    $size = $product_attributes['size']['value'];
                        $info .= "{id: '$product_id', size: '$size', price: '$price'},";
                }
                else {
                    $info .= "{id: '$product_id', price: '$price'},";
                }
            }

            $info = substr($info, 0, -1);

            echo "<script type='text/javascript'>

                    var basketDetailsForPrz = {
                    products: [
                      $info
                    ]};
                     </script>";
        }

        if (is_woocommerce() || is_search() || is_checkout() || is_cart() || is_404() || is_front_page() || is_order_received_page()) {
            echo "<script type='text/javascript' async>(function (w, d, n, i, a, l, s, r, c) { r = Math.round(Math.random() * 1e4); c = d.getElementById(i); if (!c) { s = d.createElement(n); s.type = 'text/javascript'; s.id = i; s.src = '//' + a + '?rnd=' + r; s.async = 1; l = d.getElementsByTagName(n)[0]; l.parentNode.insertBefore(s, l); } if (c) { runPRZPlugin(true); } })(window, document, 'script', 'prz_loader', 'cdn.perzonalization.com/js/loader/woocommerce.loader.js'); </script>";
        }
		
		echo "
		<!--PERZONALIZATION-END Do not modify or delete this comment-->";
    });

    // Add a div.perzonalization on pages
    add_action('woocommerce_after_shop_loop', 'echo_div'); //  home
    add_action('woocommerce_after_cart', 'echo_div'); //  cart
    add_action('woocommerce_cart_is_empty', 'echo_div');// empty cart
    add_action('woocommerce_after_single_product', 'echo_div'); // single product
    add_action('woocommerce_checkout_after_order_review', 'echo_div'); // checkout


    add_action('get_footer', 'echo_div_footer');// search page
    add_action('loop_end', 'stop_loop');// search page
    add_filter('the_post', 'test_my');// search page
    add_action('woocommerce_after_shop_loop', 'echo_div');// search page

    add_action('woocommerce_archive_description', function () {
        if (!is_shop() && !is_product_category() && !is_product_tag()) {
            echo_div();
        }
    });// search page $_GET['post_type=product']

    function stop_loop()
    {
        global $is_loop_stop;
        $is_loop_stop = true;
    }

    function test_my($content)
    {
        if (is_search()) {
            global $have_product, $post;
            $have_product = true;
        }
    }

    function echo_div_footer()
    {
        global $have_product;
        if (is_order_received_page() || is_front_page() || is_search()) {
            echo_div();
        }
    }

    // get all parents of current category
    function get_all_parents($id, &$output = array())
    {
        $id = (int)$id;
        global $wpdb;

        $_term = $wpdb->get_row("
						SELECT t.name, tt.parent
						FROM  $wpdb->terms t
						LEFT JOIN $wpdb->term_taxonomy  tt
						ON tt.term_id = t.term_id
						WHERE t.term_id =$id");
        $output[] = $_term;

        if ((int)$_term->parent != 0) {
            return get_all_parents($_term->parent, $output);
        } else {
            return $output;
        }
    }

    function echo_div()
    {
        $user_id = get_current_user_id();
        echo "<!--PERZONALIZATION-START Do not modify or delete this comment-->
				<div style='clear: both;'></div>
				<div id='perzonalization' class='perzonalization'></div>
				<!--PERZONALIZATION-END Do not modify or delete this comment-->";

        global $post, $store_name;
        
    }

    function clean_string($string)
    {
        return trim(preg_replace('/\s\s+/', ' ', addslashes($string)));
    }

    function guid_woocommerce()
    {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }
        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }
	
	function plugin_settings_link($links) {
	   $settings_link = '<a href="admin.php?page=perzonalization">Settings</a>';
	   array_unshift($links, $settings_link);
	   return $links;
	}
	
	$plugin = plugin_basename(__FILE__);
	add_filter("plugin_action_links_$plugin", 'plugin_settings_link' );
	
} else {
	$blog_info = get_bloginfo('wpurl');
    if (substr($blog_info, 0, 7) == "http://") {
        $store_name = str_replace('http://', '', $blog_info);
    } elseif (substr($blog_info, 0, 8) == "https://") {
        $store_name = str_replace('https://', '', $blog_info);
    }
	$email = get_option('admin_email');
	
	//user doesn't have woocommerce or it's disabled
	$data_post['platformName'] = 'woocommerce';
	$data_post['message'] = "user doesn't have woocommerce or it's disabled, skipping install. store: " .$store_name . " email: " . $email;
	
	$data_post_send = '';
	foreach ($data_post as $key => $value) {
		$data_post_send .= $key . '=' . $value . '&';
	}
	$headers = array(
		"Accept-Encoding: gzip, deflate",
		"Accept: */*",
		"Content-Type: application/x-www-form-urlencoded"
	);
	//curl to create the Perzonalization API page
	$ch = curl_init();
	$url = "http://api.perzonalization.com/v1.0/events/NoWoocommerce";
	
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_post_send);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$test = curl_exec($ch);
	if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 201) {
		// all is good
	} else {
		// error
	}
	curl_close($ch);
	//exit(sprintf('<p><strong>Perzonalization</strong> requires WooCommerce plugin in order to function.</p>'));
}