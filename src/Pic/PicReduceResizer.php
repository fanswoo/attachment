<?php

namespace FF\Attachment\Pic;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PicReduceResizer extends PicResizer
{
    private string $width;

    private string $height;

    private string $fileType;

    public function createPic(int $width, int $height, string $fileType): bool
    {
        $this->width = $width;
        $this->height = $height;
        $this->fileType = $fileType;

        $target = $this->getTargetSize();

        if ($this->fileType === 'image/png') {
            $this->imagePng($target['width'], $target['height']);
            return true;
        }

        $this->imageJpeg($target['width'], $target['height']);
        return true;
    }

    private function getTargetSize(): array
    {
        $originSize = $this->picHandler->getOriginSize();

        // if ($this->width > $originSize['width']) {
        $heightRatio = $originSize['height'] / $this->height;
        $newWidth = $originSize['width'] / $heightRatio;

        if ($newWidth <= $this->width) {
            return [
                'width' => $newWidth,
                'height' => $this->height,
            ];
        }

        $widthRatio = $originSize['width'] / $this->width;
        $newHeight = $originSize['height'] / $widthRatio;

        if ($newHeight <= $this->height) {
            return [
                'width' => $this->width,
                'height' => $newHeight,
            ];
        }

        return [
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    private function imagePng(int $targetWidth, int $targetHeight)
    {
        $newImage = imagecreatetruecolor($targetWidth, $targetHeight);
        $originImage = $this->picHandler->getOriginImage();
        $originSize = $this->picHandler->getOriginSize();

        $alpha = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
        imagefill($newImage, 0, 0, $alpha);
        imagecopyresampled(
            $newImage,
            $originImage,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $originSize['width'],
            $originSize['height'],
        );

        imagesavealpha($newImage, true);

        Storage::makeDirectory('temporary');
        Storage::setVisibility('temporary', 'public');

        $temporaryPath =
            'temporary' . DIRECTORY_SEPARATOR . Str::random(8) . '.tmp';

        $temporaryPathFromStorage = Storage::path(
            $temporaryPath,
        );

        imagepng($newImage, $temporaryPathFromStorage, 9);

        Storage::disk(config('attachment.upload_disk'))->put(
            $this->picHandler->getFullSavePath(),
            Storage::get($temporaryPath),
            'public'
        );
        Storage::delete($temporaryPath);

        imagedestroy($newImage);
    }

    private function imageJpeg(int $targetWidth, int $targetHeight)
    {
        $newImage = imagecreatetruecolor($targetWidth, $targetHeight);
        $originImage = $this->picHandler->getOriginImage();
        $originSize = $this->picHandler->getOriginSize();

        imagecopyresampled(
            $newImage,
            $originImage,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $originSize['width'],
            $originSize['height'],
        );

        Storage::makeDirectory('temporary');

        Storage::setVisibility('temporary', 'public');

        $temporaryPath =
            'temporary' . DIRECTORY_SEPARATOR . Str::random(8) . '.tmp';
        $temporaryPathFromStorage = Storage::path(
            $temporaryPath,
        );

        imagejpeg($newImage, $temporaryPathFromStorage, 100);

        Storage::disk(config('attachment.upload_disk'))->put(
            $this->picHandler->getFullSavePath(),
            Storage::get($temporaryPath),
            'public'
        );

        Storage::delete($temporaryPath);

        imagedestroy($newImage);
    }
}
