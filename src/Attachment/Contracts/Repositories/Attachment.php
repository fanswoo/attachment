<?php

namespace FF\Attachment\Attachment\Contracts\Repositories;

interface Attachment
{
    public function download();

    public static function getDenyType(): array|null;

    public static function getAllowType(): array|null;
}
