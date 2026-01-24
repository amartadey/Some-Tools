#!/usr/bin/env python3
"""
Inode Counter Tool
Counts files and directories (inodes) in a folder and its subfolders.
Useful for estimating inode usage before hosting on Linux servers.
"""

import os
import sys
from pathlib import Path
from collections import defaultdict
import time
import itertools
import threading

# Spinner animation characters
SPINNER_CHARS = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏']
# Alternative spinners you can use:
# SPINNER_CHARS = ['|', '/', '-', '\\']
# SPINNER_CHARS = ['◐', '◓', '◑', '◒']
# SPINNER_CHARS = ['⣾', '⣽', '⣻', '⢿', '⡿', '⣟', '⣯', '⣷']

class ProgressSpinner:
    """Animated spinner for showing progress."""
    
    def __init__(self):
        self.spinner = itertools.cycle(SPINNER_CHARS)
        self.running = False
        self.thread = None
        self.message = ""
        self.stats = {'files': 0, 'dirs': 0, 'total': 0}
        self.start_time = None
    
    def spin(self):
        """Spinner animation loop."""
        while self.running:
            elapsed = time.time() - self.start_time if self.start_time else 0
            elapsed_str = f"{int(elapsed)}s"
            
            # Create progress message
            progress_msg = (
                f"\r  {next(self.spinner)} Scanning... "
                f"Files: {format_number(self.stats['files'])} | "
                f"Dirs: {format_number(self.stats['dirs'])} | "
                f"Total: {format_number(self.stats['total'])} | "
                f"Time: {elapsed_str}  "
            )
            
            sys.stdout.write(progress_msg)
            sys.stdout.flush()
            time.sleep(0.1)
    
    def start(self, message="Processing..."):
        """Start the spinner."""
        self.message = message
        self.running = True
        self.start_time = time.time()
        self.thread = threading.Thread(target=self.spin)
        self.thread.daemon = True
        self.thread.start()
    
    def update_stats(self, files, dirs, total):
        """Update statistics displayed in spinner."""
        self.stats['files'] = files
        self.stats['dirs'] = dirs
        self.stats['total'] = total
    
    def stop(self):
        """Stop the spinner."""
        self.running = False
        if self.thread:
            self.thread.join()
        sys.stdout.write('\r' + ' ' * 100 + '\r')  # Clear the line
        sys.stdout.flush()

def format_number(num):
    """Format number with thousand separators."""
    return f"{num:,}"

def get_file_extension(filepath):
    """Get file extension in lowercase."""
    ext = os.path.splitext(filepath)[1].lower()
    return ext if ext else 'no extension'

def count_inodes(directory_path, show_progress=True):
    """
    Count all files and directories (inodes) in the given directory.
    
    Args:
        directory_path: Path to the directory to scan
        show_progress: Whether to show progress during scanning
    
    Returns:
        Dictionary with detailed statistics
    """
    stats = {
        'total_inodes': 0,
        'total_files': 0,
        'total_directories': 0,
        'total_size': 0,
        'file_types': defaultdict(int),
        'largest_files': [],
        'deepest_path': '',
        'max_depth': 0,
        'errors': []
    }
    
    print(f"\n🔍 Scanning directory: {directory_path}")
    print("=" * 70)
    
    # Start spinner
    spinner = ProgressSpinner()
    if show_progress:
        spinner.start("Scanning files and directories...")
    
    start_time = time.time()
    
    try:
        for root, dirs, files in os.walk(directory_path):
            # Calculate depth
            depth = root[len(str(directory_path)):].count(os.sep)
            if depth > stats['max_depth']:
                stats['max_depth'] = depth
                stats['deepest_path'] = root
            
            # Count directories (each directory is an inode)
            stats['total_directories'] += len(dirs)
            stats['total_inodes'] += len(dirs)
            
            # Count files (each file is an inode)
            for file in files:
                stats['total_files'] += 1
                stats['total_inodes'] += 1
                
                filepath = os.path.join(root, file)
                
                try:
                    # Get file size
                    file_size = os.path.getsize(filepath)
                    stats['total_size'] += file_size
                    
                    # Track file extension
                    ext = get_file_extension(file)
                    stats['file_types'][ext] += 1
                    
                    # Track largest files (keep top 10)
                    stats['largest_files'].append((filepath, file_size))
                    if len(stats['largest_files']) > 10:
                        stats['largest_files'].sort(key=lambda x: x[1], reverse=True)
                        stats['largest_files'] = stats['largest_files'][:10]
                    
                except (OSError, PermissionError) as e:
                    stats['errors'].append(f"Error accessing {filepath}: {str(e)}")
            
            # Update spinner with current stats
            if show_progress:
                spinner.update_stats(stats['total_files'], stats['total_directories'], stats['total_inodes'])
    
    except Exception as e:
        if show_progress:
            spinner.stop()
        print(f"\n❌ Error scanning directory: {str(e)}")
        stats['errors'].append(f"Fatal error: {str(e)}")
    
    # Stop spinner
    if show_progress:
        spinner.stop()
    
    # Add root directory itself as an inode
    stats['total_inodes'] += 1
    stats['total_directories'] += 1
    
    # Sort largest files
    stats['largest_files'].sort(key=lambda x: x[1], reverse=True)
    
    elapsed_time = time.time() - start_time
    stats['scan_time'] = elapsed_time
    
    # Show completion message
    print(f"✅ Scan complete! Found {format_number(stats['total_inodes'])} inodes in {elapsed_time:.2f}s")
    
    return stats

