<?php

namespace FF\Attachment\Pic\Repositories;

use FF\Attachment\Attachment\Contracts\Repositories\Attachment;
use FF\Attachment\Attachment\Contracts\Repositories\AttachmentCreator;

class PicCreator implements AttachmentCreator
{
    private Attachment $pic;

    private string $errorMessage;

    public function __construct(private string $picClassName)
    {
    }

    public function setPicClassName(string $picClassName) {
        $this->picClassName = $picClassName;
    }

    public function create(
        string $fileName,
        string $title,
        string $fileType,
        int $fileSize
    ): bool {
        $pic = new $this->picClassName;
        $pic->fileSize = $fileSize;
        $pic->fileName = $fileName;
        $pic->title = $title;
        $pic->fileType = $fileType;
        $pic->md5 = $this->getRandomMd5();
        $result = $pic->save();

        $this->pic = $pic;

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
        return $this->pic;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }
}
