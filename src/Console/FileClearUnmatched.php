<?php

namespace FF\Attachment\Console;

use Illuminate\Console\Command;
use FF\Attachment\File\Repositories\File as FileModel;
use Illuminate\Support\Facades\Storage;

class FileClearUnmatched extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file:clear-unmatched';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove files that are not referenced in the File ORM.';

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
        $this->comment('Start removing unmatched files...');

        $disk = config('attachment.upload_disk');
        $rootPath = 'file';
        
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

        $this->comment("Finished removing unmatched files. Total deleted: {$totalDeletedCount}");
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
        
        // 取得此 ID 範圍內的所有 file 記錄，建立預期檔案清單
        $expectedFiles = collect();
        
        FileModel::withTrashed()
            ->whereBetween('id', [$baseId, $maxId])
            ->chunk(100, function ($files) use (&$expectedFiles) {
                foreach ($files as $file) {
                    if (empty($file->md5) || empty($file->id) || empty($file->file_name)) {
                        continue;
                    }
                    
                    // 建立 PathGetter 實例來計算路徑
                    $pathGetter = app(\FF\Attachment\File\PathGetter::class);
                    $pathGetter->setParameter(
                        id: $file->id,
                        fileName: $file->file_name,
                        md5: $file->md5,
                        fileType: $file->file_type,
                    );
                    
                    // File 只有原檔案，沒有縮圖變體
                    $originalPath = $pathGetter->getFullPath();
                    $expectedFiles->push($originalPath);
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
}