<?php
/**
 * YALLO Chatbot - RAG AI Settings Page
 */

if (!defined('ABSPATH')) exit;

require_once YALLO_CHATBOT_PLUGIN_DIR . 'yallo-chatbot-rag-ai.php';

$rag = new YALLO_Chatbot_RAG();
$stats = $rag->get_stats();

// Handle form submissions
if (isset($_POST['yallo_save_ai_settings'])) {
    check_admin_referer('yallo_ai_settings');
    
    update_option('yallo_claude_api_key', sanitize_text_field($_POST['claude_api_key']));
    update_option('yallo_ai_enabled', isset($_POST['ai_enabled']) ? 1 : 0);
    update_option('yallo_ai_fallback_message', sanitize_textarea_field($_POST['ai_fallback_message']));
    
    echo '<div class="notice notice-success"><p>✅ Settings saved!</p></div>';
}

if (isset($_POST['yallo_scrape_website'])) {
    check_admin_referer('yallo_ai_settings');
    
    $count = $rag->scrape_website();
    echo '<div class="notice notice-success"><p>✅ Scraped ' . $count . ' pages successfully!</p></div>';
    
    $stats = $rag->get_stats(); // Refresh stats
}

$ai_enabled = get_option('yallo_ai_enabled', false);
$api_key = get_option('yallo_claude_api_key', '');
$fallback_message = get_option('yallo_ai_fallback_message', "I don't have specific information about that. Would you like to speak with our team?");
?>

<div class="wrap yallo-ai-settings-wrap">
    <h1>🧠 YALLO AI Chatbot (RAG)</h1>
    <p class="description">Enable intelligent responses powered by Claude AI and your website content.</p>
    
    <!-- Stats Dashboard -->
    <div class="yallo-stats-grid">
        <div class="yallo-stat-card">
            <div class="yallo-stat-icon">📚</div>
            <div class="yallo-stat-value"><?php echo number_format($stats['total_chunks']); ?></div>
            <div class="yallo-stat-label">Knowledge Chunks</div>
        </div>
        
        <div class="yallo-stat-card">
            <div class="yallo-stat-icon">📄</div>
            <div class="yallo-stat-value"><?php echo number_format($stats['total_pages']); ?></div>
            <div class="yallo-stat-label">Pages Indexed</div>
        </div>
        
        <div class="yallo-stat-card">
            <div class="yallo-stat-icon">🕒</div>
            <div class="yallo-stat-value"><?php echo $stats['last_updated'] ? date('M j, Y', strtotime($stats['last_updated'])) : 'Never'; ?></div>
            <div class="yallo-stat-label">Last Updated</div>
        </div>
    </div>
    
    <hr style="margin: 30px 0;">
    
    <!-- Settings Form -->
    <form method="post">
        <?php wp_nonce_field('yallo_ai_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="ai_enabled">Enable AI Chatbot</label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" 
                               name="ai_enabled" 
                               id="ai_enabled" 
                               value="1" 
                               <?php checked($ai_enabled, 1); ?>>
                        Enable intelligent AI responses
                    </label>
                    <p class="description">
                        When enabled, chatbot will use Claude AI to answer questions based on your website content.
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="claude_api_key">Claude API Key *</label>
                </th>
                <td>
                    <input type="password" 
                           name="claude_api_key" 
                           id="claude_api_key" 
                           value="<?php echo esc_attr($api_key); ?>" 
                           class="regular-text"
                           placeholder="sk-ant-...">
                    <p class="description">
                        Get your API key from <a href="https://console.anthropic.com/settings/keys" target="_blank">Anthropic Console</a>
                        <br>Cost: ~$0.003 per conversation (very affordable)
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="ai_fallback_message">Fallback Message</label>
                </th>
                <td>
                    <textarea name="ai_fallback_message" 
                              id="ai_fallback_message" 
                              rows="3" 
                              class="large-text"><?php echo esc_textarea($fallback_message); ?></textarea>
                    <p class="description">
                        Shown when AI doesn't have enough information to answer
                    </p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" name="yallo_save_ai_settings" class="button button-primary button-large">
                💾 Save Settings
            </button>
        </p>
    </form>
    
    <hr style="margin: 30px 0;">
    
    <!-- Knowledge Base Management -->
    <h2>📚 Knowledge Base</h2>
    <p>Scrape your website content to build the AI knowledge base.</p>
    
    <form method="post">
        <?php wp_nonce_field('yallo_ai_settings'); ?>
        
        <p>
            <button type="submit" 
                    name="yallo_scrape_website" 
                    class="button button-secondary button-large"
                    onclick="return confirm('This will scrape all published pages and posts. Continue?');">
                🔄 Scrape Website Now
            </button>
        </p>
        
        <p class="description">
            <strong>Note:</strong> Run this whenever you update your website content to keep the AI knowledge up-to-date.
        </p>
    </form>
    
    <hr style="margin: 30px 0;">
    
    <!-- How It Works -->
    <div class="yallo-info-panel">
        <h3>🎯 How RAG Works:</h3>
        <ol>
            <li><strong>Scrape:</strong> Extract content from all your pages/posts</li>
            <li><strong>Store:</strong> Save content chunks in database</li>
            <li><strong>Search:</strong> When user asks a question, find relevant chunks</li>
            <li><strong>Generate:</strong> Send chunks + question to Claude AI</li>
            <li><strong>Respond:</strong> AI generates intelligent answer based on your content</li>
        </ol>
        
        <h3>💰 Cost Estimate:</h3>
        <ul>
            <li>~$0.003 per conversation</li>
            <li>1000 conversations = ~$3</li>
            <li>10,000 conversations = ~$30/month</li>
        </ul>
        
        <h3>✨ Benefits:</h3>
        <ul>
            <li>✅ Answers based on YOUR content (not generic)</li>
            <li>✅ Always up-to-date (re-scrape anytime)</li>
            <li>✅ No hallucinations (uses only your content)</li>
            <li>✅ Shows sources (transparency)</li>
            <li>✅ Very affordable</li>
        </ul>
    </div>
    
    <!-- Test Area -->
    <hr style="margin: 30px 0;">
    
    <div class="yallo-test-panel">
        <h3>🧪 Test AI Response</h3>
        <p>Try asking a question to test the AI:</p>
        
        <div class="yallo-test-form">
            <input type="text" 
                   id="yallo-test-question" 
                   placeholder="e.g. What services does YALLO offer?"
                   class="large-text">
            <button type="button" id="yallo-test-ai" class="button button-secondary">
                Ask AI
            </button>
        </div>
        
        <div id="yallo-test-response" style="display: none; margin-top: 20px;">
            <h4>AI Response:</h4>
            <div class="yallo-ai-response"></div>
            
            <h4 style="margin-top: 15px;">Sources:</h4>
            <div class="yallo-ai-sources"></div>
        </div>
    </div>
</div>

<style>
.yallo-ai-settings-wrap {
    max-width: 1200px;
}

.yallo-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin: 20px 0;
}

