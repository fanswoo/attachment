<?php

namespace FF\Attachment\File;

use FF\Attachment\Attachment\Contracts\FileModifyer as IFileModifyer;
use FF\Attachment\Attachment\Contracts\PathGetter;
use FF\Attachment\Attachment\Contracts\Repositories\Attachment;
use FF\Attachment\Attachment\Utils\StorageVisibility;
use Illuminate\Support\Facades\Storage;

class FileModifyer implements IFileModifyer
{
    public function __construct(private PathGetter $pathGetter)
    {
    }

    public function modify(
        Attachment $attachment,
        string     $file,
        ?string    $fileType,
        ?string    $uploadDisk
    )
    {
        $this->pathGetter->setParameter(
            id: $attachment->id,
            fileName: $attachment->file_name,
            md5: $attachment->md5,
        );
        $fullSavePath = $this->pathGetter->getFullPath();
        $directory = $this->pathGetter->getDirectory();

        StorageVisibility::makeDirectoryWithAllVisibility(
            $uploadDisk ?? config('attachment.upload_disk'),
            $directory,
            'public'
        );

        return Storage::disk(
            $uploadDisk ?? config('attachment.upload_disk'),
        )->put($fullSavePath, $file, 'public');
    }
}
