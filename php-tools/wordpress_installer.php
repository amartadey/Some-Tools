<?php
/**
 * WordPress Latest Version Downloader with Animation
 * Downloads, extracts, and optionally deletes the WordPress zip file
 */

// Configuration
$wordpressUrl = 'https://wordpress.org/latest.zip';
$zipFile = 'wordpress-latest.zip';
$extractTo = './'; // Extract to current directory

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'download':
            echo json_encode(downloadWordPress($wordpressUrl, $zipFile));
            break;
            
        case 'extract':
            echo json_encode(extractZip($zipFile, $extractTo));
            break;
            
        case 'delete':
            echo json_encode(deleteZipFile($zipFile));
            break;
            
        case 'delete_self':
            echo json_encode(deleteSelf());
            break;
            
        case 'progress':
            // Check if file exists and return progress
            if (file_exists($zipFile)) {
                echo json_encode([
                    'status' => 'downloading',
                    'size' => filesize($zipFile)
                ]);
            } else {
                echo json_encode(['status' => 'not_started']);
            }
            break;
    }
    exit;
}

// Functions
function downloadWordPress($url, $destination) {
    if (file_exists($destination)) {
        unlink($destination);
    }
    
    $ch = curl_init($url);
    $fp = fopen($destination, 'wb');
    
    if ($fp === false) {
        return [
            'success' => false,
            'message' => 'Unable to create file. Check write permissions.'
        ];
    }
    
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    fclose($fp);
    
    if ($result === false || $httpCode != 200) {
        if (file_exists($destination)) {
            unlink($destination);
        }
        return [
            'success' => false,
            'message' => "Download failed: $error (HTTP $httpCode)"
        ];
    }
    
    $fileSize = filesize($destination);
    $fileSizeMB = round($fileSize / (1024 * 1024), 2);
    
    return [
        'success' => true,
        'message' => "Download completed successfully! ($fileSizeMB MB)",
        'size' => $fileSizeMB
    ];
}

function extractZip($zipFile, $extractTo) {
    if (!file_exists($zipFile)) {
        return [
            'success' => false,
            'message' => 'Zip file not found.'
        ];
    }
    
    if (!class_exists('ZipArchive')) {
        return [
            'success' => false,
            'message' => 'ZipArchive extension not available on this server.'
        ];
    }
    
    $zip = new ZipArchive;
    $res = $zip->open($zipFile);
    
    if ($res !== true) {
        return [
            'success' => false,
            'message' => 'Failed to open zip file.'
        ];
    }
    
    $zip->extractTo($extractTo);
    $numFiles = $zip->numFiles;
    $zip->close();
    
    return [
        'success' => true,
        'message' => "Extracted $numFiles files successfully!",
        'files' => $numFiles
    ];
}

function deleteZipFile($zipFile) {
    if (!file_exists($zipFile)) {
        return [
            'success' => false,
            'message' => 'Zip file not found.'
        ];
    }
    
    if (unlink($zipFile)) {
        return [
            'success' => true,
            'message' => 'Zip file deleted successfully!'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to delete zip file.'
        ];
    }
}

