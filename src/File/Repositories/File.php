<?php

namespace FF\Attachment\File\Repositories;

use FF\Attachment\Attachment\Contracts\Repositories\Attachment;
use FF\Attachment\File\Contracts\Repositories\File as IFile;
use FF\Attachment\File\PathGetter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class File extends Model implements IFile, Attachment
{
    public $table = 'files';

    protected $appends = ['downloadUrl'];

    protected $fillable = [
        'id',
        'user_id',
        'title',
        'file_name',
        'file_size',
        'file_type',
        'priority',
        'md5',
        'fileable_id',
        'fileable_type',
        'fileable_attr',
        'updated_at',
        'created_at',
        'deleted_at',
    ];

    protected $attributes = [
        'file_name' => '',
        'file_type' => '',
        'md5' => '',
        'fileable_attr' => '',
        'fileable_type' => '',
        'fileable_id' => 0,
        'user_id' => 0,
        'priority' => 0,
    ];

    protected $casts = [
        'fileable_id' => 'integer',
        'user_id' => 'integer',
        'priority' => 'integer'
    ];

    public static function getMaxSize(): int
    {
        return 200 * 1024 * 1024;
    }

    public static function getAllowType(): array|null
    {
        return null;
    }

    public static function getDenyType(): array|null
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
            fileName: $this->file_name,
            md5: $this->md5,
        );

        return $pathGetter->getFullPath();
    }

    public function download($uploadDisk = null)
    {
        $pathGetter = app(PathGetter::class);
        $pathGetter->setParameter(
            id: $this->id,
            fileName: $this->file_name,
            md5: $this->md5,
        );

        return Storage::disk(
            $uploadDisk ?? config('attachment.upload_disk'),
        )->download($pathGetter->getFullPath(), $pathGetter->getFileName());
    }
}
