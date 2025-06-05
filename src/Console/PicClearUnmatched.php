<?php

namespace FF\Attachment\Console;

use Illuminate\Console\Command;
use FF\Attachment\Pic\Repositories\Pic as PicModel;
use Illuminate\Support\Facades\Storage;

class PicClearUnmatched extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pic:clear-unmatched';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove pictures that are not referenced in the Pic ORM.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->comment('Start removing unmatched pictures...');

        $disk = config('attachment.upload_disk');
        $rootPath = 'pic';
        
        $totalDeletedCount = 0;

        // 掃描所有可能的目錄結構 (00-99)
        for ($dir1 = 0; $dir1 <= 99; $dir1++) {
            $dir1Str = sprintf('%02d', $dir1);
            
            if (!Storage::disk($disk)->exists($rootPath . '/' . $dir1Str)) {
                continue;
            }

            for ($dir2 = 0; $dir2 <= 99; $dir2++) {
                $dir2Str = sprintf('%02d', $dir2);
                $dir2Path = $rootPath . '/' . $dir1Str . '/' . $dir2Str;
                
                if (!Storage::disk($disk)->exists($dir2Path)) {
                    continue;
                }

                for ($dir3 = 0; $dir3 <= 99; $dir3++) {
                    $dir3Str = sprintf('%02d', $dir3);
                    $dir3Path = $dir2Path . '/' . $dir3Str;
                    
                    if (!Storage::disk($disk)->exists($dir3Path)) {
                        continue;
                    }

                    $this->comment("Processing directory: {$dir3Path}");
                    $deletedCount = $this->processDirectory($disk, $dir3Path, $dir1Str, $dir2Str, $dir3Str);
                    $totalDeletedCount += $deletedCount;
                    
                    if ($deletedCount > 0) {
                        $this->info("Deleted {$deletedCount} files from {$dir3Path}");
                    }
                }
            }
        }

        $this->comment("Finished removing unmatched pictures. Total deleted: {$totalDeletedCount}");
    }
    
    /**
     * 處理單一目錄內的檔案清理
     */
    private function processDirectory(string $disk, string $dir3Path, string $dir1, string $dir2, string $dir3): int
    {
        $deletedCount = 0;
        
        // 計算此目錄對應的 ID 範圍 (00000001-00000099)
        $baseId = intval($dir1 . $dir2 . $dir3 . '00'); // 最小ID
        $maxId = intval($dir1 . $dir2 . $dir3 . '99');  // 最大ID
        
        // 取得此 ID 範圍內的所有 pic 記錄，建立預期檔案清單
        $expectedFiles = collect();
        
        PicModel::withTrashed()
            ->whereBetween('id', [$baseId, $maxId])
            ->chunk(100, function ($pics) use (&$expectedFiles) {
                foreach ($pics as $pic) {
                    if (empty($pic->md5) || empty($pic->id) || empty($pic->file_name)) {
                        continue;
                    }
                    
                    // 建立 PathGetter 實例來計算路徑
                    $pathGetter = app(\FF\Attachment\Pic\PathGetter::class);
                    $pathGetter->setParameter(
                        id: $pic->id,
                        fileName: $pic->file_name,
                        md5: $pic->md5,
                        fileType: $pic->file_type,
                    );
                    
                    // 原圖路徑
                    $originalPath = $pathGetter->getFullPath();
                    $expectedFiles->push($originalPath);
                    
                    // 取得該 pic 的 scale sizes
                    $scaleSizes = $this->getRelatedScaleSizes($pic);
                    
                    // 所有縮圖路徑
                    foreach ($scaleSizes as $scaleSize) {
                        $variantPath = $pathGetter->getFullPathVariant(
                            width: $scaleSize['width'],
                            height: $scaleSize['height'],
                            fileType: $scaleSize['fileType'] ?? null,
                        );
                        $expectedFiles->push($variantPath);
                    }
                }
            });
        
        // 取得此目錄下的所有實際檔案
        $actualFiles = collect(Storage::disk($disk)->files($dir3Path));
        
        // 找出不在預期清單中的檔案並刪除
        foreach ($actualFiles as $filePath) {
            if (!$expectedFiles->contains($filePath)) {
                Storage::disk($disk)->delete($filePath);
                $deletedCount++;
                $this->line("Deleted: {$filePath}");
            }
        }
        
        return $deletedCount;
    }
    
    /**
     * 根據 pic 記錄的 picable_type 和 picable_attr 取得對應的縮放尺寸
     */
    private function getRelatedScaleSizes(PicModel $pic): array
    {
        if (empty($pic->picable_type)) {
            return PicModel::getScaleSizes();
        }

        try {
            if (!class_exists($pic->picable_type)) {
                return PicModel::getScaleSizes();
            }

            $relatedClass = $pic->picable_type;

            // 如果有 picable_attr，檢查該屬性是否有 getScaleSizes 方法
            if (!empty($pic->picable_attr)) {
                $relatedInstance = new $relatedClass();
                if (method_exists($relatedInstance, $pic->picable_attr)) {
                    $relation = $relatedInstance->{$pic->picable_attr}();
                    $relatedModel = $relation->getRelated();
                    if (method_exists($relatedModel, 'getScaleSizes')) {
                        return $relatedModel::getScaleSizes();
                    }
                }
                return PicModel::getScaleSizes();
            }

            // 如果沒有 picable_attr，檢查相關類別是否有 getScaleSizes 方法
            if (method_exists($relatedClass, 'getScaleSizes')) {
                return $relatedClass::getScaleSizes();
            }

            return PicModel::getScaleSizes();

        } catch (\Exception $e) {
            return PicModel::getScaleSizes();
        }
    }
}
