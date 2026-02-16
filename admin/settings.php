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

                <!-- ══════════════════════════════════════════ -->
                <!--  CHATBOT SETTINGS                         -->
                <!-- ══════════════════════════════════════════ -->
                <tr>
                    <th colspan="2" style="background:#f9f9f9;padding:14px 10px;border-left:4px solid #BFA25E;">
                        <h3 style="margin:0;color:#1a1a1a;font-size:15px;">💬 Chatbot Settings</h3>
                    </th>
                </tr>

                <tr>
                    <th scope="row"><label for="yallo_chatbot_enabled">Enable Chatbot</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="yallo_chatbot_enabled" id="yallo_chatbot_enabled" value="1"
                                <?php checked(get_option('yallo_chatbot_enabled', true), true); ?> />
                            Enable the chatbot on your website
                        </label>
                        <p class="description">Uncheck to temporarily disable the chatbot without uninstalling the plugin.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="yallo_chatbot_auto_open">Auto Open</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="yallo_chatbot_auto_open" id="yallo_chatbot_auto_open" value="1"
                                <?php checked(get_option('yallo_chatbot_auto_open', true), true); ?> />
                            Automatically open chatbot when user scrolls
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="yallo_chatbot_scroll_trigger">Scroll Trigger (%)</label></th>
                    <td>
                        <input type="number" name="yallo_chatbot_scroll_trigger" id="yallo_chatbot_scroll_trigger"
                            value="<?php echo esc_attr(get_option('yallo_chatbot_scroll_trigger', 50)); ?>"
                            min="0" max="100" step="5" class="regular-text" />
                        <p class="description">Percentage of page scroll to trigger auto-open. Default: 50%</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="yallo_chatbot_notification_email">Notification Email</label></th>
                    <td>
                        <input type="email" name="yallo_chatbot_notification_email" id="yallo_chatbot_notification_email"
                            value="<?php echo esc_attr(get_option('yallo_chatbot_notification_email', get_option('admin_email'))); ?>"
                            class="regular-text" />
                        <p class="description">Receives all lead &amp; newsletter alerts. Separate multiple with commas.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="yallo_chatbot_display_mode">Display On</label></th>
                    <td>
                        <?php $display_mode = get_option('yallo_chatbot_display_mode', 'all_pages'); ?>
                        <select name="yallo_chatbot_display_mode" id="yallo_chatbot_display_mode" class="regular-text">
                            <option value="all_pages"      <?php selected($display_mode, 'all_pages'); ?>>All Pages</option>
                            <option value="homepage_only"  <?php selected($display_mode, 'homepage_only'); ?>>Homepage Only</option>
                            <option value="specific_pages" <?php selected($display_mode, 'specific_pages'); ?>>Specific Pages</option>
                        </select>
                    </td>
                </tr>

                <tr id="yallo_specific_pages_row" style="<?php echo ($display_mode !== 'specific_pages') ? 'display:none;' : ''; ?>">
                    <th scope="row"><label for="yallo_chatbot_specific_pages">Specific Pages</label></th>
                    <td>
                        <textarea name="yallo_chatbot_specific_pages" id="yallo_chatbot_specific_pages"
                            rows="6" class="large-text code"><?php echo esc_textarea(get_option('yallo_chatbot_specific_pages', '')); ?></textarea>
                        <p class="description">One page per line. Supports: full URL, partial URL (<code>/services</code>), <code>id:123</code>, <code>slug:about</code></p>
                    </td>
                </tr>

                <!-- ══════════════════════════════════════════ -->
                <!--  NEWSLETTER SETTINGS                       -->
                <!-- ══════════════════════════════════════════ -->
                <tr>
                    <th colspan="2" style="background:#f9f9f9;padding:14px 10px;border-left:4px solid #BFA25E;">
                        <h3 style="margin:0;color:#1a1a1a;font-size:15px;">📧 Newsletter Popup Settings</h3>
                    </th>
                </tr>

                <tr>
                    <th scope="row"><label for="yallo_newsletter_enabled">Enable Newsletter Popup</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="yallo_newsletter_enabled" id="yallo_newsletter_enabled" value="1"
                                <?php checked(get_option('yallo_newsletter_enabled', false), true); ?> />
                            Show newsletter subscription popup to visitors
                        </label>
                        <p class="description">Works independently — shows even if the chatbot is disabled.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="yallo_newsletter_delay">Popup Delay (ms)</label></th>
                    <td>
                        <input type="number" name="yallo_newsletter_delay" id="yallo_newsletter_delay"
                            value="<?php echo esc_attr(get_option('yallo_newsletter_delay', 5000)); ?>"
                            min="0" max="60000" step="1000" class="regular-text" />
                        <p class="description">Default: 5000ms (5 seconds)</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="yallo_newsletter_title">Popup Title</label></th>
                    <td>
                        <input type="text" name="yallo_newsletter_title" id="yallo_newsletter_title"
                            value="<?php echo esc_attr(get_option('yallo_newsletter_title', 'Stay Updated with YALLO')); ?>"
                            class="regular-text" />
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="yallo_newsletter_description">Popup Description</label></th>
                    <td>
                        <textarea name="yallo_newsletter_description" id="yallo_newsletter_description"
                            rows="3" class="large-text"><?php echo esc_textarea(get_option('yallo_newsletter_description', 'Get the latest insights on tech talent and enterprise architecture delivered to your inbox.')); ?></textarea>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="yallo_newsletter_button_text">Button Text</label></th>
                    <td>
                        <input type="text" name="yallo_newsletter_button_text" id="yallo_newsletter_button_text"
                            value="<?php echo esc_attr(get_option('yallo_newsletter_button_text', 'Subscribe Now')); ?>"
                            class="regular-text" />
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="yallo_newsletter_show_once">Show Once Per Visitor</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="yallo_newsletter_show_once" id="yallo_newsletter_show_once" value="1"
                                <?php checked(get_option('yallo_newsletter_show_once', true), true); ?> />
                            Only show popup once every 30 days per visitor
                        </label>
                    </td>
                </tr>

                <!-- ══════════════════════════════════════════ -->
                <!--  SMTP SETTINGS                             -->
                <!-- ══════════════════════════════════════════ -->
                <tr>
                    <th colspan="2" style="background:#f9f9f9;padding:14px 10px;border-left:4px solid #BFA25E;">
                        <h3 style="margin:0;color:#1a1a1a;font-size:15px;">✉️ SMTP Email Settings</h3>
                    </th>
                </tr>

                <!-- Enable SMTP -->
                <tr>
                    <th scope="row"><label for="yallo_smtp_enabled">Enable SMTP</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="yallo_smtp_enabled" id="yallo_smtp_enabled" value="1"
                                <?php checked(get_option('yallo_smtp_enabled', false), true); ?> />
                            Use custom SMTP server to send emails
                        </label>
                        <p class="description">
                            When enabled, all plugin emails (lead alerts, newsletter notifications) use your SMTP server.<br>
                            When disabled, WordPress default PHP mail is used.
                        </p>
                    </td>
                </tr>

                <!-- SMTP Host -->
                <tr class="yallo-smtp-field">
                    <th scope="row"><label for="yallo_smtp_host">SMTP Host</label></th>
                    <td>
                        <input type="text" name="yallo_smtp_host" id="yallo_smtp_host"
                            value="<?php echo esc_attr(get_option('yallo_smtp_host', '')); ?>"
                            class="regular-text" placeholder="smtp.gmail.com" />
                        <p class="description">
                            <strong>Common providers:</strong><br>
                            Gmail: <code>smtp.gmail.com</code> &nbsp;|&nbsp;
                            Outlook / Office 365: <code>smtp.office365.com</code> &nbsp;|&nbsp;
                            Yahoo: <code>smtp.mail.yahoo.com</code> &nbsp;|&nbsp;
                            SendGrid: <code>smtp.sendgrid.net</code> &nbsp;|&nbsp;
                            Mailgun: <code>smtp.mailgun.org</code>
                        </p>
                    </td>
                </tr>

                <!-- SMTP Port -->
                <tr class="yallo-smtp-field">
                    <th scope="row"><label for="yallo_smtp_port">SMTP Port</label></th>
                    <td>
                        <input type="number" name="yallo_smtp_port" id="yallo_smtp_port"
                            value="<?php echo esc_attr(get_option('yallo_smtp_port', 587)); ?>"
                            class="small-text" min="1" max="65535" />
                        <p class="description">
                            <code>587</code> — TLS (recommended) &nbsp;|&nbsp;
                            <code>465</code> — SSL &nbsp;|&nbsp;
                            <code>25</code> — None (not recommended)
                        </p>
                    </td>
                </tr>

                <!-- Encryption -->
                <tr class="yallo-smtp-field">
                    <th scope="row"><label for="yallo_smtp_encryption">Encryption</label></th>
                    <td>
                        <?php $enc = get_option('yallo_smtp_encryption', 'tls'); ?>
                        <select name="yallo_smtp_encryption" id="yallo_smtp_encryption">
                            <option value="tls" <?php selected($enc, 'tls'); ?>>TLS — Port 587 (Recommended)</option>
                            <option value="ssl" <?php selected($enc, 'ssl'); ?>>SSL — Port 465</option>
                            <option value=""    <?php selected($enc, '');    ?>>None — Port 25 (Not Recommended)</option>
                        </select>
                    </td>
                </tr>

                <!-- SMTP Username -->
                <tr class="yallo-smtp-field">
                    <th scope="row"><label for="yallo_smtp_username">SMTP Username</label></th>
                    <td>
                        <input type="text" name="yallo_smtp_username" id="yallo_smtp_username"
                            value="<?php echo esc_attr(get_option('yallo_smtp_username', '')); ?>"
                            class="regular-text" placeholder="your@email.com" autocomplete="off" />
                        <p class="description">Usually your full email address.</p>
                    </td>
                </tr>

                <!-- SMTP Password -->
                <tr class="yallo-smtp-field">
                    <th scope="row"><label for="yallo_smtp_password">SMTP Password</label></th>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                            <input type="password" name="yallo_smtp_password" id="yallo_smtp_password"
                                value="<?php echo esc_attr(get_option('yallo_smtp_password', '')); ?>"
                                class="regular-text" autocomplete="new-password" />
                            <button type="button" id="yallo-toggle-password"
                                style="padding:5px 12px;cursor:pointer;border:1px solid #ccc;border-radius:4px;background:#fff;">
                                👁 Show
                            </button>
                        </div>
                        <p class="description">
                            For Gmail, use an <strong>App Password</strong> — not your normal Google password.<br>
                            <a href="https://myaccount.google.com/apppasswords" target="_blank">Generate a Gmail App Password →</a>
                        </p>
                    </td>
                </tr>

                <!-- From Email -->
                <tr class="yallo-smtp-field">
                    <th scope="row"><label for="yallo_smtp_from_email">From Email</label></th>
                    <td>
                        <input type="email" name="yallo_smtp_from_email" id="yallo_smtp_from_email"
                            value="<?php echo esc_attr(get_option('yallo_smtp_from_email', get_option('admin_email'))); ?>"
                            class="regular-text" placeholder="noreply@yoursite.com" />
                        <p class="description">The address emails will appear to come from.</p>
                    </td>
                </tr>

                <!-- From Name -->
                <tr class="yallo-smtp-field">
                    <th scope="row"><label for="yallo_smtp_from_name">From Name</label></th>
                    <td>
                        <input type="text" name="yallo_smtp_from_name" id="yallo_smtp_from_name"
                            value="<?php echo esc_attr(get_option('yallo_smtp_from_name', get_bloginfo('name'))); ?>"
                            class="regular-text" placeholder="YALLO" />
                        <p class="description">The sender name shown in the inbox.</p>
                    </td>
                </tr>

                <!-- Test Email -->
                <tr class="yallo-smtp-field">
                    <th scope="row">Send Test Email</th>
                    <td>
                        <button type="button" id="yallo-smtp-test"
                            style="padding:8px 18px;background:#BFA25E;border:none;border-radius:5px;color:#000;font-weight:700;font-size:14px;cursor:pointer;">
                            📨 Send Test Email
                        </button>
                        <span id="yallo-smtp-test-result" style="margin-left:15px;font-weight:600;font-size:14px;"></span>
                        <p class="description">
                            Sends a test email to <strong><?php echo esc_html(get_option('yallo_chatbot_notification_email', get_option('admin_email'))); ?></strong>.<br>
                            <em>Save your settings first before testing.</em>
                        </p>
                    </td>
                </tr>

            </tbody>
        </table>

        <?php submit_button('💾 Save Settings'); ?>
    </form>
