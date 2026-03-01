<?php

namespace App\Service;

use Cloudinary\Cloudinary;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CloudinaryService
{
    private $cloudinary;

    public function __construct(string $cloudinaryUrl)
    {
        $this->cloudinary = new \Cloudinary\Cloudinary($cloudinaryUrl);
    }

    public function uploadImage(UploadedFile $file, string $folder = 'ecotrip'): string
    {
        $path = $file->getRealPath() ?: $file->getPathname();
        if (!is_readable($path)) {
            throw new \RuntimeException('Uploaded file is not readable. Check PHP upload_max_filesize and temp directory.');
        }
        $upload = $this->cloudinary->uploadApi()->upload($path, [
            'folder' => $folder,
        ]);

        return $upload['secure_url'];
    }

    public function deleteImage(string $publicId): void
    {
        $this->cloudinary->uploadApi()->destroy($publicId);
    }
}
