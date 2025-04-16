<?php

namespace FF\Attachment\File\Repositories;

use App\Common\ClassTag\ClassTag;
use FF\Attachment\Attachment\Contracts\Repositories\Attachment;
use FF\Attachment\File\Contracts\Repositories\File as IFile;
use FF\Attachment\File\PathGetter;
use FF\ORM\ORM;
use FF\User\User;
use Storage;

class File extends ORM implements IFile, Attachment
{
    protected $appends = ['downloadUrl'];

    public $table = 'File';

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
            ->title('檔案標題')
            ->default('');
        $this->attr('fileName')
            ->title('檔案名稱')
            ->default('');
        $this->attr('fileSize')
            ->title('檔案尺寸')
            ->default(0);
        $this->attr('fileType')
            ->title('檔案類型')
            ->default('');
        $this->attr('priority')
            ->title('優先排序指數')
            ->default(0);
        $this->attr('md5')->default(0);
        $this->attr('fileableId');
        $this->attr('fileableType');
        $this->attr('fileableAttr');
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

    public static function getDenyType(): array
    {
        return [
            'com',
            'exe',
            'sys',
            'prg',
            'bin',
            'bat',
            'cmd',
            'dpl',
            'dll',
            'scr',
            'cpl',
            'ocx',
            'tsp',
            'drv',
            'vxd',
            'pif',
            'lnk',
            'reg',
            'ini',
            'cla',
            'vbs',
            'vbe',
            'js',
            'htm',
            'htt',
            'hta',
            'asp',
            'chm',
            'pht',
            'php',
            'wsh',
            'wsf',
            'the',
            'hlp',
            'eml',
            'nws',
            'msg',
            'plg',
            'mbx',
            'doc',
            'dot',
            'fpm',
            'rtf',
            'shs',
            'dwg',
            'msi',
            'otm',
            'swf',
            'emf',
            'ico',
            'ov?',
            'xl*',
            'pp*',
            'md*',
        ];
    }

    public function User()
    {
        return $this->hasOne(User::class, 'id', 'userId');
    }

    public function fileable()
    {
        return $this->morphTo();
    }

    // public function forceDelete(array $options = [])
    // {
    //     return parent::forceDelete($options);
    // }

    public function getDownloadUrlAttribute()
    {
        return url('api/file/download/' . $this->id);
    }

    public function path()
    {
        $pathGetter = app(PathGetter::class);
        $pathGetter->setParameter(
            id: $this->id,
            fileName: $this->fileName,
            md5: $this->md5,
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
        );

        return Storage::disk(
            $uploadDisk ?? config('filesystems.upload_disk'),
        )->download($pathGetter->getFullPath(), $pathGetter->getFileName());
    }

    // 分類類別之多類別多屬性多對多
    public function classTags($action = null, $ids = [])
    {
        return $this->relationClassTag(
            ClassTag::class,
            __FUNCTION__,
            $action,
            $ids,
        );
    }
}
