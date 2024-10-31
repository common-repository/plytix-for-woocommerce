<?php
/**
 * Class taking care about analytics integration for different events:
 * Views (Single Product & Categories)
 * AddToCart (From Single Product & Categories)
 * RemoveFromCart
 * Checkout
 * Conversion
 * Todo: Check difference between enqueue on footer or echo script. Sometimes is failing to send track.
 */
if ( ! class_exists( 'Plytix_Integration' ) ) :

class Constants {
    const ADD_IMPRESSION = 'addImpression';
    const ADD_PRODUCT = 'addProduct';
    const SET_ACTION = 'setAction';
    const CHECKOUT = 'checkout';
    const PRODUCT_DETAIL = 'detail';
    const CONVERSION = 'purchase';
    const ADD_TO_CART = 'add';
    const REMOVE_FROM_CART = 'remove';
    const PLYTIX_COOKIE = 'Scope';
}
    

class Plytix_Integration {
    /**
     * Registering actions with Woo hooks we want to track
     */
    function __construct() {
        $product_count = 0;
        // Add to cart Single Product
        add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'add_to_cart' ) );
        // Add to cart Archive Product
        add_action( 'woocommerce_after_shop_loop_item',     array( $this, 'loop_add_to_cart' ) );
        // Product View
        add_action( 'woocommerce_after_single_product'    , array( $this, 'product_view' ));
        // Product View Category / Search
        add_action( 'woocommerce_after_shop_loop_item'    , array( $this, 'product_view_loop' ) );
        // Checkout Process
        add_action( 'woocommerce_after_checkout_form'     , array( $this, 'checkout_process' ) );
        // Convesion
        add_action( 'woocommerce_thankyou'                , array( $this, 'conversion') );

        add_filter( 'wp_footer', array( $this, 'wp_footer'), 10, 1);
        add_filter( 'woocommerce_update_cart_action_cart_updated', array( $this, 'woocommerce_update_cart_action_cart_updated'), 10, 1);
        add_filter( 'woocommerce_update_cart_validation', array( $this, 'woocommerce_update_cart_validation'), 10, 4);
        add_filter( 'woocommerce_cart_item_removed_title', array( $this, 'woocommerce_cart_item_removed_title'), 10, 2);
    }

    private static $cart_operations = null;
    function addOperation($operation) {
        if (!self::$cart_operations) {
            $cart_operations = $this->getOperations();
        }
        self::$cart_operations[] = $operation;
        $cookie=json_encode(self::$cart_operations);
        setcookie(Constants::PLYTIX_COOKIE, $cookie);
    }

    function getOperations() {
        if (self::$cart_operations) {
            return self::$cart_operations;
        }
        else if ( isset($_COOKIE[Constants::PLYTIX_COOKIE]) && !empty($_COOKIE[Constants::PLYTIX_COOKIE])) {
            self::$cart_operations = json_decode(str_replace("\\", "", $_COOKIE[Constants::PLYTIX_COOKIE]));
            return self::$cart_operations;
        }
        else {
            self::$cart_operations=array();
            return self::$cart_operations;
        }
    }

    function getProductJson($wc_product, $wc_category) {
        if ($wc_product->get_title() || $wc_product->get_sku()){
            $product = new stdClass();
            if ($wc_product->get_title()) $product->name = $wc_product->get_title();
            if ($wc_product->get_id()) $product->id = $wc_product->get_id() ."";
            if ($wc_product->get_price()) $product->price = floatval($wc_product->get_price());

            $settings = get_option('plytix-settings-options');            
            if ($settings && isset($settings['field_gtin']) && is_array($settings['field_gtin'])) {
                $i=0;
                $gtin=null;
                while ($i< count($settings['field_gtin']) && empty($gtin)) {
                    $gtin = get_post_meta( $wc_product->get_id() , $settings['field_gtin'][$i], true );
                    $i++;
                }                
                if (!empty($gtin)) {
                    $product->gtin = $gtin;
                }
            }
            if ($wc_category){
                $category = $wc_category;
                $categories = "";
                foreach ($category as $term) {
                    $categories .= $term->name . ",";
                }
                $product->category = rtrim($categories, ",");
            }
            return $product;
        }
        else return null;
    }

    function woocommerce_cart_item_removed_title($title, $cart) {
        $variation = new stdClass();
        $variation->before = $cart["quantity"];
        $variation->after = 0;
        
        $wc_product = wc_get_product($cart['product_id']);
        $wc_category = get_the_terms($cart['product_id'], "product_cat");
        $variation->product = $this->getProductJson($wc_product, $wc_category);
        $this->addOperation($variation);
        return $title;
    }

    
    function wp_footer($args) {
        $cart_rows = $this->getOperations();
        if ($cart_rows && count($cart_rows)>0) {
            $js_remove = "";
            $js_add = "";
            
            foreach ($cart_rows as $cart) {
                if ($cart->before > $cart->after) {
                    $cart->product->quantity = $cart->before - $cart->after;
                    $js_remove .= "_pl('".Constants::ADD_PRODUCT."', ".json_encode($cart->product).");";
                }
                else {
                    $cart->product->quantity = $cart->after - $cart->before;
                    $js_add .= "_pl('".Constants::ADD_PRODUCT."', ".json_encode($cart->product).");";
                }
            }        
            if (!empty($js_remove)) {
                $js_remove .= "_pl('".Constants::SET_ACTION."', '".Constants::REMOVE_FROM_CART."');";  
                echo("<script>$js_remove</script>");
            }
            if (!empty($js_add)) {
                $js_add .= "_pl('".Constants::SET_ACTION."', '".Constants::ADD_TO_CART."');";
                echo("<script>$js_add</script>");
            }            
            setcookie(Constants::PLYTIX_COOKIE, null);
            //$this::$cart_operations = null;
        }
        return $args;
    }

    function woocommerce_update_cart_validation($true, $cart_item_key, $values, $quantity) {
        try {
            $variation = new stdClass();
            $variation->before = $values["quantity"];
            $variation->after = $quantity;
            //$wc_product = wc_get_product($values['product_id']);
            $wc_product = wc_get_product($values['product_id']);
            $wc_category = get_the_terms($values['product_id'], "product_cat");
            $variation->product = $this->getProductJson($wc_product, $wc_category);            
            $this->addOperation($variation);
            error_log(print_r(count($this->getOperations()), true));
        }
        catch (Exception $ex) {

        }
        return $true;
    }

    function woocommerce_update_cart_action_cart_updated($passed) {
        return $passed;
    }

    /**
     * Register Conversion products in thank you page
     * It will only work if payment method redirects to thank you page when order is paid.
     *
     * @param $order_id
     */
    function conversion($order_id) {
        $order = new WC_Order( $order_id );
        $items = $order->get_items();
        $js_echo = "";
        foreach ($items as $item) {
            $wc_product = wc_get_product($item['product_id']);
            $wc_category = null;
            if ($item['product_id']) {
                $wc_category = get_the_terms($item['product_id'], "product_cat");
            }
            $product = $this->getProductJson($wc_product, $wc_category);
            if ($product){
                $product->quantity = $item["qty"];
                $json_product = json_encode($product);
                $js_echo .= "_pl('".Constants::ADD_PRODUCT."', ".$json_product.");";
            }
        }
        if (!empty($js_echo)) {
            $js_echo .= "_pl('".Constants::SET_ACTION."', '".Constants::CONVERSION."');";
            echo "<script>$js_echo</script>";
        }
    }

    /**
     * Register Checkout Products Page
     */
    function checkout_process() {
        global $woocommerce;
        $js_echo = "";
        foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
            $wc_product = wc_get_product($cart_item["product_id"]);
            $product = $this->getProductJson($wc_product, get_the_terms(get_the_ID(), "product_cat"));
            if ($product){
                $product->quantity = $cart_item['quantity'];
                $json_product = json_encode($product);
                $js_echo .= "_pl('".Constants::ADD_PRODUCT."', ".$json_product.");";
                
            }
        }
        if (!empty($js_echo)) {
            $js_echo .= "_pl('".Constants::SET_ACTION."', '".Constants::CHECKOUT."');";
            echo "<script>$js_echo</script>";
        }
    }

    /**
     * Registering Product View (Loop)
     */
    function product_view_loop() {
        $wc_product = wc_get_product(get_the_ID());
        $product = $this->getProductJson($wc_product, get_the_terms(get_the_ID(), "product_cat"));
        if ($product){
            global $product_count;
            $product->position = ++$product_count;
            $json_product = json_encode($product);
            $js_echo = "_pl('".Constants::ADD_IMPRESSION."', ".$json_product.");";
            wc_enqueue_js($js_echo);
        }
    }

    /**
     * Registering Product View
     */
    function product_view() {
        $wc_product = wc_get_product(get_the_ID());
        $product = $this->getProductJson($wc_product, get_the_terms(get_the_ID(), "product_cat"));
        if ($product){
            $json_product = json_encode($product);
            $js_echo = "_pl('".Constants::ADD_PRODUCT."', ".$json_product.");";
            $js_echo .= "_pl('".Constants::SET_ACTION."', '".Constants::PRODUCT_DETAIL."');";
            wc_enqueue_js($js_echo);
        }
    }

    /**
     * Registering Add To Cart (Single Product)
     */
    function add_to_cart() {
        $wc_product = wc_get_product(get_the_ID());
        $product = $this->getProductJson($wc_product, get_the_terms(get_the_ID(), "product_cat"));
        if ($product){
            $json_product = json_encode($product);
            $js_echo  = "jQuery('.single_add_to_cart_button').click(function(){";
            $js_echo .= "var json_product = ".$json_product.";";
            $js_echo .= "json_product.quantity = jQuery(this).parent().find(\"input[name=quantity]\").val();";
            $js_echo .= "_pl('".Constants::ADD_PRODUCT."', ".$json_product.");";            
            $js_echo .= "_pl('".Constants::SET_ACTION."', '".Constants::ADD_TO_CART."');";
            $js_echo .= "});";
            wc_enqueue_js($js_echo);
        }
    }

    /**
     * Registering Add to cart Archive Product
     */
    function loop_add_to_cart() {
        $wc_product = wc_get_product(get_the_ID());
        $product = $this->getProductJson($wc_product, get_the_terms(get_the_ID(), "product_cat"));
        if ($product){
            $json_product = json_encode($product);            
            $js_echo  = "jQuery('.add_to_cart_button[data-product_id=".get_the_ID()."]').click(function(){ \n";
            $js_echo .= "_pl('".Constants::ADD_PRODUCT."', ".$json_product.");\n";            
            $js_echo .= "_pl('".Constants::SET_ACTION."', '".Constants::ADD_TO_CART."');\n";
            $js_echo .= "});\n";
            wc_enqueue_js($js_echo);
        }
    }
}
endif;
