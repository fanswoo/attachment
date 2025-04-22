<?php

namespace FF\Attachment\Pic;

use Exception;
use Illuminate\Support\Facades\Storage;

class PicHandler
{
    private string $pic;

    private string $errorMessage = '';

    private ?array $originSize = null;

    private $originImage = null;

    private string $uploadDisk;

    private string $fullSavePath;

    public function setPic(string $pic): void
    {
        $this->pic = $pic;
    }

    public function setUploadDisk(string $uploadDisk)
    {
        $this->uploadDisk = $uploadDisk;
    }

    public function getOriginImage()
    {
        if ($this->originImage) {
            return $this->originImage;
        }

        if (!function_exists('imagecreatefromstring')) {
            throw new Exception(
                "this server doesn't support imagecreatefromstring",
            );
        }

        $this->originImage = imagecreatefromstring($this->pic);

        return $this->originImage;
    }

    public function setFullSavePath(string $fullSavePath)
    {
        $this->fullSavePath = $fullSavePath;
    }

    public function getFullSavePath()
    {
        return $this->fullSavePath;
    }

    public function getUploadPath()
    {
        return Storage::disk(
            $this->uploadDisk ?? config('attachment.upload_disk'),
        )->path($this->fullSavePath);
    }

    public function getOriginSize()
    {
        if ($this->originSize) {
            return $this->originSize;
        }

        $originSize = getimagesizefromstring($this->pic);

        $this->originSize = [
            'width' => $originSize[0],
            'height' => $originSize[1],
        ];

        return $this->originSize;
    }
}
