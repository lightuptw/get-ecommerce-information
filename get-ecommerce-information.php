<?php
/**
 * @package Get Ecommerce Information
 * @version 1.0.0
 */
/*
Plugin Name: Get Ecommerce Information
Plugin URI: https://github.com/lightuptw/get-ecommerce-information/
Description: A plugin to fetch user, order, product, etc. information.
Version: 1.0.0
Author: Lightup
Author URI: https://lightup.tw/
License: None
*/

// 防止直接訪問
if ( !defined( 'ABSPATH' ) ) {
    exit; 
}

// 創建管理頁面
function uop_info_menu() {
    add_menu_page(
        'Get Ecommerce Information', // 標題
        'Get Ecommerce Information',   // 菜單名稱
        'manage_options',          // 權限
        'get-ecommerce-information', // 菜單 slug
        'uop_info_page',           // 顯示的函數
        'dashicons-list-view',          // 菜單圖示
        51                          // 菜單位置(在 Woocommerce 之後)
    );
}
add_action( 'admin_menu', 'uop_info_menu' );

// 插件主頁顯示函數
function uop_info_page() {
    // 確保 WooCommerce 已加載
    if ( !class_exists( 'WooCommerce' ) ) {
        echo '<div class="wrap"><h1>WooCommerce is not installed or activated.</h1></div>';
        return;
    }

    // 分頁參數
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'users';
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 50;

    echo '<div class="wrap">';
    echo '<h1>Get Ecommerce Information</h1>';
    echo '<h2 class="nav-tab-wrapper">';
    echo '<a href="?page=get-ecommerce-information&tab=users" class="nav-tab ' . ($current_tab === 'users' ? 'nav-tab-active' : '') . '">Users</a>';
    echo '<a href="?page=get-ecommerce-information&tab=products" class="nav-tab ' . ($current_tab === 'products' ? 'nav-tab-active' : '') . '">Products</a>';
    echo '<a href="?page=get-ecommerce-information&tab=orders" class="nav-tab ' . ($current_tab === 'orders' ? 'nav-tab-active' : '') . '">Orders</a>';
    echo '</h2>';

    if ($current_tab === 'users') {
        uop_display_users($current_page, $per_page);
    } elseif ($current_tab === 'products') {
        uop_display_products($current_page, $per_page);
    } elseif ($current_tab === 'orders') {
        uop_display_orders($current_page, $per_page);
    }

    echo '</div>';
}

// 顯示使用者資料
function uop_display_users($current_page, $per_page) {
    $offset = ($current_page - 1) * $per_page;
    $args = array(
        'number' => $per_page,
        'offset' => $offset
    );
    $users = get_users($args);

    echo '<h2>User Information</h2>';
    if ($users) {
        echo '<table class="widefat fixed">';
        echo '<thead><tr><th>ID</th><th>Username</th><th>Email</th></tr></thead><tbody>';
        foreach ($users as $user) {
            echo '<tr><td>' . esc_html($user->ID) . '</td><td>' . esc_html($user->user_login) . '</td><td>' . esc_html($user->user_email) . '</td></tr>';
        }
        echo '</tbody></table>';
    } else {
        echo 'No users found.';
    }

    uop_pagination_links('users', $current_page, $per_page, count_users()['total_users']);
}

// 顯示商品資料
function uop_display_products($current_page, $per_page) {
    $offset = ($current_page - 1) * $per_page;
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $per_page,
        'offset' => $offset
    );
    $products = get_posts($args);

    echo '<h2>Product Information</h2>';
    if ($products) {
        echo '<table class="widefat fixed">';
        echo '<thead><tr><th>ID</th><th>Name</th><th>SKU</th></tr></thead><tbody>';
        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);
            if ($product) {
                echo '<tr><td>' . esc_html($product->get_id()) . '</td><td>' . esc_html($product->get_name()) . '</td><td>' . esc_html($product->get_sku()) . '</td></tr>';
            }
        }
        echo '</tbody></table>';
    } else {
        echo 'No products found.';
    }

    uop_pagination_links('products', $current_page, $per_page, wp_count_posts('product')->publish);
}

// 顯示訂單資料
function uop_display_orders($current_page, $per_page) {
    $offset = ($current_page - 1) * $per_page;
    $args = array(
        'post_type'      => 'shop_order',
        'posts_per_page' => $per_page,
        'offset'         => $offset,
        'post_status'    => array('wc-completed', 'wc-processing'), // 篩選完成或處理中的訂單
    );
    $query = new WP_Query($args);

    echo '<h2>Order Information</h2>';
    if ($query->have_posts()) {
        echo '<table class="widefat fixed">';
        echo '<thead><tr><th>ID</th><th>Date</th><th>Total</th></tr></thead><tbody>';
        while ($query->have_posts()) {
            $query->the_post();
            $order = wc_get_order(get_the_ID());
            if ($order) {
                echo '<tr>';
                echo '<td>' . esc_html($order->get_id()) . '</td>';
                echo '<td>' . esc_html($order->get_date_created()->date('Y-m-d H:i:s')) . '</td>';
                echo '<td>' . esc_html($order->get_total()) . '</td>';
                echo '</tr>';
            }
        }
        echo '</tbody></table>';
    } else {
        echo 'No orders found.';
    }

    // 分頁導航
    $total_orders = wp_count_posts('shop_order')->publish; // 計算總訂單數量
    uop_pagination_links('orders', $current_page, $per_page, $total_orders);

    // 恢復原始查詢
    wp_reset_postdata();
}

// 分頁連結生成函數
function uop_pagination_links($tab, $current_page, $per_page, $total_items) {
    $total_pages = ceil($total_items / $per_page);

    if ($total_pages > 1) {
        echo '<div class="tablenav-pages">';
        echo '<span class="pagination-links">';

        if ($current_page > 1) {
            echo '<a class="prev-page" href="?page=get-ecommerce-information&tab=' . $tab . '&paged=' . ($current_page - 1) . '">&laquo; Previous</a>';
        }

        for ($i = 1; $i <= $total_pages; $i++) {
            $class = $i === $current_page ? ' class="current"' : '';
            echo '<a' . $class . ' href="?page=get-ecommerce-information&tab=' . $tab . '&paged=' . $i . '">' . $i . '</a> ';
        }

        if ($current_page < $total_pages) {
            echo '<a class="next-page" href="?page=get-ecommerce-information&tab=' . $tab . '&paged=' . ($current_page + 1) . '">Next &raquo;</a>';
        }

        echo '</span>';
        echo '</div>';
    }
}   