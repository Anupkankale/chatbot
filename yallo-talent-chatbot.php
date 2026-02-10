<?php
/**
 * Plugin Name: YALLO Talent Chatbot
 * Plugin URI: https://yallo.com
 * Description: An intelligent chatbot for YALLO talent acquisition and consultation services with a sleek dark theme interface.
 * Version: 1.0.1
 * Author: Anup
 * Author URI: https://yallo.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: yallo-chatbot
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('YALLO_CHATBOT_VERSION', '1.0.1');
define('YALLO_CHATBOT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YALLO_CHATBOT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('YALLO_CHATBOT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class YALLO_Talent_Chatbot {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Add chatbot to footer
        add_action('wp_footer', array($this, 'render_chatbot'));
        
        // Register AJAX endpoints
        add_action('wp_ajax_yallo_submit_lead', array($this, 'handle_lead_submission'));
        add_action('wp_ajax_nopriv_yallo_submit_lead', array($this, 'handle_lead_submission'));
        
        // Register newsletter AJAX endpoints
        add_action('wp_ajax_yallo_newsletter_subscribe', array($this, 'handle_newsletter_subscription'));
        add_action('wp_ajax_nopriv_yallo_newsletter_subscribe', array($this, 'handle_newsletter_subscription'));
        
        // Register admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add settings link on plugins page
        add_filter('plugin_action_links_' . YALLO_CHATBOT_PLUGIN_BASENAME, array($this, 'add_settings_link'));
    }
    
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
        
        // Cache busting version - increments automatically
        // Change YALLO_CHATBOT_VERSION in main file to force refresh for all users
        $version = YALLO_CHATBOT_VERSION;
        
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
        
        // Enqueue newsletter styles and scripts if enabled
        if (get_option('yallo_newsletter_enabled', false)) {
            wp_enqueue_style(
                'yallo-newsletter-styles',
                YALLO_CHATBOT_PLUGIN_URL . 'assets/css/newsletter.css',
                array(),
                $version
            );
            
            wp_enqueue_script(
                'yallo-newsletter-script',
                YALLO_CHATBOT_PLUGIN_URL . 'assets/js/newsletter.js',
                array('jquery'),
                $version,
                true
            );
            
            wp_localize_script('yallo-newsletter-script', 'yalloNewsletter', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('yallo_chatbot_nonce'),
                'delay' => get_option('yallo_newsletter_delay', 5000),
                'showOnce' => get_option('yallo_newsletter_show_once', true),
            ));
        }
        
        // Localize script for AJAX with debug info
        wp_localize_script('yallo-chatbot-script', 'yalloChatbot', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yallo_chatbot_nonce'),
            'autoOpen' => get_option('yallo_chatbot_auto_open', true),
            'scrollTrigger' => get_option('yallo_chatbot_scroll_trigger', 50),
            'displayMode' => get_option('yallo_chatbot_display_mode', 'all_pages'),
            'currentUrl' => home_url($_SERVER['REQUEST_URI']),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
        ));
    }
    
    /**
     * Check if chatbot should display on current page
     */
    private function should_display_on_current_page() {
        $display_mode = get_option('yallo_chatbot_display_mode', 'all_pages');
        
        // Display on all pages
        if ($display_mode === 'all_pages') {
            return true;
        }
        
        // Display on homepage only
        if ($display_mode === 'homepage_only') {
            return is_front_page() || is_home();
        }
        
        // Display on specific pages only
        if ($display_mode === 'specific_pages') {
            $specific_pages = get_option('yallo_chatbot_specific_pages', '');
            
            if (empty($specific_pages)) {
                return false;
            }
            
            // Get current page information
            global $post;
            $current_url = home_url($_SERVER['REQUEST_URI']);
            $current_path = parse_url($current_url, PHP_URL_PATH);
            
            // Clean and parse the specific pages
            $pages_array = array_filter(array_map('trim', explode("\n", $specific_pages)));
            
            foreach ($pages_array as $page_rule) {
                if (empty($page_rule)) {
                    continue;
                }
                
                // Check for page ID (format: id:123)
                if (strpos($page_rule, 'id:') === 0) {
                    $page_id = intval(str_replace('id:', '', $page_rule));
                    if ($post && ($post->ID == $page_id || is_page($page_id) || is_single($page_id))) {
                        return true;
                    }
                }
                // Check for slug (format: slug:contact-us)
                elseif (strpos($page_rule, 'slug:') === 0) {
                    $slug = str_replace('slug:', '', $page_rule);
                    if ($post && $post->post_name == $slug) {
                        return true;
                    }
                    if (is_page($slug) || is_single($slug)) {
                        return true;
                    }
                }
                // Check for URL match (full or partial)
                else {
                    // Clean the rule for comparison
                    $clean_rule = preg_replace('#^https?://(www\.)?#', '', trim($page_rule, '/'));
                    $clean_rule = '/' . ltrim($clean_rule, '/');
                    
                    // Clean current path
                    $clean_current = rtrim($current_path, '/');
                    if (empty($clean_current)) {
                        $clean_current = '/';
                    }
                    
                    // Exact match
                    if ($clean_current === $clean_rule) {
                        return true;
                    }
                    
                    // Contains match (for wildcards)
                    if (strpos($clean_current, $clean_rule) !== false) {
                        return true;
                    }
                    
                    // Match without leading slash
                    if (strpos($clean_current, '/' . trim($clean_rule, '/')) !== false) {
                        return true;
                    }
                }
            }
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Render chatbot HTML
     */
    public function render_chatbot() {
        // Check if chatbot is enabled
        if (!get_option('yallo_chatbot_enabled', true)) {
            echo '<!-- YALLO Chatbot: Disabled in settings -->';
            return;
        }
        
        // Check page restrictions
        if (!$this->should_display_on_current_page()) {
            $display_mode = get_option('yallo_chatbot_display_mode', 'all_pages');
            $current_url = home_url($_SERVER['REQUEST_URI']);
            echo "<!-- YALLO Chatbot: Not displayed on this page (Mode: {$display_mode}, URL: {$current_url}) -->";
            return;
        }
        
        echo '<!-- YALLO Chatbot: Active -->';
        include YALLO_CHATBOT_PLUGIN_DIR . 'templates/chatbot.php';
        
        // Render newsletter popup if enabled
        if (get_option('yallo_newsletter_enabled', false)) {
            echo '<!-- YALLO Newsletter: Active -->';
            include YALLO_CHATBOT_PLUGIN_DIR . 'templates/newsletter-popup.php';
        }
    }
    
    /**
     * Handle lead submission via AJAX
     */
    public function handle_lead_submission() {
        // Verify nonce
        check_ajax_referer('yallo_chatbot_nonce', 'nonce');
        
        // Sanitize input data
        $lead_data = array(
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'company' => sanitize_text_field($_POST['company'] ?? ''),
            'location' => sanitize_text_field($_POST['location'] ?? ''),
            'industry' => sanitize_text_field($_POST['industry'] ?? ''),
            'platforms' => sanitize_text_field($_POST['platforms'] ?? ''),
            'capabilities' => sanitize_text_field($_POST['capabilities'] ?? ''),
            'service_type' => sanitize_text_field($_POST['service_type'] ?? ''),
            'pain' => sanitize_textarea_field($_POST['pain'] ?? ''),
            'initial_intent' => sanitize_text_field($_POST['initial_intent'] ?? ''),
            'lead_type' => sanitize_text_field($_POST['lead_type'] ?? ''),
            'page_url' => esc_url_raw($_POST['page_url'] ?? ''),
            'created_at' => current_time('mysql'),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'ip_address' => $this->get_client_ip(),
        );
        
        // Validate email
        if (!is_email($lead_data['email'])) {
            wp_send_json_error(array('message' => 'Invalid email address'));
            return;
        }
        
        // Save to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'yallo_chatbot_leads';
        
        $inserted = $wpdb->insert(
            $table_name,
            $lead_data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($inserted) {
            // Send email notification
            $this->send_lead_notification($lead_data);
            
            wp_send_json_success(array('message' => 'Lead submitted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to save lead'));
        }
    }
    
    /**
     * Send email notification for new lead
     */
    private function send_lead_notification($lead_data) {
        $admin_email = get_option('yallo_chatbot_notification_email', get_option('admin_email'));
        
        $subject = sprintf('[YALLO Chatbot] New Lead: %s', $lead_data['name']);
        
        $message = "New lead received from YALLO Chatbot:\n\n";
        $message .= "Name: {$lead_data['name']}\n";
        $message .= "Email: {$lead_data['email']}\n";
        $message .= "Company: {$lead_data['company']}\n";
        $message .= "Location: {$lead_data['location']}\n";
        $message .= "Industry: {$lead_data['industry']}\n";
        $message .= "Platforms: {$lead_data['platforms']}\n";
        $message .= "Capabilities: {$lead_data['capabilities']}\n";
        $message .= "Service Type: {$lead_data['service_type']}\n";
        $message .= "Pain Point: {$lead_data['pain']}\n";
        $message .= "Initial Intent: {$lead_data['initial_intent']}\n";
        $message .= "Lead Type: {$lead_data['lead_type']}\n";
        $message .= "Page URL: {$lead_data['page_url']}\n";
        $message .= "Submitted: {$lead_data['created_at']}\n";
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        wp_mail($admin_email, $subject, $message, $headers);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        return sanitize_text_field($ip);
    }
    
    /**
     * Handle newsletter subscription via AJAX
     */
    public function handle_newsletter_subscription() {
        // Verify nonce
        check_ajax_referer('yallo_chatbot_nonce', 'nonce');
        
        // Sanitize email and name
        $email = sanitize_email($_POST['email'] ?? '');
        $name = sanitize_text_field($_POST['name'] ?? '');
        
        // Validate email
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Invalid email address'));
            return;
        }
        
        // Save to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'yallo_newsletter_subscribers';
        
        // Check if already subscribed
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE email = %s",
            $email
        ));
        
        if ($existing) {
            wp_send_json_error(array('message' => 'This email is already subscribed'));
            return;
        }
        
        $inserted = $wpdb->insert(
            $table_name,
            array(
                'email' => $email,
                'name' => $name,
                'subscribed_at' => current_time('mysql'),
                'ip_address' => $this->get_client_ip(),
                'page_url' => esc_url_raw($_POST['page_url'] ?? ''),
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
        
        if ($inserted) {
            // Send notification email
            $this->send_newsletter_notification($email, $name);
            
            wp_send_json_success(array('message' => 'Successfully subscribed!'));
        } else {
            wp_send_json_error(array('message' => 'Failed to subscribe'));
        }
    }
    
    /**
     * Send email notification for new newsletter subscriber
     */
    private function send_newsletter_notification($email, $name) {
        $admin_email = get_option('yallo_chatbot_notification_email', get_option('admin_email'));
        
        $subject = '[YALLO Newsletter] New Subscriber: ' . $email;
        
        $message = "New newsletter subscriber:\n\n";
        $message .= "Email: {$email}\n";
        $message .= "Name: {$name}\n";
        $message .= "Subscribed: " . current_time('mysql') . "\n";
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        wp_mail($admin_email, $subject, $message, $headers);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'YALLO Chatbot',
            'YALLO Chatbot',
            'manage_options',
            'yallo-chatbot',
            array($this, 'render_admin_page'),
            'dashicons-format-chat',
            30
        );
        
        add_submenu_page(
            'yallo-chatbot',
            'Settings',
            'Settings',
            'manage_options',
            'yallo-chatbot',
            array($this, 'render_admin_page')
        );
        
        add_submenu_page(
            'yallo-chatbot',
            'Leads',
            'Leads',
            'manage_options',
            'yallo-chatbot-leads',
            array($this, 'render_leads_page')
        );
        
        add_submenu_page(
            'yallo-chatbot',
            'Newsletter Subscribers',
            'Newsletter',
            'manage_options',
            'yallo-chatbot-newsletter',
            array($this, 'render_newsletter_page')
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('yallo_chatbot_settings', 'yallo_chatbot_enabled');
        register_setting('yallo_chatbot_settings', 'yallo_chatbot_auto_open');
        register_setting('yallo_chatbot_settings', 'yallo_chatbot_scroll_trigger');
        register_setting('yallo_chatbot_settings', 'yallo_chatbot_notification_email');
        register_setting('yallo_chatbot_settings', 'yallo_chatbot_display_mode');
        register_setting('yallo_chatbot_settings', 'yallo_chatbot_specific_pages');
        register_setting('yallo_chatbot_settings', 'yallo_chatbot_bypass_cache');
        
        // Newsletter popup settings
        register_setting('yallo_chatbot_settings', 'yallo_newsletter_enabled');
        register_setting('yallo_chatbot_settings', 'yallo_newsletter_delay');
        register_setting('yallo_chatbot_settings', 'yallo_newsletter_title');
        register_setting('yallo_chatbot_settings', 'yallo_newsletter_description');
        register_setting('yallo_chatbot_settings', 'yallo_newsletter_button_text');
        register_setting('yallo_chatbot_settings', 'yallo_newsletter_show_once');
    }
    
    /**
     * Render admin settings page
     */
    public function render_admin_page() {
        include YALLO_CHATBOT_PLUGIN_DIR . 'admin/settings.php';
    }
    
    /**
     * Render leads page
     */
    public function render_leads_page() {
        include YALLO_CHATBOT_PLUGIN_DIR . 'admin/leads.php';
    }
    
    /**
     * Render newsletter subscribers page
     */
    public function render_newsletter_page() {
        include YALLO_CHATBOT_PLUGIN_DIR . 'admin/newsletter.php';
    }
    
    /**
     * Add settings link on plugins page
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=yallo-chatbot') . '">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

/**
 * Plugin activation
 */
function yallo_chatbot_activate() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'yallo_chatbot_leads';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        company varchar(255) DEFAULT '',
        location varchar(255) DEFAULT '',
        industry varchar(255) DEFAULT '',
        platforms varchar(255) DEFAULT '',
        capabilities varchar(255) DEFAULT '',
        service_type varchar(255) DEFAULT '',
        pain text DEFAULT '',
        initial_intent varchar(255) DEFAULT '',
        lead_type varchar(50) DEFAULT '',
        page_url varchar(500) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        user_agent varchar(500) DEFAULT '',
        ip_address varchar(100) DEFAULT '',
        PRIMARY KEY  (id),
        KEY email (email),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Set default options
    add_option('yallo_chatbot_enabled', true);
    add_option('yallo_chatbot_auto_open', true);
    add_option('yallo_chatbot_scroll_trigger', 50);
    add_option('yallo_chatbot_notification_email', get_option('admin_email'));
    add_option('yallo_chatbot_display_mode', 'all_pages');
    add_option('yallo_chatbot_specific_pages', '');
    
    // Create newsletter subscribers table
    $newsletter_table = $wpdb->prefix . 'yallo_newsletter_subscribers';
    
    $newsletter_sql = "CREATE TABLE IF NOT EXISTS $newsletter_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        email varchar(255) NOT NULL,
        name varchar(255) DEFAULT '',
        subscribed_at datetime DEFAULT CURRENT_TIMESTAMP,
        ip_address varchar(100) DEFAULT '',
        page_url varchar(500) DEFAULT '',
        PRIMARY KEY  (id),
        UNIQUE KEY email (email),
        KEY subscribed_at (subscribed_at)
    ) $charset_collate;";
    
    dbDelta($newsletter_sql);
    
    // Set default newsletter options
    add_option('yallo_newsletter_enabled', false);
    add_option('yallo_newsletter_delay', 5000);
    add_option('yallo_newsletter_title', 'Stay Updated with YALLO');
    add_option('yallo_newsletter_description', 'Get the latest insights on tech talent and enterprise architecture delivered to your inbox.');
    add_option('yallo_newsletter_button_text', 'Subscribe Now');
    add_option('yallo_newsletter_show_once', true);
}
register_activation_hook(__FILE__, 'yallo_chatbot_activate');

/**
 * Plugin deactivation
 */
function yallo_chatbot_deactivate() {
    // Clean up if needed
}
register_deactivation_hook(__FILE__, 'yallo_chatbot_deactivate');

/**
 * Initialize the plugin
 */
function yallo_chatbot_init() {
    return YALLO_Talent_Chatbot::get_instance();
}

// Start the plugin
yallo_chatbot_init();