.yallo-stat-card {
    background: linear-gradient(135deg, #BFA25E 0%, #d4b76e 100%);
    color: #fff;
    padding: 30px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.yallo-stat-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.yallo-stat-value {
    font-size: 36px;
    font-weight: bold;
    margin-bottom: 5px;
}

.yallo-stat-label {
    font-size: 14px;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.yallo-info-panel {
    background: #f0f8ff;
    border-left: 4px solid #2196F3;
    padding: 20px;
    border-radius: 4px;
}

.yallo-info-panel h3 {
    margin-top: 0;
    color: #2196F3;
}

.yallo-info-panel ul,
.yallo-info-panel ol {
    margin-left: 20px;
}

.yallo-test-panel {
    background: #fff;
    border: 1px solid #ccc;
    padding: 20px;
    border-radius: 4px;
}

.yallo-test-form {
    display: flex;
    gap: 10px;
    align-items: center;
}

.yallo-test-form input {
    flex: 1;
}

.yallo-ai-response {
    background: #f9f9f9;
    padding: 15px;
    border-left: 4px solid #BFA25E;
    border-radius: 4px;
}

.yallo-ai-sources {
    background: #f0f0f0;
    padding: 15px;
    border-radius: 4px;
}

.yallo-ai-sources a {
    display: block;
    margin-bottom: 5px;
    text-decoration: none;
}

.yallo-ai-sources a:hover {
    text-decoration: underline;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#yallo-test-ai').on('click', function() {
        const question = $('#yallo-test-question').val().trim();
        
        if (!question) {
            alert('Please enter a question');
            return;
        }
        
        $(this).prop('disabled', true).text('Thinking...');
        
        $.post(ajaxurl, {
            action: 'yallo_test_ai',
            nonce: '<?php echo wp_create_nonce('yallo_ai_test'); ?>',
            question: question
        }, function(response) {
            $('#yallo-test-ai').prop('disabled', false).text('Ask AI');
            
            if (response.success) {
                $('.yallo-ai-response').html(response.data.message);
                
                let sourcesHtml = '';
                if (response.data.sources && response.data.sources.length > 0) {
                    response.data.sources.forEach(source => {
                        sourcesHtml += `<a href="${source.url}" target="_blank">📄 ${source.title}</a>`;
                    });
                } else {
                    sourcesHtml = '<em>No sources found</em>';
                }
                $('.yallo-ai-sources').html(sourcesHtml);
                
                $('#yallo-test-response').slideDown();
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });
});
</script>
 
 