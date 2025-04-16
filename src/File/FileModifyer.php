<?php

namespace FF\Attachment\File;

use FF\Attachment\Contracts\FileModifyer as IFileModifyer;
use FF\Attachment\Contracts\PathGetter;
use FF\Attachment\Contracts\Repositories\Attachment;
use FF\Attachment\Utils\StorageVisibility;
use Illuminate\Support\Facades\Storage;

class FileModifyer implements IFileModifyer
{
    public function __construct(private PathGetter $pathGetter)
    {
    }

    public function modify(
        Attachment $attachment,
        string $file,
        string $fileType = null,
        string $uploadDisk = null
    ) {
        $this->pathGetter->setParameter(
            id: $attachment->id,
            fileName: $attachment->fileName,
            md5: $attachment->md5,
        );
        $fullSavePath = $this->pathGetter->getFullPath();
        $directory = $this->pathGetter->getDirectory();

        StorageVisibility::makeDirectoryWithAllVisibility(
            $uploadDisk ?? config('filesystems.upload_disk'),
            $directory,
            'public'
        );

        return Storage::disk(
            $uploadDisk ?? config('filesystems.upload_disk'),
        )->put($fullSavePath, $file, 'public');
    }
}
