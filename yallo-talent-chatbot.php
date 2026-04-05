<?php
/**
 * Plugin Name: YALLO Talent Chatbot
 * Plugin URI: https://yallo.com
 * Description: An intelligent chatbot for YALLO talent acquisition and consultation services with a sleek dark theme interface and Claude AI integration.
 * Version: 1.0.3
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
define('YALLO_CHATBOT_VERSION', '1.0.3');
define('YALLO_CHATBOT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YALLO_CHATBOT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('YALLO_CHATBOT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load RAG AI handler
if (file_exists(YALLO_CHATBOT_PLUGIN_DIR . 'yallo-chatbot-rag-ai.php')) {
    require_once YALLO_CHATBOT_PLUGIN_DIR . 'yallo-chatbot-rag-ai.php';
}

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
        
        // Load chatbot questions dynamically
        add_action('wp_ajax_yallo_get_questions', array($this, 'get_chatbot_questions'));
        add_action('wp_ajax_nopriv_yallo_get_questions', array($this, 'get_chatbot_questions'));
        
        // Register newsletter AJAX endpoints
        add_action('wp_ajax_yallo_newsletter_subscribe', array($this, 'handle_newsletter_subscription'));
        add_action('wp_ajax_nopriv_yallo_newsletter_subscribe', array($this, 'handle_newsletter_subscription'));
        
        // AI AJAX endpoints
        add_action('wp_ajax_yallo_ai_chat', array($this, 'handle_ai_chat'));
        add_action('wp_ajax_nopriv_yallo_ai_chat', array($this, 'handle_ai_chat'));
        add_action('wp_ajax_yallo_test_ai', array($this, 'handle_test_ai'));
        
        // Register admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add settings link on plugins page
        add_filter('plugin_action_links_' . YALLO_CHATBOT_PLUGIN_BASENAME, array($this, 'add_settings_link'));

        // Hook into WordPress mailer to apply SMTP settings
        add_action('phpmailer_init', array($this, 'configure_smtp'));

        // Simple API test
        add_action('wp_ajax_yallo_test_api_simple', array($this, 'handle_test_api_simple'));

        // SMTP test email
        add_action('wp_ajax_yallo_smtp_test', array($this, 'handle_smtp_test'));

        // Lead status update
        add_action('wp_ajax_yallo_update_lead_status', array($this, 'handle_update_lead_status'));

        // CSV export (admin_post: non-AJAX file download)
        add_action('admin_post_yallo_export_leads', array($this, 'handle_export_leads'));

        // DB upgrade check
        add_action('admin_init', array($this, 'maybe_upgrade_db'));
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
            'aiEnabled'     => get_option('yallo_claude_api_key') ? true : false,
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
            echo '<!-- YALLO Chatbot: Not displayed on this page (Mode: ' . esc_html($display_mode) . ', URL: ' . esc_html($current_url) . ') -->';
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

        // Duplicate detection: return existing lead ID without re-inserting
        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE email = %s LIMIT 1",
            $lead_data['email']
        ));

        if ($existing_id) {
            wp_send_json_success(array('message' => 'Lead already exists', 'lead_id' => (int) $existing_id));
            return;
        }

        $inserted = $wpdb->insert(
            $table_name,
            $lead_data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($inserted) {
            // Send email notification
            $this->send_lead_notification($lead_data);

            wp_send_json_success(array('message' => 'Lead submitted successfully', 'lead_id' => (int) $wpdb->insert_id));
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
     * Send email notification for new lead
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
     * Handle lead update with additional information
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
        check_ajax_referer('yallo_chatbot_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email'] ?? '');
        $name = sanitize_text_field($_POST['name'] ?? '');
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Invalid email address'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'yallo_newsletter_subscribers';
        
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
            'Edit Questions',
            'Questions',
            'manage_options',
            'yallo-chatbot-questions',
            array($this, 'render_questions_page')
        );
        
        add_submenu_page(
            'yallo-chatbot',
            'AI Settings',
            '🤖 AI Settings',
            'manage_options',
            'yallo-chatbot-ai',
            array($this, 'render_ai_settings_page')
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
        
        // Chatbot questions
        register_setting('yallo_chatbot_settings', 'yallo_chatbot_questions_json');
        register_setting('yallo_chatbot_settings', 'yallo_chatbot_bypass_cache');

        // Newsletter settings
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
        
        // AI settings
        register_setting('yallo_chatbot_settings', 'yallo_claude_api_key');
        register_setting('yallo_chatbot_settings', 'yallo_ai_enabled');
        register_setting('yallo_chatbot_settings', 'yallo_ai_fallback_message');
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
     * Render questions editor page
     */
    public function render_questions_page() {
        include YALLO_CHATBOT_PLUGIN_DIR . 'admin/questions.php';
    }
    
    /**
     * Render AI settings page
     */
    public function render_ai_settings_page() {
        include YALLO_CHATBOT_PLUGIN_DIR . 'admin/yallo-chatbot-rag-settings.php';
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
     * Handle AI chat via AJAX
     */
    public function handle_ai_chat() {
        check_ajax_referer('yallo_chatbot_nonce', 'nonce');

        // Rate limiting: max 20 AI requests per IP per hour
        $rate_key   = 'yallo_ai_rate_' . md5($this->get_client_ip());
        $rate_count = (int) get_transient($rate_key);
        if ($rate_count >= 20) {
            wp_send_json_error(array('message' => 'Too many requests. Please try again later.'));
            return;
        }
        set_transient($rate_key, $rate_count + 1, HOUR_IN_SECONDS);

        $message = sanitize_text_field($_POST['message'] ?? '');

        if (!get_option('yallo_claude_api_key')) {
            wp_send_json_error(array('message' => 'AI not configured. Please add your Claude API key in AI Settings.'));
            return;
        }

        // Parse and sanitize conversation history from frontend
        $history_raw = isset($_POST['history']) ? wp_unslash($_POST['history']) : '[]';
        $history     = json_decode($history_raw, true);
        if (!is_array($history)) {
            $history = array();
        }
        $history = array_slice($history, -10); // keep last 10 turns max
        $history = array_map(function($turn) {
            return array(
                'role'    => in_array($turn['role'] ?? '', array('user', 'assistant'), true) ? $turn['role'] : 'user',
                'content' => sanitize_textarea_field($turn['content'] ?? ''),
            );
        }, $history);

        if (!class_exists('YALLO_Chatbot_RAG')) {
            require_once YALLO_CHATBOT_PLUGIN_DIR . 'yallo-chatbot-rag-ai.php';
        }

        $rag      = new YALLO_Chatbot_RAG();
        $response = $rag->get_ai_response($message, $history);

        if ($response['success']) {
            wp_send_json_success($response);
        } else {
            wp_send_json_error($response);
        }
    }

    /**
     * Simple API key test
     */
    public function handle_test_api_simple() {
        check_ajax_referer('yallo_chatbot_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $api_key = get_option('yallo_claude_api_key', '');
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'No API key configured. Please add your Claude API key and save settings.'));
            return;
        }
        
        // Test API with minimal request
        $api_url = 'https://api.anthropic.com/v1/messages';
        
        $headers = array(
            'Content-Type' => 'application/json',
            'x-api-key' => $api_key,
            'anthropic-version' => '2023-06-01'
        );
        
        $body = array(
            'model' => 'claude-3-5-haiku-20241022',
            'max_tokens' => 20,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => 'Say hello'
                )
            )
        );
        
        $response = wp_remote_post($api_url, array(
            'headers' => $headers,
            'body' => wp_json_encode($body),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => 'Connection failed: ' . $response->get_error_message() . '. Your server might be blocking external API calls.'
            ));
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        
        if ($response_code !== 200) {
            $error_msg = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
            
            if ($response_code === 401) {
                $error_msg = 'Invalid API key. Please check your key at console.anthropic.com/settings/keys';
            } elseif ($response_code === 429) {
                $error_msg = 'Rate limit exceeded. Wait a few minutes or add payment method to your Anthropic account.';
            } elseif ($response_code === 400) {
                $error_msg = 'Bad request: ' . $error_msg;
            }
            
            wp_send_json_error(array(
                'message' => $error_msg,
                'code' => $response_code
            ));
            return;
        }
        
        if (isset($data['content'][0]['text'])) {
            wp_send_json_success(array(
                'model' => 'Claude 3.5 Haiku',
                'response' => $data['content'][0]['text'],
                'tokens' => isset($data['usage']['input_tokens']) ? $data['usage']['input_tokens'] + $data['usage']['output_tokens'] : 'N/A'
            ));
        } else {
            wp_send_json_error(array('message' => 'Invalid response from API'));
        }
    }
    
    /**
     * Add settings link on plugins page
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=yallo-chatbot') . '">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Get chatbot questions via AJAX
     */
    public function get_chatbot_questions() {
        $questions = get_option('yallo_chatbot_questions', null);
        
        // If no custom questions, return defaults
        if (!$questions) {
            $questions = array(
                'welcome' => array(
                    'text' => "Hi, we're YALLO 👋\n\nHow can we help?"
                ),
                'services' => array(
                    array(
                        'text' => 'Hire tech talent / build a squad',
                        'message' => "Great – tech talent & squads.\n\nVetted profiles across AI, Data, Cloud, SAP, Oracle, Salesforce & more – delivered in ~72 hrs.",
                        'intent' => 'Hire tech talent / build a squad',
                        'lead_type' => 'details'
                    ),
                    array(
                        'text' => 'Stabilise a troubled project',
                        'message' => "Got it – stabilise a project.\n\nWe use architects & delivery leads to find and fix talent or role clarity gaps fast.",
                        'intent' => 'Stabilise a troubled project',
                        'lead_type' => 'call'
                    ),
                    array(
                        'text' => 'Enterprise Architecture / IT strategy',
                        'message' => "Understood – EA / IT strategy.\n\nWe provide Chief Architect capacity to align roadmaps and talent – no big consulting lock-in.",
                        'intent' => 'Enterprise Architecture / IT strategy',
                        'lead_type' => 'call'
                    ),
                    array(
                        'text' => 'Not sure / explore options',
                        'message' => "No problem – we'll figure it out together.\n\nTell us a little and we'll recommend the right next step.",
                        'intent' => 'Not sure / explore options',
                        'lead_type' => 'details'
                    )
                ),
                'consultation' => array(
                    array('key' => 'name', 'text' => "What's your **full name?**"),
                    array('key' => 'email', 'text' => "Thanks {name}! Your **work email?**"),
                    array('key' => 'company', 'text' => "Your **company** name?"),
                    array('key' => 'location', 'text' => "**Where** are you based?\n(e.g. Dubai, UAE)"),
                    array('key' => 'industry', 'text' => "**Industry?**\n\n- Retail & Consumer\n- Manufacturing & Logistics\n- Banking & Financial Services\n- Government & Public Sector\n- Healthcare & Life Science\n- Telco & Media\n- Other"),
                    array('key' => 'platforms', 'text' => "**Core platform?**\n\n- SAP\n- Oracle\n- Microsoft\n- Salesforce\n- Blue Yonder\n- Workday\n- Other / Not sure"),
                    array('key' => 'capabilities', 'text' => "**Biggest gap?**\n\n- Data & AI\n- Digital & DevOps\n- Cloud & Infrastructure\n- Cybersecurity\n- Integration & Middleware\n- Emerging Technologies"),
                    array('key' => 'service_type', 'text' => "**What do you need?**\n\n- Talent in a Box\n- TS/EA as a Service\n- Managed IT CoE\n- Not sure"),
                    array('key' => 'pain', 'text' => "In **one line** – what's the main challenge?")
                )
            );
        }
        
        wp_send_json_success($questions);
    }

    /**
     * DB upgrade: add columns introduced after initial release
     */
    public function maybe_upgrade_db() {
        $db_version = get_option('yallo_chatbot_db_version', '1.0');
        if (version_compare($db_version, '1.1', '>=')) {
            return;
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'yallo_chatbot_leads';
        $col = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'status'");
        if (!$col) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN status varchar(20) NOT NULL DEFAULT 'new' AFTER lead_type");
            $wpdb->query("ALTER TABLE $table_name ADD KEY status (status)");
        }
        update_option('yallo_chatbot_db_version', '1.1');
    }

    /**
     * AJAX: update a lead's status (admin only)
     */
    public function handle_update_lead_status() {
        check_ajax_referer('yallo_chatbot_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        $lead_id         = intval($_POST['lead_id'] ?? 0);
        $status          = sanitize_text_field($_POST['status'] ?? '');
        $allowed_statuses = array('new', 'contacted', 'converted', 'lost');

        if (!$lead_id || !in_array($status, $allowed_statuses, true)) {
            wp_send_json_error(array('message' => 'Invalid data'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'yallo_chatbot_leads';
        $updated = $wpdb->update(
            $table_name,
            array('status' => $status),
            array('id' => $lead_id),
            array('%s'),
            array('%d')
        );

        if ($updated !== false) {
            wp_send_json_success(array('message' => 'Status updated'));
        } else {
            wp_send_json_error(array('message' => 'Update failed'));
        }
    }

    /**
     * Admin POST: export all leads as CSV download
     */
    public function handle_export_leads() {
        check_admin_referer('yallo_export_leads');
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'yallo_chatbot_leads';
        $leads = $wpdb->get_results(
            "SELECT id, name, email, company, location, industry, platforms, capabilities,
                    service_type, pain, initial_intent, lead_type, status, page_url, created_at
             FROM $table_name ORDER BY created_at DESC",
            ARRAY_A
        );

        $filename = 'yallo-leads-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

        if (!empty($leads)) {
            fputcsv($output, array_keys($leads[0]));
            foreach ($leads as $lead) {
                fputcsv($output, $lead);
            }
        }

        fclose($output);
        exit;
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
        status varchar(20) NOT NULL DEFAULT 'new',
        page_url varchar(500) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        user_agent varchar(500) DEFAULT '',
        ip_address varchar(100) DEFAULT '',
        PRIMARY KEY  (id),
        KEY email (email),
        KEY created_at (created_at),
        KEY status (status)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
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
    
    // Create AI knowledge base table
    if (class_exists('YALLO_Chatbot_RAG')) {
        YALLO_Chatbot_RAG::create_table();
    }
    
    // Set default options
    add_option('yallo_chatbot_enabled', true);
    add_option('yallo_chatbot_auto_open', true);
    add_option('yallo_chatbot_scroll_trigger', 50);
    add_option('yallo_chatbot_notification_email', get_option('admin_email'));
    add_option('yallo_chatbot_display_mode', 'all_pages');
    add_option('yallo_chatbot_specific_pages', '');
    
    // Newsletter options
    add_option('yallo_newsletter_enabled', false);
    add_option('yallo_newsletter_delay', 5000);
    add_option('yallo_newsletter_title', 'Stay Updated with YALLO');
    add_option('yallo_newsletter_description', 'Get the latest insights on tech talent and enterprise architecture delivered to your inbox.');
    add_option('yallo_newsletter_button_text', 'Subscribe Now');
    add_option('yallo_newsletter_show_once', true);

    // SMTP options
    add_option('yallo_smtp_enabled', false);
    add_option('yallo_smtp_host', '');
    add_option('yallo_smtp_port', 587);
    add_option('yallo_smtp_username', '');
    add_option('yallo_smtp_password', '');
    add_option('yallo_smtp_encryption', 'tls');
    add_option('yallo_smtp_from_email', get_option('admin_email'));
    add_option('yallo_smtp_from_name', get_bloginfo('name'));
    
    // AI options
    add_option('yallo_claude_api_key', '');
    add_option('yallo_ai_enabled', false);
    add_option('yallo_ai_fallback_message', "I don't have specific information about that. Would you like to speak with our team?");
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