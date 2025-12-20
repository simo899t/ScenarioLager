<?php
// Shortcodes for frontend display

// Start session for frontend auth
add_action('init', 'scenario_lager_start_session');
function scenario_lager_start_session() {
    if (!session_id()) {
        session_start();
    }
}

// Register shortcodes
add_shortcode('scenario_lager', 'scenario_lager_frontend_shortcode');

function scenario_lager_frontend_shortcode() {
    // Handle logout
    if (isset($_GET['sl_logout'])) {
        unset($_SESSION['sl_logged_in']);
        wp_redirect(remove_query_arg('sl_logout'));
        exit;
    }
    
    // Handle login
    if (isset($_POST['sl_login'])) {
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];
        
        // Simple auth - in production, use proper password hashing
        if ($username === 'admin' && $password === 'admin123') {
            $_SESSION['sl_logged_in'] = true;
            $_SESSION['sl_username'] = $username;
        } else {
            return scenario_lager_login_form('Invalid username or password');
        }
    }
    
    // Check if logged in
    if (empty($_SESSION['sl_logged_in'])) {
        return scenario_lager_login_form();
    }
    
    // Handle actions
    if (isset($_POST['sl_action'])) {
        if (!isset($_POST['sl_nonce']) || !wp_verify_nonce($_POST['sl_nonce'], 'sl_action')) {
            return '<div class="sl-alert sl-alert-danger">Security check failed</div>';
        }
        
        if ($_POST['sl_action'] === 'add' && isset($_POST['sku'])) {
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'sl_products',
                array(
                    'sku' => sanitize_text_field($_POST['sku']),
                    'name' => sanitize_text_field($_POST['name']),
                    'description' => sanitize_textarea_field($_POST['description']),
                    'location' => sanitize_text_field($_POST['location']),
                    'is_available' => 1
                )
            );
            wp_redirect(remove_query_arg(['sl_page', 'id'], get_permalink()));
            exit;
        } elseif ($_POST['sl_action'] === 'checkout' && isset($_POST['product_id'])) {
            scenario_lager_checkout_product(
                $_POST['product_id'],
                sanitize_text_field($_POST['borrower_name']),
                sanitize_text_field($_POST['from_date']),
                sanitize_text_field($_POST['to_date'])
            );
            wp_redirect(add_query_arg(['sl_page' => 'info', 'id' => $_POST['product_id']], get_permalink()));
            exit;
        } elseif ($_POST['sl_action'] === 'return' && isset($_POST['product_id'])) {
            scenario_lager_return_product($_POST['product_id']);
            wp_redirect(remove_query_arg(['sl_page', 'id'], get_permalink()));
            exit;
        }
    }
    
    // Get current page
    $page = isset($_GET['sl_page']) ? sanitize_text_field($_GET['sl_page']) : 'inventory';
    $product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    ob_start();
    
    // Render navigation and content
    echo '<div class="scenario-lager-frontend">';
    scenario_lager_render_nav($page);
    
    switch ($page) {
        case 'add':
            scenario_lager_render_add_page();
            break;
        case 'checkout':
            scenario_lager_render_checkout_page($product_id);
            break;
        case 'info':
            scenario_lager_render_info_page($product_id);
            break;
        case 'history':
            scenario_lager_render_history_page();
            break;
        default:
            scenario_lager_render_inventory_page();
            break;
    }
    
    echo '</div>';
    
    return ob_get_clean();
}

