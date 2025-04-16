<?php

namespace FF\Attachment\Pic\Repositories;

use FF\ORM\ORM;
use FF\User\User;
use Illuminate\Support\Facades\Storage;
use FF\Attachment\Pic\PathGetter;
use FF\Attachment\Pic\Contracts\Repositories\Pic as IPic;
use FF\Attachment\Attachment\Contracts\Repositories\Attachment;

class Pic extends ORM implements IPic, Attachment
{
    protected $appends = ['url', 'downloadUrl'];

    public $table = 'Pic';

    protected function attrSetting()
    {
        // 繼承父類別的設定
        parent::attrSetting();

        // 設定物件的 ID 唯一值
        $this->setPrimaryKey('id');

        // 設定屬性
        $this->attr('id')
            ->title('ID')
            ->validator(['required', 'max:10']);
        $this->attr('userId')
            ->title('會員 ID')
            ->validator(['required', 'max:10'])
            ->default(0);
        $this->attr('title')
            ->title('圖片標題')
            ->default('');
        $this->attr('fileName')
            ->title('檔案名稱')
            ->default('');
        $this->attr('fileSize')
            ->title('檔案尺寸')
            ->default(0);
        $this->attr('fileType')
            ->title('檔案類型')
            ->default(0);
        $this->attr('priority')
            ->title('優先排序指數')
            ->validator(['required', 'numeric', 'max:99999999'])
            ->default(0);
        $this->attr('md5')->default(0);
        $this->attr('thumb')->default('');
        $this->attr('picableId')->default(0);
        $this->attr('picableType');
        $this->attr('picableAttr');
        $this->attr('updatedAt')
            ->title('更新時間');
        $this->attr('createdAt')
            ->title('新增時間');
        $this->attr('deletedAt')
            ->title('刪除時間');
        $this->attr('downloadFileUrl')->append();
    }

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

    public function User()
    {
        return $this->hasOne(User::class, 'id', 'userId');
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
