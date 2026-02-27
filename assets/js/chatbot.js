 /**
 * YALLO Talent Chatbot - Main JavaScript
 * v1.0.2 — Short messages, email-first lead capture
 */

(function($) {
    'use strict';

    const YALLO_CHATBOT = {

        // ── State ────────────────────────────────────────────
        isOpen: false,
        isBotTyping: false,
        isChatFinished: false,
        isInputDisabled: false,
        isConsultationActive: false,
        consultationStep: 0,
        hasAutoOpened: false,
        leadSavedEarly: false,

        // ── Data ─────────────────────────────────────────────
        messages: [],
        consultationData: {
            initial_intent: null,
            lead_type: null,
        },

        // ── DOM ──────────────────────────────────────────────
        $window: null,
        $toggle: null,
        $messagesContainer: null,
        $form: null,
        $input: null,
        $sendBtn: null,
        $chatIcon: null,
        $closeIcon: null,

        // ── Welcome + Service Questions ───────────────────────
        // These are loaded dynamically from WordPress on init
        questions: [],
        
        // ── Consultation Questions ────────────────────────────
        // These are loaded dynamically from WordPress on init
        consultationQuestions: [],
        
        // ── Load Questions Dynamically ─────────────────────────
        loadQuestions: function() {
            const self = this;
            const cached = localStorage.getItem('yallo_questions');
            const cacheTime = localStorage.getItem('yallo_questions_time');
            const now = Date.now();
            
            // Use cache if less than 5 minutes old
            if (cached && cacheTime && (now - parseInt(cacheTime)) < 300000) {
                try {
                    const data = JSON.parse(cached);
                    self.applyQuestions(data);
                    return Promise.resolve();
                } catch(e) {
                    console.error('YALLO: Cache parse error', e);
                }
            }
            
            // Fetch from server
            return $.post(yalloChatbot.ajaxUrl, {
                action: 'yallo_get_questions'
            })
            .done(function(response) {
                if (response.success && response.data) {
                    localStorage.setItem('yallo_questions', JSON.stringify(response.data));
                    localStorage.setItem('yallo_questions_time', now.toString());
                    self.applyQuestions(response.data);
                } else {
                    console.warn('YALLO: Questions load failed, using defaults');
                    self.useDefaultQuestions();
                }
            })
            .fail(function() {
                console.warn('YALLO: AJAX failed, using default questions');
                self.useDefaultQuestions();
            });
        },
        
        useDefaultQuestions: function() {
            // Fallback to hardcoded defaults
            const defaultData = {
                welcome: {
                    text: "Hi, we're YALLO 👋\n\nHow can we help?"
                },
                services: [
                    {
                        text: 'Hire tech talent / build a squad',
                        message: "Great – tech talent & squads.\n\nVetted profiles across AI, Data, Cloud, SAP, Oracle, Salesforce & more – delivered in ~72 hrs.",
                        intent: 'Hire tech talent / build a squad',
                        lead_type: 'details'
                    },
                    {
                        text: 'Stabilise a troubled project',
                        message: "Got it – stabilise a project.\n\nWe use architects & delivery leads to find and fix talent or role clarity gaps fast.",
                        intent: 'Stabilise a troubled project',
                        lead_type: 'call'
                    },
                    {
                        text: 'Enterprise Architecture / IT strategy',
                        message: "Understood – EA / IT strategy.\n\nWe provide Chief Architect capacity to align roadmaps and talent – no big consulting lock-in.",
                        intent: 'Enterprise Architecture / IT strategy',
                        lead_type: 'call'
                    },
                    {
                        text: 'Not sure / explore options',
                        message: "No problem – we'll figure it out together.\n\nTell us a little and we'll recommend the right next step.",
                        intent: 'Not sure / explore options',
                        lead_type: 'details'
                    }
                ],
                consultation: [
                    {key: 'name', text: "What's your **full name?**"},
                    {key: 'email', text: "Thanks {name}! Your **work email?**"},
                    {key: 'company', text: "Your **company** name?"},
                    {key: 'location', text: "**Where** are you based?\n(e.g. Dubai, UAE)"},
                    {key: 'industry', text: "**Industry?**\n\n- Retail & Consumer\n- Manufacturing & Logistics\n- Banking & Financial Services\n- Government & Public Sector\n- Healthcare & Life Science\n- Telco & Media\n- Other"},
                    {key: 'platforms', text: "**Core platform?**\n\n- SAP\n- Oracle\n- Microsoft\n- Salesforce\n- Blue Yonder\n- Workday\n- Other / Not sure"},
                    {key: 'capabilities', text: "**Biggest gap?**\n\n- Data & AI\n- Digital & DevOps\n- Cloud & Infrastructure\n- Cybersecurity\n- Integration & Middleware\n- Emerging Technologies"},
                    {key: 'service_type', text: "**What do you need?**\n\n- Talent in a Box\n- TS/EA as a Service\n- Managed IT CoE\n- Not sure"},
                    {key: 'pain', text: "In **one line** – what's the main challenge?"}
                ]
            };
            this.applyQuestions(defaultData);
            console.log('✅ Default questions loaded');
        },
        
        applyQuestions: function(data) {
            // Build questions array from loaded data
            this.questions = [
                {
                    id: 0,
                    keywords: ['hi', 'hello', 'start', 'menu'],
                    answer: data.welcome.text,
                    options: data.services.map((service, i) => ({
                        text: service.text,
                        nextId: 10 + i,
                        intent: service.intent,
                        leadType: service.lead_type
                    }))
                }
            ];
            
            // Add service response questions with consultation buttons
            data.services.forEach((service, i) => {
                this.questions.push({
                    id: 10 + i,
                    answer: service.message,
                    options: [
                        { text: '📋 Share my details', nextId: 300, leadType: service.lead_type },
                        { text: '← Back', nextId: 0 }
                    ]
                });
            });
            
            // Build consultation questions from loaded data
            this.consultationQuestions = data.consultation.map(q => ({
                key: q.key,
                text: q.text
            }));
        },

        // ── OLD HARDCODED QUESTIONS (kept as fallback) ─────────
        questions_backup: [
            {
                id: 0,
                keywords: ['hi', 'hello', 'start', 'menu'],
                answer: "Hi, we're **YALLO** 👋\n\nHow can we help?",
                options: [
                    { text: '🧑‍💻 Hire tech talent / squad',      nextId: 10, intent: 'Hire tech talent / build a squad' },
                    { text: '🔧 Stabilise a project',             nextId: 11, intent: 'Stabilise a project / programme' },
                    { text: '🏛 EA / IT strategy support',        nextId: 12, intent: 'TS/EA as a Service' },
                    { text: '🤔 Not sure – need guidance',        nextId: 13, intent: 'Not sure – need guidance' }
                ]
            },
            {
                id: 10,
                keywords: ['hire', 'talent', 'squad'],
                answer: "Great – **tech talent & squads**.\n\nVetted profiles across AI, Data, Cloud, SAP, Oracle, Salesforce & more – delivered in ~72 hrs.",
                options: [
                    { text: '📋 Share my details', nextId: 300, leadType: 'details' },
                    { text: '📞 Book a call',       nextId: 300, leadType: 'call' },
                    { text: '← Back',               nextId: 0 }
                ]
            },
            {
                id: 11,
                keywords: ['stabilise', 'project', 'programme'],
                answer: "Got it – **stabilise a project**.\n\nWe use architects & delivery leads to find and fix talent or role clarity gaps fast.",
                options: [
                    { text: '📋 Share my details', nextId: 300, leadType: 'details' },
                    { text: '📞 Book a call',       nextId: 300, leadType: 'call' },
                    { text: '← Back',               nextId: 0 }
                ]
            },
            {
                id: 12,
                keywords: ['ea', 'strategy', 'architecture'],
                answer: "Understood – **EA / IT strategy**.\n\nWe provide Chief Architect capacity to align roadmaps and talent – no big consulting lock-in.",
                options: [
                    { text: '📋 Share my details', nextId: 300, leadType: 'details' },
                    { text: '📞 Book a call',       nextId: 300, leadType: 'call' },
                    { text: '← Back',               nextId: 0 }
                ]
            },
            {
                id: 13,
                keywords: ['not sure', 'guidance'],
                answer: "No problem – we'll figure it out together.\n\nTell us a little and we'll recommend the right next step.",
                options: [
                    { text: '📋 Share my details', nextId: 300, leadType: 'details' },
                    { text: '📞 Book a call',       nextId: 300, leadType: 'call' },
                    { text: '← Back',               nextId: 0 }
                ]
            }
        ],

        // ── Consultation Questions ────────────────────────────
        // Lead saved after Q2 (name + email both collected)
        consultationQuestions: [
            {
                key: 'name',
                text: "What's your **full name?**"
            },
            {
                key: 'email',
                text: "Thanks {name}! Your **work email?**"
            },
            {
                key: 'company',
                text: "Your **company** name?"
            },
            {
                key: 'location',
                text: "**Where** are you based?\n(e.g. Dubai, UAE)"
            },
            {
                key: 'industry',
                text: "**Industry?**\n\n- Retail & Consumer\n- Manufacturing & Logistics\n- Banking & Financial Services\n- Government & Public Sector\n- Healthcare & Life Science\n- Telco & Media\n- Other"
            },
            {
                key: 'platforms',
                text: "**Core platform?**\n\n- SAP\n- Oracle\n- Microsoft\n- Salesforce\n- Blue Yonder\n- Workday\n- Other / Not sure"
            },
            {
                key: 'capabilities',
                text: "**Biggest gap?**\n\n- Data & AI\n- Digital & DevOps\n- Cloud & Infrastructure\n- Cybersecurity\n- Integration & Middleware\n- Emerging Technologies"
            },
            {
                key: 'service_type',
                text: "**What do you need?**\n\n- Talent in a Box\n- TS/EA as a Service\n- Managed IT CoE\n- Not sure"
            },
            {
                key: 'pain',
                text: "In **one line** – what's the main challenge?"
            }
        ],

        // ── Init ─────────────────────────────────────────────
        init: function() {
            const self = this;
            
            // Cache DOM elements immediately (before loading questions)
            this.cacheDom();
            
            // Load questions from WordPress, then initialize rest
            this.loadQuestions().always(function() {
                self.bindEvents();
                self.checkAutoOpen();

                // ── Startup diagnostics ──────────────────────────
                const checks = {
                    'Toggle button (#yallo-chat-toggle)':    self.$toggle.length,
                    'Chat window (#yallo-chatbot-window)':   self.$window.length,
                    'Messages container':                    self.$messagesContainer.length,
                    'Input field':                           self.$input.length,
                    'Send button':                           self.$sendBtn.length,
                    'yalloChatbot config':                   typeof yalloChatbot !== 'undefined' ? 1 : 0,
                    'Questions loaded':                      self.questions.length > 0 ? 1 : 0,
                };
                let allOk = true;
                Object.keys(checks).forEach(function(label) {
                    if (!checks[label]) {
                        console.error('❌ YALLO Chatbot: Missing — ' + label);
                        allOk = false;
                    }
                });
                if (allOk) {
                    console.log('✅ YALLO Chatbot: Initialised OK. Click the button to open.');
                }
            });
        },

        cacheDom: function() {
            this.$window            = $('#yallo-chatbot-window');
            this.$toggle            = $('#yallo-chat-toggle');
            this.$messagesContainer = $('#yallo-messages-container');
            this.$form              = $('#yallo-chat-form');
            this.$input             = $('#yallo-message-input');
            this.$sendBtn           = $('#yallo-send-btn');
            this.$chatIcon          = $('#yallo-chat-icon');
            this.$closeIcon         = $('#yallo-close-icon');
        },

        // ── Events ───────────────────────────────────────────
        bindEvents: function() {
            const self = this;

            this.$toggle.on('click', function() { self.toggleChat(); });
            $('#yallo-chat-close').on('click', function() { self.closeChat(); });

            this.$form.on('submit', function(e) {
                e.preventDefault();
                const msg = self.$input.val().trim();
                if (msg) {
                    self.$input.val('');
                    self.handleUserInput(msg);
                }
            });

            this.$sendBtn.on('click', function() {
                const msg = self.$input.val().trim();
                if (msg) {
                    self.$input.val('');
                    self.handleUserInput(msg);
                }
            });

            this.$input.on('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    const msg = self.$input.val().trim();
                    if (msg) {
                        self.$input.val('');
                        self.handleUserInput(msg);
                    }
                }
            });
        },

        // ── Auto open ────────────────────────────────────────
        checkAutoOpen: function() {
            if (!yalloChatbot.autoOpen) return;
            const self = this;
            $(window).on('scroll.yallo', function() {
                const scrollPct = ($(window).scrollTop() / ($(document).height() - $(window).height())) * 100;
                if (scrollPct >= yalloChatbot.scrollTrigger && !self.hasAutoOpened) {
                    self.hasAutoOpened = true;
                    $(window).off('scroll.yallo');
                    self.openChat();
                }
            });
        },

        // ── Chat open / close / toggle ────────────────────────
        toggleChat: function() {
            this.isOpen ? this.closeChat() : this.openChat();
        },

        openChat: function() {
            const self = this;
            this.isOpen = true;
            this.$window.addClass('yallo-open');
            this.$chatIcon.hide();
            this.$closeIcon.show();
            
            if (this.messages.length === 0) {
                // Safety check: if questions not loaded yet, wait
                if (this.questions.length === 0) {
                    console.log('⏳ Waiting for questions to load...');
                    setTimeout(function() {
                        self.showTypingIndicator();
                        setTimeout(() => {
                            self.hideTypingIndicator();
                            self.askQuestionById(0);
                        }, 600);
                    }, 500);
                } else {
                    this.showTypingIndicator();
                    setTimeout(() => {
                        this.hideTypingIndicator();
                        this.askQuestionById(0);
                    }, 600);
                }
            }
        },

        closeChat: function() {
            this.isOpen = false;
            this.$window.removeClass('yallo-open');
            this.$chatIcon.show();
            this.$closeIcon.hide();
        },

        // ── Message routing ───────────────────────────────────
        handleUserInput: function(userMessage) {
            if (this.isInputDisabled || this.isChatFinished) return;
            this.addMessage(userMessage, 'user');
            this.showTypingIndicator();

            setTimeout(() => {
                this.hideTypingIndicator();
                if (this.isConsultationActive) {
                    this.handleConsultationInput(userMessage);
                } else {
                    this.processQuestion(userMessage);
                }
            }, 500);
        },

        processQuestion: function(input) {
            const lower = input.toLowerCase();
            for (const q of this.questions) {
                if (q.keywords.some(k => lower.includes(k))) {
                    this.addMessage(q.answer, 'bot', q.options);
                    return;
                }
            }
            this.addMessage("Please choose one of the options below 👇", 'bot',
                this.questions[0].options);
        },

        askQuestionById: function(id) {
            const q = this.questions.find(q => q.id === id);
            if (q) this.addMessage(q.answer, 'bot', q.options);
        },

        // ── Option click ──────────────────────────────────────
        handleOptionClick: function(option) {
            this.addMessage(option.text, 'user');
            if (option.intent) this.consultationData.initial_intent = option.intent;
            if (option.leadType) this.consultationData.lead_type = option.leadType;

            this.showTypingIndicator();
            setTimeout(() => {
                this.hideTypingIndicator();
                if (option.nextId === 300) {
                    this.startConsultation();
                } else {
                    this.askQuestionById(option.nextId);
                }
            }, 500);
        },

        // ── Consultation ──────────────────────────────────────
        startConsultation: function() {
            this.isConsultationActive = true;
            this.isInputDisabled      = false;
            this.consultationStep     = 0;
            this.updateInputState();
            this.askConsultationQuestion();
        },

        askConsultationQuestion: function() {
            const q = this.consultationQuestions[this.consultationStep];
            if (!q) return;
            this.isInputDisabled = false;
            this.updateInputState();
            // Replace {name} placeholder with collected name
            const text = q.text.replace('{name}', this.consultationData.name || '');
            this.addMessage(text, 'bot');
        },

        handleConsultationInput: function(answer) {
            const q = this.consultationQuestions[this.consultationStep];
            if (!q) return;

            // Email validation
            if (q.key === 'email' && !this.isValidEmail(answer)) {
                this.addMessage("That doesn't look like a valid email. Please try again.", 'bot');
                return;
            }

            this.consultationData[q.key] = answer;
            this.consultationStep++;

            // ✅ Save lead after BOTH name + email collected (step 2)
            if (this.consultationStep === 2 && !this.leadSavedEarly) {
                this.saveEarlyLead();
            }

            if (this.consultationStep < this.consultationQuestions.length) {
                this.showTypingIndicator();
                setTimeout(() => {
                    this.hideTypingIndicator();
                    this.askConsultationQuestion();
                }, 400);
            } else {
                this.finalizeConsultation();
            }
        },

        isValidEmail: function(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        // ── Final message ─────────────────────────────────────
        finalizeConsultation: function() {
            this.isChatFinished  = true;
            this.isInputDisabled = true;
            this.updateInputState();

            const name  = this.consultationData.name  || 'there';
            const email = this.consultationData.email || 'your email';

            this.addMessage(
                `Thanks, **${name}**! 🙌\n\nWe'll be in touch at **${email}** within 24 hrs.`,
                'bot'
            );

            if (!this.leadSavedEarly) {
                this.submitLead();
            } else {
                this.updateLead();
            }
        },

        // ── AJAX: save lead after name + email ───────────────
        saveEarlyLead: function() {
            const self = this;
            this.leadSavedEarly = true;

            $.post(yalloChatbot.ajaxUrl, {
                action:         'yallo_submit_lead',
                nonce:          yalloChatbot.nonce,
                name:           this.consultationData.name  || '',
                email:          this.consultationData.email || '',
                company:        '',
                location:       '',
                industry:       '',
                platforms:      '',
                capabilities:   '',
                service_type:   '',
                pain:           '',
                initial_intent: this.consultationData.initial_intent || '',
                lead_type:      this.consultationData.lead_type      || '',
                page_url:       window.location.href,
                early_save:     true
            })
            .done(function(r) {
                if (r.success && r.data && r.data.lead_id) {
                    self.savedLeadId = r.data.lead_id;
                }
            });
        },

        // ── AJAX: update lead with remaining answers ───────────
        updateLead: function() {
            $.post(yalloChatbot.ajaxUrl, {
                action:       'yallo_update_lead',
                nonce:        yalloChatbot.nonce,
                email:        this.consultationData.email        || '',
                company:      this.consultationData.company      || '',
                location:     this.consultationData.location     || '',
                industry:     this.consultationData.industry     || '',
                platforms:    this.consultationData.platforms    || '',
                capabilities: this.consultationData.capabilities || '',
                service_type: this.consultationData.service_type || '',
                pain:         this.consultationData.pain         || ''
            });
        },

        // ── AJAX: full submit (fallback if early save missed) ──
        submitLead: function() {
            $.post(yalloChatbot.ajaxUrl, {
                action:         'yallo_submit_lead',
                nonce:          yalloChatbot.nonce,
                name:           this.consultationData.name           || '',
                email:          this.consultationData.email          || '',
                company:        this.consultationData.company        || '',
                location:       this.consultationData.location       || '',
                industry:       this.consultationData.industry       || '',
                platforms:      this.consultationData.platforms      || '',
                capabilities:   this.consultationData.capabilities   || '',
                service_type:   this.consultationData.service_type   || '',
                pain:           this.consultationData.pain           || '',
                initial_intent: this.consultationData.initial_intent || '',
                lead_type:      this.consultationData.lead_type      || '',
                page_url:       window.location.href
            });
        },

        // ── Render message bubble ─────────────────────────────
        addMessage: function(text, sender, options) {
            options = options || [];
            this.messages.push({ text, sender, options });

            const $msg    = $('<div>').addClass('yallo-message').addClass(sender);
            const $bubble = $('<div>').addClass('yallo-message-bubble');
            const html    = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                                 .replace(/\n/g, '<br>');
            $bubble.html(html);
            $msg.append($bubble);

            if (sender === 'bot' && options.length > 0) {
                const $opts = $('<div>').addClass('yallo-message-options');
                options.forEach(opt => {
                    $('<button>').addClass('yallo-option-btn').text(opt.text)
                        .on('click', () => this.handleOptionClick(opt))
                        .appendTo($opts);
                });
                $msg.append($opts);
            }

            this.$messagesContainer.append($msg);
            this.scrollToBottom();
        },

        // ── Typing indicator ──────────────────────────────────
        showTypingIndicator: function() {
            this.isBotTyping = true;
            const $t = $('<div>').addClass('yallo-message bot').attr('id', 'yallo-typing');
            const $i = $('<div>').addClass('yallo-typing-indicator');
            for (let i = 0; i < 3; i++) $i.append($('<span>').addClass('yallo-typing-dot'));
            $t.append($i);
            this.$messagesContainer.append($t);
            this.scrollToBottom();
        },

        hideTypingIndicator: function() {
            this.isBotTyping = false;
            $('#yallo-typing').remove();
        },

        // ── Input state ───────────────────────────────────────
        updateInputState: function() {
            if (this.isChatFinished) {
                this.$input.prop('disabled', true).attr('placeholder', 'Chat ended – thank you!');
                this.$sendBtn.prop('disabled', true);
            } else if (this.isInputDisabled) {
                this.$input.prop('disabled', true).attr('placeholder', 'Please choose an option above…');
                this.$sendBtn.prop('disabled', true);
            } else if (this.isConsultationActive) {
                this.$input.prop('disabled', false).attr('placeholder', 'Type your answer…');
                this.$sendBtn.prop('disabled', false);
            } else {
                this.$input.prop('disabled', false).attr('placeholder', 'Type a message…');
                this.$sendBtn.prop('disabled', false);
            }
        },

        scrollToBottom: function() {
            setTimeout(() => {
                this.$messagesContainer.scrollTop(this.$messagesContainer[0].scrollHeight);
            }, 100);
        }
    };

    $(document).ready(function() {
        YALLO_CHATBOT.init();
    });

})(jQuery);