function scenario_lager_login_form($error = '') {
    ob_start();
    ?>
    <div class="sl-login-container">
        <div class="sl-login-box">
            <h1>Scenari<span class="red-o">o</span> Lager</h1>
            <?php if ($error): ?>
                <div class="sl-alert sl-alert-danger"><?php echo esc_html($error); ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="sl-form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="sl-form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" name="sl_login" class="sl-btn sl-btn-primary sl-btn-full">Login</button>
            </form>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function scenario_lager_render_nav($current_page) {
    $base_url = get_permalink();
    ?>
    <nav class="sl-navbar">
        <div class="sl-nav-container">
            <div class="sl-nav-brand">Scenari<span class="red-o">o</span> Lager</div>
            <ul class="sl-nav-menu">
                <li><a href="<?php echo $base_url; ?>" class="<?php echo $current_page === 'inventory' ? 'active' : ''; ?>">Inventory</a></li>
                <li><a href="<?php echo add_query_arg('sl_page', 'add', $base_url); ?>" class="<?php echo $current_page === 'add' ? 'active' : ''; ?>">Add Item</a></li>
                <li><a href="<?php echo add_query_arg('sl_page', 'history', $base_url); ?>" class="<?php echo $current_page === 'history' ? 'active' : ''; ?>">History</a></li>
                <li><a href="<?php echo add_query_arg('sl_logout', '1', $base_url); ?>" class="logout">Logout</a></li>
            </ul>
        </div>
    </nav>
    <?php
}

function scenario_lager_render_inventory_page() {
    $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    $products = scenario_lager_get_products($search);
    $base_url = get_permalink();
    
    ?>
    <div class="sl-container">
        <div class="sl-page-header">
            <h1>Inventory</h1>
            <a href="<?php echo add_query_arg('sl_page', 'add', $base_url); ?>" class="sl-btn sl-btn-primary">Add New</a>
        </div>
        
        <div class="sl-search-box">
            <form method="get">
                <input type="text" name="search" value="<?php echo esc_attr($search); ?>" placeholder="Search by name or ID...">
                <button type="submit" class="sl-btn sl-btn-primary">Search</button>
                <?php if ($search): ?>
                    <a href="<?php echo $base_url; ?>" class="sl-btn">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="sl-section">
            <table class="sl-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="5">No items found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr class="<?php echo $product->is_available ? '' : 'item-in-use'; ?>">
                                <td><?php echo esc_html($product->sku); ?></td>
                                <td><strong><?php echo esc_html($product->name); ?></strong></td>
                                <td><?php echo esc_html($product->location ?: '-'); ?></td>
                                <td>
                                    <?php if ($product->is_available): ?>
                                        <span class="sl-badge sl-badge-success">Available</span>
                                    <?php else: ?>
                                        <span class="sl-badge sl-badge-warning">In Use</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo add_query_arg(['sl_page' => 'info', 'id' => $product->id], $base_url); ?>" class="sl-btn sl-btn-sm">Info</a>
                                    <?php if ($product->is_available): ?>
                                        <a href="<?php echo add_query_arg(['sl_page' => 'checkout', 'id' => $product->id], $base_url); ?>" class="sl-btn sl-btn-primary sl-btn-sm">Use</a>
                                    <?php else: ?>
                                        <form method="post" style="display:inline;">
                                            <?php wp_nonce_field('sl_action', 'sl_nonce'); ?>
                                            <input type="hidden" name="sl_action" value="return">
                                            <input type="hidden" name="product_id" value="<?php echo $product->id; ?>">
                                            <button type="submit" class="sl-btn sl-btn-sm" onclick="return confirm('Mark this item as returned?')">Return</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

function scenario_lager_render_checkout_page($product_id) {
    global $wpdb;
    $product = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sl_products WHERE id = %d",
        $product_id
    ));
    
    if (!$product) {
        echo '<div class="sl-alert sl-alert-danger">Product not found</div>';
        return;
    }
    
    if (!$product->is_available) {
        echo '<div class="sl-alert sl-alert-warning">This item is currently in use</div>';
        return;
    }
    
    $base_url = get_permalink();
    $now = date('Y-m-d\TH:i');
    $tomorrow = date('Y-m-d\TH:i', strtotime('+1 day'));
    
    ?>
    <div class="sl-container">
        <div class="sl-page-header">
            <h1>Check Out Item</h1>
            <a href="<?php echo $base_url; ?>" class="sl-btn">Back to Inventory</a>
        </div>
        
        <div class="sl-section">
            <h2><?php echo esc_html($product->name); ?></h2>
            <p><strong>ID:</strong> <?php echo esc_html($product->sku); ?></p>
            <p><strong>Location:</strong> <?php echo esc_html($product->location ?: '-'); ?></p>
            
            <form method="post" class="sl-form">
                <?php wp_nonce_field('sl_action', 'sl_nonce'); ?>
                <input type="hidden" name="sl_action" value="checkout">
                <input type="hidden" name="product_id" value="<?php echo $product->id; ?>">
                
                <div class="sl-form-group">
                    <label>Borrower Name *</label>
                    <input type="text" name="borrower_name" required>
                </div>
                
                <div class="sl-form-row">
                    <div class="sl-form-group">
                        <label>From *</label>
                        <input type="datetime-local" name="from_date" value="<?php echo $now; ?>" required>
                    </div>
                    <div class="sl-form-group">
                        <label>To *</label>
                        <input type="datetime-local" name="to_date" value="<?php echo $tomorrow; ?>" required>
                    </div>
                </div>
                
                <div class="sl-form-actions">
                    <button type="submit" class="sl-btn sl-btn-primary">Check Out</button>
                    <a href="<?php echo $base_url; ?>" class="sl-btn">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php
}