function deleteSelf() {
    $selfFile = __FILE__;
    
    // Use a delayed deletion approach
    // This will be executed after the response is sent
    if (unlink($selfFile)) {
        return [
            'success' => true,
            'message' => 'Installer script deleted successfully!'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to delete installer script. You can manually delete it.'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordPress Downloader & Installer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 32px;
            text-align: center;
        }
        
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
            font-size: 60px;
        }
        
        .status-box {
            background: #f7f9fc;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            min-height: 100px;
        }
        
        .status-message {
            color: #555;
            line-height: 1.6;
            font-size: 15px;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            margin: 15px 0;
            display: none;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            width: 0%;
            transition: width 0.3s ease;
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
            display: none;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        button {
            flex: 1;
            min-width: 150px;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
        }
        
        .success-icon {
            color: #38ef7d;
            font-size: 50px;
            text-align: center;
            margin: 20px 0;
            display: none;
            animation: scaleIn 0.5s ease;
        }
        
        @keyframes scaleIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .error-icon {
            color: #ff6a00;
            font-size: 50px;
            text-align: center;
            margin: 20px 0;
            display: none;
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
            padding: 15px;
            background: #f7f9fc;
            border-radius: 10px;
        }
        
        .checkbox-container input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .checkbox-container label {
            cursor: pointer;
            user-select: none;
            color: #555;
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 14px;
            color: #555;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🚀</div>
        <h1>WordPress Installer</h1>
        <p class="subtitle">Download, extract, and install WordPress with one click</p>
        
        <div class="status-box">
            <div class="spinner" id="spinner"></div>
            <div class="success-icon" id="successIcon">✓</div>
            <div class="error-icon" id="errorIcon">✗</div>
            <div class="status-message" id="statusMessage">
                Ready to download WordPress. Click the button below to start.
            </div>
            <div class="progress-bar" id="progressBar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
        </div>
        
        <div class="checkbox-container">
            <input type="checkbox" id="deleteZip" checked>
            <label for="deleteZip">Delete zip file after extraction</label>
        </div>
        
        <div class="checkbox-container">
            <input type="checkbox" id="deleteInstaller" checked>
            <label for="deleteInstaller">Delete this installer script after completion</label>
        </div>
        
        <div class="button-group">
            <button class="btn-primary" id="downloadBtn" onclick="startProcess()">
                Download & Install
            </button>
        </div>
        
        <div class="info-box" id="infoBox">
            <strong>Installation Complete!</strong><br>
            WordPress has been extracted to the current directory. You can now proceed with the WordPress installation by visiting your website.
        </div>
    </div>

    <script>
        let deleteAfterExtract = true;
        let deleteInstallerAfterCompletion = true;
        
        document.getElementById('deleteZip').addEventListener('change', function() {
            deleteAfterExtract = this.checked;
        });
        
        document.getElementById('deleteInstaller').addEventListener('change', function() {
            deleteInstallerAfterCompletion = this.checked;
        });
        
        function updateStatus(message, type = 'info') {
            const statusMessage = document.getElementById('statusMessage');
            const successIcon = document.getElementById('successIcon');
            const errorIcon = document.getElementById('errorIcon');
            const spinner = document.getElementById('spinner');
            
            statusMessage.textContent = message;
            
            successIcon.style.display = 'none';
            errorIcon.style.display = 'none';
            
            if (type === 'success') {
                successIcon.style.display = 'block';
            } else if (type === 'error') {
                errorIcon.style.display = 'block';
            }
        }
        
        function showSpinner(show) {
            document.getElementById('spinner').style.display = show ? 'block' : 'none';
        }
        
        function showProgress(show) {
            document.getElementById('progressBar').style.display = show ? 'block' : 'none';
        }
        
        function setProgress(percent) {
            document.getElementById('progressFill').style.width = percent + '%';
        }
        
        async function startProcess() {
            const downloadBtn = document.getElementById('downloadBtn');
            downloadBtn.disabled = true;
            
            // Step 1: Download
            updateStatus('Downloading WordPress... Please wait.');
            showSpinner(true);
            showProgress(true);
            setProgress(30);
            
            try {
                const downloadResponse = await fetch('?action=download');
                const downloadResult = await downloadResponse.json();
                
                if (!downloadResult.success) {
                    throw new Error(downloadResult.message);
                }
                
                setProgress(50);
                updateStatus('Download complete! Now extracting files...');
                
                // Step 2: Extract
                await new Promise(resolve => setTimeout(resolve, 500)); // Brief pause for UX
                setProgress(70);
                
                const extractResponse = await fetch('?action=extract');
                const extractResult = await extractResponse.json();
                
                if (!extractResult.success) {
                    throw new Error(extractResult.message);
                }
                
                setProgress(90);
                updateStatus('Extraction complete!');
                
                // Step 3: Delete (if enabled)
                if (deleteAfterExtract) {
                    await new Promise(resolve => setTimeout(resolve, 500));
                    const deleteResponse = await fetch('?action=delete');
                    const deleteResult = await deleteResponse.json();
                    
                    if (deleteResult.success) {
                        updateStatus('WordPress installed successfully! Zip file has been deleted.', 'success');
                    } else {
                        updateStatus('WordPress installed successfully! (Could not delete zip file)', 'success');
                    }
                } else {
                    updateStatus('WordPress installed successfully! Zip file preserved.', 'success');
                }
                
                setProgress(100);
                showSpinner(false);
                document.getElementById('infoBox').style.display = 'block';
                
                // Step 4: Delete installer script (if enabled)
                if (deleteInstallerAfterCompletion) {
                    await new Promise(resolve => setTimeout(resolve, 1000));
                    updateStatus('Cleaning up... Deleting installer script.', 'success');
                    
                    try {
                        const deleteSelfResponse = await fetch('?action=delete_self');
                        const deleteSelfResult = await deleteSelfResponse.json();
                        
                        if (deleteSelfResult.success) {
                            updateStatus('All done! Installer script has been removed. This page will no longer be accessible.', 'success');
                            
                            // Show a final message and redirect or disable further actions
                            setTimeout(() => {
                                alert('Installation complete! The installer has been deleted. You can now close this page.');
                                // Optionally redirect to home page
                                // window.location.href = '/';
                            }, 2000);
                        } else {
                            updateStatus('Installation complete! Please manually delete this installer file for security.', 'success');
                        }
                    } catch (error) {
                        updateStatus('Installation complete! Please manually delete this installer file for security.', 'success');
                    }
                }
                
            } catch (error) {
                updateStatus('Error: ' + error.message, 'error');
                showSpinner(false);
                setProgress(0);
                downloadBtn.disabled = false;
            }
        }
    </script>
</body>
</html>
