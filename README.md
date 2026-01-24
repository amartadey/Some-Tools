# 🛠️ Development Tools Collection

A curated collection of professional development utilities for PHP and Python developers. These tools help with server diagnostics, email testing, and image optimization.

## 📋 Table of Contents

- [Tools Overview](#tools-overview)
- [Installation](#installation)
- [Usage](#usage)
- [Contributing](#contributing)
- [License](#license)

## 🎯 Tools Overview

### PHP Tools

#### 📧 Email Diagnostic Tool v2.0
**File:** `php-tools/email_diagnostic.php`

A comprehensive PHP script that thoroughly tests email functionality with advanced security and diagnostics.

**Features:**
- 🔒 Password protection with session management
- 🗑️ Self-delete functionality for security
- ✉️ SPF record validation
- 🔐 DMARC policy checking
- 📝 DKIM guidance
- 🚫 Blacklist checker (Spamhaus, SpamCop, Barracuda, SORBS)
- 🔄 Reverse DNS (PTR) verification
- 📊 Email deliverability score (0-100)
- ✅ Tests PHP mail() function availability
- ✅ Validates SMTP configuration
- ✅ Checks DNS and MX records
- ✅ Tests port connectivity (25, 587, 465, 2525)
- ✅ Sends test emails (text and HTML)
- 📋 Copy to clipboard & JSON export
- 🎨 Compressed, modern UI

**Use Cases:**
- Debugging email delivery issues
- Testing email authentication (SPF/DKIM/DMARC)
- Checking if server IP is blacklisted
- Validating hosting provider email capabilities
- Getting overall deliverability health score

---

#### 🔍 Server Diagnostics Tool
**File:** `php-tools/server_diagnostics.php`

An all-in-one server diagnostics tool that provides comprehensive insights into server capabilities.

**Features:**
- ✅ Basic server information (PHP version, OS, server software)
- ✅ PHP configuration and limits (memory, execution time, upload sizes)
- ✅ Loaded PHP extensions with status indicators
- ✅ Database connectivity testing (MySQL, PDO)
- ✅ phpMyAdmin availability checker
- ✅ Email functionality testing
- ✅ File system permissions testing
- ✅ Network and external connection testing (cURL, fsockopen)
- ✅ DNS capabilities
- ✅ Security settings and restrictions
- ✅ Session handling
- ✅ Image processing capabilities
- ✅ Compression support
- ✅ Environment variables

**Use Cases:**
- Evaluating new hosting providers
- Debugging server configuration issues
- Checking compatibility for web applications
- Identifying hosting limitations
- Security auditing

---

### Python Tools

#### 🖼️ Image Optimizer
**File:** `python-tools/image-optimize.py`

A Python script that batch optimizes images in a directory, reducing file sizes while maintaining quality.

**Features:**
- ✅ Supports multiple formats (JPG, PNG, WebP, BMP, TIFF)
- ✅ Resizes images to target dimensions (default 1000x1000)
- ✅ Adjustable quality settings (default 85%)
- ✅ Converts PNG to JPG for better compression
- ✅ Creates optimized_images output folder
- ✅ Shows detailed statistics (original vs optimized sizes)
- ✅ Displays percentage reduction for each image
- ✅ Calculates total space savings

**Use Cases:**
- Optimizing website images for faster loading
- Batch processing photo galleries
- Reducing storage space for image collections
- Preparing images for web deployment

---

#### 🔢 Inode Counter
**File:** `python-tools/inode-counter.py`

A Python script that counts files and directories (inodes) to estimate Linux server hosting requirements.

**Features:**
- 🎯 Animated progress spinner with real-time stats
- 📊 Counts all files and directories (inodes)
- 📈 File type breakdown with percentages
- 📁 Shows largest files (top 10)
- 📏 Calculates total size
- 🔍 Measures directory depth
- 🐧 Provides Linux hosting recommendations
- 💾 Export report to text file
- ⚡ Progress indicator for large scans

**Use Cases:**
- Check inode count before uploading to shared hosting
- Identify folders with too many files
- Estimate hosting requirements
- Find large files to optimize
- Analyze project structure before deployment

## 📥 Installation

### PHP Tools

1. Download the desired PHP tool from the `php-tools/` directory
2. Upload the `.php` file to your web server
3. Access the file through your web browser (e.g., `https://yoursite.com/email_diagnostic.php`)

### Python Tools

1. Download the desired Python tool from the `python-tools/` directory
2. Install required dependencies (if needed):
   ```bash
   # For Image Optimizer only
   pip install Pillow
   
   # Inode Counter has no dependencies - ready to use!
   ```
3. Run the script:
   ```bash
   python image-optimize.py
   # or
   python inode-counter.py
   ```

## 🚀 Usage

### Email Diagnostic Tool

```bash
# Upload to your server
# Access via browser: https://yoursite.com/email_diagnostic.php
# Configure test email in the script (line 10):
$TEST_EMAIL = 'your-email@example.com';
```

### Server Diagnostics Tool

```bash
# Upload to your server
# Access via browser: https://yoursite.com/server_diagnostics.php
# No configuration needed - runs automatically
```

### Image Optimizer

```bash
# Place the script in a directory with images
python image-optimize.py

# Customize settings in the script:
optimize_images(
    quality=85,              # Adjust quality (1-100)
    target_size=(1000, 1000) # Resize dimensions
)
```

## 📖 Documentation

For detailed documentation and live demos, visit our [GitHub Pages site](https://YOUR-USERNAME.github.io/Some-Tools/).

## 🤝 Contributing

Contributions are welcome! If you have a useful development tool to add:

1. Fork the repository
2. Create a new branch (`git checkout -b feature/new-tool`)
3. Add your tool to the appropriate folder (`php-tools/` or `python-tools/`)
4. Update this README with tool description
5. Submit a pull request

## 📝 License

This project is open source and available under the MIT License.

## 🌟 Support

If you find these tools helpful, please consider:
- ⭐ Starring this repository
- 🐛 Reporting bugs or issues
- 💡 Suggesting new features or tools
- 🔀 Contributing your own tools

## 📧 Contact

For questions or suggestions, please open an issue on GitHub.

---

**Made with ❤️ for the developer community**
