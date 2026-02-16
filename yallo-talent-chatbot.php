<?php
/**
 * Plugin Name: YALLO Talent Chatbot
 * Plugin URI: https://yallo.com
 * Description: An intelligent chatbot for YALLO talent acquisition and consultation services with a sleek dark theme interface.
 * Version: 1.0.2
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
define('YALLO_CHATBOT_VERSION', '1.0.2');
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
        
        // Register lead update AJAX endpoint (for early lead capture)
        add_action('wp_ajax_yallo_update_lead', array($this, 'handle_lead_update'));
        add_action('wp_ajax_nopriv_yallo_update_lead', array($this, 'handle_lead_update'));
        
        // Register newsletter AJAX endpoints
        add_action('wp_ajax_yallo_newsletter_subscribe', array($this, 'handle_newsletter_subscription'));
        add_action('wp_ajax_nopriv_yallo_newsletter_subscribe', array($this, 'handle_newsletter_subscription'));
        
        // Register admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add settings link on plugins page
        add_filter('plugin_action_links_' . YALLO_CHATBOT_PLUGIN_BASENAME, array($this, 'add_settings_link'));

        // Hook into WordPress mailer to apply SMTP settings
        add_action('phpmailer_init', array($this, 'configure_smtp'));

        // SMTP test email
        add_action('wp_ajax_yallo_smtp_test', array($this, 'handle_smtp_test'));
    }
    
    /**
     * Enqueue CSS and JavaScript
     */
    public function enqueue_assets() {
        // Only load on frontend
        if (is_admin()) {
            return;
        }

        $version = YALLO_CHATBOT_VERSION;

        // ─────────────────────────────────────────────────────────
        // NEWSLETTER: Loads independently — NOT affected by chatbot toggle
        // ─────────────────────────────────────────────────────────
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
                'ajaxUrl'   => admin_url('admin-ajax.php'),
                'nonce'     => wp_create_nonce('yallo_chatbot_nonce'),
                'delay'     => get_option('yallo_newsletter_delay', 5000),
                'showOnce'  => get_option('yallo_newsletter_show_once', true),
            ));
        }

        // ─────────────────────────────────────────────────────────
        // CHATBOT: Has its own enable/page restriction checks
        // ─────────────────────────────────────────────────────────
        if (!get_option('yallo_chatbot_enabled', true)) {
            return;
        }

        if (!$this->should_display_on_current_page()) {
            return;
        }

        // Enqueue chatbot styles
        wp_enqueue_style(
            'yallo-chatbot-styles',
            YALLO_CHATBOT_PLUGIN_URL . 'assets/css/chatbot.css',
            array(),
            $version
        );

        // Enqueue chatbot scripts
        wp_enqueue_script(
            'yallo-chatbot-script',
            YALLO_CHATBOT_PLUGIN_URL . 'assets/js/chatbot.js',
            array('jquery'),
            $version,
            true
        );

        // Localize chatbot script
        wp_localize_script('yallo-chatbot-script', 'yalloChatbot', array(
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'nonce'         => wp_create_nonce('yallo_chatbot_nonce'),
            'autoOpen'      => get_option('yallo_chatbot_auto_open', true),
            'scrollTrigger' => get_option('yallo_chatbot_scroll_trigger', 50),
            'displayMode'   => get_option('yallo_chatbot_display_mode', 'all_pages'),
            'currentUrl'    => home_url($_SERVER['REQUEST_URI']),
            'debug'         => defined('WP_DEBUG') && WP_DEBUG,
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

        // ─────────────────────────────────────────────────────────
        // NEWSLETTER: Renders independently — NOT affected by chatbot toggle
        // ─────────────────────────────────────────────────────────
        if (get_option('yallo_newsletter_enabled', false)) {
            echo '<!-- YALLO Newsletter: Active -->';
            include YALLO_CHATBOT_PLUGIN_DIR . 'templates/newsletter-popup.php';
        }

        // ─────────────────────────────────────────────────────────
        // CHATBOT: Has its own enable/page restriction checks
        // ─────────────────────────────────────────────────────────
        if (!get_option('yallo_chatbot_enabled', true)) {
            echo '<!-- YALLO Chatbot: Disabled in settings -->';
            return;
        }

        if (!$this->should_display_on_current_page()) {
            $display_mode = get_option('yallo_chatbot_display_mode', 'all_pages');
            $current_url  = home_url($_SERVER['REQUEST_URI']);
            echo "<!-- YALLO Chatbot: Not displayed on this page (Mode: {$display_mode}, URL: {$current_url}) -->";
            return;
        }

        echo '<!-- YALLO Chatbot: Active -->';
        include YALLO_CHATBOT_PLUGIN_DIR . 'templates/chatbot.php';
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
     * Configure SMTP via phpmailer_init hook
     */
    public function configure_smtp( $phpmailer ) {
        // Only apply if SMTP is enabled
        if ( ! get_option( 'yallo_smtp_enabled', false ) ) {
            return;
        }

        $host       = get_option( 'yallo_smtp_host', '' );
        $port       = (int) get_option( 'yallo_smtp_port', 587 );
        $username   = get_option( 'yallo_smtp_username', '' );
        $password   = get_option( 'yallo_smtp_password', '' );
        $encryption = get_option( 'yallo_smtp_encryption', 'tls' );
        $from_email = get_option( 'yallo_smtp_from_email', get_option( 'admin_email' ) );
        $from_name  = get_option( 'yallo_smtp_from_name', get_bloginfo( 'name' ) );

        if ( empty( $host ) || empty( $username ) || empty( $password ) ) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host        = $host;
        $phpmailer->SMTPAuth    = true;
        $phpmailer->Username    = $username;
        $phpmailer->Password    = $password;
        $phpmailer->SMTPSecure  = $encryption;
        $phpmailer->Port        = $port;
        $phpmailer->From        = $from_email;
        $phpmailer->FromName    = $from_name;
        $phpmailer->CharSet     = 'UTF-8';
    }

    /**
     * Central method to send all plugin emails
     */
    private function send_email( $to, $subject, $html_body ) {
        // Support comma-separated multiple recipients
        $recipients = array_map( 'trim', explode( ',', $to ) );

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
        );

        $from_name  = get_option( 'yallo_smtp_from_name', get_bloginfo( 'name' ) );
        $from_email = get_option( 'yallo_smtp_from_email', get_option( 'admin_email' ) );
        $headers[]  = "From: {$from_name} <{$from_email}>";

        foreach ( $recipients as $recipient ) {
            if ( is_email( $recipient ) ) {
                wp_mail( $recipient, $subject, $html_body, $headers );
            }
        }
    }

    /**
     * Build a branded HTML email template
     */
    private function build_email_html( $title, $rows, $footer_note = '' ) {
        $logo_color  = '#BFA25E';
        $rows_html   = '';

        foreach ( $rows as $label => $value ) {
            $value      = esc_html( $value ?: '—' );
            $label      = esc_html( $label );
            $rows_html .= "
            <tr>
                <td style='padding:10px 15px;font-weight:600;color:#555;width:35%;border-bottom:1px solid #f0f0f0;'>{$label}</td>
                <td style='padding:10px 15px;color:#222;border-bottom:1px solid #f0f0f0;'>{$value}</td>
            </tr>";
        }

        $footer_html = $footer_note
            ? "<p style='font-size:12px;color:#999;margin-top:30px;'>{$footer_note}</p>"
            : '';

        return "<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'></head>
<body style='margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;'>
  <table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f4f4;padding:30px 0;'>
    <tr><td align='center'>
      <table width='600' cellpadding='0' cellspacing='0' style='background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.1);max-width:96%;'>

        <!-- Header -->
        <tr>
          <td style='background:{$logo_color};padding:25px 30px;'>
            <h1 style='margin:0;color:#000;font-size:22px;font-weight:700;'>YALLO</h1>
            <p style='margin:5px 0 0;color:#000;font-size:13px;opacity:0.8;'>Talent &amp; Architecture Intelligence</p>
          </td>
        </tr>

        <!-- Title -->
        <tr>
          <td style='padding:25px 30px 10px;'>
            <h2 style='margin:0;font-size:18px;color:#111;'>{$title}</h2>
          </td>
        </tr>

        <!-- Data Table -->
        <tr>
          <td style='padding:0 30px 20px;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #f0f0f0;border-radius:6px;overflow:hidden;'>
              {$rows_html}
            </table>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style='padding:15px 30px 30px;border-top:2px solid {$logo_color};'>
            <p style='font-size:13px;color:#666;margin:10px 0 0;'>
              This notification was sent by <strong>YALLO Talent Chatbot</strong>.<br>
              Received at: " . current_time('d M Y, H:i') . "
            </p>
            {$footer_html}
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>";
    }

    /**
     * Send email notification for new lead (early capture — name + email only)
     */
    private function send_lead_notification( $lead_data ) {
        $to      = get_option( 'yallo_chatbot_notification_email', get_option( 'admin_email' ) );
        $subject = '🔔 [YALLO] New Lead Captured: ' . $lead_data['name'];

        $rows = array(
            'Name'           => $lead_data['name'],
            'Email'          => $lead_data['email'],
            'Company'        => $lead_data['company'],
            'Location'       => $lead_data['location'],
            'Industry'       => $lead_data['industry'],
            'Platforms'      => $lead_data['platforms'],
            'Capabilities'   => $lead_data['capabilities'],
            'Service Type'   => $lead_data['service_type'],
            'Pain Point'     => $lead_data['pain'],
            'Initial Intent' => $lead_data['initial_intent'],
            'Lead Type'      => $lead_data['lead_type'],
            'Page URL'       => $lead_data['page_url'],
            'Submitted'      => $lead_data['created_at'],
        );

        $html = $this->build_email_html( 'New Lead Captured', $rows, 'Log in to your WordPress dashboard to view all leads.' );
        $this->send_email( $to, $subject, $html );
    }

    /**
     * Send email notification when a lead updates additional info
     */
    private function send_lead_update_notification( $email, $update_data ) {
        $to      = get_option( 'yallo_chatbot_notification_email', get_option( 'admin_email' ) );
        $subject = '✏️ [YALLO] Lead Updated: ' . $email;

        $rows = array(
            'Email'        => $email,
            'Company'      => $update_data['company'],
            'Location'     => $update_data['location'],
            'Industry'     => $update_data['industry'],
            'Platforms'    => $update_data['platforms'],
            'Capabilities' => $update_data['capabilities'],
            'Service Type' => $update_data['service_type'],
            'Pain Point'   => $update_data['pain'],
        );

        $html = $this->build_email_html( 'Lead Updated With Full Details', $rows, 'The lead has completed the full consultation form.' );
        $this->send_email( $to, $subject, $html );
    }

    /**
     * Send email notification for new newsletter subscriber
     */
    private function send_newsletter_notification( $email, $name ) {
        $to      = get_option( 'yallo_chatbot_notification_email', get_option( 'admin_email' ) );
        $subject = '📧 [YALLO] New Newsletter Subscriber: ' . $email;

        $rows = array(
            'Name'       => $name,
            'Email'      => $email,
            'Subscribed' => current_time( 'mysql' ),
        );

        $html = $this->build_email_html( 'New Newsletter Subscriber', $rows, 'Manage subscribers in YALLO Chatbot → Newsletter.' );
        $this->send_email( $to, $subject, $html );
    }

    /**
     * Handle lead update with additional information (for early lead capture)
     */
    public function handle_lead_update() {
        check_ajax_referer( 'yallo_chatbot_nonce', 'nonce' );

        $email = sanitize_email( $_POST['email'] ?? '' );
        if ( ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => 'Invalid email address' ) );
            return;
        }

        $update_data = array(
            'company'      => sanitize_text_field( $_POST['company'] ?? '' ),
            'location'     => sanitize_text_field( $_POST['location'] ?? '' ),
            'industry'     => sanitize_text_field( $_POST['industry'] ?? '' ),
            'platforms'    => sanitize_text_field( $_POST['platforms'] ?? '' ),
            'capabilities' => sanitize_text_field( $_POST['capabilities'] ?? '' ),
            'service_type' => sanitize_text_field( $_POST['service_type'] ?? '' ),
            'pain'         => sanitize_textarea_field( $_POST['pain'] ?? '' ),
        );

        global $wpdb;
        $table_name = $wpdb->prefix . 'yallo_chatbot_leads';

        $updated = $wpdb->update(
            $table_name,
            $update_data,
            array( 'email' => $email ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
            array( '%s' )
        );

        if ( $updated !== false ) {
            $this->send_lead_update_notification( $email, $update_data );
            wp_send_json_success( array( 'message' => 'Lead updated successfully', 'email' => $email ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to update lead' ) );
        }
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip = '';
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        return sanitize_text_field( $ip );
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

        // SMTP settings
        register_setting('yallo_chatbot_settings', 'yallo_smtp_enabled');
        register_setting('yallo_chatbot_settings', 'yallo_smtp_host');
        register_setting('yallo_chatbot_settings', 'yallo_smtp_port');
        register_setting('yallo_chatbot_settings', 'yallo_smtp_username');
        register_setting('yallo_chatbot_settings', 'yallo_smtp_password');
        register_setting('yallo_chatbot_settings', 'yallo_smtp_encryption');
        register_setting('yallo_chatbot_settings', 'yallo_smtp_from_email');
        register_setting('yallo_chatbot_settings', 'yallo_smtp_from_name');
    }

    /**
     * Handle SMTP test email via AJAX
     */
    public function handle_smtp_test() {
        check_ajax_referer( 'yallo_chatbot_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ) );
            return;
        }

        $to = get_option( 'yallo_chatbot_notification_email', get_option( 'admin_email' ) );

        $rows = array(
            'Status'  => 'SMTP connection successful ✅',
            'Host'    => get_option( 'yallo_smtp_host' ),
            'Port'    => get_option( 'yallo_smtp_port' ),
            'From'    => get_option( 'yallo_smtp_from_email' ),
            'Sent to' => $to,
            'Time'    => current_time( 'mysql' ),
        );

        $html   = $this->build_email_html( 'SMTP Test Email', $rows, 'Your YALLO SMTP settings are working correctly.' );
        $result = wp_mail( $to, '✅ [YALLO] SMTP Test Email', $html, array( 'Content-Type: text/html; charset=UTF-8' ) );

        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Test email sent to ' . $to ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to send. Check your SMTP credentials.' ) );
        }
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

    // Set default SMTP options
    add_option('yallo_smtp_enabled',    false);
    add_option('yallo_smtp_host',       '');
    add_option('yallo_smtp_port',       587);
    add_option('yallo_smtp_username',   '');
    add_option('yallo_smtp_password',   '');
    add_option('yallo_smtp_encryption', 'tls');
    add_option('yallo_smtp_from_email', get_option('admin_email'));
    add_option('yallo_smtp_from_name',  get_bloginfo('name'));
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