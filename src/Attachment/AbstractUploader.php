<?php

namespace FF\Attachment;

use FF\Attachment\Contracts\AttachmentProcessor;
use FF\Attachment\Contracts\Repositories\Attachment;

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
