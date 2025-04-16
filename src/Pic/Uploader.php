<?php

namespace FF\Attachment\Pic;

use FF\Attachment\Attachment\AbstractUploader;
use FF\Attachment\Attachment\Contracts\Uploader as IUploader;
use Illuminate\Http\UploadedFile;

class Uploader extends AbstractUploader implements IUploader
{
    public function setFile(UploadedFile $uploadedFile): void
    {
        $this->attachmentProcessor->setFile(
            file: $uploadedFile->get(),
            fileSize: $uploadedFile->getSize(),
            originPathName: $uploadedFile->getPathName(),
            fileName: $uploadedFile->getClientOriginalName(),
            title: $uploadedFile->getClientOriginalName(),
            fileType: $uploadedFile->getMimeType(),
        );
    }

    public function setPicClassName(?string $picClassName = null) {
        $this->attachmentProcessor->setPicClassName($picClassName);
    }
}
