<?php

namespace FF\Attachment\Attachment\Contracts;

use FF\Attachment\Attachment\Contracts\Repositories\Attachment;

interface Uploader
{
    public function upload(): bool;

    public function getAttachment(): Attachment;

    public function getErrorMessage(): string;
}
