<?php

/**
 * this page creates a admin account menu tab in the front end to show the lab reports
 * 
 * @see https://wpbeaveraddons.com/docs/woopack/modules/my-account/add-custom-tab/
 */

function custom_my_account_menu_items($items) {
    $items['lab-reports'] = __('Lab Reports', 'textdomain');
    return $items;
}
add_filter('woocommerce_account_menu_items', 'custom_my_account_menu_items');


// get the product links from order id
function get_product_id_from_order_id($order_id){
	$order = wc_get_order();

	if(!$order){
		return;
	}

	$product_urls = array();

	foreach($order->get_items() as $item_id=>$item){
		if($item->get_type() === 'line_item') {
			$product_id = $item->get_product_id();
			$product = wc_get_product($product_id);
			if($product){
				$product_url = get_permalink($product_id);
				$product_urls[] = $product_url;
			}
		}
	}

	return $product_urls;
}

// Display content for new endpoint
	function custom_my_account_endpoint_content() {
		global $wpdb, $table_prefix;
		// Get the current user ID
		$user_id = get_current_user_id();
		
		// Get all orders for the current user
		$args = array(
			'customer' => $user_id,
			'status'   => array('completed', 'processing'), // Adjust based on what statuses you want to include
			'limit'    => -1, // Retrieve all orders
		);
		
		$orders = wc_get_orders($args);
	
		if (!empty($orders)) {
			echo '<h3>' . __('Lab Reports', 'textdomain') . '</h3>';
			?>
            <table>
                <thead style="background: #19be6f;">
                    <tr style="border-bottom: 5px solid #ADADAD;">
                        <th style="color: #FFF; padding: 10px;">Order ID</th>
                        <th style="color: #FFF; padding: 10px;">Order Date</th>
                        <th style="color: #FFF; padding: 10px;">Stored Product Name</th>
                        <th style="color: #FFF; padding: 10px;">Product Associated PDF Name</th>
                        <th style="color: #FFF; padding: 10px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($orders as $order) {
                        $order_id = $order->get_id();
                        $order_status = $order->get_status();
                        $order_date = $order->get_date_created();
                        $lab_details = get_post_meta($order_id, 'lab_details', true);
                        $lab_details = maybe_unserialize($lab_details); // Unserialize the lab details

                        if (order_contains_booking_category_product($order)) {
                            if ($order_status === 'completed') {
                                if (!empty($lab_details) && is_array($lab_details)) {
                                    foreach ($lab_details as $detail) {
                                        $product_name = esc_html($detail['product_name']);
                                        $pdf_url = esc_url($detail['pdf_file']);
                                        $pdf_name = esc_html($detail['pdf_name']);
                                        
                                        echo '<tr style="border-bottom: 1px solid #000; border-left: 1px solid #000;">';
                                        echo '<td style="border-right: 1px solid #000; text-align:center;">' . esc_html($order_id) . '</td>';
                                        echo '<td style="border-right: 1px solid #000; text-align:center;">' . esc_html($order_date) . '</td>';
                                        echo '<td style="border-right: 1px solid #000; text-align:center;">' . $product_name . '</td>';
                                        echo '<td style="border-right: 1px solid #000; text-align:center;">' . $pdf_name . '</td>';
                                        echo '<td style="border-right: 1px solid #000; text-align:center;">';
                                        echo '<a href="' . $pdf_url . '" target="_blank" style="margin-right: 5px;">View</a>';
                                        echo '<br>';
                                        echo '<a href="' . $pdf_url . '" download style="margin-right: 5px;">Download</a>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    // No lab details available
                                    echo '<tr style="border-bottom: 1px solid #000; border-left: 1px solid #000;">';
                                    echo '<td colspan="5" style="text-align:center;">For Order # ' . esc_html($order_id) . ': Your lab reports are updating. Please wait for some time.</td>';
                                    echo '</tr>';
                                }
                            } else {
                                // If order status is not completed
                                echo '<tr style="border-bottom: 1px solid #000; border-left: 1px solid #000;">';
                                echo '<td colspan="5" style="text-align:center;">For Order # ' . esc_html($order_id) . ': Your lab reports are being processed. Please wait for some time.</td>';
                                echo '</tr>';
                            }
                        }
                    }
                    ?>
                </tbody>
            </table>

			<?php
		} else {
			echo '<p>' . __('You have no lab reports available.', 'textdomain') . '</p>';
		}
	}
add_action('woocommerce_account_lab-reports_endpoint', 'custom_my_account_endpoint_content');

