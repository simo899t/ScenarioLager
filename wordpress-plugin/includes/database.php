<?php
// Database functions for Scenario Lager

function scenario_lager_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Products table
    $table_products = $wpdb->prefix . 'sl_products';
    $sql_products = "CREATE TABLE $table_products (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(200) NOT NULL,
        sku varchar(100) NOT NULL,
        description text,
        quantity int(11) DEFAULT 1,
        unit varchar(50) DEFAULT 'stk',
        location varchar(100),
        is_available tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY sku (sku)
    ) $charset_collate;";
    
    // Checkouts table
    $table_checkouts = $wpdb->prefix . 'sl_checkouts';
    $sql_checkouts = "CREATE TABLE $table_checkouts (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        product_id bigint(20) NOT NULL,
        user_id bigint(20) NOT NULL,
        borrower_name varchar(200) NOT NULL,
        from_date datetime NOT NULL,
        to_date datetime NOT NULL,
        status varchar(20) DEFAULT 'active',
        notes text,
        checked_out_at datetime DEFAULT CURRENT_TIMESTAMP,
        returned_at datetime,
        PRIMARY KEY  (id),
        KEY product_id (product_id),
        KEY user_id (user_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_products);
    dbDelta($sql_checkouts);
}

// Get all products
function scenario_lager_get_products($search = '') {
    global $wpdb;
    $table = $wpdb->prefix . 'sl_products';
    
    if (!empty($search)) {
        $search = '%' . $wpdb->esc_like($search) . '%';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE name LIKE %s OR sku LIKE %s ORDER BY name",
            $search, $search
        ));
    }
    
    return $wpdb->get_results("SELECT * FROM $table ORDER BY name");
}

// Get single product
function scenario_lager_get_product($id) {
    global $wpdb;
    $table = $wpdb->prefix . 'sl_products';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
}

// Add product
function scenario_lager_add_product($data) {
    global $wpdb;
    $table = $wpdb->prefix . 'sl_products';
    
    return $wpdb->insert($table, array(
        'name' => sanitize_text_field($data['name']),
        'sku' => sanitize_text_field($data['sku']),
        'description' => sanitize_textarea_field($data['description']),
        'quantity' => 1,
        'unit' => 'stk',
        'location' => sanitize_text_field($data['location']),
        'is_available' => 1
    ));
}

// Update product
function scenario_lager_update_product($id, $data) {
    global $wpdb;
    $table = $wpdb->prefix . 'sl_products';
    
    return $wpdb->update(
        $table,
        array(
            'name' => sanitize_text_field($data['name']),
            'sku' => sanitize_text_field($data['sku']),
            'description' => sanitize_textarea_field($data['description']),
            'location' => sanitize_text_field($data['location'])
        ),
        array('id' => $id)
    );
}

// Delete product
function scenario_lager_delete_product($id) {
    global $wpdb;
    $table = $wpdb->prefix . 'sl_products';
    return $wpdb->delete($table, array('id' => $id));
}

// Checkout product
function scenario_lager_checkout_product($product_id, $data) {
    global $wpdb;
    $table_checkouts = $wpdb->prefix . 'sl_checkouts';
    $table_products = $wpdb->prefix . 'sl_products';
    
    // Add checkout record
    $result = $wpdb->insert($table_checkouts, array(
        'product_id' => $product_id,
        'user_id' => get_current_user_id(),
        'borrower_name' => sanitize_text_field($data['borrower_name']),
        'from_date' => $data['from_date'],
        'to_date' => $data['to_date'],
        'notes' => sanitize_textarea_field($data['notes']),
        'status' => 'active'
    ));
    
    if ($result) {
        // Mark product as unavailable
        $wpdb->update(
            $table_products,
            array('is_available' => 0),
            array('id' => $product_id)
        );
    }
    
    return $result;
}

// Return product
function scenario_lager_return_product($product_id) {
    global $wpdb;
    $table_checkouts = $wpdb->prefix . 'sl_checkouts';
    $table_products = $wpdb->prefix . 'sl_products';
    
    // Find active checkout
    $checkout = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_checkouts WHERE product_id = %d AND status = 'active' LIMIT 1",
        $product_id
    ));
    
    if ($checkout) {
        // Mark as returned
        $wpdb->update(
            $table_checkouts,
            array('status' => 'returned', 'returned_at' => current_time('mysql')),
            array('id' => $checkout->id)
        );
        
        // Mark product as available
        $wpdb->update(
            $table_products,
            array('is_available' => 1),
            array('id' => $product_id)
        );
        
        return true;
    }
    
    return false;
}

// Get active checkout for product
function scenario_lager_get_active_checkout($product_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'sl_checkouts';
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE product_id = %d AND status = 'active' LIMIT 1",
        $product_id
    ));
}

// Get checkout history for product
function scenario_lager_get_checkout_history($product_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'sl_checkouts';
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE product_id = %d ORDER BY checked_out_at DESC",
        $product_id
    ));
}

// Get all checkouts
function scenario_lager_get_all_checkouts($limit = 50, $offset = 0) {
    global $wpdb;
    $table = $wpdb->prefix . 'sl_checkouts';
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table ORDER BY checked_out_at DESC LIMIT %d OFFSET %d",
        $limit, $offset
    ));
}
?>
