<?php

namespace FF\Attachment\Pic\Repositories;

use FF\Attachment\Attachment\Contracts\Repositories\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use FF\Attachment\Pic\Contracts\Repositories\Pic as IPic;
use FF\Attachment\Pic\PathGetter;
use Illuminate\Support\Facades\Log; // 建議加入日誌記錄

class Pic extends Model implements IPic, Attachment
{
    use SoftDeletes {
        SoftDeletes::forceDelete as softDeletesForceDelete;
    }

    protected $appends = ['url', 'downloadUrl'];

    public $table = 'pics';

    protected $fillable = [
        'id',
        'user_id',
        'title',
        'file_name',
        'file_size',
        'file_type',
        'priority',
        'md5',
        'thumb',
        'picable_id',
        'picable_type',
        'picable_attr',
        'updated_at',
        'created_at',
        'deleted_at',
    ];

    protected $attributes = [
        'file_name' => '',
        'file_type' => '',
        'md5' => '',
        'thumb' => '',
        'picable_attr' => '',
        'picable_type' => '',
        'picable_id' => 0,
        'user_id' => 0,
        'priority' => 0,
    ];

    protected $casts = [
        'picable_id' => 'integer',
        'user_id' => 'integer',
        'priority' => 'integer'
    ];

    public static function getMaxSize(): int
    {
        return 200 * 1024 * 1024;
    }

    public static function getAllowType(): array|null
    {
        return ['jpg', 'jpeg', 'png', 'gif'];
    }

