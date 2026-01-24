# PHP Development Tools

This folder contains PHP-based development utilities for server diagnostics and email testing.

## Tools

### 📧 Email Diagnostic Tool
**File:** `email_diagnostic.php`

Comprehensive email functionality testing script.

**Quick Start:**
1. Upload `email_diagnostic.php` to your web server
2. Edit line 10 to set your test email address:
   ```php
   $TEST_EMAIL = 'your-email@example.com';
   ```
3. Access via browser: `https://yoursite.com/email_diagnostic.php`
4. Check your inbox for test emails

**What it tests:**
- PHP mail() function availability
- SMTP configuration
- DNS and MX records
- Port connectivity (25, 587, 465, 2525)
- Email header validation
- Sends actual test emails

---

### 🔍 Server Diagnostics Tool
**File:** `server_diagnostics.php`

All-in-one server capability and configuration testing tool.

**Quick Start:**
1. Upload `server_diagnostics.php` to your web server
2. Access via browser: `https://yoursite.com/server_diagnostics.php`
3. Review the comprehensive diagnostic report

**What it checks:**
- PHP version and configuration
- Server software and OS
- PHP extensions and capabilities
- Database connectivity
- File system permissions
- Network connectivity
- Security settings
- Email functionality
- And much more!

## Requirements

- PHP 5.6 or higher (PHP 7.4+ recommended)
- Web server (Apache, Nginx, etc.)
- Write permissions for file system tests

## Support

For issues or questions, please open an issue on the main repository.
