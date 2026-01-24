<?php
/**
 * Comprehensive Email Diagnostics Script
 * Tests all aspects of email functionality on the server
 */

// ============================================================================
// CONFIGURATION - Change test email address here
// ============================================================================
$TEST_EMAIL = 'webgraphicshub@gmail.com';

// ============================================================================
// SMTP Configuration (if you want to test SMTP)
// ============================================================================
$SMTP_CONFIG = [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => '',  // Your SMTP username
    'password' => '',  // Your SMTP password
    'encryption' => 'tls', // tls or ssl
];

// ============================================================================
// HTML Output Functions
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
                background: #f5f5f5;
                padding: 20px;
                line-height: 1.6;
            }
            .container { 
                max-width: 1200px;
                margin: 0 auto;
                background: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            h1 { 
                color: #2c3e50;
                margin-bottom: 10px;
                font-size: 28px;
            }
            .subtitle {
                color: #7f8c8d;
                margin-bottom: 30px;
                font-size: 14px;
            }
            .section { 
                margin: 25px 0;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 6px;
                border-left: 4px solid #3498db;
            }
            .section h2 { 
                color: #34495e;
                margin-bottom: 15px;
                font-size: 20px;
            }
            .test-item { 
                background: white;
                padding: 15px;
                margin: 10px 0;
                border-radius: 4px;
                border: 1px solid #e1e8ed;
            }
            .test-name { 
                font-weight: 600;
                color: #2c3e50;
                margin-bottom: 5px;
            }
            .status { 
                display: inline-block;
                padding: 4px 12px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 600;
                margin-left: 10px;
            }
            .status.pass { 
                background: #d4edda;
                color: #155724;
            }
            .status.fail { 
                background: #f8d7da;
                color: #721c24;
            }
            .status.warning { 
                background: #fff3cd;
                color: #856404;
            }
            .status.info { 
                background: #d1ecf1;
                color: #0c5460;
            }
            .details { 
                color: #6c757d;
                font-size: 14px;
                margin-top: 8px;
                padding: 10px;
                background: #f8f9fa;
                border-radius: 4px;
            }
            .code { 
                font-family: 'Courier New', monospace;
                background: #2c3e50;
                color: #ecf0f1;
                padding: 15px;
                border-radius: 4px;
                overflow-x: auto;
                margin: 10px 0;
                font-size: 13px;
            }
            .summary { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 25px;
                border-radius: 8px;
                margin-bottom: 30px;
            }
            .summary h2 { 
                color: white;
                margin-bottom: 15px;
            }
            .summary-grid { 
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
                margin-top: 15px;
            }
            .summary-item { 
                background: rgba(255,255,255,0.2);
                padding: 15px;
                border-radius: 6px;
                text-align: center;
            }
            .summary-item .number { 
                font-size: 32px;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .summary-item .label { 
                font-size: 13px;
                opacity: 0.9;
            }
            table { 
                width: 100%;
                border-collapse: collapse;
                margin: 15px 0;
            }
            th, td { 
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #dee2e6;
            }
            th { 
                background: #e9ecef;
                font-weight: 600;
                color: #495057;
            }
            tr:hover { 
                background: #f8f9fa;
            }
            .progress-bar {
                width: 100%;
                height: 6px;
                background: #e9ecef;
                border-radius: 3px;
                overflow: hidden;
                margin: 20px 0;
            }
            .progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
                transition: width 0.3s ease;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>📧 Email Diagnostics Report</h1>
            <div class="subtitle">Comprehensive email functionality testing for your server</div>
    <?php
}

function outputFooter() {
    ?>
        </div>
    </body>
    </html>
    <?php
}

function testResult($name, $status, $details = '') {
    $statusClass = $status ? 'pass' : 'fail';
    $statusText = $status ? '✓ PASS' : '✗ FAIL';
    
    echo "<div class='test-item'>";
    echo "<div class='test-name'>{$name} <span class='status {$statusClass}'>{$statusText}</span></div>";
    if ($details) {
        echo "<div class='details'>{$details}</div>";
    }
    echo "</div>";
}

function sectionHeader($title) {
    echo "<div class='section'><h2>{$title}</h2>";
}

function sectionFooter() {
    echo "</div>";
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

function recordResult($passed) {
    global $results;
    $results['total']++;
    if ($passed) {
        $results['passed']++;
    } else {
        $results['failed']++;
    }
}

outputHeader();

// ============================================================================
// 1. PHP MAIL CONFIGURATION TESTS
// ============================================================================
sectionHeader("1. PHP Mail Configuration");

// Check if mail function exists
$mailFunctionExists = function_exists('mail');
testResult(
    "mail() Function Available",
    $mailFunctionExists,
    $mailFunctionExists ? "The mail() function is available on this server" : "The mail() function is NOT available. Email cannot be sent using PHP's built-in mail() function."
);
recordResult($mailFunctionExists);

// Check PHP mail configuration
$sendmailPath = ini_get('sendmail_path');
testResult(
    "Sendmail Path Configuration",
    !empty($sendmailPath),
    "Sendmail path: " . ($sendmailPath ?: 'Not configured')
);
recordResult(!empty($sendmailPath));

// Check SMTP settings
$SMTPServer = ini_get('SMTP');
$SMTPPort = ini_get('smtp_port');
testResult(
    "SMTP Configuration (Windows)",
    !empty($SMTPServer) || PHP_OS_FAMILY !== 'Windows',
    "SMTP Server: " . ($SMTPServer ?: 'Not set') . " | Port: " . ($SMTPPort ?: 'Not set') . " (Only used on Windows)"
);

sectionFooter();

// ============================================================================
// 2. SERVER ENVIRONMENT TESTS
// ============================================================================
sectionHeader("2. Server Environment");

// Get server information
$serverInfo = [
    'PHP Version' => PHP_VERSION,
    'Operating System' => PHP_OS,
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'Server Name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
    'Server IP' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
    'Script Directory' => __DIR__,
];

echo "<table>";
echo "<tr><th>Property</th><th>Value</th></tr>";
foreach ($serverInfo as $key => $value) {
    echo "<tr><td>{$key}</td><td>{$value}</td></tr>";
}
echo "</table>";

// Check if running as CLI or Web
$isCLI = php_sapi_name() === 'cli';
testResult(
    "Execution Mode",
    true,
    "Running as: " . ($isCLI ? 'CLI (Command Line)' : 'Web Server')
);

sectionFooter();

// ============================================================================
// 3. DNS AND MX RECORD TESTS
// ============================================================================
sectionHeader("3. DNS and MX Record Tests");

// Extract domain from test email
$emailDomain = substr(strrchr($TEST_EMAIL, "@"), 1);

// Check DNS resolution
$dnsRecords = dns_get_record($emailDomain, DNS_A);
$dnsResolved = !empty($dnsRecords);
testResult(
    "DNS Resolution for {$emailDomain}",
    $dnsResolved,
    $dnsResolved ? "Domain resolves to IP: " . $dnsRecords[0]['ip'] : "Cannot resolve domain"
);
recordResult($dnsResolved);

// Check MX records
$mxRecords = [];
$mxExists = getmxrr($emailDomain, $mxRecords);
testResult(
    "MX Records for {$emailDomain}",
    $mxExists,
    $mxExists ? "Found " . count($mxRecords) . " MX record(s): " . implode(', ', array_slice($mxRecords, 0, 3)) : "No MX records found"
);
recordResult($mxExists);

// Check local server hostname
$hostname = gethostname();
$hostIP = gethostbyname($hostname);
testResult(
    "Server Hostname Resolution",
    $hostIP !== $hostname,
    "Hostname: {$hostname} | IP: {$hostIP}"
);
recordResult($hostIP !== $hostname);

sectionFooter();

// ============================================================================
// 4. PORT CONNECTIVITY TESTS
// ============================================================================
sectionHeader("4. Port Connectivity Tests");

$portsToTest = [
    25 => 'SMTP (Standard)',
    587 => 'SMTP (Submission/TLS)',
    465 => 'SMTPS (SSL)',
    2525 => 'SMTP (Alternative)'
];

foreach ($portsToTest as $port => $description) {
    $testHost = !empty($mxRecords) ? $mxRecords[0] : 'smtp.gmail.com';
    $connection = @fsockopen($testHost, $port, $errno, $errstr, 5);
    $connected = is_resource($connection);
    
    if ($connected) {
        fclose($connection);
    }
    
    testResult(
        "Port {$port} Connectivity ({$description})",
        $connected,
        $connected ? "Successfully connected to {$testHost}:{$port}" : "Cannot connect to {$testHost}:{$port} - {$errstr}"
    );
}

sectionFooter();

// ============================================================================
// 5. EMAIL HEADER VALIDATION
// ============================================================================
sectionHeader("5. Email Header Validation");

// Test various email formats
$emailFormats = [
    'Standard Format' => $TEST_EMAIL,
    'With Display Name' => "Test User <{$TEST_EMAIL}>",
    'RFC 2822 Format' => "\"Test User\" <{$TEST_EMAIL}>",
];

foreach ($emailFormats as $format => $email) {
    $isValid = filter_var(
        preg_replace('/^.*<(.+)>.*$/', '$1', $email),
        FILTER_VALIDATE_EMAIL
    );
    testResult(
        "{$format}: {$email}",
        $isValid !== false,
        $isValid ? "Valid email format" : "Invalid email format"
    );
}

sectionFooter();

// ============================================================================
// 6. ACTUAL EMAIL SENDING TESTS
// ============================================================================
sectionHeader("6. Email Sending Tests");

// Test 1: Simple text email
$subject1 = "Email Diagnostic Test - Simple Text [" . date('Y-m-d H:i:s') . "]";
$message1 = "This is a simple text email sent from the PHP email diagnostic script.\n\n";
$message1 .= "Server: " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "\n";
$message1 .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
$message1 .= "IP Address: " . ($_SERVER['SERVER_ADDR'] ?? 'Unknown') . "\n";

$headers1 = "From: noreply@" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n";
$headers1 .= "Reply-To: noreply@" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n";
$headers1 .= "X-Mailer: PHP/" . phpversion() . "\r\n";

$sent1 = @mail($TEST_EMAIL, $subject1, $message1, $headers1);
testResult(
    "Simple Text Email",
    $sent1,
    $sent1 ? "Email sent to {$TEST_EMAIL}" : "Failed to send email. Check error logs."
);
recordResult($sent1);

// Test 2: HTML email
$subject2 = "Email Diagnostic Test - HTML Email [" . date('Y-m-d H:i:s') . "]";
$message2 = "
<html>
<head>
    <title>Email Diagnostic Test</title>
</head>
<body style='font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4;'>
    <div style='max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);'>
        <h2 style='color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px;'>Email Diagnostic Test</h2>
        <p style='color: #666; line-height: 1.6;'>This is an HTML formatted email sent from the PHP email diagnostic script.</p>
        <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
            <tr style='background: #f9f9f9;'>
                <td style='padding: 10px; border: 1px solid #ddd;'><strong>Server:</strong></td>
                <td style='padding: 10px; border: 1px solid #ddd;'>" . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "</td>
            </tr>
            <tr>
                <td style='padding: 10px; border: 1px solid #ddd;'><strong>Timestamp:</strong></td>
                <td style='padding: 10px; border: 1px solid #ddd;'>" . date('Y-m-d H:i:s') . "</td>
            </tr>
            <tr style='background: #f9f9f9;'>
                <td style='padding: 10px; border: 1px solid #ddd;'><strong>PHP Version:</strong></td>
                <td style='padding: 10px; border: 1px solid #ddd;'>" . PHP_VERSION . "</td>
            </tr>
        </table>
        <p style='color: #999; font-size: 12px; margin-top: 30px; border-top: 1px solid #eee; padding-top: 15px;'>
            This is an automated test email. If you received this, your email system is working correctly!
        </p>
    </div>
</body>
</html>
";

$headers2 = "From: noreply@" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n";
$headers2 .= "Reply-To: noreply@" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n";
$headers2 .= "MIME-Version: 1.0\r\n";
$headers2 .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers2 .= "X-Mailer: PHP/" . phpversion() . "\r\n";

$sent2 = @mail($TEST_EMAIL, $subject2, $message2, $headers2);
testResult(
    "HTML Formatted Email",
    $sent2,
    $sent2 ? "HTML email sent to {$TEST_EMAIL}" : "Failed to send HTML email"
);
recordResult($sent2);

// Test 3: Email with multiple recipients
$subject3 = "Email Diagnostic Test - Multiple Headers [" . date('Y-m-d H:i:s') . "]";
$message3 = "This email tests multiple headers including CC and BCC functionality.";

$headers3 = "From: Diagnostic Script <noreply@" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . ">\r\n";
$headers3 .= "Reply-To: noreply@" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n";
$headers3 .= "X-Priority: 1\r\n";
$headers3 .= "X-MSMail-Priority: High\r\n";
$headers3 .= "Importance: High\r\n";

$sent3 = @mail($TEST_EMAIL, $subject3, $message3, $headers3);
testResult(
    "Email with Priority Headers",
    $sent3,
    $sent3 ? "Email with priority headers sent to {$TEST_EMAIL}" : "Failed to send email with priority headers"
);
recordResult($sent3);

sectionFooter();

// ============================================================================
// 7. PHP EXTENSIONS CHECK
// ============================================================================
sectionHeader("7. PHP Extensions for Email");

$extensions = [
    'openssl' => 'Required for TLS/SSL SMTP connections',
    'sockets' => 'Required for socket-based SMTP',
    'imap' => 'For IMAP email retrieval',
    'mbstring' => 'For multibyte string handling in emails',
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
// 8. FILE PERMISSIONS
// ============================================================================
sectionHeader("8. File System Permissions");

$tempFile = sys_get_temp_dir() . '/email_test_' . time() . '.txt';
$canWrite = @file_put_contents($tempFile, 'test');
if ($canWrite) {
    @unlink($tempFile);
}

testResult(
    "Temporary Directory Write Access",
    $canWrite !== false,
    "Temp directory: " . sys_get_temp_dir() . " - " . ($canWrite ? "Writable" : "Not writable")
);

sectionFooter();

// ============================================================================
// 9. ERROR LOG CHECK
// ============================================================================
sectionHeader("9. Error Logging Configuration");

$errorLog = ini_get('error_log');
$logErrors = ini_get('log_errors');
$displayErrors = ini_get('display_errors');

testResult(
    "Error Logging Enabled",
    $logErrors == 1,
    "log_errors = " . ($logErrors ? 'On' : 'Off') . " | Error log location: " . ($errorLog ?: 'Default')
);

testResult(
    "Display Errors Setting",
    true,
    "display_errors = " . ($displayErrors ? 'On' : 'Off') . " (Should be Off in production)"
);

sectionFooter();

// ============================================================================
// 10. ADDITIONAL MAIL FUNCTIONS
// ============================================================================
sectionHeader("10. Additional Email Functions Test");

// Test email validation
$testEmails = [
    'valid@example.com' => true,
    'invalid.email' => false,
    'user@domain' => false,
    'user+tag@example.com' => true,
];

foreach ($testEmails as $email => $shouldBeValid) {
    $isValid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    $status = ($isValid === $shouldBeValid);
    testResult(
        "Email Validation: {$email}",
        $status,
        ($isValid ? "Valid" : "Invalid") . " - " . ($status ? "As expected" : "Unexpected result")
    );
}

sectionFooter();

// ============================================================================
// SUMMARY
// ============================================================================
$passRate = $results['total'] > 0 ? round(($results['passed'] / $results['total']) * 100, 1) : 0;

echo "<div class='summary'>";
echo "<h2>Test Summary</h2>";
echo "<div class='progress-bar'><div class='progress-fill' style='width: {$passRate}%'></div></div>";
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
sectionHeader("Recommendations");

if ($results['failed'] > 0) {
    echo "<div class='test-item'>";
    echo "<div class='test-name'>⚠️ Issues Detected</div>";
    echo "<div class='details'>";
    echo "Some tests failed. Please review the failed tests above and:<br>";
    echo "• Check your server's mail configuration<br>";
    echo "• Verify firewall settings allow outbound SMTP connections<br>";
    echo "• Ensure mail server (sendmail/postfix/exim) is installed and running<br>";
    echo "• Check spam folder for test emails<br>";
    echo "• Review server error logs for detailed error messages<br>";
    echo "</div></div>";
} else {
    echo "<div class='test-item'>";
    echo "<div class='test-name'>✅ All Tests Passed</div>";
    echo "<div class='details'>";
    echo "Email functionality appears to be working correctly. Please check {$TEST_EMAIL} for test emails.";
    echo "</div></div>";
}

echo "<div class='test-item'>";
echo "<div class='test-name'>📝 Next Steps</div>";
echo "<div class='details'>";
echo "1. Check the inbox (and spam folder) of {$TEST_EMAIL}<br>";
echo "2. If emails weren't received, check server error logs<br>";
echo "3. Consider implementing SMTP authentication for better deliverability<br>";
echo "4. Set up SPF, DKIM, and DMARC records for your domain<br>";
echo "5. Use a dedicated email service (SendGrid, Mailgun, AWS SES) for production<br>";
echo "</div></div>";

sectionFooter();

// ============================================================================
// CONFIGURATION DUMP
// ============================================================================
sectionHeader("PHP Mail Configuration Details");

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
