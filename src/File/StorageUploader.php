<?php

namespace FF\Attachment\File;

use FF\Attachment\AbstractUploader;
use FF\Attachment\Contracts\Uploader as IUploader;
use FF\Attachment\File\Contracts\StorageUploader as IStorageUploader;
use Illuminate\Support\Facades\Storage;

class StorageUploader extends AbstractUploader implements
    IStorageUploader,
    IUploader
{
    public function setFile(
        string $storageDisk,
        string $filePath,
        string $uploadDisk = null
    ) {
        $explodeStoragePath = explode('/', $filePath);
        $fileName = end($explodeStoragePath);

        $this->attachmentProcessor->setFile(
            file: Storage::disk($storageDisk)->get($filePath),
            fileSize: Storage::disk($storageDisk)->size($filePath),
            originPathName: $filePath,
            fileName: $fileName,
            title: $fileName,
            fileType: Storage::disk($storageDisk)->mimeType($filePath),
            uploadDisk: $uploadDisk,
        );
    }
}
