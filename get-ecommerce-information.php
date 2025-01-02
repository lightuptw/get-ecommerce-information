<?php
/*
Plugin Name: Get Ecommerce Information
Plugin URI: https://github.com/lightuptw/get-ecommerce-information/
Description: A plugin to fetch user, order, product, etc. information.
Version: 1.0
Author: Silvester Peng
Author URI: https://example.com/
License: GPL2
*/

// 防止直接訪問
if ( !defined( 'ABSPATH' ) ) {
    exit; 
}

// 創建管理頁面
function uop_info_menu() {
    add_menu_page(
        'User Order Product Info', // 標題
        'User Order Info',         // 菜單名稱
        'manage_options',          // 權限
        'user-order-product-info', // 菜單 slug
        'uop_info_page',           // 顯示的函數
        'dashicons-cart',          // 菜單圖示
        20                          // 菜單位置
    );
}
add_action( 'admin_menu', 'uop_info_menu' );

// 插件主頁顯示函數
function uop_info_page() {
    // 獲取當前使用者
    $current_user = wp_get_current_user();
    $user_info = 'Name: ' . $current_user->user_login . '<br>';
    $user_info .= 'Email: ' . $current_user->user_email . '<br>';
    
    // 獲取最新訂單資料
    $args = array(
        'post_type' => 'shop_order',
        'posts_per_page' => 1, 
        'post_status' => 'wc-completed' 
    );
    $orders = get_posts( $args );
    if ( $orders ) {
        $order = $orders[0];
        $order_info = 'Order ID: ' . $order->ID . '<br>';
        $order_info .= 'Order Date: ' . get_the_date( 'Y-m-d', $order ) . '<br>';
        
        // 獲取訂單中的商品
        $order_items = wc_get_order_items( $order->ID );
        $product_info = 'Products:<br>';
        foreach ( $order_items as $item ) {
            $product = $item->get_product();
            $product_info .= 'Product Name: ' . $product->get_name() . '<br>';
            $product_info .= 'Product SKU: ' . $product->get_sku() . '<br>';
        }
    } else {
        $order_info = 'No completed orders found.<br>';
        $product_info = 'No products found.<br>';
    }

    // 顯示資料
    echo '<div class="wrap">';
    echo '<h1>User, Order, and Product Info</h1>';
    echo '<h2>User Information</h2>';
    echo $user_info;
    echo '<h2>Order Information</h2>';
    echo $order_info;
    echo '<h2>Product Information</h2>';
    echo $product_info;
    echo '</div>';
}
