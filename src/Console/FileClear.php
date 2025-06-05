<?php

namespace FF\Attachment\Console;

use FF\Attachment\File\Repositories\File as FileModel;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FileClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Soft delete unlinked files and permanently delete files soft-deleted over a week ago.';

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
        // --- 第一階段：軟刪除未連結的檔案 ---
        $this->comment('Starting to soft delete unlinked files...');
        $totalSoftDeleted = 0;

        FileModel::query()
            ->whereDate('created_at', '<=', now()->subDay()->toDateString())
            ->where(function (Builder $query) {
                $query
                    ->where('fileable_id', '')
                    ->orWhereNull('fileable_id');
            })
            ->chunkById(200, function (Collection $files) use (&$totalSoftDeleted) {
                $this->info("Processing a chunk of " . $files->count() . " files for soft deletion...");
                foreach ($files as $file) {
                    $file->delete(); // 執行軟刪除
                    $totalSoftDeleted++;
                }
            });

        if ($totalSoftDeleted > 0) {
            $this->info("Successfully soft-deleted {$totalSoftDeleted} unlinked files.");
        } else {
            $this->info('No unlinked files found to soft delete matching the criteria.');
        }
        $this->line('');

        $this->comment('Starting to permanently delete files soft-deleted over a week ago...');
        $totalPermanentlyDeleted = 0;
        $oneWeekAgo = now()->subWeek()->toDateTimeString();

        FileModel::onlyTrashed() // 只查詢被軟刪除的記錄
        ->where('deleted_at', '<=', $oneWeekAgo)
        ->chunkById(200, function (Collection $trashedFiles) use (&$totalPermanentlyDeleted) {
            $this->info("Processing a chunk of " . $trashedFiles->count() . " old soft-deleted files for permanent deletion...");
            foreach ($trashedFiles as $file) {
                $file->forceDelete();
                $totalPermanentlyDeleted++;
            }
        });

        if ($totalPermanentlyDeleted > 0) {
            $this->info("Successfully permanently deleted {$totalPermanentlyDeleted} files that were soft-deleted over a week ago.");
        } else {
            $this->info('No files found that were soft-deleted over a week ago and eligible for permanent deletion.');
        }

        $this->line('');
        $this->comment('Finished all file cleanup tasks.');
        return 0;
    }
}