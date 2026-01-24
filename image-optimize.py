from PIL import Image
import os
from pathlib import Path

def optimize_images(quality=85, target_size=(1000, 1000)):
    """
    Optimize all images in the current directory.
    
    Args:
        quality: JPEG quality (1-100, default 85)
        target_size: Target dimensions (width, height) in pixels
    """
    # Supported image formats
    formats = ('.jpg', '.jpeg', '.png', '.webp', '.bmp', '.tiff')
    
    # Create output directory
    output_dir = Path('optimized_images')
    output_dir.mkdir(exist_ok=True)
    
    current_dir = Path('.')
    total_original = 0
    total_optimized = 0
    count = 0
    
    print(f"Starting optimization (quality={quality}, size={target_size})...\n")
    
    for img_file in current_dir.iterdir():
        if img_file.suffix.lower() in formats and img_file.is_file():
            try:
                # Open image
                img = Image.open(img_file)
                original_size = img_file.stat().st_size
                
                # Resize to target size
                img = img.resize(target_size, Image.Resampling.LANCZOS)
                
                # Determine output format and settings
                output_file = output_dir / img_file.name
                save_kwargs = {'optimize': True}
                
                if img_file.suffix.lower() in ('.jpg', '.jpeg'):
                    save_kwargs['quality'] = quality
                elif img_file.suffix.lower() == '.png':
                    img = img.convert('RGB')  # Convert to RGB for better compression
                    output_file = output_file.with_suffix('.jpg')
                    save_kwargs['quality'] = quality
                
                # Save optimized image
                img.save(output_file, **save_kwargs)
                optimized_size = output_file.stat().st_size
                
                # Calculate savings
                reduction = ((original_size - optimized_size) / original_size) * 100
                
                total_original += original_size
                total_optimized += optimized_size
                count += 1
                
                print(f"✓ {img_file.name}")
                print(f"  {original_size/1024:.1f}KB → {optimized_size/1024:.1f}KB "
                      f"({reduction:.1f}% reduction)\n")
                
            except Exception as e:
                print(f"✗ Error processing {img_file.name}: {e}\n")
    
    if count > 0:
        total_reduction = ((total_original - total_optimized) / total_original) * 100
        print(f"{'='*50}")
        print(f"Optimized {count} images")
        print(f"Total size: {total_original/1024/1024:.2f}MB → {total_optimized/1024/1024:.2f}MB")
        print(f"Total savings: {total_reduction:.1f}%")
        print(f"\nOptimized images saved to '{output_dir}' folder")
    else:
        print("No images found to optimize.")

if __name__ == "__main__":
    # Customize these settings:
    optimize_images(
        quality=85,              # Adjust quality (1-100, 85 is good balance)
        target_size=(1000, 1000) # Resize all images to 1000x1000 pixels
    )