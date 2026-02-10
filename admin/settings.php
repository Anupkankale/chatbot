 <div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?> - Settings</h1>
    
    <?php settings_errors(); ?>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('yallo_chatbot_settings');
        do_settings_sections('yallo_chatbot_settings');
        ?>
        
        <table class="form-table" role="presentation">
            <tbody>
                <!-- Enable/Disable Chatbot -->
                <tr>
                    <th scope="row">
                        <label for="yallo_chatbot_enabled">Enable Chatbot</label>
                    </th>
                    <td>
                        <label for="yallo_chatbot_enabled">
                            <input 
                                type="checkbox" 
                                name="yallo_chatbot_enabled" 
                                id="yallo_chatbot_enabled" 
                                value="1"
                                <?php checked(get_option('yallo_chatbot_enabled', true), true); ?>
                            />
                            Enable the chatbot on your website
                        </label>
                        <p class="description">Uncheck to temporarily disable the chatbot without uninstalling the plugin.</p>
                    </td>
                </tr>
                
                <!-- Auto Open -->
                <tr>
                    <th scope="row">
                        <label for="yallo_chatbot_auto_open">Auto Open</label>
                    </th>
                    <td>
                        <label for="yallo_chatbot_auto_open">
                            <input 
                                type="checkbox" 
                                name="yallo_chatbot_auto_open" 
                                id="yallo_chatbot_auto_open" 
                                value="1"
                                <?php checked(get_option('yallo_chatbot_auto_open', true), true); ?>
                            />
                            Automatically open chatbot when user scrolls
                        </label>
                        <p class="description">The chatbot will open automatically when the user reaches the scroll trigger percentage.</p>
                    </td>
                </tr>
                
                <!-- Scroll Trigger Percentage -->
                <tr>
                    <th scope="row">
                        <label for="yallo_chatbot_scroll_trigger">Scroll Trigger (%)</label>
                    </th>
                    <td>
                        <input 
                            type="number" 
                            name="yallo_chatbot_scroll_trigger" 
                            id="yallo_chatbot_scroll_trigger" 
                            value="<?php echo esc_attr(get_option('yallo_chatbot_scroll_trigger', 50)); ?>"
                            min="0"
                            max="100"
                            step="5"
                            class="regular-text"
                        />
                        <p class="description">Percentage of page scroll to trigger auto-open (0-100). Default: 50%</p>
                    </td>
                </tr>
                
                <!-- Notification Email -->
                <tr>
                    <th scope="row">
                        <label for="yallo_chatbot_notification_email">Notification Email</label>
                    </th>
                    <td>
                        <input 
                            type="email" 
                            name="yallo_chatbot_notification_email" 
                            id="yallo_chatbot_notification_email" 
                            value="<?php echo esc_attr(get_option('yallo_chatbot_notification_email', get_option('admin_email'))); ?>"
                            class="regular-text"
                        />
                        <p class="description">Email address to receive new lead notifications. Multiple emails can be comma-separated.</p>
                    </td>
                </tr>
                
                <!-- Display Mode -->
                <tr>
                    <th scope="row">
                        <label for="yallo_chatbot_display_mode">Display On</label>
                    </th>
                    <td>
                        <?php $display_mode = get_option('yallo_chatbot_display_mode', 'all_pages'); ?>
                        <select name="yallo_chatbot_display_mode" id="yallo_chatbot_display_mode" class="regular-text">
                            <option value="all_pages" <?php selected($display_mode, 'all_pages'); ?>>All Pages</option>
                            <option value="homepage_only" <?php selected($display_mode, 'homepage_only'); ?>>Homepage Only</option>
                            <option value="specific_pages" <?php selected($display_mode, 'specific_pages'); ?>>Specific Pages</option>
                        </select>
                        <p class="description">Choose where the chatbot should appear on your website.</p>
                    </td>
                </tr>
                
                <!-- Specific Pages -->
                <tr id="yallo_specific_pages_row" style="<?php echo ($display_mode !== 'specific_pages') ? 'display: none;' : ''; ?>">
                    <th scope="row">
                        <label for="yallo_chatbot_specific_pages">Specific Pages</label>
                    </th>
                    <td>
                        <textarea 
                            name="yallo_chatbot_specific_pages" 
                            id="yallo_chatbot_specific_pages" 
                            rows="8" 
                            class="large-text code"
                            placeholder="Enter one per line (see examples below)"
                        ><?php echo esc_textarea(get_option('yallo_chatbot_specific_pages', '')); ?></textarea>
                        
                        <p class="description">
                            <strong>Enter one page per line. You can use:</strong><br>
                            • <strong>Full URL:</strong> https://yoursite.com/contact<br>
                            • <strong>Partial URL:</strong> /services/consulting<br>
                            • <strong>Page ID:</strong> id:123<br>
                            • <strong>Page Slug:</strong> slug:about-us<br>
                            • <strong>Wildcard path:</strong> /landing-pages<br><br>
                            
                            <strong>Examples:</strong><br>
                            <code>https://yoursite.com/services</code><br>
                            <code>/contact-us</code><br>
                            <code>id:45</code><br>
                            <code>slug:consultation</code><br>
                            <code>/landing-pages/enterprise</code>
                        </p>
                    </td>
                </tr>
                
                <!-- NEWSLETTER POPUP SECTION -->
                <tr>
                    <th colspan="2" style="background: #f5f5f5; padding: 15px;">
                        <h3 style="margin: 0; color: #BFA25E;">📧 Newsletter Popup Settings</h3>
                    </th>
                </tr>
                
                <!-- Enable Newsletter -->
                <tr>
                    <th scope="row">
                        <label for="yallo_newsletter_enabled">Enable Newsletter Popup</label>
                    </th>
                    <td>
                        <label for="yallo_newsletter_enabled">
                            <input 
                                type="checkbox" 
                                name="yallo_newsletter_enabled" 
                                id="yallo_newsletter_enabled" 
                                value="1"
                                <?php checked(get_option('yallo_newsletter_enabled', false), true); ?>
                            />
                            Show newsletter subscription popup to visitors
                        </label>
                        <p class="description">When enabled, a popup will appear asking visitors to subscribe to your newsletter.</p>
                    </td>
                </tr>
                
                <!-- Popup Delay -->
                <tr>
                    <th scope="row">
                        <label for="yallo_newsletter_delay">Popup Delay (ms)</label>
                    </th>
                    <td>
                        <input 
                            type="number" 
                            name="yallo_newsletter_delay" 
                            id="yallo_newsletter_delay" 
                            value="<?php echo esc_attr(get_option('yallo_newsletter_delay', 5000)); ?>"
                            min="0"
                            max="60000"
                            step="1000"
                            class="regular-text"
                        />
                        <p class="description">Time in milliseconds before showing popup (1000ms = 1 second). Default: 5000ms (5 seconds)</p>
                    </td>
                </tr>
                
                <!-- Popup Title -->
                <tr>
                    <th scope="row">
                        <label for="yallo_newsletter_title">Popup Title</label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            name="yallo_newsletter_title" 
                            id="yallo_newsletter_title" 
                            value="<?php echo esc_attr(get_option('yallo_newsletter_title', 'Stay Updated with YALLO')); ?>"
                            class="regular-text"
                        />
                        <p class="description">Main heading shown in the newsletter popup.</p>
                    </td>
                </tr>
                
                <!-- Popup Description -->
                <tr>
                    <th scope="row">
                        <label for="yallo_newsletter_description">Popup Description</label>
                    </th>
                    <td>
                        <textarea 
                            name="yallo_newsletter_description" 
                            id="yallo_newsletter_description" 
                            rows="3"
                            class="large-text"
                        ><?php echo esc_textarea(get_option('yallo_newsletter_description', 'Get the latest insights on tech talent and enterprise architecture delivered to your inbox.')); ?></textarea>
                        <p class="description">Description text shown below the title.</p>
                    </td>
                </tr>
                
                <!-- Button Text -->
                <tr>
                    <th scope="row">
                        <label for="yallo_newsletter_button_text">Button Text</label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            name="yallo_newsletter_button_text" 
                            id="yallo_newsletter_button_text" 
                            value="<?php echo esc_attr(get_option('yallo_newsletter_button_text', 'Subscribe Now')); ?>"
                            class="regular-text"
                        />
                        <p class="description">Text shown on the subscription button.</p>
                    </td>
                </tr>
                
                <!-- Show Once -->
                <tr>
                    <th scope="row">
                        <label for="yallo_newsletter_show_once">Show Once Per Visitor</label>
                    </th>
                    <td>
                        <label for="yallo_newsletter_show_once">
                            <input 
                                type="checkbox" 
                                name="yallo_newsletter_show_once" 
                                id="yallo_newsletter_show_once" 
                                value="1"
                                <?php checked(get_option('yallo_newsletter_show_once', true), true); ?>
                            />
                            Only show popup once per visitor (30 days)
                        </label>
                        <p class="description">If checked, popup will only appear once every 30 days for the same visitor.</p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button('Save Settings'); ?>
    </form>
    
    <hr>

<style>
.wrap h1 {
    color: #1a1a1a;
}

.wrap .card {
    max-width: 800px;
    padding: 20px;
    margin: 20px 0;
}

.wrap .card h3 {
    margin-top: 0;
    color: #BFA25E;
}

.form-table th {
    padding: 20px 10px 20px 0;
}

.form-table td {
    padding: 15px 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Toggle specific pages field based on display mode
    $('#yallo_chatbot_display_mode').on('change', function() {
        if ($(this).val() === 'specific_pages') {
            $('#yallo_specific_pages_row').show();
        } else {
            $('#yallo_specific_pages_row').hide();
        }
    });
});
</script>