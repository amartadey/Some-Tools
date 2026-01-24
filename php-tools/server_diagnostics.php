<?php
/**
 * Comprehensive Server Diagnostics Tool
 * Tests server capabilities, limitations, and available features
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set execution time limit
set_time_limit(300);

// Start output buffering
ob_start();

// Helper function to format bytes
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Diagnostics Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            line-height: 1.6;
            min-height: 100vh;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        header h1 {
            font-size: 2.8em;
            margin-bottom: 10px;
            font-weight: 700;
        }
        .timestamp {
            font-size: 1em;
            opacity: 0.95;
            font-weight: 300;
        }
        .content {
            padding: 30px;
        }
        .section {
            margin-bottom: 25px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .section:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .section-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 18px 24px;
            font-size: 1.4em;
            font-weight: 600;
            cursor: pointer;
            user-select: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: opacity 0.2s;
        }
        .section-header:hover {
            opacity: 0.92;
        }
        .section-content {
            padding: 24px;
            background: #f9fafb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            font-size: 0.95em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        tr:last-child td {
            border-bottom: none;
        }
        tr:hover {
            background: #f3f4f6;
        }
        .status-good {
            color: #10b981;
            font-weight: 600;
        }
        .status-warning {
            color: #f59e0b;
            font-weight: 600;
        }
        .status-bad {
            color: #ef4444;
            font-weight: 600;
        }
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .badge-success {
            background: #10b981;
            color: white;
        }
        .badge-warning {
            background: #f59e0b;
            color: white;
        }
        .badge-danger {
            background: #ef4444;
            color: white;
        }
        .badge-info {
            background: #3b82f6;
            color: white;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 18px;
            margin-top: 15px;
        }
        .info-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .info-card h4 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 0.95em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-card p {
            font-size: 1.3em;
            color: #1f2937;
            font-weight: 500;
        }
        code {
            background: #f3f4f6;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: 'Courier New', Consolas, monospace;
            font-size: 0.9em;
            color: #dc2626;
        }
        pre {
            background: #1f2937;
            color: #f3f4f6;
            padding: 18px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 0.9em;
            line-height: 1.5;
        }
        .toggle-icon {
            transition: transform 0.3s ease;
            font-size: 0.8em;
        }
        .toggle-icon.rotated {
            transform: rotate(180deg);
        }
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid;
        }
        .alert-info {
            background: #dbeafe;
            border-color: #3b82f6;
            color: #1e40af;
        }
        .alert-warning {
            background: #fef3c7;
            border-color: #f59e0b;
            color: #92400e;
        }
        .alert-danger {
            background: #fee2e2;
            border-color: #ef4444;
            color: #991b1b;
        }
        h3 {
            color: #1f2937;
            margin: 20px 0 12px 0;
            font-size: 1.3em;
            font-weight: 600;
        }
        ul {
            margin-left: 20px;
            margin-top: 10px;
        }
        li {
            margin: 6px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🔍 Server Diagnostics Report</h1>
            <p class="timestamp">Generated: <?php echo date('Y-m-d H:i:s T'); ?></p>
        </header>
        
        <div class="content">
            <?php
            
            // ==========================================
            // BASIC SERVER INFORMATION
            // ==========================================
            ?>
            <div class="section">
                <div class="section-header" onclick="toggleSection(this)">
                    <span>📊 Basic Server Information</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="section-content">
                    <div class="info-grid">
                        <div class="info-card">
                            <h4>Server Software</h4>
                            <p><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                        </div>
                        <div class="info-card">
                            <h4>PHP Version</h4>
                            <p><?php echo PHP_VERSION; ?></p>
                        </div>
                        <div class="info-card">
                            <h4>Server Name</h4>
                            <p><?php echo $_SERVER['SERVER_NAME'] ?? 'Unknown'; ?></p>
                        </div>
                        <div class="info-card">
                            <h4>Server IP</h4>
                            <p><?php echo $_SERVER['SERVER_ADDR'] ?? 'Unknown'; ?></p>
                        </div>
                        <div class="info-card">
                            <h4>Document Root</h4>
                            <p style="font-size: 0.9em; word-break: break-all;"><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></p>
                        </div>
                        <div class="info-card">
                            <h4>Server Protocol</h4>
                            <p><?php echo $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown'; ?></p>
                        </div>
                        <div class="info-card">
                            <h4>Operating System</h4>
                            <p><?php echo PHP_OS; ?></p>
                        </div>
                        <div class="info-card">
                            <h4>Server Admin</h4>
                            <p style="font-size: 0.9em;"><?php echo $_SERVER['SERVER_ADMIN'] ?? 'Not set'; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            // ==========================================
            // PHP CONFIGURATION & LIMITS
            // ==========================================
            ?>
            <div class="section">
                <div class="section-header" onclick="toggleSection(this)">
                    <span>⚙️ PHP Configuration & Limits</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="section-content">
                    <table>
                        <tr>
                            <th>Configuration</th>
                            <th>Value</th>
                            <th>Status</th>
                        </tr>
                        <?php
                        $configs = [
                            'memory_limit' => ini_get('memory_limit'),
                            'max_execution_time' => ini_get('max_execution_time') . ' seconds',
                            'max_input_time' => ini_get('max_input_time') . ' seconds',
                            'post_max_size' => ini_get('post_max_size'),
                            'upload_max_filesize' => ini_get('upload_max_filesize'),
                            'max_file_uploads' => ini_get('max_file_uploads'),
                            'max_input_vars' => ini_get('max_input_vars'),
                            'default_socket_timeout' => ini_get('default_socket_timeout') . ' seconds',
                            'allow_url_fopen' => ini_get('allow_url_fopen') ? 'On' : 'Off',
                            'allow_url_include' => ini_get('allow_url_include') ? 'On' : 'Off',
                            'file_uploads' => ini_get('file_uploads') ? 'Enabled' : 'Disabled',
                            'session.save_path' => ini_get('session.save_path'),
                            'upload_tmp_dir' => ini_get('upload_tmp_dir') ?: 'Default',
                            'disable_functions' => ini_get('disable_functions') ?: 'None',
                            'open_basedir' => ini_get('open_basedir') ?: 'Not restricted',
                        ];
                        
                        foreach ($configs as $key => $value) {
                            $status = '';
                            if ($key == 'allow_url_fopen' && $value == 'On') $status = '<span class="badge badge-success">✓ Good</span>';
                            elseif ($key == 'allow_url_include' && $value == 'Off') $status = '<span class="badge badge-success">✓ Secure</span>';
                            elseif ($key == 'file_uploads' && $value == 'Enabled') $status = '<span class="badge badge-success">✓ Enabled</span>';
                            elseif ($key == 'disable_functions' && $value != 'None') $status = '<span class="badge badge-warning">⚠ Limited</span>';
                            
                            echo "<tr>";
                            echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
                            echo "<td><code>" . htmlspecialchars($value) . "</code></td>";
                            echo "<td>$status</td>";
                            echo "</tr>";
                        }
                        ?>
                    </table>
                </div>
            </div>

            <?php
            // ==========================================
            // LOADED PHP EXTENSIONS
            // ==========================================
            ?>
            <div class="section">
                <div class="section-header" onclick="toggleSection(this)">
                    <span>🔌 Loaded PHP Extensions</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="section-content">
                    <?php
                    $extensions = get_loaded_extensions();
                    sort($extensions);
                    
                    $important_extensions = ['mysqli', 'pdo', 'pdo_mysql', 'curl', 'gd', 'mbstring', 
                                            'zip', 'xml', 'json', 'openssl', 'fileinfo', 'iconv', 'session'];
                    
                    echo '<div style="margin-bottom: 20px;">';
                    echo '<h3>Important Extensions Status:</h3>';
                    echo '<div style="display: flex; flex-wrap: wrap; gap: 10px;">';
                    
                    foreach ($important_extensions as $ext) {
                        $loaded = extension_loaded($ext);
                        $badge_class = $loaded ? 'badge-success' : 'badge-danger';
                        $icon = $loaded ? '✓' : '✗';
                        echo "<span class='badge $badge_class'>$icon $ext</span>";
                    }
                    
                    echo '</div></div>';
                    
                    echo '<h3>All Loaded Extensions (' . count($extensions) . '):</h3>';
                    echo '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 8px;">';
                    foreach ($extensions as $ext) {
                        echo "<span class='badge badge-info'>$ext</span>";
                    }
                    echo '</div>';
                    ?>
                </div>
            </div>

            <?php
            // ==========================================
            // DATABASE CONNECTIVITY
            // ==========================================
            ?>
            <div class="section">
                <div class="section-header" onclick="toggleSection(this)">
                    <span>🗄️ Database Connectivity</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="section-content">
                    <?php
                    echo '<h3>MySQL/MariaDB Support:</h3>';
                    echo '<table>';
                    echo '<tr><th>Feature</th><th>Status</th><th>Details</th></tr>';
                    
                    // Check mysqli
                    $mysqli_available = extension_loaded('mysqli');
                    echo '<tr>';
                    echo '<td><strong>MySQLi Extension</strong></td>';
                    echo '<td>' . ($mysqli_available ? '<span class="badge badge-success">✓ Available</span>' : '<span class="badge badge-danger">✗ Not Available</span>') . '</td>';
                    echo '<td>' . ($mysqli_available ? 'Client version: ' . mysqli_get_client_info() : 'N/A') . '</td>';
                    echo '</tr>';
                    
                    // Check PDO MySQL
                    $pdo_mysql = extension_loaded('pdo_mysql');
                    echo '<tr>';
                    echo '<td><strong>PDO MySQL</strong></td>';
                    echo '<td>' . ($pdo_mysql ? '<span class="badge badge-success">✓ Available</span>' : '<span class="badge badge-danger">✗ Not Available</span>') . '</td>';
                    echo '<td>' . ($pdo_mysql ? 'Modern database abstraction layer' : 'N/A') . '</td>';
                    echo '</tr>';
                    
                    // Check PDO
                    if (extension_loaded('pdo')) {
                        $drivers = PDO::getAvailableDrivers();
                        echo '<tr>';
                        echo '<td><strong>PDO Drivers</strong></td>';
                        echo '<td><span class="badge badge-info">' . count($drivers) . ' Available</span></td>';
                        echo '<td>' . implode(', ', $drivers) . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</table>';
                    
                    // phpMyAdmin check
                    echo '<h3 style="margin-top: 20px;">phpMyAdmin Availability:</h3>';
                    
                    $possible_phpmyadmin_paths = [
                        '/phpmyadmin',
                        '/phpMyAdmin',
                        '/pma',
                        '/mysql',
                        '/db'
                    ];
                    
                    echo '<table>';
                    echo '<tr><th>Common Path</th><th>Test Link</th></tr>';
                    foreach ($possible_phpmyadmin_paths as $path) {
                        $full_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] . $path;
                        echo '<tr>';
                        echo '<td><code>' . htmlspecialchars($path) . '</code></td>';
                        echo '<td><a href="' . htmlspecialchars($full_url) . '" target="_blank" style="color: #667eea; font-weight: 600;">Test Link →</a></td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                    
                    echo '<div class="alert alert-info">';
                    echo '<strong>💡 InfinityFree Database Access</strong><br>';
                    echo 'Access phpMyAdmin through: VistaPanel → MySQL Databases → phpMyAdmin button';
                    echo '</div>';
                    ?>
                </div>
            </div>

            <?php
            // ==========================================
            // EMAIL FUNCTIONALITY
            // ==========================================
            ?>
            <div class="section">
                <div class="section-header" onclick="toggleSection(this)">
                    <span>📧 Email Functionality</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="section-content">
                    <?php
                    echo '<table>';
                    echo '<tr><th>Feature</th><th>Status</th><th>Details</th></tr>';
                    
                    // Check mail function
                    $mail_enabled = function_exists('mail');
                    echo '<tr>';
                    echo '<td><strong>mail() Function</strong></td>';
                    echo '<td>' . ($mail_enabled ? '<span class="badge badge-success">✓ Available</span>' : '<span class="badge badge-danger">✗ Not Available</span>') . '</td>';
                    echo '<td>' . ($mail_enabled ? 'PHP mail function is enabled' : 'Function disabled') . '</td>';
                    echo '</tr>';
                    
                    // SMTP settings
                    $smtp_settings = [
                        'SMTP' => ini_get('SMTP'),
                        'smtp_port' => ini_get('smtp_port'),
                        'sendmail_from' => ini_get('sendmail_from'),
                        'sendmail_path' => ini_get('sendmail_path')
                    ];
                    
                    foreach ($smtp_settings as $key => $value) {
                        echo '<tr>';
                        echo '<td><strong>' . htmlspecialchars($key) . '</strong></td>';
                        echo '<td>' . ($value ? '<span class="badge badge-info">Set</span>' : '<span class="badge badge-warning">Not Set</span>') . '</td>';
                        echo '<td><code>' . htmlspecialchars($value ?: 'Default/Not configured') . '</code></td>';
                        echo '</tr>';
                    }
                    
                    echo '</table>';
                    
                    echo '<div class="alert alert-warning">';
                    echo '<strong>⚠️ InfinityFree Email Limitations</strong><br>';
                    echo '<strong>Important:</strong> InfinityFree has strict limitations on the mail() function:<br>';
                    echo '<ul>';
                    echo '<li>Very limited sending capacity (may be disabled)</li>';
                    echo '<li>Emails often end up in spam folders</li>';
                    echo '<li><strong>Recommended:</strong> Use external SMTP services like Gmail SMTP, SendGrid, Mailgun, or Amazon SES</li>';
                    echo '<li>Use PHPMailer or SwiftMailer libraries with SMTP</li>';
                    echo '</ul></div>';
                    
                    // Test if we can use SMTP libraries
                    echo '<h3>Email Libraries Support:</h3>';
                    echo '<table>';
                    echo '<tr><th>Library</th><th>Requirements</th><th>Status</th></tr>';
                    
                    $openssl = extension_loaded('openssl');
                    echo '<tr>';
                    echo '<td><strong>PHPMailer/SMTP</strong></td>';
                    echo '<td>OpenSSL for TLS/SSL</td>';
                    echo '<td>' . ($openssl ? '<span class="badge badge-success">✓ Supported</span>' : '<span class="badge badge-danger">✗ Missing OpenSSL</span>') . '</td>';
                    echo '</tr>';
                    
                    $curl = extension_loaded('curl');
                    echo '<tr>';
                    echo '<td><strong>API-based (SendGrid, etc.)</strong></td>';
                    echo '<td>cURL or allow_url_fopen</td>';
                    echo '<td>' . ($curl ? '<span class="badge badge-success">✓ Supported</span>' : '<span class="badge badge-warning">⚠ Limited</span>') . '</td>';
                    echo '</tr>';
                    
                    echo '</table>';
                    ?>
                </div>
            </div>

            <?php
            // ==========================================
            // FILE SYSTEM & PERMISSIONS
            // ==========================================
            ?>
            <div class="section">
                <div class="section-header" onclick="toggleSection(this)">
                    <span>📁 File System & Permissions</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="section-content">
                    <?php
                    echo '<table>';
                    echo '<tr><th>Test</th><th>Result</th><th>Details</th></tr>';
                    
                    // Test file creation
                    $test_file = 'test_write_' . time() . '.txt';
                    $can_write = @file_put_contents($test_file, 'test');
                    echo '<tr>';
                    echo '<td><strong>File Write</strong></td>';
                    echo '<td>' . ($can_write ? '<span class="badge badge-success">✓ Writable</span>' : '<span class="badge badge-danger">✗ Not Writable</span>') . '</td>';
                    echo '<td>' . ($can_write ? 'Can create and write files' : 'Cannot write to current directory') . '</td>';
                    echo '</tr>';
                    
                    // Test file read
                    $can_read = false;
                    if ($can_write) {
                        $can_read = @file_get_contents($test_file) === 'test';
                    }
                    echo '<tr>';
                    echo '<td><strong>File Read</strong></td>';
                    echo '<td>' . ($can_read ? '<span class="badge badge-success">✓ Readable</span>' : '<span class="badge badge-danger">✗ Not Readable</span>') . '</td>';
                    echo '<td>' . ($can_read ? 'Can read files' : 'Cannot read files') . '</td>';
                    echo '</tr>';
                    
                    // Test file delete
                    $can_delete = false;
                    if ($can_write) {
                        $can_delete = @unlink($test_file);
                    }
                    echo '<tr>';
                    echo '<td><strong>File Delete</strong></td>';
                    echo '<td>' . ($can_delete ? '<span class="badge badge-success">✓ Can Delete</span>' : '<span class="badge badge-danger">✗ Cannot Delete</span>') . '</td>';
                    echo '<td>' . ($can_delete ? 'Can delete files' : 'Cannot delete files') . '</td>';
                    echo '</tr>';
                    
                    // Test directory creation
                    $test_dir = 'test_dir_' . time();
                    $can_mkdir = @mkdir($test_dir);
                    echo '<tr>';
                    echo '<td><strong>Directory Creation</strong></td>';
                    echo '<td>' . ($can_mkdir ? '<span class="badge badge-success">✓ Can Create</span>' : '<span class="badge badge-danger">✗ Cannot Create</span>') . '</td>';
                    echo '<td>' . ($can_mkdir ? 'Can create directories' : 'Cannot create directories') . '</td>';
                    echo '</tr>';
                    
                    if ($can_mkdir) {
                        @rmdir($test_dir);
                    }
                    
                    // Disk space
                    $free_space = @disk_free_space('.');
                    $total_space = @disk_total_space('.');
                    echo '<tr>';
                    echo '<td><strong>Disk Space</strong></td>';
                    echo '<td>' . ($free_space ? '<span class="badge badge-info">Available</span>' : '<span class="badge badge-warning">Unknown</span>') . '</td>';
                    echo '<td>' . ($free_space ? 'Free: ' . formatBytes($free_space) . ' / Total: ' . formatBytes($total_space) : 'Cannot determine') . '</td>';
                    echo '</tr>';
                    
                    echo '</table>';
                    
                    // Directory permissions
                    echo '<h3>Directory Information:</h3>';
                    echo '<table>';
                    echo '<tr><th>Path</th><th>Permissions</th><th>Writable</th></tr>';
                    
                    $dirs_to_check = [
                        'Current Directory' => '.',
                        'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? '.',
                        'Temp Directory' => sys_get_temp_dir()
                    ];
                    
                    foreach ($dirs_to_check as $name => $path) {
                        if (file_exists($path)) {
                            $perms = substr(sprintf('%o', fileperms($path)), -4);
                            $writable = is_writable($path);
                            echo '<tr>';
                            echo '<td><strong>' . htmlspecialchars($name) . '</strong><br><code style="font-size: 0.85em;">' . htmlspecialchars(realpath($path)) . '</code></td>';
                            echo '<td><code>' . $perms . '</code></td>';
                            echo '<td>' . ($writable ? '<span class="badge badge-success">✓ Yes</span>' : '<span class="badge badge-danger">✗ No</span>') . '</td>';
                            echo '</tr>';
                        }
                    }
                    echo '</table>';
                    ?>
                </div>
            </div>

            <?php
            // ==========================================
            // NETWORK & EXTERNAL CONNECTIONS
            // ==========================================
            ?>
            <div class="section">
                <div class="section-header" onclick="toggleSection(this)">
                    <span>🌐 Network & External Connections</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="section-content">
                    <?php
                    echo '<table>';
                    echo '<tr><th>Feature</th><th>Status</th><th>Test Result</th></tr>';
                    
                    // cURL test
                    $curl_available = extension_loaded('curl');
                    $curl_works = false;
                    $curl_version = '';
                    if ($curl_available) {
                        $curl_version = curl_version();
                        $ch = @curl_init('https://www.google.com');
                        if ($ch) {
                            @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            @curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                            @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                            $result = @curl_exec($ch);
                            $curl_works = ($result !== false);
                            @curl_close($ch);
                        }
                    }
                    echo '<tr>';
                    echo '<td><strong>cURL Extension</strong></td>';
                    echo '<td>' . ($curl_available ? '<span class="badge badge-success">✓ Available</span>' : '<span class="badge badge-danger">✗ Not Available</span>') . '</td>';
                    echo '<td>' . ($curl_works ? '<span class="status-good">✓ Working</span>' : ($curl_available ? '<span class="status-warning">⚠ May be restricted</span>' : '<span class="status-bad">✗ Not available</span>')) . '</td>';
                    echo '</tr>';
                    
                    if ($curl_available && is_array($curl_version)) {
                        echo '<tr>';
                        echo '<td><strong>cURL Version</strong></td>';
                        echo '<td colspan="2"><code>' . ($curl_version['version'] ?? 'Unknown') . '</code></td>';
                        echo '</tr>';
                    }
                    
                    // file_get_contents test
                    $fgc_allowed = ini_get('allow_url_fopen');
                    $fgc_works = false;
                    if ($fgc_allowed) {
                        $ctx = stream_context_create(['http' => ['timeout' => 5]]);
                        $result = @file_get_contents('https://www.google.com', false, $ctx);
                        $fgc_works = ($result !== false);
                    }
                    echo '<tr>';
                    echo '<td><strong>file_get_contents (URL)</strong></td>';
                    echo '<td>' . ($fgc_allowed ? '<span class="badge badge-success">✓ Allowed</span>' : '<span class="badge badge-danger">✗ Disabled</span>') . '</td>';
                    echo '<td>' . ($fgc_works ? '<span class="status-good">✓ Working</span>' : ($fgc_allowed ? '<span class="status-warning">⚠ May be restricted</span>' : '<span class="status-bad">✗ Disabled</span>')) . '</td>';
                    echo '</tr>';
                    
                    // fsockopen test
                    $fsock_available = function_exists('fsockopen');
                    $fsock_works = false;
                    if ($fsock_available) {
                        $fp = @fsockopen('www.google.com', 80, $errno, $errstr, 5);
                        if ($fp) {
                            $fsock_works = true;
                            @fclose($fp);
                        }
                    }
                    echo '<tr>';
                    echo '<td><strong>fsockopen</strong></td>';
                    echo '<td>' . ($fsock_available ? '<span class="badge badge-success">✓ Available</span>' : '<span class="badge badge-danger">✗ Disabled</span>') . '</td>';
                    echo '<td>' . ($fsock_works ? '<span class="status-good">✓ Working</span>' : ($fsock_available ? '<span class="status-warning">⚠ May be restricted</span>' : '<span class="status-bad">✗ Disabled</span>')) . '</td>';
                    echo '</tr>';
                    
                    // Socket extension
                    $socket_available = extension_loaded('sockets');
                    echo '<tr>';
                    echo '<td><strong>Sockets Extension</strong></td>';
                    echo '<td>' . ($socket_available ? '<span class="badge badge-success">✓ Available</span>' : '<span class="badge badge-danger">✗ Not Available</span>') . '</td>';
                    echo '<td>' . ($socket_available ? 'Low-level socket support enabled' : 'Not available') . '</td>';
                    echo '</tr>';
                    
                    echo '</table>';
                    
                    // DNS functions
                    echo '<h3>DNS Capabilities:</h3>';
                    echo '<table>';
                    echo '<tr><th>Function</th><th>Status</th><th>Test</th></tr>';
                    
                    $dns_test = @gethostbyname('www.google.com');
                    echo '<tr>';
                    echo '<td><strong>gethostbyname()</strong></td>';
                    echo '<td>' . (function_exists('gethostbyname') ? '<span class="badge badge-success">✓ Available</span>' : '<span class="badge badge-danger">✗ Not Available</span>') . '</td>';
                    echo '<td>' . ($dns_test && $dns_test != 'www.google.com' ? '<code>' . $dns_test . '</code>' : 'Cannot resolve') . '</td>';
                    echo '</tr>';
                    
                    $dns_records_available = function_exists('dns_get_record');
                    echo '<tr>';
                    echo '<td><strong>dns_get_record()</strong></td>';
                    echo '<td>' . ($dns_records_available ? '<span class="badge badge-success">✓ Available</span>' : '<span class="badge badge-danger">✗ Not Available</span>') . '</td>';
                    echo '<td>' . ($dns_records_available ? 'DNS record lookup available' : 'Not available') . '</td>';
                    echo '</tr>';
                    
                    echo '</table>';
                    ?>
                </div>
            </div>

            <?php
            // ==========================================
            // SECURITY & RESTRICTIONS
            // ==========================================
            ?>
            <div class="section">
                <div class="section-header" onclick="toggleSection(this)">
                    <span>🔒 Security & Restrictions</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="section-content">
                    <?php
                    echo '<table>';
                    echo '<tr><th>Security Feature</th><th>Status</th><th>Value</th></tr>';
                    
                    $security_settings = [
                        'safe_mode' => ini_get('safe_mode'),
                        'open_basedir' => ini_get('open_basedir'),
                        'disable_functions' => ini_get('disable_functions'),
                        'disable_classes' => ini_get('disable_classes'),
                        'expose_php' => ini_get('expose_php'),
                        'display_errors' => ini_get('display_errors'),
                        'log_errors' => ini_get('log_errors'),
                        'error_reporting' => error_reporting(),
                    ];
                    
                    foreach ($security_settings as $key => $value) {
                        $status = '';
                        $display_value = $value ?: 'Not set';
                        
                        if ($key == 'safe_mode') {
                            $status = $value ? '<span class="badge badge-warning">Enabled</span>' : '<span class="badge badge-info">Disabled</span>';
                            $display_value = $value ? 'On (Deprecated)' : 'Off';
                        } elseif ($key == 'open_basedir') {
                            $status = $value ? '<span class="badge badge-warning">Restricted</span>' : '<span class="badge badge-info">Not Restricted</span>';
                        } elseif ($key == 'disable_functions' || $key == 'disable_classes') {
                            $status = $value ? '<span class="badge badge-warning">⚠ Some Disabled</span>' : '<span class="badge badge-success">✓ None</span>';
                        } elseif ($key == 'expose_php') {
                            $status = $value ? '<span class="badge badge-warning">Exposed</span>' : '<span class="badge badge-success">✓ Hidden</span>';
                        }
                        
                        echo '<tr>';
                        echo '<td><strong>' . htmlspecialchars($key) . '</strong></td>';
                        echo '<td>' . $status . '</td>';
                        echo '<td><code style="font-size: 0.85em; word-break: break-all;">' . htmlspecialchars(substr($display_value, 0, 100)) . (strlen($display_value) > 100 ? '...' : '') . '</code></td>';
                        echo '</tr>';
                    }
                    
                    echo '</table>';
                    
                    // Disabled functions detail
                    $disabled_funcs = ini_get('disable_functions');
                    if ($disabled_funcs) {
                        echo '<div class="alert alert-warning">';
                        echo '<strong>⚠️ Disabled Functions:</strong><br>';
                        echo '<code style="display: block; margin-top: 10px; white-space: pre-wrap; word-break: break-all;">' . htmlspecialchars($disabled_funcs) . '</code>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>

            <?php
            // ==========================================
            // PERFORMANCE & RESOURCES
            // ==========================================
            ?>
            <div class="section">
                <div class="section-header" onclick="toggleSection(this)">
                    <span>⚡ Performance & Resources</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="section-content">
                    <?php
                    echo '<div class="info-grid">';
                    
                    // Memory usage
                    $memory_usage = memory_get_usage(true);
                    $memory_peak = memory_get_peak_usage(true);
                    $memory_limit = ini_get('memory_limit');
                    
                    echo '<div class="info-card">';
                    echo '<h4>Current Memory Usage</h4>';
                    echo '<p>' . formatBytes($memory_usage) . '</p>';
                    echo '</div>';
                    
                    echo '<div class="info-card">';
                    echo '<h4>Peak Memory Usage</h4>';
                    echo '<p>' . formatBytes($memory_peak) . '</p>';
                    echo '</div>';
                    
                    echo '<div class="info-card">';
                    echo '<h4>Memory Limit</h4>';
                    echo '<p>' . $memory_limit . '</p>';
                    echo '</div>';
                    
                    // CPU info
                    if (function_exists('sys_getloadavg')) {
                        $load = sys_getloadavg();
                        echo '<div class="info-card">';
                        echo '<h4>Server Load (1min)</h4>';
                        echo '<p>' . round($load[0], 2) . '</p>';
                        echo '</div>';
                    }
                    
                    echo '</div>';
                    
                    echo '<h3>Resource Tests:</h3>';
                    echo '<table>';
                    echo '<tr><th>Test</th><th>Result</th><th>Details</th></tr>';
                    
                    // Script execution time test
                    $start_time = microtime(true);
                    for ($i = 0; $i < 100000; $i++) {
                        $temp = md5($i);
                    }
                    $exec_time = microtime(true) - $start_time;
                    
                    echo '<tr>';
                    echo '<td><strong>CPU Performance</strong></td>';
                    echo '<td><span class="badge badge-info">Tested</span></td>';
                    echo '<td>100K MD5 hashes: ' . round($exec_time * 1000, 2) . ' ms</td>';
                    echo '</tr>';
                    
                    // String operations
                    $start_time = microtime(true);
                    $str = '';
                    for ($i = 0; $i < 10000; $i++) {
                        $str .= 'test';
                    }
                    $string_time = microtime(true) - $start_time;
                    
                    echo '<tr>';
                    echo '<td><strong>String Operations</strong></td>';
                    echo '<td><span class="badge badge-info">Tested</span></td>';
                    echo '<td>10K concatenations: ' . round($string_time * 1000, 2) . ' ms</td>';
                    echo '</tr>';
                    
                    echo '</table>';
                    ?>
                </div>
            </div>

            <?php
            // ==========================================
            // SESSION & COOKIES
            // ==========================================
            ?>
            <div class="section">
                <div class="section-header" onclick="toggleSection(this)">
                    <span>🍪 Session & Cookies</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="section-content">
                    <?php
                    echo '<table>';
                    echo '<tr><th>Setting</th><th>Value</th></tr>';
                    
                    $session_settings = [
                        'session.save_handler' => ini_get('session.save_handler'),
                        'session.save_path' => ini_get('session.save_path'),
                        'session.name' => ini_get('session.name'),
                        'session.auto_start' => ini_get('session.auto_start') ? 'On' : 'Off',
                        'session.cookie_lifetime' => ini_get('session.cookie_lifetime') . ' seconds',
                        'session.cookie_path' => ini_get('session.cookie_path'),
                        'session.cookie_domain' => ini_get('session.cookie_domain') ?: 'Not set',
                        'session.cookie_secure' => ini_get('session.cookie_secure') ? 'Yes' : 'No',
                        'session.cookie_httponly' => ini_get('session.cookie_httponly') ? 'Yes' : 'No',
                        'session.use_cookies' => ini_get('session.use_cookies') ? 'Yes' : 'No',
                        'session.gc_maxlifetime' => ini_get('session.gc_maxlifetime') . ' seconds',
                    ];
                    
                    foreach ($session_settings as $key => $value) {
                        echo '<tr>';
                        echo '<td><strong>' . htmlspecialchars($key) . '</strong></td>';
                        echo '<td><code>' . htmlspecialchars($value) . '</code></td>';
                        echo '</tr>';
                    }
                    
                    echo '</table>';
                    
                    // Test session functionality
                    echo '<h3>Session Test:</h3>';
                    $session_works = false;
                    try {
                        if (session_status() == PHP_SESSION_NONE) {
                            @session_start();
                        }
                        $_SESSION['test'] = 'working';
                        $session_works = ($_SESSION['test'] == 'working');
                    } catch (Exception $e) {
                        $session_works = false;
                    }
                    
                    echo '<div class="alert ' . ($session_works ? 'alert-info' : 'alert-danger') . '">';
                    echo $session_works ? '<strong>✓ Sessions are working correctly</strong>' : '<strong>✗ Session functionality is not working</strong>';
                    echo '</div>';
                    ?>
                </div>
            </div>

            <?php
            // ==========================================
            // IMAGE PROCESSING
            // ==========================================
            ?>
            <div class="section">
                <div class="section-header" onclick="toggleSection(this)">
                    <span>🖼️ Image Processing</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="section-content">
                    <?php
                    echo '<table>';
                    echo '<tr><th>Library</th><th>Status</th><th>Details</th></tr>';
                    
                    // GD Library
                    $gd_available = extension_loaded('gd');
                    echo '<tr>';
                    echo '<td><strong>GD Library</strong></td>';
                    echo '<td>' . ($gd_available ? '<span class="badge badge-success">✓ Available</span>' : '<span class="badge badge-danger">✗ Not Available</span>') . '</td>';
                    echo '<td>';
                    if ($gd_available) {
                        $gd_info = gd_info();
                        echo 'Version: ' . ($gd_info['GD Version'] ?? 'Unknown');
                    } else {
                        echo 'Not installed';
                    }
                    echo '</td>';
                    echo '</tr>';
                    
                    if ($gd_available) {
                        $gd_info = gd_info();
                        $formats = [
                            'JPEG Support' => $gd_info['JPEG Support'] ?? false,
                            'PNG Support' => $gd_info['PNG Support'] ?? false,
                            'GIF Create Support' => $gd_info['GIF Create Support'] ?? false,
                            'WebP Support' => $gd_info['WebP Support'] ?? false,
                            'FreeType Support' => $gd_info['FreeType Support'] ?? false,
                        ];
                        
                        foreach ($formats as $format => $supported) {
                            echo '<tr>';
                            echo '<td><strong>' . $format . '</strong></td>';
                            echo '<td colspan="2">' . ($supported ? '<span class="badge badge-success">✓ Supported</span>' : '<span class="badge badge-danger">✗ Not Supported</span>') . '</td>';
                            echo '</tr>';
                        }
                    }
                    
                    // ImageMagick
                    $imagick_available = extension_loaded('imagick');
                    echo '<tr>';
                    echo '<td><strong>ImageMagick</strong></td>';
                    echo '<td>' . ($imagick_available ? '<span class="badge badge-success">✓ Available</span>' : '<span class="badge badge-danger">✗ Not Available</span>') . '</td>';
                    echo '<td>' . ($imagick_available ? 'Advanced image processing available' : 'Not installed') . '</td>';
                    echo '</tr>';
                    
                    // Exif
                    $exif_available = extension_loaded('exif');
                    echo '<tr>';
                    echo '<td><strong>EXIF Support</strong></td>';
                    echo '<td>' . ($exif_available ? '<span class="badge badge-success">✓ Available</span>' : '<span class="badge badge-danger">✗ Not Available</span>') . '</td>';
                    echo '<td>' . ($exif_available ? 'Can read image metadata' : 'Not available') . '</td>';
                    echo '</tr>';
                    
                    echo '</table>';
                    ?>
                </div>
            </div>

            <?php
            // ==========================================
            // COMPRESSION & ARCHIVES
            // ==========================================
            ?>
            <div class="section">
                <div class="section-header" onclick="toggleSection(this)">
                    <span>📦 Compression & Archives</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="section-content">
                    <?php
                    echo '<table>';
                    echo '<tr><th>Format</th><th>Status</th><th>Functions</th></tr>';
                    
                    // ZIP
                    $zip_available = extension_loaded('zip');
                    echo '<tr>';
                    echo '<td><strong>ZIP</strong></td>';
                    echo '<td>' . ($zip_available ? '<span class="badge badge-success">✓ Available</span>' : '<span class="badge badge-danger">✗ Not Available</span>') . '</td>';
                    echo '<td>' . ($zip_available ? 'ZipArchive class available' : 'Not available') . '</td>';
                    echo '</tr>';
                    
                    // ZLIB
                    $zlib_available = extension_loaded('zlib');
                    echo '<tr>';
                    echo '<td><strong>ZLIB (gzip)</strong></td>';
                    echo '<td>' . ($zlib_available ? '<span class="badge badge-success">✓ Available</span>' : '<span class="badge badge-danger">✗ Not Available</span>') . '</td>';
                    echo '<td>' . ($zlib_available ? 'gzcompress, gzuncompress available' : 'Not available') . '</td>';
                    echo '</tr>';
                    
                    // BZ2
                    $bz2_available = extension_loaded('bz2');
                    echo '<tr>';
                    echo '<td><strong>BZ2 (bzip2)</strong></td>';
                    echo '<td>' . ($bz2_available ? '<span class="badge badge-success">✓ Available</span>' : '<span class="badge badge-danger">✗ Not Available</span>') . '</td>';
                    echo '<td>' . ($bz2_available ? 'bzcompress, bzdecompress available' : 'Not available') . '</td>';
                    echo '</tr>';
                    
                    // RAR
                    $rar_available = extension_loaded('rar');
                    echo '<tr>';
                    echo '<td><strong>RAR</strong></td>';
                    echo '<td>' . ($rar_available ? '<span class="badge badge-success">✓ Available</span>' : '<span class="badge badge-danger">✗ Not Available</span>') . '</td>';
                    echo '<td>' . ($rar_available ? 'RAR archive support' : 'Not available') . '</td>';
                    echo '</tr>';
                    
                    echo '</table>';
                    ?>
                </div>
            </div>

            <?php
            // ==========================================
            // CACHING SYSTEMS
            // ==========================================
            ?>
            <div class="section">
                <div class="section-header" onclick="toggleSection(this)">
                    <span>💾 Caching Systems</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="section-content">
                    <?php
                    echo '<table>';
                    echo '<tr><th>Cache System</th><th>Status</th><th>Details</th></tr>';
                    
                    // OPcache
                    $opcache_available = extension_loaded('Zend OPcache');
                    $opcache_enabled = false;
                    if ($opcache_available) {
                        $opcache_enabled = ini_get('opcache.enable');
                    }
                    echo '<tr>';
                    echo '<td><strong>OPcache</strong></td>';
                    echo '<td>' . ($opcache_enabled ? '<span class="badge badge-success">✓ Enabled</span>' : ($opcache_available ? '<span class="badge badge-warning">Available but disabled</span>' : '<span class="badge badge-danger">✗ Not Available</span>')) . '</td>';
                    echo '<td>' . ($opcache_enabled ? 'PHP opcode caching active' : 'Not active') . '</td>';
                    echo '</tr>';
                    
                    // APCu
                    $apcu_available = extension_loaded('apcu');
                    echo '<tr>';
                    echo '<td><strong>APCu</strong></td>';
                    echo '<td>' . ($apcu_available ? '<span class="badge badge-success">✓ Available</span>' : '<span class="badge badge-danger">✗ Not Available</span>') . '</td>';
                    echo '<td>' . ($apcu_available ? 'User cache available' : 'Not available') . '</td>';
                    echo '</tr>';
                    
                    // Memcached
                    $memcached_available = extension_loaded('memcached');
                    echo '<tr>';
                    echo '<td><strong>Memcached</strong></td>';
                    echo '<td>' . ($memcached_available ? '<span class="badge badge-success">✓ Available</span>' : '<span class="badge badge-danger">✗ Not Available</span>') . '</td>';
                    echo '<td>' . ($memcached_available ? 'Distributed caching available' : 'Not available') . '</td>';
                    echo '</tr>';
                    
                    // Redis
                    $redis_available = extension_loaded('redis');
                    echo '<tr>';
                    echo '<td><strong>Redis</strong></td>';
                    echo '<td>' . ($redis_available ? '<span class="badge badge-success">✓ Available</span>' : '<span class="badge badge-danger">✗ Not Available</span>') . '</td>';
                    echo '<td>' . ($redis_available ? 'Redis caching available' : 'Not available') . '</td>';
                    echo '</tr>';
                    
                    echo '</table>';
                    ?>
                </div>
            </div>

            <?php
            // ==========================================
            // SERVER ENVIRONMENT VARIABLES
            // ==========================================
            ?>
            <div class="section">
                <div class="section-header" onclick="toggleSection(this)">
                    <span>🌍 Server Environment Variables</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="section-content">
                    <?php
                    echo '<table>';
                    echo '<tr><th>Variable</th><th>Value</th></tr>';
                    
                    $important_vars = [
                        'SERVER_SOFTWARE', 'SERVER_NAME', 'SERVER_ADDR', 'SERVER_PORT',
                        'DOCUMENT_ROOT', 'SCRIPT_FILENAME', 'REQUEST_METHOD', 'QUERY_STRING',
                        'HTTP_HOST', 'HTTP_USER_AGENT', 'REMOTE_ADDR', 'REMOTE_PORT',
                        'SERVER_PROTOCOL', 'REQUEST_TIME', 'HTTPS'
                    ];
                    
                    foreach ($important_vars as $var) {
                        if (isset($_SERVER[$var])) {
                            echo '<tr>';
                            echo '<td><strong>' . htmlspecialchars($var) . '</strong></td>';
                            echo '<td><code style="word-break: break-all;">' . htmlspecialchars($_SERVER[$var]) . '</code></td>';
                            echo '</tr>';
                        }
                    }
                    
                    echo '</table>';
                    ?>
                </div>
            </div>

            <?php
            // ==========================================
            // INFINITYFREE SPECIFIC CHECKS
            // ==========================================
            ?>
            <div class="section">
                <div class="section-header" onclick="toggleSection(this)">
                    <span>🚀 InfinityFree Specific Information</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="section-content">
                    <div class="alert alert-info">
                        <strong>📋 InfinityFree Hosting Limitations:</strong>
                        <ul>
                            <li><strong>Disk Space:</strong> Unlimited (with fair usage policy)</li>
                            <li><strong>Bandwidth:</strong> Unlimited (with fair usage policy)</li>
                            <li><strong>MySQL Databases:</strong> Unlimited databases</li>
                            <li><strong>FTP Accounts:</strong> Unlimited</li>
                            <li><strong>Email Accounts:</strong> Not available on free plan</li>
                            <li><strong>Cron Jobs:</strong> Not available on free plan</li>
                            <li><strong>SSL:</strong> Free SSL certificates available</li>
                        </ul>
                    </div>
                    
                    <div class="alert alert-warning">
                        <strong>⚠️ Known Restrictions:</strong>
                        <ul>
                            <li>Some functions may be disabled for security (check Disabled Functions section)</li>
                            <li>Email sending via mail() is severely limited - use SMTP instead</li>
                            <li>Execution time limits are enforced</li>
                            <li>CPU and memory usage is monitored</li>
                            <li>External connections may be restricted</li>
                            <li>File uploads are limited in size</li>
                        </ul>
                    </div>
                    
                    <h3>Recommended Tools & Libraries:</h3>
                    <table>
                        <tr>
                            <th>Purpose</th>
                            <th>Recommended Solution</th>
                            <th>Why</th>
                        </tr>
                        <tr>
                            <td><strong>Email Sending</strong></td>
                            <td>PHPMailer with Gmail SMTP</td>
                            <td>Reliable delivery, avoid spam folders</td>
                        </tr>
                        <tr>
                            <td><strong>Database Management</strong></td>
                            <td>phpMyAdmin via VistaPanel</td>
                            <td>Official access method provided by InfinityFree</td>
                        </tr>
                        <tr>
                            <td><strong>File Management</strong></td>
                            <td>FTP/SFTP or File Manager</td>
                            <td>Reliable file operations</td>
                        </tr>
                        <tr>
                            <td><strong>Caching</strong></td>
                            <td>File-based caching</td>
                            <td>Memcached/Redis not available</td>
                        </tr>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script>
        function toggleSection(header) {
            const content = header.nextElementSibling;
            const icon = header.querySelector('.toggle-icon');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                icon.classList.remove('rotated');
            } else {
                content.style.display = 'none';
                icon.classList.add('rotated');
            }
        }
        
        // All sections open by default, but you can close them
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Server Diagnostics Report loaded successfully');
        });
    </script>
</body>
</html>
<?php
ob_end_flush();
?>
