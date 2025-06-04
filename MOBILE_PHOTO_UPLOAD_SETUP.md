# Mobile Photo Upload Configuration Guide

This document explains how to properly configure your system for mobile photo uploads up to 50MB.

## Changes Made

### 1. Laravel Application Changes ✅
- **Increased validation limit** from 4MB to 50MB
- **Added support for more image formats**: JPEG, PNG, GIF, WebP, HEIC, HEIF
- **Enhanced mobile UI** with image preview and progress indicators
- **Added mobile-specific CSS** for better touch experience
- **Improved error handling** with detailed user feedback

### 2. .htaccess Configuration ✅
The following PHP limits have been set in `public/.htaccess`:
```apache
php_value upload_max_filesize 50M
php_value post_max_size 55M
php_value memory_limit 256M
php_value max_execution_time 300
php_value max_input_time 300
```

### 3. PHP Configuration (Manual Setup Required)

If the .htaccess settings don't work (some servers disable `php_value` directives), you need to update your PHP configuration:

#### For XAMPP Users:
1. Find your `php.ini` file (usually in `C:\xampp\php\php.ini`)
2. Edit these values:
```ini
upload_max_filesize = 50M
post_max_size = 55M
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
file_uploads = On
```
3. Restart Apache server

#### For cPanel/Shared Hosting:
1. Login to cPanel
2. Go to "MultiPHP INI Editor"
3. Select your domain
4. Add/modify these settings:
```ini
upload_max_filesize = 50M
post_max_size = 55M
memory_limit = 256M
max_execution_time = 300
```

#### For VPS/Dedicated Servers:
1. Edit `/etc/php/8.x/apache2/php.ini` (replace 8.x with your PHP version)
2. Update the values as shown above
3. Restart web server: `sudo systemctl restart apache2`

## Mobile-Specific Features Added

### 1. Enhanced File Input
- **Camera capture**: `capture="environment"` for direct camera access
- **Multiple formats**: Support for HEIC/HEIF from iOS devices
- **Better touch targets**: Improved button sizes for mobile
- **Visual feedback**: Clear file selection and upload status

### 2. Image Preview
- **Instant preview**: Users can see their photo before uploading
- **Size validation**: Real-time file size checking
- **Format validation**: Immediate feedback on unsupported formats

### 3. Mobile Optimizations
- **Font size fix**: Prevents iOS zoom on file input
- **Responsive design**: Better layout on small screens
- **Progress indicators**: Visual upload progress
- **Error handling**: Clear, actionable error messages

## Testing Mobile Upload

### Test with different file sizes:
1. **Small image** (< 2MB): Should work immediately
2. **Medium image** (2-10MB): Tests new PHP limits
3. **Large image** (10-50MB): Tests maximum capacity
4. **Oversized image** (> 50MB): Should show proper error message

### Test with different formats:
- JPEG/JPG ✅
- PNG ✅
- GIF ✅
- WebP ✅
- HEIC (iOS) ✅
- HEIF (iOS) ✅

### Test on different devices:
- iPhone/iPad (Safari, Chrome)
- Android phones (Chrome, Samsung Browser)
- Tablets
- Desktop browsers

## Troubleshooting

### If uploads still fail:

1. **Check PHP limits**:
```bash
php -r "echo 'upload_max_filesize: ' . ini_get('upload_max_filesize') . PHP_EOL;"
php -r "echo 'post_max_size: ' . ini_get('post_max_size') . PHP_EOL;"
```

2. **Check Laravel logs**:
```bash
tail -f storage/logs/laravel.log
```

3. **Check server error logs**:
- XAMPP: `xampp/apache/logs/error.log`
- Linux: `/var/log/apache2/error.log`

4. **Common issues**:
- Server doesn't allow .htaccess php_value directives
- Web server upload limits (nginx: `client_max_body_size`)
- Disk space limitations
- File permissions on storage directory

### Error Messages and Solutions:

| Error | Solution |
|-------|----------|
| "File size exceeds 50MB" | Compress image or choose smaller photo |
| "Upload failed: post size exceeded" | Increase `post_max_size` in PHP config |
| "Invalid file upload" | Check file permissions and disk space |
| "Image must be JPEG, PNG..." | Use supported image format |

## Verification Commands

Run these to verify your configuration:

```bash
# Check current PHP limits
php -r "
echo 'upload_max_filesize: ' . ini_get('upload_max_filesize') . PHP_EOL;
echo 'post_max_size: ' . ini_get('post_max_size') . PHP_EOL;
echo 'memory_limit: ' . ini_get('memory_limit') . PHP_EOL;
echo 'max_execution_time: ' . ini_get('max_execution_time') . PHP_EOL;
"

# Clear Laravel caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

## Support

If you continue experiencing issues with mobile uploads:

1. Check the browser console for JavaScript errors
2. Verify server logs for PHP errors
3. Test with a smaller image first
4. Ensure storage directory has write permissions
5. Check available disk space

The system now supports mobile photo uploads up to 50MB with proper error handling and user feedback. 