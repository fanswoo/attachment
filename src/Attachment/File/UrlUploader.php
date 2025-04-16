<?php

namespace FF\Attachment\File;

use FF\Attachment\Attachment\AbstractUploader;
use FF\Attachment\Attachment\Contracts\Uploader as IUploader;

class UrlUploader extends AbstractUploader implements IUploader
{
    public function setFile(UploadedFile $uploadedFile)
    {
        // $this->attachmentProcessor->setFile(
        // );
    }
}
