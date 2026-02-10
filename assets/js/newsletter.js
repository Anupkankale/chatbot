/**
 * YALLO Newsletter Popup - JavaScript
 */

(function($) {
    'use strict';
    
    const YALLO_NEWSLETTER = {
        
        // State
        hasShown: false,
        
        // DOM elements
        $popup: null,
        $overlay: null,
        $closeBtn: null,
        $form: null,
        $nameInput: null,
        $emailInput: null,
        $submitBtn: null,
        $successMsg: null,
        $errorMsg: null,
        
        /**
         * Initialize
         */
        init: function() {
            this.cacheDom();
            this.bindEvents();
            this.checkAndShow();
        },
        
        /**
         * Cache DOM elements
         */
        cacheDom: function() {
            this.$popup = $('#yallo-newsletter-popup');
            this.$overlay = $('.yallo-newsletter-overlay');
            this.$closeBtn = $('#yallo-newsletter-close');
            this.$form = $('#yallo-newsletter-form');
            this.$nameInput = $('#yallo-newsletter-name');
            this.$emailInput = $('#yallo-newsletter-email');
            this.$submitBtn = $('#yallo-newsletter-submit');
            this.$successMsg = $('#yallo-newsletter-success');
            this.$errorMsg = $('#yallo-newsletter-error');
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            const self = this;
            
            // Close button
            this.$closeBtn.on('click', function() {
                self.closePopup();
            });
            
            // Close on overlay click
            this.$overlay.on('click', function(e) {
                if (e.target === this) {
                    self.closePopup();
                }
            });
            
            // Close on ESC key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.$popup.is(':visible')) {
                    self.closePopup();
                }
            });
            
            // Form submission
            this.$form.on('submit', function(e) {
                e.preventDefault();
                self.handleSubmit();
            });
        },
        
        /**
         * Check if should show popup
         */
        checkAndShow: function() {
            // Check if already shown (cookie)
            if (yalloNewsletter.showOnce && this.getCookie('yallo_newsletter_shown')) {
                return;
            }
            
            // Check if already subscribed (localStorage)
            if (localStorage.getItem('yallo_newsletter_subscribed')) {
                return;
            }
            
            // Show after delay
            const delay = parseInt(yalloNewsletter.delay) || 5000;
            
            setTimeout(() => {
                this.showPopup();
            }, delay);
        },
        
        /**
         * Show popup
         */
        showPopup: function() {
            if (this.hasShown) return;
            
            this.hasShown = true;
            this.$popup.fadeIn(300);
            
            // Set cookie if show once enabled
            if (yalloNewsletter.showOnce) {
                this.setCookie('yallo_newsletter_shown', '1', 30); // 30 days
            }
            
            // Focus on email input
            setTimeout(() => {
                this.$emailInput.focus();
            }, 400);
        },
        
        /**
         * Close popup
         */
        closePopup: function() {
            this.$overlay.addClass('closing');
            
            setTimeout(() => {
                this.$popup.fadeOut(200, () => {
                    this.$overlay.removeClass('closing');
                });
            }, 200);
        },
        
        /**
         * Handle form submission
         */
        handleSubmit: function() {
            const self = this;
            
            // Get values
            const name = this.$nameInput.val().trim();
            const email = this.$emailInput.val().trim();
            
            // Validate email
            if (!this.isValidEmail(email)) {
                this.showError('Please enter a valid email address');
                return;
            }
            
            // Clear previous errors
            this.hideError();
            
            // Show loading state
            this.$submitBtn.addClass('loading').prop('disabled', true);
            
            // Submit via AJAX
            $.ajax({
                url: yalloNewsletter.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'yallo_newsletter_subscribe',
                    nonce: yalloNewsletter.nonce,
                    name: name,
                    email: email,
                    page_url: window.location.href
                },
                success: function(response) {
                    self.$submitBtn.removeClass('loading').prop('disabled', false);
                    
                    if (response.success) {
                        self.showSuccess();
                        
                        // Mark as subscribed
                        localStorage.setItem('yallo_newsletter_subscribed', '1');
                        
                        // Close after 3 seconds
                        setTimeout(() => {
                            self.closePopup();
                        }, 3000);
                    } else {
                        self.showError(response.data.message || 'An error occurred');
                    }
                },
                error: function() {
                    self.$submitBtn.removeClass('loading').prop('disabled', false);
                    self.showError('Connection error. Please try again.');
                }
            });
        },
        
        /**
         * Show success message
         */
        showSuccess: function() {
            this.$form.slideUp(300);
            this.$successMsg.fadeIn(300);
        },
        
        /**
         * Show error message
         */
        showError: function(message) {
            this.$errorMsg.find('p').text(message);
            this.$errorMsg.fadeIn(200);
            
            // Shake animation
            this.$form.addClass('shake');
            setTimeout(() => {
                this.$form.removeClass('shake');
            }, 500);
        },
        
        /**
         * Hide error message
         */
        hideError: function() {
            this.$errorMsg.fadeOut(200);
        },
        
        /**
         * Validate email
         */
        isValidEmail: function(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },
        
        /**
         * Set cookie
         */
        setCookie: function(name, value, days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            const expires = "expires=" + date.toUTCString();
            document.cookie = name + "=" + value + ";" + expires + ";path=/";
        },
        
        /**
         * Get cookie
         */
        getCookie: function(name) {
            const cookieName = name + "=";
            const cookies = document.cookie.split(';');
            
            for (let i = 0; i < cookies.length; i++) {
                let cookie = cookies[i].trim();
                if (cookie.indexOf(cookieName) === 0) {
                    return cookie.substring(cookieName.length, cookie.length);
                }
            }
            
            return null;
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        YALLO_NEWSLETTER.init();
    });
    
})(jQuery);

// Add shake animation CSS
const style = document.createElement('style');
style.textContent = `
    .yallo-newsletter-form.shake {
        animation: shake 0.5s;
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
`;
document.head.appendChild(style);