<?php

namespace FF\Attachment\Contracts;

use FF\Attachment\Contracts\Repositories\Attachment;

interface Uploader
{
    public function upload(): bool;

    public function getAttachment(): Attachment;

    public function getErrorMessage(): string;
}
