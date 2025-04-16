<?php

namespace FF\Attachment\File\Repositories;

use App\Common\ClassTag\ClassTag;
use FF\Attachment\Contracts\Repositories\Attachment;
use FF\Attachment\File\Contracts\Repositories\File as IFile;
use FF\Attachment\File\PathGetter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class File extends Model implements IFile, Attachment
{
    protected $appends = ['downloadUrl'];

    public $table = 'File';

    protected $fillable = [
        'id',
        'userId',
        'title',
        'fileName',
        'fileSize',
        'fileType',
        'priority',
        'md5',
        'fileableId',
        'fileableType',
        'fileableAttr',
        'updatedAt',
        'createdAt',
        'deletedAt',
    ];

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

    public function fileable()
    {
        return $this->morphTo();
    }

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
