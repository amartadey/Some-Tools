<?php
/**
 * Multi-Domain Email Diagnostics Script v3.0
 * Features:
 * - Multi-domain/client configuration support
 * - Automatic sender fallback mechanism
 * - Detailed email sending logs showing actual sender used
 * - Multiple sending methods (mail(), SMTP)
 * - Enhanced deliverability testing
 */

// ============================================================================
// SECURITY - Password Protection
// ============================================================================
session_start();
$DIAGNOSTIC_PASSWORD = 'email_diag_2024'; // CHANGE THIS PASSWORD!

if (!isset($_SESSION['authenticated'])) {
    if (isset($_POST['password']) && $_POST['password'] === $DIAGNOSTIC_PASSWORD) {
        $_SESSION['authenticated'] = true;
        $_SESSION['login_time'] = time();
    } else {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Authentication Required</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; 
                       background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                       min-height: 100vh; display: flex; align-items: center; justify-content: center; }
                .login-box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); 
                            max-width: 400px; width: 90%; }
                h2 { color: #333; margin-bottom: 20px; text-align: center; }
                input { width: 100%; padding: 12px; margin: 10px 0; border: 2px solid #e0e0e0; 
                       border-radius: 6px; font-size: 14px; }
                input:focus { outline: none; border-color: #667eea; }
                button { width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                        color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; 
                        cursor: pointer; margin-top: 10px; }
                button:hover { opacity: 0.9; }
                .error { color: #ef4444; font-size: 13px; margin-top: 10px; text-align: center; }
            </style>
        </head>
        <body>
            <div class="login-box">
                <h2>🔒 Email Diagnostics</h2>
                <p style="color: #666; text-align: center; margin-bottom: 20px; font-size: 14px;">
                    Authentication Required
                </p>
                <form method="post">
                    <input type="password" name="password" placeholder="Enter diagnostic password" required autofocus>
                    <button type="submit">Access Diagnostics</button>
                    <?php if (isset($_POST['password'])): ?>
                        <p class="error">❌ Incorrect password</p>
                    <?php endif; ?>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Session timeout (30 minutes)
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 1800)) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Self-delete functionality
if (isset($_GET['delete_now']) && $_GET['delete_now'] === 'confirm') {
    $filename = basename(__FILE__);
    session_destroy();
    if (@unlink(__FILE__)) {
        die("<html><body style='font-family: Arial; text-align: center; padding: 100px;'>
             <h1 style='color: #10b981;'>✓ File '{$filename}' deleted successfully!</h1>
             <p style='color: #666; margin-top: 20px;'>The diagnostic tool has been removed from the server.</p>
             </body></html>");
    } else {
        die("<html><body style='font-family: Arial; text-align: center; padding: 100px;'>
             <h1 style='color: #ef4444;'>✗ Failed to delete file</h1>
             <p style='color: #666; margin-top: 20px;'>Please delete manually: {$filename}</p>
             </body></html>");
    }
}

// Logout functionality
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ============================================================================
// MULTI-DOMAIN CONFIGURATION
// ============================================================================

/**
 * CONFIGURATION - Customize for your website
 * 
 * Option 1: Auto-detect domain from server (recommended for single-site use)
 * Option 2: Manually configure multiple domains below
 */

// Auto-detect current server domain
$detectedDomain = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
// Remove www. prefix if present
$detectedDomain = preg_replace('/^www\./', '', $detectedDomain);

// Default test email - CHANGE THIS to your email address
$DEFAULT_TEST_EMAIL = 'webgraphicshub@gmail.com'; // ⚠️ CHANGE THIS!

/**
 * Domain configurations
 * The script will auto-detect the current domain and use its configuration
 * Add your domains here as needed
 */
$DOMAIN_CONFIGS = [
    // Auto-detected domain configuration (will be populated below)
    $detectedDomain => [
        'domain' => $detectedDomain,
        'test_email' => $DEFAULT_TEST_EMAIL,
        'from_emails' => [
            "noreply@{$detectedDomain}",
            "info@{$detectedDomain}",
            "admin@{$detectedDomain}",
            "contact@{$detectedDomain}"
        ],
        'smtp' => null // Use server default mail()
    ],
    
    // Add more domains here for multi-site testing
    /*
    'example.com' => [
        'domain' => 'example.com',
        'test_email' => 'admin@example.com',
        'from_emails' => [
            'noreply@example.com',
            'info@example.com'
        ],
        'smtp' => [
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'user@example.com',
            'password' => 'your-password',
            'encryption' => 'tls' // or 'ssl'
        ]
    ],
    */
];

// Select active domain (can be changed via URL parameter, defaults to detected domain)
$activeDomain = $_GET['domain'] ?? $detectedDomain;
if (!isset($DOMAIN_CONFIGS[$activeDomain])) {
    $activeDomain = $detectedDomain;
}
$CONFIG = $DOMAIN_CONFIGS[$activeDomain];

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

// Email sending log
$emailLog = [];

/**
 * Try to send email with fallback mechanism
 */
function sendEmailWithFallback($to, $subject, $message, $isHTML = false) {
    global $CONFIG, $emailLog;
    
    $logEntry = [
        'to' => $to,
        'subject' => $subject,
        'attempts' => [],
        'success' => false,
        'final_sender' => null
    ];
    
    // Get server default email
    $serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $serverDefaultEmail = ini_get('sendmail_from') ?: "noreply@{$serverName}";
    
    // Build list of emails to try (user-defined + server default)
    $emailsToTry = $CONFIG['from_emails'];
    if (!in_array($serverDefaultEmail, $emailsToTry)) {
        $emailsToTry[] = $serverDefaultEmail;
    }
    
    // Try each sender email
    foreach ($emailsToTry as $fromEmail) {
        $headers = "From: {$fromEmail}\r\n";
        $headers .= "Reply-To: {$fromEmail}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        
        if ($isHTML) {
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        }
        
        $attempt = [
            'from' => $fromEmail,
            'method' => 'PHP mail()',
            'timestamp' => date('Y-m-d H:i:s'),
            'success' => false,
            'error' => null
        ];
        
        // Try sending
        $sent = @mail($to, $subject, $message, $headers);
        
        if ($sent) {
            $attempt['success'] = true;
            $logEntry['success'] = true;
            $logEntry['final_sender'] = $fromEmail;
            $logEntry['attempts'][] = $attempt;
            break; // Success! Stop trying
        } else {
            $attempt['error'] = error_get_last()['message'] ?? 'Unknown error';
            $logEntry['attempts'][] = $attempt;
        }
    }
    
    $emailLog[] = $logEntry;
    return $logEntry['success'];
}

/**
 * Send email ONLY from server default (no fallback)
 * Used for manual testing via UI button
 */
function sendEmailFromServerDefault($to, $subject, $message, $isHTML = false) {
    global $emailLog;
    
    // Get server default email
    $serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $serverDefaultEmail = ini_get('sendmail_from') ?: "noreply@{$serverName}";
    
    $logEntry = [
        'to' => $to,
        'subject' => $subject,
        'attempts' => [],
        'success' => false,
        'final_sender' => null,
        'manual_test' => true
    ];
    
    $headers = "From: {$serverDefaultEmail}\r\n";
    $headers .= "Reply-To: {$serverDefaultEmail}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    if ($isHTML) {
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    }
    
    $attempt = [
        'from' => $serverDefaultEmail,
        'method' => 'PHP mail() - Server Default Only',
        'timestamp' => date('Y-m-d H:i:s'),
        'success' => false,
        'error' => null
    ];
    
    // Try sending
    $sent = @mail($to, $subject, $message, $headers);
    
    if ($sent) {
        $attempt['success'] = true;
        $logEntry['success'] = true;
        $logEntry['final_sender'] = $serverDefaultEmail;
    } else {
        $attempt['error'] = error_get_last()['message'] ?? 'Unknown error';
    }
    
    $logEntry['attempts'][] = $attempt;
    $emailLog[] = $logEntry;
    
    return $logEntry['success'];
}

// Check if domain is blacklisted
function checkBlacklist($ip) {
    $blacklists = [
        'zen.spamhaus.org' => 'Spamhaus',
        'bl.spamcop.net' => 'SpamCop',
        'b.barracudacentral.org' => 'Barracuda',
        'dnsbl.sorbs.net' => 'SORBS'
    ];
    
    $results = [];
    $reverse_ip = implode('.', array_reverse(explode('.', $ip)));
    
    foreach ($blacklists as $bl => $name) {
        $lookup = $reverse_ip . '.' . $bl;
        $result = @gethostbyname($lookup);
        $listed = ($result !== $lookup && $result !== $reverse_ip . '.' . $bl);
        $results[$name] = $listed;
    }
    
    return $results;
}

// Check SPF record
function checkSPF($domain) {
    $records = @dns_get_record($domain, DNS_TXT);
    if (!$records) return null;
    
    foreach ($records as $record) {
        if (isset($record['txt']) && strpos($record['txt'], 'v=spf1') === 0) {
            return $record['txt'];
        }
    }
    return null;
}

// Check DMARC record
function checkDMARC($domain) {
    $dmarc_domain = '_dmarc.' . $domain;
    $records = @dns_get_record($dmarc_domain, DNS_TXT);
    if (!$records) return null;
    
    foreach ($records as $record) {
        if (isset($record['txt']) && strpos($record['txt'], 'v=DMARC1') === 0) {
            return $record['txt'];
        }
    }
    return null;
}

// Calculate deliverability score
function calculateDeliverabilityScore($checks) {
    $total = 0;
    $passed = 0;
    
    foreach ($checks as $check) {
        $total++;
        if ($check) $passed++;
    }
    
    return $total > 0 ? round(($passed / $total) * 100) : 0;
}

/**
 * Detect email provider from MX records
 * Returns array with provider info and recommendations
 */
function detectEmailProvider($mxRecords) {
    if (empty($mxRecords)) {
        return [
            'provider' => 'Unknown',
            'is_third_party' => false,
            'requires_smtp' => false,
            'warning' => null
        ];
    }
    
    $mxString = strtolower(implode(' ', $mxRecords));
    
    // Check for common providers
    $providers = [
        'icloud' => [
            'name' => 'Apple iCloud Mail',
            'patterns' => ['icloud.com', 'apple.com'],
            'smtp_host' => 'smtp.mail.me.com',
            'smtp_port' => 587,
            'requires_smtp' => true
        ],
        'gmail' => [
            'name' => 'Google Gmail/Workspace',
            'patterns' => ['google.com', 'googlemail.com', 'gmail.com'],
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 587,
            'requires_smtp' => true
        ],
        'office365' => [
            'name' => 'Microsoft Office 365',
            'patterns' => ['outlook.com', 'office365.com', 'microsoft.com'],
            'smtp_host' => 'smtp.office365.com',
            'smtp_port' => 587,
            'requires_smtp' => true
        ],
        'zoho' => [
            'name' => 'Zoho Mail',
            'patterns' => ['zoho.com', 'zohomail.com'],
            'smtp_host' => 'smtp.zoho.com',
            'smtp_port' => 587,
            'requires_smtp' => true
        ],
        'protonmail' => [
            'name' => 'ProtonMail',
            'patterns' => ['protonmail.ch', 'proton.me'],
            'smtp_host' => 'smtp.protonmail.ch',
            'smtp_port' => 587,
            'requires_smtp' => true
        ],
        'mailgun' => [
            'name' => 'Mailgun',
            'patterns' => ['mailgun.org'],
            'smtp_host' => 'smtp.mailgun.org',
            'smtp_port' => 587,
            'requires_smtp' => true
        ],
        'sendgrid' => [
            'name' => 'SendGrid',
            'patterns' => ['sendgrid.net'],
            'smtp_host' => 'smtp.sendgrid.net',
            'smtp_port' => 587,
            'requires_smtp' => true
        ]
    ];
    
    foreach ($providers as $key => $provider) {
        foreach ($provider['patterns'] as $pattern) {
            if (strpos($mxString, $pattern) !== false) {
                return [
                    'provider' => $provider['name'],
                    'provider_key' => $key,
                    'is_third_party' => true,
                    'requires_smtp' => $provider['requires_smtp'],
                    'smtp_host' => $provider['smtp_host'],
                    'smtp_port' => $provider['smtp_port'],
                    'mx_records' => $mxRecords
                ];
            }
        }
    }
    
    // Unknown provider but has MX records
    return [
        'provider' => 'Custom/Self-hosted',
        'is_third_party' => false,
        'requires_smtp' => false,
        'mx_records' => $mxRecords
    ];
}

// ============================================================================
// HTML OUTPUT FUNCTIONS
// ============================================================================
function outputHeader() {
    global $DOMAIN_CONFIGS, $activeDomain;
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Email Diagnostics Report - <?php echo htmlspecialchars($activeDomain); ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
                background: #f5f7fa;
                padding: 15px;
                line-height: 1.4;
                font-size: 14px;
            }
            .container { 
                max-width: 1200px;
                margin: 0 auto;
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 20px;
                border-radius: 6px;
                margin-bottom: 20px;
            }
            .header h1 { font-size: 22px; font-weight: 700; margin-bottom: 8px; }
            .header .domain-selector {
                margin-top: 12px;
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
            }
            .header .domain-selector a {
                background: rgba(255,255,255,0.2);
                color: white;
                padding: 6px 12px;
                border-radius: 4px;
                text-decoration: none;
                font-size: 12px;
                font-weight: 600;
            }
            .header .domain-selector a.active {
                background: rgba(255,255,255,0.4);
            }
            .header .domain-selector a:hover { background: rgba(255,255,255,0.3); }
            .header .actions { 
                display: flex; 
                gap: 10px; 
                margin-top: 12px;
                flex-wrap: wrap;
            }
            .header .actions a {
                background: rgba(255,255,255,0.2);
                color: white;
                padding: 6px 12px;
                border-radius: 4px;
                text-decoration: none;
                font-size: 12px;
                font-weight: 600;
            }
            .header .actions a:hover { background: rgba(255,255,255,0.3); }
            .config-info {
                background: #e0e7ff;
                border-left: 4px solid #667eea;
                padding: 12px;
                margin-bottom: 20px;
                border-radius: 4px;
            }
            .config-info h3 { font-size: 14px; margin-bottom: 8px; color: #4338ca; }
            .config-info ul { margin-left: 20px; font-size: 13px; color: #4338ca; }
            .deliverability-score {
                background: white;
                padding: 15px;
                border-radius: 6px;
                margin-bottom: 20px;
                text-align: center;
                border: 2px solid #e0e0e0;
            }
            .score-circle {
                width: 100px;
                height: 100px;
                border-radius: 50%;
                margin: 10px auto;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 32px;
                font-weight: bold;
                color: white;
            }
            .score-excellent { background: linear-gradient(135deg, #10b981, #059669); }
            .score-good { background: linear-gradient(135deg, #3b82f6, #2563eb); }
            .score-fair { background: linear-gradient(135deg, #f59e0b, #d97706); }
            .score-poor { background: linear-gradient(135deg, #ef4444, #dc2626); }
            .section { 
                margin-bottom: 15px;
                border: 1px solid #e0e0e0;
                border-radius: 6px;
                overflow: hidden;
            }
            .section-header { 
                background: #f8f9fa;
                padding: 10px 15px;
                font-size: 15px;
                font-weight: 600;
                cursor: pointer;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 1px solid #e0e0e0;
            }
            .section-header:hover { background: #e9ecef; }
            .section-content { padding: 15px; background: #fafbfc; display: none; }
            .section-content.active { display: block; }
            .test-item { 
                background: white;
                padding: 10px;
                margin: 8px 0;
                border-radius: 4px;
                border-left: 3px solid #e0e0e0;
            }
            .test-name { 
                font-weight: 600;
                color: #2c3e50;
                margin-bottom: 4px;
                font-size: 13px;
            }
            .status { 
                display: inline-block;
                padding: 2px 8px;
                border-radius: 10px;
                font-size: 11px;
                font-weight: 600;
                margin-left: 8px;
            }
            .status.pass { background: #d4edda; color: #155724; }
            .status.fail { background: #f8d7da; color: #721c24; }
            .status.warning { background: #fff3cd; color: #856404; }
            .status.info { background: #d1ecf1; color: #0c5460; }
            .details { 
                color: #6c757d;
                font-size: 12px;
                margin-top: 4px;
                padding: 8px;
                background: #f8f9fa;
                border-radius: 3px;
            }
            .code { 
                font-family: 'Courier New', monospace;
                background: #2c3e50;
                color: #ecf0f1;
                padding: 10px;
                border-radius: 4px;
                overflow-x: auto;
                font-size: 12px;
                margin: 8px 0;
            }
            .email-log {
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                padding: 12px;
                margin: 10px 0;
            }
            .email-log-entry {
                background: white;
                border-left: 4px solid #6c757d;
                padding: 10px;
                margin: 8px 0;
                border-radius: 4px;
            }
            .email-log-entry.success { border-left-color: #28a745; }
            .email-log-entry.failed { border-left-color: #dc3545; }
            .email-log-entry h4 { font-size: 13px; margin-bottom: 6px; }
            .email-log-entry .attempt {
                background: #f8f9fa;
                padding: 6px 8px;
                margin: 4px 0;
                border-radius: 3px;
                font-size: 12px;
            }
            .email-log-entry .attempt.success { background: #d4edda; }
            .email-log-entry .attempt.failed { background: #f8d7da; }
            .summary { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 15px;
                border-radius: 6px;
                margin-bottom: 20px;
            }
            .summary h2 { color: white; margin-bottom: 10px; font-size: 18px; }
            .summary-grid { 
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                gap: 10px;
                margin-top: 10px;
            }
            .summary-item { 
                background: rgba(255,255,255,0.2);
                padding: 10px;
                border-radius: 4px;
                text-align: center;
            }
            .summary-item .number { font-size: 24px; font-weight: bold; margin-bottom: 4px; }
            .summary-item .label { font-size: 11px; opacity: 0.9; }
            table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 13px; }
            th, td { padding: 8px; text-align: left; border-bottom: 1px solid #e0e0e0; }
            th { background: #f8f9fa; font-weight: 600; font-size: 12px; }
            tr:hover { background: #f8f9fa; }
            .toggle-icon { transition: transform 0.3s; font-size: 12px; }
            .toggle-icon.rotated { transform: rotate(180deg); }
            .alert {
                padding: 10px 12px;
                border-radius: 4px;
                margin: 10px 0;
                border-left: 3px solid;
                font-size: 13px;
            }
            .alert-info { background: #dbeafe; border-color: #3b82f6; color: #1e40af; }
            .alert-warning { background: #fef3c7; border-color: #f59e0b; color: #92400e; }
            .alert-danger { background: #fee2e2; border-color: #ef4444; color: #991b1b; }
            .alert-success { background: #d1fae5; border-color: #10b981; color: #065f46; }
            .copy-btn {
                background: #3b82f6;
                color: white;
                border: none;
                padding: 4px 8px;
                border-radius: 3px;
                cursor: pointer;
                font-size: 11px;
                margin-left: 8px;
            }
            .copy-btn:hover { background: #2563eb; }
            .test-server-btn {
                background: linear-gradient(135deg, #f59e0b, #d97706);
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 6px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
                margin: 10px 0;
                display: inline-block;
                text-decoration: none;
            }
            .test-server-btn:hover { opacity: 0.9; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>📧 Email Diagnostics Report</h1>
                <div style="font-size: 12px; opacity: 0.9; margin-top: 4px;">
                    Generated: <?php echo date('Y-m-d H:i:s T'); ?>
                </div>
                
                <?php if (count($DOMAIN_CONFIGS) > 1): ?>
                <div class="domain-selector">
                    <strong style="font-size: 12px; align-self: center;">Select Domain:</strong>
                    <?php foreach ($DOMAIN_CONFIGS as $domain => $config): ?>
                        <a href="?domain=<?php echo urlencode($domain); ?>" 
                           class="<?php echo $domain === $activeDomain ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($domain); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="actions">
                    <a href="?domain=<?php echo urlencode($activeDomain); ?>&refresh=1" title="Refresh Tests">🔄 Refresh</a>
                    <a href="?logout=1" title="Logout">🚪 Logout</a>
                    <a href="javascript:void(0)" onclick="if(confirm('Delete this diagnostic file?')) location.href='?delete_now=confirm'" 
                       title="Delete this file" style="background: rgba(239,68,68,0.3);">🗑️ Delete</a>
                </div>
            </div>
            
            <div class="config-info">
                <h3>📋 Current Configuration: <?php echo htmlspecialchars($activeDomain); ?></h3>
                <ul>
                    <li><strong>Test Email:</strong> <?php echo htmlspecialchars($CONFIG['test_email']); ?></li>
                    <li><strong>Sender Emails (fallback order):</strong> <?php echo implode(', ', array_map('htmlspecialchars', $CONFIG['from_emails'])); ?></li>
                    <li><strong>SMTP:</strong> <?php echo $CONFIG['smtp'] ? 'Configured' : 'Using server default mail()'; ?></li>
                </ul>
            </div>
    <?php
}

function outputFooter() {
    ?>
            <script>
                // Toggle sections
                document.querySelectorAll('.section-header').forEach(header => {
                    header.addEventListener('click', function() {
                        const content = this.nextElementSibling;
                        const icon = this.querySelector('.toggle-icon');
                        content.classList.toggle('active');
                        icon.classList.toggle('rotated');
                    });
                });
                
                // Auto-expand first section
                document.querySelector('.section-content')?.classList.add('active');
                document.querySelector('.toggle-icon')?.classList.add('rotated');
                
                // Copy to clipboard
                function copyToClipboard(text) {
                    navigator.clipboard.writeText(text).then(() => {
                        alert('✓ Copied to clipboard!');
                    }).catch(() => {
                        alert('❌ Failed to copy');
                    });
                }
            </script>
        </div>
    </body>
    </html>
    <?php
}

function testResult($name, $status, $details = '') {
    $statusClass = $status ? 'pass' : 'fail';
    $statusText = $status ? '✓ PASS' : '✗ FAIL';
    
    echo "<div class='test-item'>";
    echo "<div class='test-name'>{$name} <span class='status {$statusClass}'>{$statusText}</span>";
    echo "<button class='copy-btn' onclick='copyToClipboard(\"" . addslashes($name . ': ' . $details) . "\")'>📋</button>";
    echo "</div>";
    if ($details) {
        echo "<div class='details'>{$details}</div>";
    }
    echo "</div>";
}

function sectionHeader($title) {
    echo "<div class='section'><div class='section-header'><span>{$title}</span><span class='toggle-icon'>▼</span></div><div class='section-content'>";
}

function sectionFooter() {
    echo "</div></div>";
}

// ============================================================================
// DIAGNOSTIC TESTS
// ============================================================================

$results = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0
];

$deliverabilityChecks = [];

function recordResult($passed, $weight = 1) {
    global $results, $deliverabilityChecks;
    $results['total']++;
    if ($passed) {
        $results['passed']++;
    } else {
        $results['failed']++;
    }
    $deliverabilityChecks[] = $passed;
}

outputHeader();

// ============================================================================
// DELIVERABILITY SCORE (will be shown at top)
// ============================================================================
$emailDomain = $CONFIG['domain'];
$serverIP = $_SERVER['SERVER_ADDR'] ?? gethostbyname($_SERVER['SERVER_NAME'] ?? 'localhost');

// Pre-calculate some checks for score
$spfRecord = checkSPF($emailDomain);
$dmarcRecord = checkDMARC($emailDomain);
$blacklistResults = checkBlacklist($serverIP);
$mailFunctionExists = function_exists('mail');
$reverseDNS = @gethostbyaddr($serverIP);

// ============================================================================
// 1. EMAIL AUTHENTICATION (SPF/DKIM/DMARC)
// ============================================================================
sectionHeader("🔐 1. Email Authentication (SPF/DKIM/DMARC)");

// SPF Check
$spfExists = !empty($spfRecord);
testResult(
    "SPF Record for {$emailDomain}",
    $spfExists,
    $spfExists ? "Found: {$spfRecord}" : "No SPF record found. This may cause deliverability issues."
);
recordResult($spfExists);

// DMARC Check
$dmarcExists = !empty($dmarcRecord);
testResult(
    "DMARC Record for {$emailDomain}",
    $dmarcExists,
    $dmarcExists ? "Found: {$dmarcRecord}" : "No DMARC record found. Recommended for email security."
);
recordResult($dmarcExists);

// DKIM Check (basic - just check if selector exists)
echo "<div class='alert alert-info'>";
echo "<strong>💡 DKIM Check:</strong> DKIM requires specific selector configuration. ";
echo "Common selectors: default, google, mail. Check your DNS for TXT records like 'default._domainkey.{$emailDomain}'";
echo "</div>";

sectionFooter();

// ============================================================================
// 2. BLACKLIST CHECK
// ============================================================================
sectionHeader("🚫 2. Blacklist Check");

echo "<div class='alert alert-info'><strong>Server IP:</strong> {$serverIP}</div>";

$blacklisted = false;
foreach ($blacklistResults as $name => $listed) {
    testResult(
        "{$name} Blacklist",
        !$listed,
        $listed ? "⚠️ IP is BLACKLISTED on {$name}" : "✓ Not blacklisted"
    );
    if ($listed) $blacklisted = true;
    recordResult(!$listed);
}

if ($blacklisted) {
    echo "<div class='alert alert-danger'>";
    echo "<strong>⚠️ Warning:</strong> Your server IP is blacklisted! This will severely impact email deliverability. ";
    echo "Contact your hosting provider or visit the blacklist website to request removal.";
    echo "</div>";
}

sectionFooter();

// ============================================================================
// 3. REVERSE DNS CHECK
// ============================================================================
sectionHeader("🔄 3. Reverse DNS (PTR Record)");

$reverseDNSValid = ($reverseDNS && $reverseDNS !== $serverIP);
testResult(
    "Reverse DNS for {$serverIP}",
    $reverseDNSValid,
    $reverseDNSValid ? "PTR Record: {$reverseDNS}" : "No reverse DNS configured. This may affect deliverability."
);
recordResult($reverseDNSValid);

sectionFooter();

// ============================================================================
// 4. PHP MAIL CONFIGURATION
// ============================================================================
sectionHeader("⚙️ 4. PHP Mail Configuration");

$mailFunctionExists = function_exists('mail');
testResult(
    "mail() Function Available",
    $mailFunctionExists,
    $mailFunctionExists ? "The mail() function is available" : "mail() function is NOT available"
);
recordResult($mailFunctionExists);

$sendmailPath = ini_get('sendmail_path');
testResult(
    "Sendmail Path",
    !empty($sendmailPath),
    "Path: " . ($sendmailPath ?: 'Not configured')
);

$SMTPServer = ini_get('SMTP');
$SMTPPort = ini_get('smtp_port');
testResult(
    "SMTP Configuration",
    !empty($SMTPServer) || PHP_OS_FAMILY !== 'Windows',
    "Server: " . ($SMTPServer ?: 'Not set') . " | Port: " . ($SMTPPort ?: 'Not set')
);

sectionFooter();

// ============================================================================
// 5. DNS AND MX RECORDS
// ============================================================================
sectionHeader("🌐 5. DNS and MX Records");

$dnsRecords = @dns_get_record($emailDomain, DNS_A);
$dnsResolved = !empty($dnsRecords);
testResult(
    "DNS Resolution for {$emailDomain}",
    $dnsResolved,
    $dnsResolved ? "Resolves to: " . ($dnsRecords[0]['ip'] ?? 'Unknown') : "Cannot resolve domain"
);
recordResult($dnsResolved);

$mxRecords = [];
$mxExists = @getmxrr($emailDomain, $mxRecords);

// Detect email provider from MX records
$providerInfo = detectEmailProvider($mxRecords);

testResult(
    "MX Records for {$emailDomain}",
    $mxExists,
    $mxExists ? "Found " . count($mxRecords) . " record(s): " . implode(', ', array_slice($mxRecords, 0, 3)) : "No MX records"
);
recordResult($mxExists);

// Display provider information
if ($mxExists) {
    echo "<div class='test-item'>";
    echo "<div class='test-name'>Email Provider Detected";
    if ($providerInfo['is_third_party']) {
        echo " <span class='status warning'>THIRD-PARTY</span>";
    } else {
        echo " <span class='status info'>SELF-HOSTED</span>";
    }
    echo "</div>";
    echo "<div class='details'>";
    echo "<strong>Provider:</strong> " . htmlspecialchars($providerInfo['provider']) . "<br>";
    echo "<strong>MX Records:</strong> " . implode(', ', array_slice($mxRecords, 0, 5));
    echo "</div>";
    echo "</div>";
    
    // Warning for third-party providers
    if ($providerInfo['is_third_party'] && $providerInfo['requires_smtp']) {
        echo "<div class='alert alert-danger'>";
        echo "<strong>⚠️ CRITICAL: Third-Party Email Provider Detected!</strong><br><br>";
        echo "Your domain <strong>{$emailDomain}</strong> uses <strong>{$providerInfo['provider']}</strong> for email.<br><br>";
        echo "<strong>This means:</strong><br>";
        echo "• You CANNOT send emails FROM <code>@{$emailDomain}</code> using PHP mail() function<br>";
        echo "• Your server is NOT authorized to send emails for this domain<br>";
        echo "• Emails sent from <code>noreply@{$emailDomain}</code> will likely FAIL or go to SPAM<br><br>";
        echo "<strong>✅ Solutions:</strong><br>";
        echo "1. <strong>Use SMTP Authentication</strong> (Recommended):<br>";
        echo "   - SMTP Host: <code>{$providerInfo['smtp_host']}</code><br>";
        echo "   - SMTP Port: <code>{$providerInfo['smtp_port']}</code><br>";
        echo "   - Username: Your {$providerInfo['provider']} email address<br>";
        echo "   - Password: Your {$providerInfo['provider']} password or app-specific password<br><br>";
        echo "2. <strong>Use Server Default Email</strong>:<br>";
        echo "   - Click the 'Send Test from Server Default' button below<br>";
        echo "   - This uses your server's email (not @{$emailDomain})<br>";
        echo "   - More likely to work but won't match your domain<br><br>";
        echo "3. <strong>Use a Transactional Email Service</strong>:<br>";
        echo "   - SendGrid, Mailgun, Amazon SES, Postmark<br>";
        echo "   - These are designed for sending emails from web applications<br>";
        echo "</div>";
    }
}

sectionFooter();

// ============================================================================
// 6. EMAIL SENDING TESTS WITH FALLBACK
// ============================================================================
sectionHeader("📤 6. Email Sending Tests (with Fallback Mechanism)");

// Check if manual server default test was requested
$serverDefaultTestRequested = isset($_GET['test_server_default']) && $_GET['test_server_default'] === '1';

// Get server default email for display
$serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
$serverDefaultEmail = ini_get('sendmail_from') ?: "noreply@{$serverName}";

// Manual Server Default Email Test Section
echo "<div class='alert alert-warning'>";
echo "<strong>🔧 Manual Test: Server Default Email</strong><br>";
echo "Server default email address: <strong>" . htmlspecialchars($serverDefaultEmail) . "</strong><br>";
echo "Click the button below to send a test email ONLY from the server default address (no fallback, no domain-specific emails).";
echo "<br><br>";
echo "<a href='?domain=" . urlencode($activeDomain) . "&test_server_default=1' class='test-server-btn'>📧 Send Test from Server Default</a>";
echo "</div>";

// Process manual server default test if requested
if ($serverDefaultTestRequested) {
    echo "<div class='alert alert-info'>";
    echo "<strong>� Manual Server Default Test Triggered</strong><br>";
    echo "Sending test email from server default address only...";
    echo "</div>";
    
    $serverTestSubject = "Server Default Email Test - " . date('Y-m-d H:i:s');
    $serverTestMessage = "This is a manual test email sent ONLY from the server default address.\n\nServer Default Email: {$serverDefaultEmail}\nDomain: {$emailDomain}\nServer: " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "\nIP: {$serverIP}\nTime: " . date('Y-m-d H:i:s');
    
    $serverTestSent = sendEmailFromServerDefault($CONFIG['test_email'], $serverTestSubject, $serverTestMessage, false);
    
    testResult(
        "Manual Server Default Email Test",
        $serverTestSent,
        $serverTestSent ? "✓ Sent from {$serverDefaultEmail} to {$CONFIG['test_email']}" : "✗ Failed to send from server default"
    );
}

echo "<div class='alert alert-info'>";
echo "<strong>📝 Automatic Tests (with fallback):</strong> The tests below will try to send emails using each configured sender address in order. ";
echo "If one fails, it automatically tries the next one. The server default email is used as the final fallback.";
echo "</div>";


// Simple text email
$subject1 = "Email Diagnostic Test - " . date('Y-m-d H:i:s');
$message1 = "Test email from Email Diagnostics Tool\n\nDomain: {$emailDomain}\nServer: " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "\nIP: {$serverIP}\nTime: " . date('Y-m-d H:i:s');

$sent1 = sendEmailWithFallback($CONFIG['test_email'], $subject1, $message1, false);
testResult(
    "Simple Text Email",
    $sent1,
    $sent1 ? "✓ Sent to {$CONFIG['test_email']}" : "✗ All sender addresses failed"
);
recordResult($sent1);

// HTML email
$subject2 = "Email Diagnostic Test (HTML) - " . date('Y-m-d H:i:s');
$message2 = "<html><body style='font-family: Arial; padding: 20px;'><h2>Email Diagnostic Test</h2><p>This is an HTML test email.</p><p><strong>Domain:</strong> {$emailDomain}</p><p><strong>Server:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "</p></body></html>";

$sent2 = sendEmailWithFallback($CONFIG['test_email'], $subject2, $message2, true);
testResult(
    "HTML Email",
    $sent2,
    $sent2 ? "✓ Sent to {$CONFIG['test_email']}" : "✗ All sender addresses failed"
);
recordResult($sent2);

// Display email sending log
echo "<div class='email-log'>";
echo "<h3 style='font-size: 14px; margin-bottom: 10px;'>📋 Detailed Email Sending Log</h3>";

foreach ($emailLog as $log) {
    $logClass = $log['success'] ? 'success' : 'failed';
    $isManualTest = isset($log['manual_test']) && $log['manual_test'];
    echo "<div class='email-log-entry {$logClass}'>";
    echo "<h4>";
    echo $log['success'] ? "✅ SUCCESS" : "❌ FAILED";
    if ($isManualTest) {
        echo " <span style='background: #f59e0b; color: white; padding: 2px 6px; border-radius: 3px; font-size: 10px; margin-left: 6px;'>MANUAL TEST</span>";
    }
    echo " - To: " . htmlspecialchars($log['to']);
    if ($log['final_sender']) {
        echo " | <strong>Sent from: " . htmlspecialchars($log['final_sender']) . "</strong>";
    }
    echo "</h4>";
    echo "<div style='font-size: 12px; color: #666; margin: 4px 0;'>Subject: " . htmlspecialchars($log['subject']) . "</div>";
    
    echo "<div style='margin-top: 8px;'><strong style='font-size: 12px;'>Attempts:</strong></div>";
    foreach ($log['attempts'] as $idx => $attempt) {
        $attemptClass = $attempt['success'] ? 'success' : 'failed';
        echo "<div class='attempt {$attemptClass}'>";
        echo "<strong>Attempt " . ($idx + 1) . ":</strong> ";
        echo "From: " . htmlspecialchars($attempt['from']) . " | ";
        echo "Method: " . htmlspecialchars($attempt['method']) . " | ";
        echo "Time: " . htmlspecialchars($attempt['timestamp']) . " | ";
        echo $attempt['success'] ? "<span style='color: #28a745;'>✓ SUCCESS</span>" : "<span style='color: #dc3545;'>✗ FAILED</span>";
        if ($attempt['error']) {
            echo "<br><span style='color: #dc3545; font-size: 11px;'>Error: " . htmlspecialchars($attempt['error']) . "</span>";
        }
        echo "</div>";
    }
    
    echo "</div>";
}

echo "</div>";

sectionFooter();

// ============================================================================
// 7. PHP EXTENSIONS
// ============================================================================
sectionHeader("🔌 7. PHP Extensions for Email");

$extensions = [
    'openssl' => 'Required for TLS/SSL SMTP',
    'sockets' => 'Required for socket-based SMTP',
    'mbstring' => 'For multibyte string handling',
];

foreach ($extensions as $ext => $description) {
    $loaded = extension_loaded($ext);
    testResult(
        "{$ext} Extension",
        $loaded,
        $description . " - " . ($loaded ? "Loaded" : "Not loaded")
    );
}

sectionFooter();

// ============================================================================
// DELIVERABILITY SCORE
// ============================================================================
$deliverabilityScore = calculateDeliverabilityScore($deliverabilityChecks);

$scoreClass = 'score-poor';
$scoreLabel = 'Poor';
if ($deliverabilityScore >= 80) {
    $scoreClass = 'score-excellent';
    $scoreLabel = 'Excellent';
} elseif ($deliverabilityScore >= 60) {
    $scoreClass = 'score-good';
    $scoreLabel = 'Good';
} elseif ($deliverabilityScore >= 40) {
    $scoreClass = 'score-fair';
    $scoreLabel = 'Fair';
}

echo "<div class='deliverability-score'>";
echo "<h2 style='margin-bottom: 10px; font-size: 18px;'>📊 Email Deliverability Score</h2>";
echo "<div class='score-circle {$scoreClass}'>{$deliverabilityScore}</div>";
echo "<div style='font-size: 16px; font-weight: 600; color: #333; margin-top: 10px;'>{$scoreLabel}</div>";
echo "<div style='font-size: 12px; color: #666; margin-top: 8px;'>Based on " . count($deliverabilityChecks) . " critical checks</div>";
echo "</div>";

// ============================================================================
// SUMMARY
// ============================================================================
$passRate = $results['total'] > 0 ? round(($results['passed'] / $results['total']) * 100, 1) : 0;

echo "<div class='summary'>";
echo "<h2>Test Summary</h2>";
echo "<div class='summary-grid'>";
echo "<div class='summary-item'><div class='number'>{$results['total']}</div><div class='label'>Total Tests</div></div>";
echo "<div class='summary-item'><div class='number'>{$results['passed']}</div><div class='label'>Passed</div></div>";
echo "<div class='summary-item'><div class='number'>{$results['failed']}</div><div class='label'>Failed</div></div>";
echo "<div class='summary-item'><div class='number'>{$passRate}%</div><div class='label'>Pass Rate</div></div>";
echo "</div>";
echo "</div>";

// ============================================================================
// RECOMMENDATIONS
// ============================================================================
sectionHeader("💡 Recommendations");

if (!$spfExists) {
    echo "<div class='alert alert-warning'><strong>⚠️ Add SPF Record:</strong> Create a TXT record: <code>v=spf1 a mx ~all</code></div>";
}

if (!$dmarcExists) {
    echo "<div class='alert alert-warning'><strong>⚠️ Add DMARC Record:</strong> Create a TXT record for _dmarc.{$emailDomain}: <code>v=DMARC1; p=quarantine; rua=mailto:admin@{$emailDomain}</code></div>";
}

if ($blacklisted) {
    echo "<div class='alert alert-danger'><strong>🚨 Remove from Blacklists:</strong> Contact your hosting provider or request delisting from the blacklist websites.</div>";
}

if (!$reverseDNSValid) {
    echo "<div class='alert alert-warning'><strong>⚠️ Configure Reverse DNS:</strong> Contact your hosting provider to set up PTR record for {$serverIP}</div>";
}

if ($deliverabilityScore < 60) {
    echo "<div class='alert alert-danger'><strong>🚨 Low Deliverability Score:</strong> Your email configuration needs improvement. Consider using external SMTP services like SendGrid, Mailgun, or Amazon SES.</div>";
} elseif ($deliverabilityScore < 80) {
    echo "<div class='alert alert-warning'><strong>⚠️ Moderate Deliverability:</strong> Some improvements needed. Review failed checks above.</div>";
} else {
    echo "<div class='alert alert-success'><strong>✅ Good Configuration:</strong> Your email setup looks good! Check {$CONFIG['test_email']} for test emails.</div>";
}

echo "<div class='alert alert-info'>";
echo "<strong>📝 Best Practices:</strong><br>";
echo "• Use external SMTP services for production (SendGrid, Mailgun, AWS SES)<br>";
echo "• Implement DKIM signing for better authentication<br>";
echo "• Monitor your sender reputation regularly<br>";
echo "• Keep your IP off blacklists<br>";
echo "• Use dedicated IP for high-volume sending<br>";
echo "• Always use valid, domain-matching sender addresses";
echo "</div>";

sectionFooter();

// ============================================================================
// CONFIGURATION DETAILS
// ============================================================================
sectionHeader("📋 PHP Mail Configuration");

$mailConfig = [
    'sendmail_path' => ini_get('sendmail_path'),
    'mail.add_x_header' => ini_get('mail.add_x_header'),
    'mail.log' => ini_get('mail.log'),
    'SMTP' => ini_get('SMTP'),
    'smtp_port' => ini_get('smtp_port'),
    'sendmail_from' => ini_get('sendmail_from'),
];

echo "<div class='code'>";
foreach ($mailConfig as $key => $value) {
    echo htmlspecialchars($key) . " = " . htmlspecialchars($value ?: '(not set)') . "\n";
}
echo "</div>";

sectionFooter();

outputFooter();
?>