def format_size(bytes_size):
    """Format bytes to human-readable size."""
    for unit in ['B', 'KB', 'MB', 'GB', 'TB']:
        if bytes_size < 1024.0:
            return f"{bytes_size:.2f} {unit}"
        bytes_size /= 1024.0
    return f"{bytes_size:.2f} PB"

def print_report(stats, directory_path):
    """Print a detailed report of the inode count."""
    print("\n" + "=" * 70)
    print("📊 INODE COUNT REPORT")
    print("=" * 70)
    
    # Summary
    print(f"\n📁 Directory: {directory_path}")
    print(f"⏱️  Scan Time: {stats['scan_time']:.2f} seconds")
    print("\n" + "-" * 70)
    print("SUMMARY")
    print("-" * 70)
    print(f"{'Total Inodes:':<30} {format_number(stats['total_inodes'])}")
    print(f"  {'├─ Files:':<28} {format_number(stats['total_files'])}")
    print(f"  {'└─ Directories:':<28} {format_number(stats['total_directories'])}")
    print(f"{'Total Size:':<30} {format_size(stats['total_size'])}")
    print(f"{'Maximum Depth:':<30} {stats['max_depth']} levels")
    
    # File type breakdown
    if stats['file_types']:
        print("\n" + "-" * 70)
        print("FILE TYPES (Top 15)")
        print("-" * 70)
        sorted_types = sorted(stats['file_types'].items(), key=lambda x: x[1], reverse=True)[:15]
        for ext, count in sorted_types:
            percentage = (count / stats['total_files']) * 100 if stats['total_files'] > 0 else 0
            print(f"  {ext:<20} {format_number(count):>10} files ({percentage:>5.1f}%)")
    
    # Largest files
    if stats['largest_files']:
        print("\n" + "-" * 70)
        print("LARGEST FILES (Top 10)")
        print("-" * 70)
        for i, (filepath, size) in enumerate(stats['largest_files'][:10], 1):
            # Show relative path if possible
            try:
                rel_path = os.path.relpath(filepath, directory_path)
            except:
                rel_path = filepath
            print(f"  {i:2}. {format_size(size):>12} - {rel_path}")
    
    # Deepest path
    if stats['deepest_path']:
        print("\n" + "-" * 70)
        print("DEEPEST PATH")
        print("-" * 70)
        try:
            rel_path = os.path.relpath(stats['deepest_path'], directory_path)
        except:
            rel_path = stats['deepest_path']
        print(f"  Depth: {stats['max_depth']} levels")
        print(f"  Path: {rel_path}")
    
    # Linux server recommendations
    print("\n" + "=" * 70)
    print("🐧 LINUX SERVER RECOMMENDATIONS")
    print("=" * 70)
    
    inode_count = stats['total_inodes']
    
    if inode_count < 10000:
        status = "✅ EXCELLENT"
        recommendation = "Your project uses very few inodes. No concerns for most hosting."
    elif inode_count < 50000:
        status = "✅ GOOD"
        recommendation = "Moderate inode usage. Should work fine on most shared hosting."
    elif inode_count < 100000:
        status = "⚠️  MODERATE"
        recommendation = "Higher inode usage. Check your hosting plan's inode limits."
    elif inode_count < 250000:
        status = "⚠️  HIGH"
        recommendation = "High inode usage. May exceed limits on shared hosting. Consider VPS."
    else:
        status = "🚨 VERY HIGH"
        recommendation = "Very high inode usage. VPS or dedicated server recommended."
    
    print(f"\nStatus: {status}")
    print(f"Total Inodes: {format_number(inode_count)}")
    print(f"\n💡 Recommendation:")
    print(f"   {recommendation}")
    
    print("\n📋 Common Hosting Inode Limits:")
    print("   • Shared Hosting (Basic):     50,000 - 100,000 inodes")
    print("   • Shared Hosting (Premium):   100,000 - 250,000 inodes")
    print("   • VPS/Cloud:                  250,000 - 500,000+ inodes")
    print("   • Dedicated Server:           Usually unlimited")
    
    # Errors
    if stats['errors']:
        print("\n" + "-" * 70)
        print(f"⚠️  ERRORS ENCOUNTERED ({len(stats['errors'])})")
        print("-" * 70)
        for error in stats['errors'][:10]:  # Show first 10 errors
            print(f"  • {error}")
        if len(stats['errors']) > 10:
            print(f"  ... and {len(stats['errors']) - 10} more errors")
    
    print("\n" + "=" * 70)

