 <div id="yallo-chatbot-container" class="yallo-chatbot-wrapper">
    <!-- Chatbot Window -->
    <div id="yallo-chatbot-window" class="yallo-chat-window">
        <div class="yallo-chat-container">
            <!-- Header -->
            <div class="yallo-chat-header">
                <h3 class="yallo-chat-title">YALLO Talent Assistant</h3>
                <button type="button" id="yallo-chat-close" class="yallo-close-btn" aria-label="Close chat">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            
            <!-- Messages Container -->
            <div id="yallo-messages-container" class="yallo-messages">
                <!-- Messages will be added here dynamically -->
            </div>
            
            <!-- Input Area -->
            <div class="yallo-input-area">
                <form id="yallo-chat-form" class="yallo-input-form">
                    <input 
                        type="text" 
                        id="yallo-message-input" 
                        class="yallo-message-input" 
                        placeholder="Type your message or use the options..."
                        autocomplete="off"
                    />
                    <button type="submit" id="yallo-send-btn" class="yallo-send-btn" aria-label="Send message">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Toggle Button -->
    <button type="button" id="yallo-chat-toggle" class="yallo-chat-toggle" aria-label="Open chat">
        <svg id="yallo-chat-icon" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <svg id="yallo-close-icon" style="display: none;" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
    </button>
</div>