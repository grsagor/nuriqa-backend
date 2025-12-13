<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ImageService
{
    /**
     * Upload an image to the specified directory
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param string|null $oldImage
     * @return string|null
     */
    public static function upload(UploadedFile $file, string $directory, string $oldImage = null): ?string
    {
        // Delete old image if exists
        if ($oldImage) {
            self::delete($oldImage, $directory);
        }

        // Generate unique filename
        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        
        // Store file in public/uploads directory
        $path = $file->move(public_path("uploads/{$directory}"), $filename);
        
        return $filename;
    }

    /**
     * Delete an image from the specified directory
     *
     * @param string $filename
     * @param string $directory
     * @return bool
     */
    public static function delete(string $filename, string $directory): bool
    {
        $path = public_path("uploads/{$directory}/{$filename}");
        
        if (file_exists($path)) {
            return unlink($path);
        }
        
        return false;
    }

    /**
     * Get the URL of an image
     *
     * @param string|null $filename
     * @param string $directory
     * @param string|null $default
     * @return string|null
     */
    public static function getUrl(?string $filename, string $directory, string $default = null): ?string
    {
        if (!$filename) {
            return $default;
        }
        
        $path = public_path("uploads/{$directory}/{$filename}");
        
        // Check if file exists
        if (!file_exists($path)) {
            return $default;
        }
        
        return url("uploads/{$directory}/{$filename}");
    }

    /**
     * Get the full path of an image
     *
     * @param string|null $filename
     * @param string $directory
     * @return string|null
     */
    public static function getPath(?string $filename, string $directory): ?string
    {
        if (!$filename) {
            return null;
        }
        
        return public_path("uploads/{$directory}/{$filename}");
    }
}