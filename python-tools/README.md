# Python Development Tools

This folder contains Python-based development utilities for image optimization, file analysis, and automation.

## Tools

### 🖼️ Image Optimizer
**File:** `image-optimize.py`

Batch image optimization tool that reduces file sizes while maintaining quality.

**Quick Start:**
1. Install dependencies:
   ```bash
   pip install Pillow
   ```
2. Place `image-optimize.py` in a folder with your images
3. Run the script:
   ```bash
   python image-optimize.py
   ```
4. Find optimized images in the `optimized_images/` folder

**Customization:**

Edit the script to adjust settings:

```python
optimize_images(
    quality=85,              # Quality: 1-100 (85 is recommended)
    target_size=(1000, 1000) # Target dimensions in pixels
)
```

**Supported Formats:**
- JPEG (.jpg, .jpeg)
- PNG (.png) - automatically converted to JPG
- WebP (.webp)
- BMP (.bmp)
- TIFF (.tiff)

**Features:**
- Resizes images to target dimensions
- Optimizes file size with quality control
- Converts PNG to JPG for better compression
- Shows detailed statistics for each image
- Calculates total space savings
- Preserves original files

**Example Output:**
```
✓ photo1.jpg
  2.5MB → 450KB (82% reduction)

✓ image2.png
  1.8MB → 320KB (82.2% reduction)

==================================================
Optimized 10 images
Total size: 15.2MB → 3.1MB
Total savings: 79.6%

Optimized images saved to 'optimized_images' folder
```

---

### 🔢 Inode Counter
**File:** `inode-counter.py`

Count files and directories (inodes) in a folder to estimate Linux server inode usage.

**Quick Start:**
1. Run the script:
   ```bash
   python inode-counter.py
   ```
2. Enter the directory path to scan (or press Enter for current directory)
3. Review the detailed report

**Alternative - Command Line:**
```bash
python inode-counter.py "C:\path\to\your\folder"
```

**Features:**
- Counts all files and directories (inodes)
- Analyzes file types and extensions
- Shows largest files (top 10)
- Calculates total size
- Measures directory depth
- Provides Linux hosting recommendations
- Export report to text file
- Progress indicator for large scans

**What it Reports:**
- Total inode count (files + directories)
- File type breakdown with percentages
- Largest files with sizes
- Deepest directory path
- Hosting recommendations based on inode count

**Example Output:**
```
📊 INODE COUNT REPORT
======================================================================
Total Inodes:                  15,432
  ├─ Files:                    12,845
  └─ Directories:              2,587
Total Size:                    2.34 GB
Maximum Depth:                 8 levels

FILE TYPES (Top 15)
  .jpg                      3,245 files (25.3%)
  .php                      2,156 files (16.8%)
  .js                       1,834 files (14.3%)

🐧 LINUX SERVER RECOMMENDATIONS
Status: ✅ GOOD
Total Inodes: 15,432

💡 Recommendation:
   Moderate inode usage. Should work fine on most shared hosting.
```

**Use Cases:**
- Check inode count before uploading to shared hosting
- Identify folders with too many files
- Estimate hosting requirements
- Find large files to optimize
- Analyze project structure

---

## Requirements

- Python 3.6 or higher
- Pillow library (`pip install Pillow`)

## Tips

- **Quality 85** provides excellent balance between size and quality
- For web use, **1000x1000** or **1920x1080** are good target sizes
- Always keep original files as backup
- Test with a few images first to find optimal settings

## Support

For issues or questions, please open an issue on the main repository.