function scenario_lager_render_info_page($product_id) {
    global $wpdb;
    $product = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sl_products WHERE id = %d",
        $product_id
    ));
    
    if (!$product) {
        echo '<div class="sl-alert sl-alert-danger">Product not found</div>';
        return;
    }
    
    // Get current checkout
    $current_checkout = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sl_checkouts WHERE product_id = %d AND status = 'active' ORDER BY created_at DESC LIMIT 1",
        $product_id
    ));
    
    // Get history
    $history = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sl_checkouts WHERE product_id = %d ORDER BY created_at DESC",
        $product_id
    ));
    
    $base_url = get_permalink();
    
    ?>
    <div class="sl-container">
        <div class="sl-page-header">
            <h1>Item Information</h1>
            <a href="<?php echo $base_url; ?>" class="sl-btn">Back to Inventory</a>
        </div>
        
        <div class="sl-info-card">
            <h2><?php echo esc_html($product->name); ?></h2>
            
            <div class="sl-info-grid">
                <div class="sl-info-item">
                    <strong>ID</strong>
                    <span><?php echo esc_html($product->sku); ?></span>
                </div>
                <div class="sl-info-item">
                    <strong>Location</strong>
                    <span><?php echo esc_html($product->location ?: '-'); ?></span>
                </div>
                <div class="sl-info-item">
                    <strong>Status</strong>
                    <span>
                        <?php if ($product->is_available): ?>
                            <span class="sl-badge sl-badge-success">Available</span>
                        <?php else: ?>
                            <span class="sl-badge sl-badge-warning">In Use</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            
            <?php if ($product->description): ?>
                <div class="sl-info-description">
                    <strong>Description</strong>
                    <p><?php echo nl2br(esc_html($product->description)); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($current_checkout): ?>
            <div class="sl-active-checkout">
                <h3>Current Checkout</h3>
                <div class="sl-checkout-details">
                    <p><strong>Borrower:</strong> <?php echo esc_html($current_checkout->borrower_name); ?></p>
                    <p><strong>From:</strong> <?php echo date('d/m/Y H:i', strtotime($current_checkout->from_date)); ?></p>
                    <p><strong>To:</strong> <?php echo date('d/m/Y H:i', strtotime($current_checkout->to_date)); ?></p>
                    <p><strong>Checked out:</strong> <?php echo date('d/m/Y H:i', strtotime($current_checkout->created_at)); ?></p>
                </div>
                <form method="post" style="margin-top:1rem;">
                    <?php wp_nonce_field('sl_action', 'sl_nonce'); ?>
                    <input type="hidden" name="sl_action" value="return">
                    <input type="hidden" name="product_id" value="<?php echo $product->id; ?>">
                    <button type="submit" class="sl-btn sl-btn-primary" onclick="return confirm('Mark this item as returned?')">Return Item</button>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($history)): ?>
            <div class="sl-section">
                <h3>Checkout History</h3>
                <table class="sl-table">
                    <thead>
                        <tr>
                            <th>Borrower</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Checked Out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $checkout): ?>
                            <tr>
                                <td><?php echo esc_html($checkout->borrower_name); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($checkout->from_date)); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($checkout->to_date)); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($checkout->created_at)); ?></td>
                                <td>
                                    <?php if ($checkout->status === 'active'): ?>
                                        <span class="sl-badge sl-badge-warning">Active</span>
                                    <?php else: ?>
                                        <span class="sl-badge sl-badge-success">Returned</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