    public static function getDenyType(): array|null
    {
        return null;
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

    /**
     * 根據 picable_type 和 picable_attr 取得對應的縮放尺寸
     */
    protected function getRelatedScaleSizes(): array
    {
        if (empty($this->picable_type)) {
            Log::warning("Pic ID {$this->id}: picable_type is empty, using default scale sizes.");
            return static::getScaleSizes();
        }

        try {
            if (!class_exists($this->picable_type)) {
                Log::warning("Pic ID {$this->id}: picable_type class '{$this->picable_type}' does not exist, using default scale sizes.");
                return static::getScaleSizes();
            }

            $relatedClass = $this->picable_type;

            // 如果有 picable_attr，檢查該屬性是否有 getScaleSizes 方法
            if (!empty($this->picable_attr)) {
                $relatedInstance = new $relatedClass();
                if ($relatedInstance->{$this->picable_attr}()->getRelated()::getScaleSizes()) {
                    return $relatedInstance->{$this->picable_attr}()->getRelated()::getScaleSizes();
                }
                Log::warning("Pic ID {$this->id}: {$relatedClass}->{$this->picable_attr} does not have getScaleSizes method, using default scale sizes.");
                return static::getScaleSizes();
            }

            // 如果沒有 picable_attr，檢查相關類別是否有 getScaleSizes 方法
            if (method_exists($relatedClass, 'getScaleSizes')) {
                return $relatedClass::getScaleSizes();
            }

            Log::warning("Pic ID {$this->id}: picable_type class '{$this->picable_type}' does not have getScaleSizes method, using default scale sizes.");
            return static::getScaleSizes();

        } catch (\Exception $e) {
            Log::error("Pic ID {$this->id}: Error getting scale sizes from related class: " . $e->getMessage());
            return static::getScaleSizes();
        }
    }

    public function picable()
    {
        return $this->morphTo();
    }

    /**
     * 永久刪除模型及其對應的儲存圖片 (包括原圖和所有變體)。
     *
     * @param array $options
     * @return bool|null
     * @throws \Exception
     */
    public function forceDelete(array $options = [])
    {
        if (empty($this->md5) || empty($this->id) || empty($this->file_name)) {
            Log::warning("Pic ID {$this->id} forceDelete: Missing md5, id, or file_name for file deletion.");
            // 即使檔案資訊不完整，仍然嘗試刪除資料庫記錄
            return $this->softDeletesForceDelete($options);
        }

        try {
            $diskName = config('attachment.upload_disk');
            if (empty($diskName)) {
                Log::error("Pic ID {$this->id} forceDelete: Storage disk name 'attachment.upload_disk' is not configured.");
                // 如果磁碟未配置，可以選擇拋出異常或僅記錄錯誤並繼續刪除資料庫記錄
                // throw new \Exception("Storage disk 'attachment.upload_disk' is not configured.");
                return $this->softDeletesForceDelete($options); // 或者直接刪除資料庫記錄
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

            // 1. 刪除原圖
            $originalPath = $pathGetter->getFullPath();
            if ($storage->exists($originalPath)) {
                if (!$storage->delete($originalPath)) {
                    Log::error("Pic ID {$this->id} forceDelete: Failed to delete original file at path: {$originalPath} on disk: {$diskName}");
                }
            } else {
                Log::warning("Pic ID {$this->id} forceDelete: Original file not found at path: {$originalPath} on disk: {$diskName}");
            }

            // 2. 刪除所有變體/縮圖
            $scaleSizes = $this->getRelatedScaleSizes();
            foreach ($scaleSizes as $scaleSize) {
                $variantPath = $pathGetter->getFullPathVariant(
                    width: $scaleSize['width'],
                    height: $scaleSize['height'],
                    fileType: $scaleSize['fileType'] ?? null, // 使用 scaleSize 中定義的 fileType
                );
                if ($storage->exists($variantPath)) {
                    if (!$storage->delete($variantPath)) {
                        Log::error("Pic ID {$this->id} forceDelete: Failed to delete variant file at path: {$variantPath} on disk: {$diskName}");
                    }
                } else {
                    Log::warning("Pic ID {$this->id} forceDelete: Variant file not found at path: {$variantPath} on disk: {$diskName}");
                }
            }

        } catch (\Exception $e) {
            // 記錄檔案刪除過程中發生的任何錯誤
            Log::error("Pic ID {$this->id} forceDelete: Error during file deletion: " . $e->getMessage(), [
                'exception' => $e,
                'pic_id' => $this->id,
                'file_name' => $this->file_name,
                'md5' => $this->md5,
            ]);
            // 根據需求決定是否要因為檔案刪除失敗而阻止資料庫記錄的刪除
            // throw $e; // 如果希望錯誤冒泡並可能回滾事務（如果在事務中）
        }

        $result = $this->softDeletesForceDelete();
        return $result;
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
            fileName: $this->file_name,
            md5: $this->md5,
            fileType: $this->file_type,
        );

        $diskName = config('attachment.upload_disk');
        if (empty($diskName)) {
            Log::error("Pic ID {$this->id} url: Storage disk name 'attachment.upload_disk' is not configured.");
            return ''; // 或者返回一個預設的錯誤圖片URL
        }

        if ($width === 0 && $height === 0) {
            return Storage::disk($diskName)->url(
                $pathGetter->getFullPath(),
            );
        }

        return Storage::disk($diskName)->url(
            $pathGetter->getFullPathVariant(
                width: $width,
                height: $height,
                fileType: $fileType ?? null,
            ),
        );
    }

    public function getUrlAttribute()
    {
        $scaleSizes = $this->getRelatedScaleSizes();
        $urls = [
            'w0h0' => $this->url(0, 0), // 原圖
        ];
        foreach ($scaleSizes as $scaleSize) {
            $key = 'w' . $scaleSize['width'] . 'h' . $scaleSize['height'];
            $urls[$key] = $this->url(
                $scaleSize['width'],
                $scaleSize['height'],
                $scaleSize['fileType'], // 使用 scaleSize 中定義的 fileType
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
            fileName: $this->file_name,
            md5: $this->md5,
            fileType: $this->file_type,
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
            fileType: $this->file_type,
        );

        $diskToUse = $uploadDisk ?? config('attachment.upload_disk');
        if (empty($diskToUse)) {
            Log::error("Pic ID {$this->id} download: Storage disk name is not configured.");
            // 可以拋出異常或返回錯誤回應
            throw new \Exception("Storage disk for download is not configured.");
        }


        return Storage::disk($diskToUse)->download($pathGetter->getFullPath(), $pathGetter->getFileName());
    }
}