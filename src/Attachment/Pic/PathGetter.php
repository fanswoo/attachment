<?php

namespace FF\Attachment\Pic;

use FF\Attachment\Attachment\Contracts\PathGetter as IPathGetter;
use FF\Attachment\Attachment\PathGetter as AttachmentPathGetter;

class PathGetter extends AttachmentPathGetter implements IPathGetter
{
    public function getFileName(): string
    {
        if (!$this->fileType) {
            return $this->getFileNameWithoutExt() . '.jpg';
        }

        switch ($this->fileType) {
            case 'image/png':
                $ext = 'png';
                break;
            case 'image/gif':
                $ext = 'gif';
                break;
            case 'image/jpeg':
                $ext = 'jpg';
                break;
            case 'image/jpg':
                $ext = 'jpg';
                break;
            default:
                $ext = 'jpg';
        }
        return $this->getFileNameWithoutExt() . '.' . $ext;
    }

    public static function getRootPath(): string
    {
        return 'pic';
    }

    public function getFullPathVariant(
        int $width,
        int $height,
        string $fileType = null
    ): string {
        switch ($fileType) {
            case 'image/png':
                $extension = 'png';
                break;
            default:
                $extension = 'jpg';
        }

        $fullPath =
            $this->getDirectory() .
            DIRECTORY_SEPARATOR .
            $this->getFileNameWithoutExt() .
            '-w' .
            $width .
            'h' .
            $height .
            '.' .
            $extension;

        return $fullPath;
    }
}
