<?php
/**
 * YALLO Chatbot - RAG AI Handler
 * Retrieval Augmented Generation with Claude API
 *
 * This enables intelligent responses based on your website content
 */

if (!defined('ABSPATH')) exit;

class YALLO_Chatbot_RAG {

    private $wpdb;
    private $table_name;
    private $claude_api_key;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'yallo_knowledge_base';
        $this->claude_api_key = get_option('yallo_claude_api_key', '');
    }

    /**
     * Create knowledge base table
     */
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'yallo_knowledge_base';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            content_hash varchar(32) NOT NULL,
            page_url varchar(500) DEFAULT '',
            page_title varchar(255) DEFAULT '',
            content_chunk text NOT NULL,
            chunk_index int(11) DEFAULT 0,
            metadata longtext DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY content_hash (content_hash),
            KEY page_url (page_url)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Add FULLTEXT index separately (dbDelta does not handle FULLTEXT reliably)
        $existing = $wpdb->get_var("SHOW INDEX FROM $table_name WHERE Key_name = 'ft_search'");
        if (!$existing) {
            $wpdb->query("ALTER TABLE $table_name ADD FULLTEXT KEY ft_search (page_title, content_chunk)");
        }
    }

    /**
     * Scrape website content — incremental: only re-indexes changed posts
     */
    public function scrape_website() {
        $posts = get_posts(array(
            'post_type'      => array('page', 'post'),
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ));

        $scraped_count = 0;
        $current_urls  = array();

        foreach ($posts as $post) {
            $url          = get_permalink($post->ID);
            $current_urls[] = $url;
            $content_hash = md5($post->post_title . $post->post_content);

            // Check if this URL already has an up-to-date hash
            $existing_hash = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT content_hash FROM {$this->table_name} WHERE page_url = %s LIMIT 1",
                    $url
                )
            );

            if ($existing_hash === $content_hash) {
                continue; // Content unchanged — skip
            }

            // Delete stale chunks for this URL, then re-index
            $this->wpdb->delete($this->table_name, array('page_url' => $url), array('%s'));

            $content = $this->extract_content($post);
            if (!empty($content)) {
                $this->store_content($post, $content);
                $scraped_count++;
            }
        }

        // Remove entries for posts that no longer exist
        if (!empty($current_urls)) {
            $placeholders = implode(',', array_fill(0, count($current_urls), '%s'));
            $this->wpdb->query(
                $this->wpdb->prepare(
                    "DELETE FROM {$this->table_name} WHERE page_url NOT IN ($placeholders)",
                    ...$current_urls
                )
            );
        } else {
            $this->wpdb->query("TRUNCATE TABLE {$this->table_name}");
        }

        return $scraped_count;
    }

    /**
     * Extract clean content from post
     */
    private function extract_content($post) {
        $content = $post->post_title . "\n\n" . $post->post_content;

        // Remove shortcodes
        $content = strip_shortcodes($content);

        // Remove HTML tags
        $content = wp_strip_all_tags($content);

        // Remove extra whitespace
        $content = preg_replace('/\s+/', ' ', $content);

        // Clean up
        $content = trim($content);

        return $content;
    }

    /**
     * Store content in chunks
     */
    private function store_content($post, $content) {
        $chunks = $this->chunk_content($content);
        $content_hash = md5($post->post_title . $post->post_content);

        foreach ($chunks as $index => $chunk) {
            $metadata = array(
                'post_id' => $post->ID,
                'post_type' => $post->post_type,
                'excerpt' => wp_trim_words($chunk, 20)
            );

            $this->wpdb->insert($this->table_name, array(
                'content_hash' => $content_hash,
                'page_url' => get_permalink($post->ID),
                'page_title' => $post->post_title,
                'content_chunk' => $chunk,
                'chunk_index' => $index,
                'metadata' => wp_json_encode($metadata)
            ));
        }
    }

    /**
     * Split content into chunks (500 words each)
     */
    private function chunk_content($content, $chunk_size = 500) {
        $words = explode(' ', $content);
        $chunks = array();

        for ($i = 0; $i < count($words); $i += $chunk_size) {
            $chunk = implode(' ', array_slice($words, $i, $chunk_size));
            if (!empty(trim($chunk))) {
                $chunks[] = $chunk;
            }
        }

        return $chunks;
    }

    /**
     * Search relevant content using FULLTEXT, with LIKE fallback
     */
    public function search_knowledge($query, $limit = 5) {
        $keywords = $this->extract_keywords($query);

        if (empty($keywords)) {
            return array();
        }

        // Build FULLTEXT boolean query: +word1* +word2* (prefix match, all terms preferred)
        $ft_query = implode(' ', array_map(function($k) {
            return '+' . $k . '*';
        }, $keywords));

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT *, MATCH(page_title, content_chunk) AGAINST (%s IN BOOLEAN MODE) AS relevance
                 FROM {$this->table_name}
                 WHERE MATCH(page_title, content_chunk) AGAINST (%s IN BOOLEAN MODE)
                 GROUP BY page_url
                 ORDER BY relevance DESC
                 LIMIT %d",
                $ft_query, $ft_query, $limit
            )
        );

        // Fallback: LIKE search if FULLTEXT returns nothing (e.g. index not ready)
        if (empty($results)) {
            $where_clauses = array();
            foreach ($keywords as $keyword) {
                $where_clauses[] = $this->wpdb->prepare(
                    "content_chunk LIKE %s OR page_title LIKE %s",
                    '%' . $this->wpdb->esc_like($keyword) . '%',
                    '%' . $this->wpdb->esc_like($keyword) . '%'
                );
            }
            $where = implode(' OR ', $where_clauses);
            $results = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->table_name} WHERE {$where} GROUP BY page_url LIMIT %d",
                    $limit
                )
            );
        }

        return $results;
    }

    /**
     * Extract keywords from query
     */
    private function extract_keywords($query) {
        // Remove common words
        $stop_words = array('the', 'is', 'at', 'which', 'on', 'a', 'an', 'and', 'or', 'but', 'in', 'with', 'to', 'for', 'of', 'as', 'by', 'do', 'you', 'have', 'what', 'where', 'when', 'how', 'i', 'we', 'can', 'does');

        $words = explode(' ', strtolower($query));
        $keywords = array_diff($words, $stop_words);

        // Filter short words
        $keywords = array_filter($keywords, function($word) {
            return strlen($word) > 2;
        });

        return array_values($keywords);
    }

    /**
     * Get AI response using Claude API with RAG and conversation history
     *
     * @param string $user_message Current user message
     * @param array  $history      Prior turns: [['role'=>'user'|'assistant','content'=>'...'], ...]
     */
    public function get_ai_response($user_message, $history = array()) {
        if (empty($this->claude_api_key)) {
            return array(
                'success' => false,
                'message' => 'Claude API key not configured'
            );
        }

        // Search knowledge base
        $relevant_chunks = $this->search_knowledge($user_message, 3);

        // Build context from chunks
        $context = '';
        if (!empty($relevant_chunks)) {
            $context = "Based on YALLO's website content:\n\n";
            foreach ($relevant_chunks as $chunk) {
                $context .= "From " . $chunk->page_title . ":\n";
                $context .= $chunk->content_chunk . "\n\n";
            }
        }

        // Prepare prompt for Claude
        $system_prompt = "You are YALLO's intelligent chatbot assistant. You help users understand YALLO's services in tech talent, enterprise architecture, and IT consulting.

IMPORTANT GUIDELINES:
- Be professional but friendly
- Keep responses concise (2-3 sentences max, under 50 words)
- If asked about services YALLO doesn't offer, politely say so
- Always be helpful and suggest relevant YALLO services when appropriate
- Use the context provided to give accurate information
- Never make up information not in the context

If you don't have enough information to answer, say: 'I don't have specific details on that. Would you like to share your contact details so our team can help you directly?'";

        $user_prompt = $context . "\nUser Question: " . $user_message . "\n\nProvide a helpful, concise response (max 50 words):";

        // Call Claude API
        $response = $this->call_claude_api($system_prompt, $user_prompt, $history);

        return $response;
    }

    /**
     * Call Claude API with optional conversation history
     */
    private function call_claude_api($system_prompt, $user_prompt, $history = array()) {
        $api_url = 'https://api.anthropic.com/v1/messages';

        $headers = array(
            'Content-Type'      => 'application/json',
            'x-api-key'         => $this->claude_api_key,
            'anthropic-version' => '2023-06-01'
        );

        // Build messages array: prior history + current user message
        $messages = array();
        foreach ($history as $turn) {
            $messages[] = array(
                'role'    => $turn['role'],
                'content' => $turn['content'],
            );
        }
        $messages[] = array('role' => 'user', 'content' => $user_prompt);

        $body = array(
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => 200,
            'system'     => $system_prompt,
            'messages'   => $messages,
        );

        $response = wp_remote_post($api_url, array(
            'headers' => $headers,
            'body'    => wp_json_encode($body),
            'timeout' => 60,
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'API request failed: ' . $response->get_error_message()
            );
        }

        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if (isset($data['content'][0]['text'])) {
            return array(
                'success' => true,
                'message' => $data['content'][0]['text'],
                'sources' => $this->get_source_urls($user_prompt),
            );
        }

        // Handle errors
        if (isset($data['error'])) {
            return array(
                'success' => false,
                'message' => 'API Error: ' . $data['error']['message']
            );
        }

        return array(
            'success' => false,
            'message' => 'Invalid API response'
        );
    }

    /**
     * Get source URLs for transparency
     */
    private function get_source_urls($query) {
        $chunks = $this->search_knowledge($query, 3);
        $urls = array();

        foreach ($chunks as $chunk) {
            if (!in_array($chunk->page_url, $urls)) {
                $urls[] = array(
                    'title' => $chunk->page_title,
                    'url'   => $chunk->page_url
                );
            }
        }

        return $urls;
    }

    /**
     * Get knowledge base statistics
     */
    public function get_stats() {
        $total_chunks = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        $total_pages  = $this->wpdb->get_var("SELECT COUNT(DISTINCT page_url) FROM {$this->table_name}");
        $last_updated = $this->wpdb->get_var("SELECT MAX(updated_at) FROM {$this->table_name}");

        return array(
            'total_chunks' => $total_chunks,
            'total_pages'  => $total_pages,
            'last_updated' => $last_updated
        );
    }
}
