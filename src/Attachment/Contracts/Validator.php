<?php

namespace FF\Attachment\Attachment\Contracts;

interface Validator
{
    public function validate(
        string $file,
        int $fileSize,
        string $originPathName,
        string $fileName,
        string $title,
    ): bool;

    public function getErrorMessage(): string;
}