function scenario_lager_render_add_page() {
    $base_url = get_permalink();
    ?>
    <div class="sl-container">
        <div class="sl-page-header">
            <h1>Add New Item</h1>
            <a href="<?php echo $base_url; ?>" class="sl-btn">Back to Inventory</a>
        </div>
        
        <div class="sl-section">
            <form method="post" class="sl-form">
                <?php wp_nonce_field('sl_action', 'sl_nonce'); ?>
                <input type="hidden" name="sl_action" value="add">
                
                <div class="sl-form-group">
                    <label>ID (SKU) *</label>
                    <input type="text" name="sku" required>
                </div>
                
                <div class="sl-form-group">
                    <label>Name *</label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="sl-form-group">
                    <label>Location</label>
                    <input type="text" name="location">
                </div>
                
                <div class="sl-form-group">
                    <label>Description</label>
                    <textarea name="description" rows="5"></textarea>
                </div>
                
                <div class="sl-form-actions">
                    <button type="submit" class="sl-btn sl-btn-primary">Add Item</button>
                    <a href="<?php echo $base_url; ?>" class="sl-btn">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php
}

function scenario_lager_render_history_page() {
    global $wpdb;
    
    $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    
    // Get all checkouts with product information
    $query = "SELECT c.*, p.name as product_name, p.sku 
              FROM {$wpdb->prefix}sl_checkouts c 
              LEFT JOIN {$wpdb->prefix}sl_products p ON c.product_id = p.id 
              WHERE 1=1";
    
    if ($search) {
        $query .= $wpdb->prepare(" AND (p.name LIKE %s OR p.sku LIKE %s OR c.borrower_name LIKE %s)", 
            '%' . $wpdb->esc_like($search) . '%',
            '%' . $wpdb->esc_like($search) . '%',
            '%' . $wpdb->esc_like($search) . '%'
        );
    }
    
    $query .= " ORDER BY c.created_at DESC";
    $history = $wpdb->get_results($query);
    
    $base_url = get_permalink();
    ?>
    <div class="sl-container">
        <div class="sl-page-header">
            <h1>Checkout History</h1>
        </div>
        
        <div class="sl-search-box">
            <form method="get">
                <input type="hidden" name="sl_page" value="history">
                <input type="text" name="search" value="<?php echo esc_attr($search); ?>" placeholder="Search by item name, ID, or borrower...">
                <button type="submit" class="sl-btn sl-btn-primary">Search</button>
                <?php if ($search): ?>
                    <a href="<?php echo add_query_arg('sl_page', 'history', $base_url); ?>" class="sl-btn">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="sl-section">
            <table class="sl-table">
                <thead>
                    <tr>
                        <th>Item ID</th>
                        <th>Item Name</th>
                        <th>Borrower</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Checked Out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="7">No checkout history found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($history as $checkout): ?>
                            <tr>
                                <td><?php echo esc_html($checkout->sku); ?></td>
                                <td><strong><?php echo esc_html($checkout->product_name); ?></strong></td>
                                <td><?php echo esc_html($checkout->borrower_name); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($checkout->from_date)); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($checkout->to_date)); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($checkout->created_at)); ?></td>
                                <td>
                                    <?php if ($checkout->status === 'active'): ?>
                                        <span class="sl-badge sl-badge-warning">Active</span>
                                    <?php else: ?>
                                        <span class="sl-badge sl-badge-success">Returned</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
?>
