<?php
/**
 * YALLO CHATBOT - CACHE FIX
 * 
 * Replace the enqueue_assets() function in yallo-talent-chatbot.php
 * This fixes the incognito/private browsing issue
 * 
 * Location: Around line 82
 */

/**
 * Enqueue CSS and JavaScript
 */
public function enqueue_assets() {
    // Only load on frontend
    if (is_admin()) {
        return;
    }
    
    // Check if chatbot is enabled
    if (!get_option('yallo_chatbot_enabled', true)) {
        return;
    }
    
    // Check page restrictions
    if (!$this->should_display_on_current_page()) {
        return;
    }
    
    // ===== CACHE FIX: Disable page caching when chatbot loads =====
    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }
    if (!defined('DONOTCACHEDB')) {
        define('DONOTCACHEDB', true);
    }
    if (!defined('DONOTMINIFY')) {
        define('DONOTMINIFY', true);
    }
    if (!defined('DONOTCDN')) {
        define('DONOTCDN', true);
    }
    
    // ===== CACHE FIX: Use timestamp for development, version for production =====
    // For PRODUCTION: Use just the version
    // $version = YALLO_CHATBOT_VERSION;
    
    // For DEVELOPMENT: Use timestamp (forces fresh load every time)
    $version = YALLO_CHATBOT_VERSION . '.' . time();
    
    // ===== NOTE: After testing, switch back to production mode above =====
    
    // Enqueue styles
    wp_enqueue_style(
        'yallo-chatbot-styles',
        YALLO_CHATBOT_PLUGIN_URL . 'assets/css/chatbot.css',
        array(),
        $version
    );
    
    // Enqueue scripts
    wp_enqueue_script(
        'yallo-chatbot-script',
        YALLO_CHATBOT_PLUGIN_URL . 'assets/js/chatbot.js',
        array('jquery'),
        $version,
        true
    );
    
    // Localize script for AJAX
    wp_localize_script('yallo-chatbot-script', 'yalloChatbot', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('yallo_chatbot_nonce'),
        'autoOpen' => get_option('yallo_chatbot_auto_open', true),
        'scrollTrigger' => get_option('yallo_chatbot_scroll_trigger', 50),
        'displayMode' => get_option('yallo_chatbot_display_mode', 'all_pages'),
        'currentUrl' => $this->get_current_url(),
        'timestamp' => time(),
    ));
}

/**
 * Get current URL reliably (add this NEW function after enqueue_assets)
 */
private function get_current_url() {
    // Try WordPress native method first
    if (function_exists('home_url')) {
        global $wp;
        if (isset($wp->request)) {
            $current_url = home_url($wp->request);
            if (!empty($current_url) && $current_url !== home_url()) {
                return $current_url;
            }
        }
    }
    
    // Fallback to REQUEST_URI
    if (isset($_SERVER['REQUEST_URI'])) {
        return home_url($_SERVER['REQUEST_URI']);
    }
    
    // Last resort fallback
    $protocol = is_ssl() ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    
    return $protocol . $host . $uri;
}

/* 
 * ===== INSTRUCTIONS =====
 * 
 * 1. FIND the enqueue_assets() function in yallo-talent-chatbot.php (around line 82)
 * 
 * 2. REPLACE the entire function with the code above
 * 
 * 3. ADD the get_current_url() function right after enqueue_assets()
 * 
 * 4. SAVE the file
 * 
 * 5. CLEAR all caches:
 *    - WordPress cache plugin
 *    - Browser cache (Ctrl+Shift+Delete)
 *    - Server cache (hosting panel)
 * 
 * 6. TEST in incognito/private window
 * 
 * 7. After confirming it works, switch to PRODUCTION mode:
 *    Change line: $version = YALLO_CHATBOT_VERSION . '.' . time();
 *    To:          $version = YALLO_CHATBOT_VERSION;
 * 
 * 8. When you make changes in future, bump the version:
 *    In main plugin file (line 16):
 *    define('YALLO_CHATBOT_VERSION', '1.0.1'); // Increment this
 * 
 * ===== WHY THIS FIXES THE ISSUE =====
 * 
 * The problem: Browser and WordPress cache the CSS/JS files
 * The fix: 
 * - DONOTCACHEPAGE tells cache plugins not to cache
 * - timestamp/version ensures fresh files load
 * - get_current_url() provides reliable page detection
 * 
 * ===== CACHE PLUGIN COMPATIBILITY =====
 * 
 * This fix works with:
 * ✓ W3 Total Cache
 * ✓ WP Super Cache
 * ✓ WP Rocket
 * ✓ LiteSpeed Cache
 * ✓ Cache Enabler
 * ✓ WP Fastest Cache
 * ✓ Autoptimize
 * ✓ Most other cache plugins
 */