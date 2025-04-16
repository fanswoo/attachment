<?php

namespace FF\Attachment\Pic\Repositories;

use FF\Attachment\Attachment\Contracts\Repositories\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use FF\Attachment\Pic\Contracts\Repositories\Pic as IPic;
use FF\Attachment\Pic\PathGetter;

class Pic extends Model implements IPic, Attachment
{
    const UPDATED_AT = 'updatedAt';

    const CREATED_AT = 'createdAt';

    const DELETED_AT = 'deletedAt';

    protected $appends = ['url', 'downloadUrl'];

    public $table = 'Pic';

    protected $fillable = [
        'id',
        'userId',
        'title',
        'fileName',
        'fileSize',
        'fileType',
        'priority',
        'md5',
        'thumb',
        'picableId',
        'picableType',
        'picableAttr',
        'updatedAt',
        'createdAt',
        'deletedAt',
    ];

    public static function getMaxSize(): int
    {
        return 200 * 1024 * 1024;
    }

    public static function getAllowType(): array
    {
        return ['jpg', 'jpeg', 'png', 'gif'];
    }

    public static function getScaleSizes(): array
    {
        return [
            [
                'width' => 50,
                'height' => 50,
                'scaleType' => 'fit',
                'fileType' => 'image/jpeg',
            ],
        ];
    }

    public function picable()
    {
        return $this->morphTo();
    }

    public function forceDelete(array $options = [])
    {
        $this->setStorageDisk();
        $this->setPath();

        //path
        if (!empty($this->md5) && !empty($this->id)) {
            $this->diskDriver->deleteFile($this->filePath);
        }

        return parent::forceDelete($options);
    }

    public function url(
        int    $width = 0,
        int    $height = 0,
        string $fileType = null
    )
    {
        $pathGetter = app(PathGetter::class);
        $pathGetter->setParameter(
            id: $this->id,
            fileName: $this->fileName,
            md5: $this->md5,
            fileType: $this->fileType,
        );

        if ($width === 0 && $height === 0) {
            return Storage::disk(config('filesystems.upload_disk'))->url(
                $pathGetter->getFullPath(),
            );
        }

        return Storage::disk(config('filesystems.upload_disk'))->url(
            $pathGetter->getFullPathVariant(
                width: $width,
                height: $height,
                fileType: $fileType ?? null,
            ),
        );
    }

    public function getUrlAttribute()
    {
        $scaleSizes = static::getScaleSizes();
        $urls = [
            'w0h0' => $this->url(0, 0),
        ];
        foreach ($scaleSizes as $scaleSize) {
            $key = 'w' . $scaleSize['width'] . 'h' . $scaleSize['height'];
            $urls[$key] = $this->url(
                $scaleSize['width'],
                $scaleSize['height'],
                $scaleSize['fileType'],
            );
        }

        return $urls;
    }

    public function getDownloadUrlAttribute()
    {
        return url('api/pic/download/' . $this->id);
    }

    public function path()
    {
        $pathGetter = app(PathGetter::class);
        $pathGetter->setParameter(
            id: $this->id,
            fileName: $this->fileName,
            md5: $this->md5,
            fileType: $this->fileType,
        );

        return $pathGetter->getFullPath();
    }

    public function download($uploadDisk = null)
    {
        $pathGetter = app(PathGetter::class);
        $pathGetter->setParameter(
            id: $this->id,
            fileName: $this->fileName,
            md5: $this->md5,
            fileType: $this->fileType,
        );

        return Storage::disk(
            $uploadDisk ?? config('filesystems.upload_disk'),
        )->download($pathGetter->getFullPath(), $pathGetter->getFileName());
    }
}
