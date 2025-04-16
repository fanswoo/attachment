<?php

namespace FF\Attachment\Attachment;

use FF\Attachment\Attachment\Contracts\Repositories\Attachment;
use FF\Attachment\Attachment\Contracts\AttachmentProcessor;

abstract class AbstractUploader
{
    public function __construct(
        protected AttachmentProcessor $attachmentProcessor
    ) {
    }

    private string $errorMessage;

    public function upload(): bool
    {
        $result = $this->attachmentProcessor->run();

        if (!$result) {
            $this->errorMessage = $this->attachmentProcessor->getErrorMessage();
            return false;
        }

        return true;
    }

    public function getAttachment(): Attachment
    {
        return $this->attachmentProcessor->getAttachment();
    }

    public function getErrorMessage(): string
    {
        return $this->attachmentProcessor->getErrorMessage();
    }
}
