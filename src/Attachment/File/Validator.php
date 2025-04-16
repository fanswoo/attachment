<?php

namespace FF\Attachment\File;

use FF\Attachment\File\Contracts\Repositories\File;
use FF\Attachment\Attachment\Contracts\Validator as IValidator;

class Validator implements IValidator
{
    private string $errorMessage = '';

    public function __construct(private string $fileClassName)
    {
    }

    public function validate(
        string $file,
        int $fileSize,
        string $originPathName,
        string $fileName,
        string $title,
    ): bool {
        if (!$this->hasFile($file)) {
            return false;
        }

        $explodeArr = explode('.', $fileName);
        $ext = array_pop($explodeArr);

        if (in_array($ext, $this->fileClassName::getDenyType())) {
            $this->errorMessage = '檔案類型為禁止項目';
            return false;
        }

        if ($fileSize > $this->fileClassName::getMaxSize()) {
            $this->errorMessage = '單一檔案尺寸過大，超過檔案最大限制';
            return false;
        }

        if ($fileSize == 0) {
            $this->errorMessage =
                '檔案尺寸過大超過伺服器限制，請聯繫伺服器管理員修改 php.ini 之 upload_max_filesize 限制';
            return false;
        }

        return true;
    }

    private function hasFile($file): bool
    {
        if (!$file) {
            $this->errorMessage = 'file path not found';
            return false;
        }

        return true;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }
}
