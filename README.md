# YALLO Talent Chatbot WordPress Plugin

A professional, AI-powered chatbot for YALLO talent acquisition and consultation services. Features a sleek dark theme with the YALLO brand color (#BFA25E).

## Features

- ✅ **Intelligent Conversation Flow** - Multi-level question routing with conditional logic
- ✅ **Lead Capture System** - Complete consultation workflow with 9-step data collection
- ✅ **Auto-Open on Scroll** - Configurable scroll trigger for automatic chatbot opening
- ✅ **Email Notifications** - Instant notifications when new leads are submitted
- ✅ **Database Storage** - All leads stored in WordPress database with full details
- ✅ **Admin Dashboard** - View, manage, and export leads from WordPress admin
- ✅ **Mobile Responsive** - Fully optimized for all device sizes
- ✅ **Dark Theme** - Modern dark UI with YALLO brand colors
- ✅ **Accessibility** - WCAG 2.1 compliant with keyboard navigation and screen reader support
- ✅ **AJAX-Powered** - Smooth interactions without page reloads
- ✅ **Typing Indicators** - Visual feedback during bot responses
- ✅ **No Dependencies** - Only requires jQuery (included with WordPress)

## Installation

### Method 1: Upload via WordPress Admin

1. Download the plugin folder as a ZIP file
2. Go to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin" and select the ZIP file
4. Click "Install Now" and then "Activate"

### Method 2: Manual Installation

1. Upload the `yallo-talent-chatbot` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to YALLO Chatbot > Settings to configure

## File Structure

```
yallo-talent-chatbot/
├── yallo-talent-chatbot.php    # Main plugin file
├── README.md                     # This file
├── assets/
│   ├── css/
│   │   └── chatbot.css          # Chatbot styles (dark theme)
│   └── js/
│       └── chatbot.js           # Chatbot functionality
├── admin/
│   ├── settings.php             # Settings page template
│   └── leads.php                # Leads management page
└── templates/
    └── chatbot.php              # Chatbot HTML template
```

## Configuration

### Settings Page

Access: **WordPress Admin > YALLO Chatbot > Settings**

Available options:
- **Enable Chatbot** - Turn the chatbot on/off
- **Auto Open** - Enable/disable automatic opening on scroll
- **Scroll Trigger** - Percentage of page scroll to trigger auto-open (0-100%)
- **Notification Email** - Email address(es) for lead notifications