</div>

<style>
.wrap h1 { color: #1a1a1a; }
.form-table th { padding: 18px 10px 18px 0; vertical-align: top; }
.form-table td { padding: 14px 10px; }
.yallo-smtp-field { transition: opacity .2s ease; }
.yallo-smtp-field.smtp-hidden { opacity: .35; pointer-events: none; }
code { background: #f1f1f1; padding: 2px 5px; border-radius: 3px; font-size: 12px; }
</style>

<script>
jQuery(document).ready(function ($) {

    /* ── Show/hide specific pages textarea ───────────── */
    $('#yallo_chatbot_display_mode').on('change', function () {
        $('#yallo_specific_pages_row').toggle($(this).val() === 'specific_pages');
    });

    /* ── Grey out SMTP fields when disabled ──────────── */
    function toggleSmtpFields() {
        var on = $('#yallo_smtp_enabled').is(':checked');
        $('.yallo-smtp-field').toggleClass('smtp-hidden', !on);
    }
    toggleSmtpFields();
    $('#yallo_smtp_enabled').on('change', toggleSmtpFields);

    /* ── Show / hide password ────────────────────────── */
    $('#yallo-toggle-password').on('click', function () {
        var $f = $('#yallo_smtp_password');
        var hide = $f.attr('type') === 'text';
        $f.attr('type', hide ? 'password' : 'text');
        $(this).text(hide ? '👁 Show' : '🙈 Hide');
    });

    /* ── Send test email ─────────────────────────────── */
    $('#yallo-smtp-test').on('click', function () {
        var $btn = $(this);
        var $msg = $('#yallo-smtp-test-result');
        $btn.prop('disabled', true).text('⏳ Sending…');
        $msg.text('').css('color', '');

        $.post(ajaxurl, {
            action: 'yallo_smtp_test',
            nonce:  '<?php echo wp_create_nonce("yallo_chatbot_nonce"); ?>'
        })
        .done(function (r) {
            if (r.success) {
                $msg.text('✅ ' + r.data.message).css('color', '#4CAF50');
            } else {
                $msg.text('❌ ' + r.data.message).css('color', '#e74c3c');
            }
        })
        .fail(function () {
            $msg.text('❌ Request failed. Check browser console.').css('color', '#e74c3c');
        })
        .always(function () {
            $btn.prop('disabled', false).text('📨 Send Test Email');
        });
    });

});
</script>