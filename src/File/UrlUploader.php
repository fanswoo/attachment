<?php

namespace FF\Attachment\File;

use FF\Attachment\AbstractUploader;
use FF\Attachment\Contracts\Uploader as IUploader;
use Illuminate\Http\UploadedFile;

class UrlUploader extends AbstractUploader implements IUploader
{
    public function setFile(UploadedFile $uploadedFile)
    {
    }
}
