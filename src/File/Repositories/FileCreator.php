<?php

namespace FF\Attachment\File\Repositories;

use FF\Attachment\Attachment\Contracts\Repositories\Attachment;
use FF\Attachment\Attachment\Contracts\Repositories\AttachmentCreator;

class FileCreator implements AttachmentCreator
{
    private Attachment $file;

    private string $errorMessage;

    public function __construct(private string $fileClassName)
    {
    }

    public function create(
        string $fileName,
        string $title,
        string $fileType,
        int $fileSize
    ): bool {
        $file = new $this->fileClassName;
        $file->file_size = $fileSize;
        $file->file_name = $fileName;
        $file->title = $title;
        $file->file_type = $fileType;
        $file->md5 = $this->getRandomMd5();
        $result = $file->save();

        $this->file = $file;

        if (!$result) {
            $this->errorMessage = 'cannot create file ORM.';
            return false;
        }

        return true;
    }

    private function getRandomMd5(): string
    {
        return substr(md5('FANSWOO' . rand(10000000, 99999999)), 8, 16);
    }

    public function getORM(): Attachment
    {
        return $this->file;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }
}
