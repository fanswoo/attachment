<?php

namespace FF\Attachment\Pic\Contracts\Repositories;

interface Pic
{
    public static function getMaxSize(): int;

    public static function getAllowType(): array;

    public static function getScaleSizes(): array;
}
