<?php

namespace FF\Attachment\File\Repositories;

use FF\Attachment\Attachment\Contracts\Repositories\Attachment;
use FF\Attachment\File\Contracts\Repositories\File as IFile;
use FF\Attachment\File\PathGetter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class File extends Model implements IFile, Attachment
{
    use SoftDeletes {
        SoftDeletes::forceDelete as softDeletesForceDelete;
    }

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

    /**
     * 永久刪除模型及其對應的儲存檔案
     *
     * @param array $options
     * @return bool|null
     * @throws \Exception
     */
    public function forceDelete(array $options = [])
    {
        if (empty($this->md5) || empty($this->id) || empty($this->file_name)) {
            Log::warning("File ID {$this->id} forceDelete: Missing md5, id, or file_name for file deletion.");
            // 即使檔案資訊不完整，仍然嘗試刪除資料庫記錄
            return $this->softDeletesForceDelete($options);
        }

        try {
            $diskName = config('attachment.upload_disk');
            if (empty($diskName)) {
                Log::error("File ID {$this->id} forceDelete: Storage disk name 'attachment.upload_disk' is not configured.");
                // 如果磁碟未配置，可以選擇拋出異常或僅記錄錯誤並繼續刪除資料庫記錄
                return $this->softDeletesForceDelete($options);
            }
            $storage = Storage::disk($diskName);

            /** @var PathGetter $pathGetter */
            $pathGetter = app(PathGetter::class);
            $pathGetter->setParameter(
                id: $this->id,
                fileName: $this->file_name,
                md5: $this->md5,
                fileType: $this->file_type,
            );

            // 刪除檔案
            $filePath = $pathGetter->getFullPath();
            if ($storage->exists($filePath)) {
                if (!$storage->delete($filePath)) {
                    Log::error("File ID {$this->id} forceDelete: Failed to delete file at path: {$filePath} on disk: {$diskName}");
                }
            } else {
                Log::warning("File ID {$this->id} forceDelete: File not found at path: {$filePath} on disk: {$diskName}");
            }

        } catch (\Exception $e) {
            // 記錄檔案刪除過程中發生的任何錯誤
            Log::error("File ID {$this->id} forceDelete: Error during file deletion: " . $e->getMessage(), [
                'exception' => $e,
                'file_id' => $this->id,
                'file_name' => $this->file_name,
                'md5' => $this->md5,
            ]);
            // 根據需求決定是否要因為檔案刪除失敗而阻止資料庫記錄的刪除
            // throw $e; // 如果希望錯誤冒泡並可能回滾事務（如果在事務中）
        }

        $result = $this->softDeletesForceDelete($options);
        return $result;
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
