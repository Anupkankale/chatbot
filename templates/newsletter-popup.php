<!-- YALLO Newsletter Popup -->
<div id="yallo-newsletter-popup" class="yallo-newsletter-overlay" style="display: none;">
    <div class="yallo-newsletter-container">
        <button type="button" id="yallo-newsletter-close" class="yallo-newsletter-close" aria-label="Close newsletter popup">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
        
        <div class="yallo-newsletter-content">
            <!-- Icon/Logo Area -->
            <div class="yallo-newsletter-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#BFA25E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
            </div>
            
            <!-- Title -->
            <h2 class="yallo-newsletter-title">
                <?php echo esc_html(get_option('yallo_newsletter_title', 'Stay Updated with YALLO')); ?>
            </h2>
            
            <!-- Description -->
            <p class="yallo-newsletter-description">
                <?php echo esc_html(get_option('yallo_newsletter_description', 'Get the latest insights on tech talent and enterprise architecture delivered to your inbox.')); ?>
            </p>
            
            <!-- Form -->
            <form id="yallo-newsletter-form" class="yallo-newsletter-form">
                <div class="yallo-newsletter-field">
                    <input 
                        type="text" 
                        id="yallo-newsletter-name" 
                        name="name"
                        placeholder="Your Name "
                        class="yallo-newsletter-input"
                    />
                </div>
                
                <div class="yallo-newsletter-field">
                    <input 
                        type="email" 
                        id="yallo-newsletter-email" 
                        name="email"
                        placeholder="Your Email Address *"
                        required
                        class="yallo-newsletter-input"
                    />
                </div>
                
                <button 
                    type="submit" 
                    id="yallo-newsletter-submit" 
                    class="yallo-newsletter-button"
                >
                    <?php echo esc_html(get_option('yallo_newsletter_button_text', 'Subscribe Now')); ?>
                </button>
                
                <p class="yallo-newsletter-privacy">
                    We respect your privacy. Unsubscribe anytime.
                </p>
            </form>
            
            <!-- Success Message (hidden by default) -->
            <div id="yallo-newsletter-success" class="yallo-newsletter-success" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#4CAF50" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <h3>Thank You!</h3>
                <p>You've successfully subscribed to our newsletter.</p>
            </div>
            
            <!-- Error Message (hidden by default) -->
            <div id="yallo-newsletter-error" class="yallo-newsletter-error" style="display: none;">
                <p></p>
            </div>
        </div>
    </div>
</div>