<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageUploadService
{
    /**
     * Upload an image to storage.
     */
    public function upload(UploadedFile $file, string $directory = 'uploads'): string
    {
        $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs($directory, $filename, 'public');
    }

    /**
     * Delete an image from storage.
     */
    public function delete(string $path): bool
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }
        return false;
    }

    /**
     * Upload profile image.
     */
    public function uploadProfileImage(UploadedFile $file): string
    {
        return $this->upload($file, 'profile_images');
    }
}
