<?php

namespace FF\Attachment\File\Contracts\Repositories;

interface File
{
    public static function getMaxSize(): int;

    public static function getDenyType(): array;
}