def save_report(stats, directory_path, output_file):
    """Save the report to a text file."""
    try:
        with open(output_file, 'w', encoding='utf-8') as f:
            f.write("=" * 70 + "\n")
            f.write("INODE COUNT REPORT\n")
            f.write("=" * 70 + "\n\n")
            f.write(f"Directory: {directory_path}\n")
            f.write(f"Scan Date: {time.strftime('%Y-%m-%d %H:%M:%S')}\n")
            f.write(f"Scan Time: {stats['scan_time']:.2f} seconds\n\n")
            
            f.write("-" * 70 + "\n")
            f.write("SUMMARY\n")
            f.write("-" * 70 + "\n")
            f.write(f"Total Inodes:      {format_number(stats['total_inodes'])}\n")
            f.write(f"  Files:           {format_number(stats['total_files'])}\n")
            f.write(f"  Directories:     {format_number(stats['total_directories'])}\n")
            f.write(f"Total Size:        {format_size(stats['total_size'])}\n")
            f.write(f"Maximum Depth:     {stats['max_depth']} levels\n\n")
            
            if stats['file_types']:
                f.write("-" * 70 + "\n")
                f.write("FILE TYPES\n")
                f.write("-" * 70 + "\n")
                sorted_types = sorted(stats['file_types'].items(), key=lambda x: x[1], reverse=True)
                for ext, count in sorted_types:
                    percentage = (count / stats['total_files']) * 100 if stats['total_files'] > 0 else 0
                    f.write(f"{ext:<20} {format_number(count):>10} files ({percentage:>5.1f}%)\n")
            
            f.write("\n")
        
        print(f"\n✅ Report saved to: {output_file}")
        return True
    except Exception as e:
        print(f"\n❌ Error saving report: {str(e)}")
        return False

def main():
    """Main function."""
    print("=" * 70)
    print("🔢 INODE COUNTER TOOL")
    print("=" * 70)
    print("Count files and directories for Linux server inode estimation")
    print()
    
    # Get directory path
    if len(sys.argv) > 1:
        directory_path = sys.argv[1]
    else:
        directory_path = input("📁 Enter directory path to scan (or press Enter for current directory): ").strip()
        if not directory_path:
            directory_path = os.getcwd()
    
    # Validate path
    if not os.path.exists(directory_path):
        print(f"\n❌ Error: Directory '{directory_path}' does not exist!")
        return
    
    if not os.path.isdir(directory_path):
        print(f"\n❌ Error: '{directory_path}' is not a directory!")
        return
    
    # Convert to absolute path
    directory_path = os.path.abspath(directory_path)
    
    # Count inodes
    stats = count_inodes(directory_path)
    
    # Print report
    print_report(stats, directory_path)
    
    # Ask to save report
    save_option = input("\n💾 Save report to file? (y/n): ").strip().lower()
    if save_option == 'y':
        default_filename = f"inode_report_{time.strftime('%Y%m%d_%H%M%S')}.txt"
        output_file = input(f"   Enter filename (default: {default_filename}): ").strip()
        if not output_file:
            output_file = default_filename
        
        save_report(stats, directory_path, output_file)
    
    print("\n✅ Scan complete!")

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\n\n⚠️  Scan interrupted by user.")
        sys.exit(0)
    except Exception as e:
        print(f"\n❌ Unexpected error: {str(e)}")
        sys.exit(1)
