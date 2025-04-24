<?php

namespace Tests\Unit\Pic;

use FF\Attachment\Pic\Repositories\Pic;

class TestingPic extends Pic
{
    public static function getScaleSizes(): array
    {
        return [
            [
                'width' => 50,
                'height' => 50,
                'scaleType' => 'fit',
                'fileType' => 'image/jpeg',
            ],
            [
                'width' => 500,
                'height' => 500,
                'scaleType' => 'reduce',
                'fileType' => 'image/png',
            ],
        ];
    }
}
