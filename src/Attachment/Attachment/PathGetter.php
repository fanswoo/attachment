<?php

namespace FF\Attachment\Attachment;

use FF\Attachment\Attachment\Contracts\PathGetter as IPathGetter;

abstract class PathGetter implements IPathGetter
{
    private int $id;

    private string $fileName;

    private string $md5;

    protected string $fileType;

    public function setParameter(
        int $id,
        string $fileName,
        string $md5,
        string $fileType = ''
    ) {
        $this->id = $id;
        $this->fileName = $fileName;
        $this->md5 = $md5;
        $this->fileType = $fileType;
    }

    public function getFileName(): string
    {
        $explodeArr = explode('.', $this->fileName);
        $ext = array_pop($explodeArr);
        return $this->getFileNameWithoutExt() . '.' . $ext;
    }

    public function getFileNameWithoutExt(): string
    {
        $id = $this->id;
        $id = sprintf('%08d', abs(intval($id)));

        $dir4 = substr($id, 6, 2);

        return $dir4 . '-' . $this->md5;
    }

    public function getFullPath(): string
    {
        $fullPath =
            $this->getDirectory() . DIRECTORY_SEPARATOR . $this->getFileName();

        return $fullPath;
    }

    public function getDirectory(): string
    {
        $id = $this->id;
        $id = sprintf('%08d', abs(intval($id)));

        $dir1 = substr($id, 0, 2);
        $dir2 = substr($id, 2, 2);
        $dir3 = substr($id, 4, 2);

        return static::getRootPath() .
            DIRECTORY_SEPARATOR .
            $dir1 .
            DIRECTORY_SEPARATOR .
            $dir2 .
            DIRECTORY_SEPARATOR .
            $dir3;
    }
}
