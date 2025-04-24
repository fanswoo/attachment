<?php

namespace FF\Attachment\File;

use FF\Attachment\File\Contracts\MutipleUploader as IMutipleUploader;
use function DeepCopy\deep_copy;

class MutipleUploader implements IMutipleUploader
{
    private array $uploaders = [];

    private string $errorMessage;

    public function __construct(protected Uploader $uploader)
    {
    }

    public function setFiles(array $files): void
    {
        $this->uploaders = [];

        foreach ($files as $file) {
            $uploader = deep_copy($this->uploader);
//            $uploader = clone unserialize(serialize($this->uploader));
            $uploader->setFile($file);
            $this->uploaders[] = $uploader;
        }
    }

    public function upload(): bool
    {
        foreach ($this->uploaders as $key => $uploader) {
            $result = $uploader->upload();

            if (!$result) {
                $this->errorMessage =
                    'files[' . $key . '] :' . $uploader->getErrorMessage();
                return false;
            }
        }

        return true;
    }

    public function getAttachments(): array
    {
        $files = [];
        foreach ($this->uploaders as $uploader) {
            $files[] = $uploader->getAttachment();
        }

        return $files;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
