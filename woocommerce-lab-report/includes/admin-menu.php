<?php
function wlr_admin_menu() {
    add_menu_page('Upload Lab Reports', 'Upload Lab Reports', 'manage_options', 'wlr-lab-reports', 'wlr_admin_menu_func', '', 6);
}
add_action('admin_menu', 'wlr_admin_menu');

function wlr_admin_menu_func() {
    $order_id = isset($_POST['orderid']) ? sanitize_text_field($_POST['orderid']) : '';
    $products = array();
    $category_slug = 'booking'; // Define the category slug here

    // Handle the order ID submission
    if (isset($_POST['submit'])) {
        if ($order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order_items = $order->get_items();
                $products = array();
                foreach ($order_items as $item_id => $item) {
                    $product = $item->get_product();
                    if ($product) {
                        $product_name = $product->get_name();
                        $quantity = $item->get_quantity();
                        
                        // Check if the product is in the specified category
                        if (has_term($category_slug, 'product_cat', $product->get_id())) {
                            // Add the product multiple times according to its quantity
                            for ($i = 0; $i < $quantity; $i++) {
                                $products[] = array(
                                    'product_name' => $product_name,
                                    'pdf_file' => '', // Placeholder for the uploaded file URL
                                    'pdf_name' => '', // Placeholder for the uploaded file name
                                );
                            }
                        }
                    }
                }
                
                // Save products in a transient
                set_transient('wlr_products_' . $order_id, $products, 60 * 60); // 1 hour
                
                // Debugging: Show how many products were found
                echo '<div class="updated"><p>Found ' . count($products) . ' products for the order.</p></div>';
                
                if (empty($products)) {
                    echo '<div class="error"><p>No products in the "booking" category found for this order.</p></div>';
                }
                
                // Load existing lab details if available
                $existing_lab_details = get_post_meta($order_id, 'lab_details', true);
                if ($existing_lab_details) {
                    foreach ($products as $index => $product) {
                        if (isset($existing_lab_details[$index])) {
                            $products[$index]['pdf_file'] = $existing_lab_details[$index]['pdf_file'];
                            $products[$index]['pdf_name'] = $existing_lab_details[$index]['pdf_name'];
                        }
                    }
                }
                
            } else {
                echo '<div class="error"><p>Invalid Order ID.</p></div>';
            }
        }
    }

    // Handle file uploads
    if (isset($_FILES['pdf_upload']) && !empty($_FILES['pdf_upload']['name'])) {
        $upload_details = array();
        $uploaded_files = $_FILES['pdf_upload'];
        $uploaded_files_count = count($uploaded_files['name']);

        // Retrieve the products from the transient
        $products = get_transient('wlr_products_' . $order_id);
        if ($products === false) {
            echo '<div class="error"><p>Products data not found. Please submit the order ID again.</p></div>';
        } else {
            // Debugging: Show how many files were uploaded
            echo '<div class="updated"><p>Number of files uploaded: ' . $uploaded_files_count . '</p></div>';

            // Ensure that the number of files matches the number of products
            if ($uploaded_files_count === count($products)) {
                foreach ($uploaded_files['name'] as $index => $file_name) {
                    if ($uploaded_files['error'][$index] === UPLOAD_ERR_OK) {
                        $tmp_name = $uploaded_files['tmp_name'][$index];
                        $upload_dir = wp_upload_dir();
                        $upload_path = $upload_dir['path'] . '/' . basename($file_name);

                        if (move_uploaded_file($tmp_name, $upload_path)) {
                            $file_url = $upload_dir['url'] . '/' . basename($file_name);
                            $file_name = basename($file_name);

                            // Match the file to the product based on the index
                            $product_index = $index;
                            if (isset($products[$product_index])) {
                                $products[$product_index]['pdf_file'] = $file_url;
                                $products[$product_index]['pdf_name'] = $file_name;
                            }

                            $upload_details[] = array(
                                'product_name' => $products[$product_index]['product_name'],
                                'pdf_file' => $file_url,
                                'pdf_name' => $file_name,
                            );
                        } else {
                            echo '<div class="error"><p>Failed to upload file: ' . esc_html($file_name) . '</p></div>';
                        }
                    }
                }


                // Check if the array is not empty
                if (!empty($upload_details)) {
                    // Sanitize and prepare the data
                    $sanitized_details = array_map(function($detail) {
                        return array(
                            'product_name' => sanitize_text_field($detail['product_name']),
                            'pdf_file' => esc_url_raw($detail['pdf_file']),
                            'pdf_name' => sanitize_text_field($detail['pdf_name']),
                        );
                    }, $upload_details);

                    // Update the post meta with the lab details
                    $update_result = update_post_meta($order_id, 'lab_details', $sanitized_details);
                    
                    // Debugging: Output the result of update_post_meta
                    if ($update_result) {
                        echo '<div class="updated"><p>Files uploaded and details saved.</p></div>';
                    } else {
                        echo '<div class="error"><p>Failed to save lab details. Please check the format and content.</p></div>';
                    }
                }

                // Clear the transient
                delete_transient('wlr_products_' . $order_id);
            } else {
                // Provide detailed error message
                $products_count = count($products);
                echo '<div class="error"><p>Number of uploaded files (' . $uploaded_files_count . ') does not match the number of products (' . $products_count . '). Ensure each product has an associated PDF.</p></div>';
            }
        }
    }

    // Ensure the products array is retrieved if it's empty
    if (empty($products) && $order_id) {
        $products = get_transient('wlr_products_' . $order_id);
        if ($products === false) {
            $products = array();
        }
    }
    
    ?>
    <div class="wrap">
        <h1>Lab Report Upload Admin Page</h1>
        <form method="post">
            <input type="text" name="orderid" value="<?php echo esc_attr($order_id); ?>" placeholder="Write Your Order Id Here" />
            <input type="submit" name="submit" value="Submit" />
        </form>

        <?php if (!empty($products)) : ?>
            <form method="post" enctype="multipart/form-data" style="margin-top:20px;">
                <input type="hidden" name="orderid" value="<?php echo esc_attr($order_id); ?>" />
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Upload PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $index => $product) : ?>
                            <tr>
                                <td><?php echo esc_html($product['product_name']); ?></td>
                                <td>
                                    <?php if (!empty($product['pdf_file'])): ?>
                                        <a href="<?php echo esc_url($product['pdf_file']); ?>" target="_blank"><?php echo esc_html($product['pdf_name']); ?></a>
                                    <?php endif; ?>
                                    <input type="file" name="pdf_upload[]" />
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <input type="submit" value="Upload PDFs" style="margin-top:20px;"/>
            </form>
        <?php endif; ?>
    </div>
    <?php
}
