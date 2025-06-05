<?php

namespace FF\Attachment\Console;

use FF\Attachment\Pic\Repositories\Pic as PicModel;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PicClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // 讓 chunk 大小可以透過參數設定，並應用於兩階段的清理
    protected $signature = 'pic:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    // 更新描述以反映命令的完整功能
    protected $description = 'Soft delete unlinked pictures and permanently delete pictures soft-deleted over a week ago.';

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
        $this->comment('Starting to soft delete unlinked pictures...');
        $totalSoftDeleted = 0;

        PicModel::query()
            ->whereDate('created_at', '<=', now()->subDay()->toDateString())
            ->where(function (Builder $query) {
                $query
                    ->where('picable_id', '')
                    ->orWhereNull('picable_id');
            })
            ->chunkById(200, function (Collection $pics) use (&$totalSoftDeleted) {
                $this->info("Processing a chunk of " . $pics->count() . " pictures for soft deletion...");
                foreach ($pics as $pic) {
                    $pic->delete(); // 執行軟刪除
                    $totalSoftDeleted++;
                }
            });

        if ($totalSoftDeleted > 0) {
            $this->info("Successfully soft-deleted {$totalSoftDeleted} unlinked pictures.");
        } else {
            $this->info('No unlinked pictures found to soft delete matching the criteria.');
        }
        $this->line('');

        $this->comment('Starting to permanently delete pictures soft-deleted over a week ago...');
        $totalPermanentlyDeleted = 0;
        $oneWeekAgo = now()->subWeek()->toDateTimeString(); // 計算一個月前的時間

        PicModel::onlyTrashed() // 只查詢被軟刪除的記錄
        ->where('deleted_at', '<=', $oneWeekAgo)
        ->chunkById(200, function (Collection $trashedPics) use (&$totalPermanentlyDeleted) {
            $this->info("Processing a chunk of " . $trashedPics->count() . " old soft-deleted pictures for permanent deletion...");
            foreach ($trashedPics as $pic) {
                $pic->forceDelete();
                $totalPermanentlyDeleted++;
            }
        });

        if ($totalPermanentlyDeleted > 0) {
            $this->info("Successfully permanently deleted {$totalPermanentlyDeleted} pictures that were soft-deleted over a week ago.");
        } else {
            $this->info('No pictures found that were soft-deleted over a week ago and eligible for permanent deletion.');
        }

        $this->line('');
        $this->comment('Finished all picture cleanup tasks.');
        return 0;
    }
}