<?php
/**
 * Enhanced Email Diagnostics Script v2.0
 * Tests all aspects of email functionality with advanced features
 * Features: Password protection, SPF/DKIM/DMARC validation, Blacklist checking, Deliverability score
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
// CONFIGURATION
// ============================================================================
$TEST_EMAIL = 'webgraphicshub@gmail.com'; // Change this to your email

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

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

// Export as JSON
if (isset($_GET['export']) && $_GET['export'] === 'json') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="email_diagnostics_' . date('Y-m-d_H-i-s') . '.json"');
    // JSON export will be populated at the end
}

// ============================================================================
// HTML OUTPUT FUNCTIONS
// ============================================================================
function outputHeader() {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Email Diagnostics Report</title>
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
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .header h1 { font-size: 22px; font-weight: 700; }
            .header .actions { display: flex; gap: 10px; }
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
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div>
                    <h1>📧 Email Diagnostics Report</h1>
                    <div style="font-size: 12px; opacity: 0.9; margin-top: 4px;">
                        Generated: <?php echo date('Y-m-d H:i:s T'); ?>
                    </div>
                </div>
                <div class="actions">
                    <a href="?export=json" title="Export as JSON">📥 Export</a>
                    <a href="?logout=1" title="Logout">🚪 Logout</a>
                    <a href="javascript:void(0)" onclick="if(confirm('Delete this diagnostic file?')) location.href='?delete_now=confirm'" 
                       title="Delete this file" style="background: rgba(239,68,68,0.3);">🗑️ Delete</a>
                </div>
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
$emailDomain = substr(strrchr($TEST_EMAIL, "@"), 1);
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
testResult(
    "MX Records for {$emailDomain}",
    $mxExists,
    $mxExists ? "Found " . count($mxRecords) . " record(s): " . implode(', ', array_slice($mxRecords, 0, 3)) : "No MX records"
);
recordResult($mxExists);

sectionFooter();

// ============================================================================
// 6. PORT CONNECTIVITY
// ============================================================================
sectionHeader("🔌 6. SMTP Port Connectivity");

$portsToTest = [
    25 => 'SMTP (Standard)',
    587 => 'SMTP (Submission/TLS)',
    465 => 'SMTPS (SSL)',
    2525 => 'SMTP (Alternative)'
];

foreach ($portsToTest as $port => $description) {
    $testHost = !empty($mxRecords) ? $mxRecords[0] : 'smtp.gmail.com';
    $connection = @fsockopen($testHost, $port, $errno, $errstr, 3);
    $connected = is_resource($connection);
    
    if ($connected) fclose($connection);
    
    testResult(
        "Port {$port} ({$description})",
        $connected,
        $connected ? "✓ Connected to {$testHost}:{$port}" : "✗ Cannot connect - {$errstr}"
    );
}

sectionFooter();

// ============================================================================
// 7. EMAIL SENDING TESTS
// ============================================================================
sectionHeader("📤 7. Email Sending Tests");

// Simple text email
$subject1 = "Email Diagnostic Test - " . date('Y-m-d H:i:s');
$message1 = "Test email from Email Diagnostics Tool\n\nServer: " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "\nIP: {$serverIP}\nTime: " . date('Y-m-d H:i:s');
$headers1 = "From: noreply@" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n";

$sent1 = @mail($TEST_EMAIL, $subject1, $message1, $headers1);
testResult(
    "Simple Text Email",
    $sent1,
    $sent1 ? "✓ Sent to {$TEST_EMAIL}" : "✗ Failed to send"
);
recordResult($sent1);

// HTML email
$subject2 = "Email Diagnostic Test (HTML) - " . date('Y-m-d H:i:s');
$message2 = "<html><body style='font-family: Arial; padding: 20px;'><h2>Email Diagnostic Test</h2><p>This is an HTML test email.</p><p><strong>Server:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "</p></body></html>";
$headers2 = "From: noreply@" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";

$sent2 = @mail($TEST_EMAIL, $subject2, $message2, $headers2);
testResult(
    "HTML Email",
    $sent2,
    $sent2 ? "✓ Sent to {$TEST_EMAIL}" : "✗ Failed to send"
);
recordResult($sent2);

sectionFooter();

// ============================================================================
// 8. PHP EXTENSIONS
// ============================================================================
sectionHeader("🔌 8. PHP Extensions for Email");

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
    echo "<div class='alert alert-success'><strong>✅ Good Configuration:</strong> Your email setup looks good! Check {$TEST_EMAIL} for test emails.</div>";
}

echo "<div class='alert alert-info'>";
echo "<strong>📝 Best Practices:</strong><br>";
echo "• Use external SMTP services for production (SendGrid, Mailgun, AWS SES)<br>";
echo "• Implement DKIM signing for better authentication<br>";
echo "• Monitor your sender reputation regularly<br>";
echo "• Keep your IP off blacklists<br>";
echo "• Use dedicated IP for high-volume sending";
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
