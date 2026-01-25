<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ImageService
{
    /**
     * Upload an image to the specified directory
     */
    public static function upload(UploadedFile $file, string $directory, ?string $oldImage = null): ?string
    {
        // Delete old image if exists
        if ($oldImage) {
            self::delete($oldImage, $directory);
        }

        // Generate unique filename
        $filename = time().'_'.Str::random(10).'.'.$file->getClientOriginalExtension();

        // Store file in public/uploads directory
        $path = $file->move(public_path("uploads/{$directory}"), $filename);

        return "uploads/{$directory}/{$filename}";
    }

    /**
     * Delete an image from the specified directory
     *
     * @param  string  $directory
     */
    public static function delete(string $filename): bool
    {
        $path = public_path($filename);

        if (file_exists($path)) {
            return unlink($path);
        }

        return false;
    }

    /**
     * Get the URL of an image
     */
    public static function getUrl(?string $filename, ?string $default = null): ?string
    {
        if (! $filename) {
            return $default;
        }

        $path = public_path($filename);

        // Check if file exists
        if (! file_exists($path)) {
            return $default;
        }

        return url($filename);
    }

    /**
     * Get the full path of an image
     */
    public static function getPath(?string $filename): ?string
    {
        if (! $filename) {
            return null;
        }

        return public_path($filename);
    }
}
