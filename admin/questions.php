<?php
/**
 * YALLO Chatbot - Questions Editor
 * Simple admin page to edit chatbot questions
 */

if (!defined('ABSPATH')) exit;

// Handle Save
if (isset($_POST['yallo_save_questions']) && check_admin_referer('yallo_questions_nonce')) {
    $questions_json = stripslashes($_POST['yallo_questions_data']);
    update_option('yallo_chatbot_questions_json', $questions_json);
    echo '<div class="notice notice-success is-dismissible"><p><strong>✅ Questions saved!</strong> Changes are live on your site.</p></div>';
}

// Handle Reset
if (isset($_POST['yallo_reset_questions']) && check_admin_referer('yallo_questions_nonce')) {
    delete_option('yallo_chatbot_questions_json');
    echo '<div class="notice notice-success is-dismissible"><p><strong>🔄 Reset complete!</strong> Using default questions.</p></div>';
}

// Load questions
$questions_json = get_option('yallo_chatbot_questions_json', '');
if (empty($questions_json)) {
    // Defaults - match current chatbot.js structure exactly
    $default = array(
        'welcome' => array(
            'text' => "Hi, we're YALLO 👋\n\nHow can we help?",
            'options' => array(
                array('text' => '🚀 Hire tech talent / build a squad', 'intent' => 'hire_tech', 'leadType' => 'details'),
                array('text' => '🛠️ Stabilise a project / fix delivery issues', 'intent' => 'stabilise', 'leadType' => 'call'),
                array('text' => '🧭 EA / IT Strategy help', 'intent' => 'strategy', 'leadType' => 'call'),
                array('text' => '🤔 Not sure — let\'s figure it out together', 'intent' => 'unsure', 'leadType' => 'details'),
            )
        ),
        'services' => array(
            array(
                'id' => 10,
                'text' => "Great – tech talent & squads.\n\nVetted profiles across AI, Data, Cloud, SAP, Oracle, Salesforce & more – delivered in ~72 hrs.",
            ),
            array(
                'id' => 11,
                'text' => "Got it – stabilise a project.\n\nWe use architects & delivery leads to find and fix talent or role clarity gaps fast.",
            ),
            array(
                'id' => 12,
                'text' => "Understood – EA / IT strategy.\n\nWe provide Chief Architect capacity to align roadmaps and talent – no big consulting lock-in.",
            ),
            array(
                'id' => 13,
                'text' => "No problem – we'll figure it out together.\n\nTell us a little and we'll recommend the right next step.",
            ),
        ),
        'consultation' => array(
            array('key' => 'name', 'text' => "What's your **full name?**", 'placeholder' => 'e.g. John Smith'),
            array('key' => 'email', 'text' => "Thanks {name}! Your **work email?**", 'placeholder' => 'you@company.com'),
            array('key' => 'company', 'text' => "Your **company** name?", 'placeholder' => 'Acme Corp'),
            array('key' => 'location', 'text' => "**Where** are you based?\n(e.g. Dubai, UAE)", 'placeholder' => 'City, Country'),
            array('key' => 'industry', 'text' => "**Industry?**\n\n- Retail & Consumer\n- Manufacturing & Logistics\n- Banking & Financial Services\n- Government & Public Sector\n- Healthcare & Life Science\n- Telco & Media\n- Other"),
            array('key' => 'platforms', 'text' => "**Core platform?**\n\n- SAP\n- Oracle\n- Microsoft\n- Salesforce\n- Blue Yonder\n- Workday\n- Other / Not sure"),
            array('key' => 'capabilities', 'text' => "**Biggest gap?**\n\n- Data & AI\n- Digital & DevOps\n- Cloud & Infrastructure\n- Cybersecurity\n- Integration & Middleware\n- Emerging Technologies"),
            array('key' => 'service_type', 'text' => "**What do you need?**\n\n- Talent in a Box\n- TS/EA as a Service\n- Managed IT CoE\n- Not sure"),
            array('key' => 'pain', 'text' => "In **one line** – what's the main challenge?", 'placeholder' => 'e.g. Can\'t hire architects fast enough'),
        ),
        'final' => array(
            'text' => "Thanks, **{name}**! 🙌\n\nWe'll be in touch at **{email}** within 24 hrs."
        )
    );
    $questions_json = json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-format-chat" style="color:#BFA25E;"></span>
        Edit Chatbot Questions
    </h1>
    
    <p class="description" style="font-size:14px;margin-bottom:25px;">
        Edit all chatbot questions below. Changes take effect immediately on your site.
    </p>

    <form method="post">
        <?php wp_nonce_field('yallo_questions_nonce'); ?>
        
        <div style="background:#fff;padding:25px;border:1px solid #ddd;border-radius:5px;">
            <h2 style="margin-top:0;">💬 Questions JSON Editor</h2>
            <p class="description" style="margin-bottom:15px;">
                Edit the questions structure below. The JSON controls:
            </p>
            <ul style="margin-left:20px;margin-bottom:20px;line-height:1.8;">
                <li><strong>welcome.text</strong> — First message users see</li>
                <li><strong>welcome.options</strong> — The 4 initial service buttons</li>
                <li><strong>services</strong> — Response messages for each service type</li>
                <li><strong>consultation</strong> — The 9 lead capture questions</li>
                <li><strong>final.text</strong> — Closing thank you message</li>
            </ul>
            
            <textarea 
                name="yallo_questions_data" 
                id="json-editor" 
                rows="40" 
                style="width:100%;font-family:'Courier New',monospace;font-size:13px;line-height:1.6;padding:15px;background:#f9f9f9;border:1px solid #ddd;border-radius:4px;"
            ><?php echo esc_textarea($questions_json); ?></textarea>
            
            <div style="margin-top:15px;">
                <button type="button" id="validate-json" class="button">
                    ✓ Validate JSON
                </button>
                <span id="json-status" style="margin-left:12px;font-weight:600;"></span>
            </div>

            <div style="margin-top:15px;padding:12px;background:#e7f3ff;border-left:4px solid #2196F3;border-radius:3px;">
                <strong>💡 Tips:</strong>
                <ul style="margin:8px 0 0 18px;line-height:1.7;">
                    <li>Use <code>\n</code> for line breaks in text</li>
                    <li>Use <code>{name}</code> or <code>{email}</code> to insert user's data dynamically</li>
                    <li>Use <code>**text**</code> for bold, <code>*text*</code> for italic (markdown)</li>
                    <li>Always validate JSON before saving!</li>
                </ul>
            </div>
        </div>

        <p style="margin-top:25px;">
            <button type="submit" name="yallo_save_questions" class="button button-primary button-large">
                💾 Save Questions
            </button>
            
            <button 
                type="submit" 
                name="yallo_reset_questions" 
                class="button button-secondary" 
                onclick="return confirm('Are you sure? This will reset all questions to defaults and cannot be undone.');"
                style="margin-left:10px;"
            >
                🔄 Reset to Defaults
            </button>
            
            <a href="<?php echo admin_url('admin.php?page=yallo-chatbot'); ?>" class="button" style="margin-left:10px;">
                ← Back to Settings
            </a>
        </p>
    </form>
</div>

<style>
#json-status.valid { color: #0a0; }
#json-status.invalid { color: #d00; }
code { background: #f1f1f1; padding: 2px 6px; border-radius: 3px; font-size: 12px; font-family: 'Courier New', monospace; }
</style>

<script>
jQuery(document).ready(function($) {
    // Validate JSON
    $('#validate-json').on('click', function() {
        const json = $('#json-editor').val();
        const $status = $('#json-status');
        try {
            const parsed = JSON.parse(json);
            $status.text('✓ Valid JSON!').removeClass('invalid').addClass('valid');
            
            // Check structure
            if (!parsed.welcome || !parsed.services || !parsed.consultation) {
                $status.text('⚠️ Valid JSON but missing required sections (welcome, services, consultation)').removeClass('valid').addClass('invalid');
            }
        } catch (e) {
            $status.text('✗ Invalid JSON: ' + e.message).removeClass('valid').addClass('invalid');
        }
    });

    // Auto-validate on page load
    $('#validate-json').click();
});
</script>