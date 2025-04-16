<?php

namespace FF\Attachment\Pic;

use Exception;
use FF\Attachment\Attachment\Contracts\FileModifyer as IFileModifyer;
use FF\Attachment\Attachment\Contracts\PathGetter;
use FF\Attachment\Attachment\Contracts\Repositories\Attachment;
use FF\Attachment\Attachment\Utils\StorageVisibility;
use Illuminate\Support\Facades\Storage;

class FileModifyer implements IFileModifyer
{
    public function __construct(private PathGetter $pathGetter)
    {
    }

    public function modify(
        Attachment $attachment,
        string     $file,
        string     $fileType = null,
        string     $uploadDisk = null
    ): bool
    {
        $this->pathGetter->setParameter(
            id: $attachment->id,
            fileName: $attachment->fileName,
            md5: $attachment->md5,
            fileType: $fileType,
        );

        $scaleSizes = get_class($attachment)::getScaleSizes();

        $resizeResult = $this->resizeAll(
            $scaleSizes,
            $file,
            $uploadDisk ?? null,
        );

        if (!$resizeResult) {
            return false;
        }

        $fullSavePath = $this->pathGetter->getFullPath();
        return Storage::disk(
            $uploadDisk ?? config('filesystems.upload_disk'),
        )->put($fullSavePath, $file, 'public');
    }

    private function resize(
        PicHandler $picHandler,
        int        $width,
        int        $height,
        string     $fileType,
        string     $scaleType
    ): bool
    {
        $picHandler->setFullSavePath(
            $this->pathGetter->getFullPathVariant(
                width: $width,
                height: $height,
                fileType: $fileType,
            ),
        );

        switch ($scaleType) {
            case 'reduce':
                $picResizer = new PicReduceResizer();
                $picResizer->setPicHandler($picHandler);
                return $picResizer->createPic(
                    width: $width,
                    height: $height,
                    fileType: $fileType ?? null,
                );
                break;
            case 'fit':
                $picResizer = new PicFitResizer();
                $picResizer->setPicHandler($picHandler);
                return $picResizer->createPic(
                    width: $width,
                    height: $height,
                    fileType: $fileType ?? null,
                );
                break;
            default:
                throw new Exception('Unknown scaleType');
        }

        return true;
    }

    private function resizeAll(
        array  $scaleSizes,
        string $file,
        string $uploadDisk = null
    ): bool
    {
        $picHandler = new PicHandler();
        $picHandler->setPic($file);

        if ($uploadDisk) {
            $picHandler->setUploadDisk($uploadDisk);
        }

        $this->makeDirectory($uploadDisk ?? null);

        foreach ($scaleSizes as $scaleSize) {
            $result = $this->resize(
                picHandler: $picHandler,
                width: $scaleSize['width'],
                height: $scaleSize['height'],
                fileType: $scaleSize['fileType'],
                scaleType: $scaleSize['scaleType'],
            );

            if (!$result) {
                return false;
            }
        }

        return true;
    }

    private function makeDirectory(string $uploadDisk = null): void
    {
        $directory = $this->pathGetter->getDirectory();
        if (
            !Storage::disk(
                $uploadDisk ?? config('filesystems.upload_disk'),
            )->has($directory)
        ) {
            StorageVisibility::makeDirectoryWithAllVisibility(
                $uploadDisk ?? config('filesystems.upload_disk'),
                $directory,
                'public'
            );
        }
    }
}
