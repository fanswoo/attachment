<?php

namespace FF\Attachment;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AttachmentProvider extends ServiceProvider
{
    public function boot()
    {
        Route::post(
            'pic/upload',
            '\FF\Attachment\Pic\Contracts\Controllers\PicController@upload',
        );

        Route::any(
            'pic/delete',
            '\FF\Attachment\Pic\Contracts\Controllers\PicController@delete',
        );

        Route::post(
            'file/upload',
            '\FF\Attachment\File\Contracts\Controllers\FileController@upload',
        );

        Route::get(
            'file/download/{id}',
            '\FF\Attachment\File\Contracts\Controllers\FileController@download',
        );

        Route::any(
            'file/delete',
            '\FF\Attachment\File\Contracts\Controllers\FileController@delete',
        );

        Route::any(
            'file/rename',
            '\FF\Attachment\File\Contracts\Controllers\FileController@rename',
        );
    }

    public function register()
    {
        $this->registerAttachment();
        $this->registerFile();
        $this->registerPic();
    }

    private function registerAttachment()
    {
        $this->app->bind(
            Contracts\Validator::class,
            \File\Validator::class,
        );
    }

    private function registerFile()
    {
        $this->app->bind(
            \File\Contracts\StorageUploader::class,
            \File\StorageUploader::class,
        );

        $this->app
            ->when(\File\FileModifyer::class)
            ->needs(Contracts\PathGetter::class)
            ->give(\File\PathGetter::class);

        $this->app
            ->when(\File\StorageUploader::class)
            ->needs(
                Contracts\AttachmentProcessor::class,
            )
            ->give(\File\AttachmentProcessor::class);

        $this->app
            ->when(\File\Uploader::class)
            ->needs(
                Contracts\AttachmentProcessor::class,
            )
            ->give(\File\AttachmentProcessor::class);

        $this->app
            ->when(\File\AttachmentProcessor::class)
            ->needs(Contracts\FileModifyer::class)
            ->give(\File\FileModifyer::class);

        $this->app
            ->when(\File\AttachmentProcessor::class)
            ->needs(Contracts\Validator::class)
            ->give(\File\Validator::class);

        $this->app
            ->when(\File\AttachmentProcessor::class)
            ->needs(
                Contracts\Repositories\AttachmentCreator::class,
            )
            ->give(\File\Repositories\FileCreator::class);

        $this->app->bind(
            \File\Contracts\Controllers\FileController::class,
            \File\Controllers\FileController::class,
        );

        $this->app
            ->when(\File\Controllers\FileController::class)
            ->needs(\File\Contracts\MutipleUploader::class)
            ->give(\File\MutipleUploader::class);

        $this->app->bind(
            \File\Contracts\MutipleUploader::class,
            \File\MutipleUploader::class,
        );

        $this->app
            ->when(\File\Validator::class)
            ->needs('$fileClassName')
            ->give(\File\Repositories\File::class);

        $this->app->bind(
            \File\Contracts\Repositories\File::class,
            \File\Repositories\File::class,
        );

        $this->app
            ->when(\File\Repositories\FileCreator::class)
            ->needs('$fileClassName')
            ->give(\File\Repositories\File::class);
    }

    private function registerPic()
    {
        $this->app->bind(
            \Pic\Contracts\UrlUploader::class,
            \Pic\UrlUploader::class,
        );

        $this->app->bind(
            \Pic\Contracts\StorageUploader::class,
            \Pic\StorageUploader::class,
        );

        $this->app
            ->when(\Pic\FileModifyer::class)
            ->needs(Contracts\PathGetter::class)
            ->give(\Pic\PathGetter::class);

        $this->app
            ->when(\Pic\StorageUploader::class)
            ->needs(
                Contracts\AttachmentProcessor::class,
            )
            ->give(\Pic\AttachmentProcessor::class);

        $this->app
            ->when(\Pic\UrlUploader::class)
            ->needs(
                Contracts\AttachmentProcessor::class,
            )
            ->give(\Pic\AttachmentProcessor::class);

        $this->app
            ->when(\Pic\Uploader::class)
            ->needs(
                Contracts\AttachmentProcessor::class,
            )
            ->give(\Pic\AttachmentProcessor::class);

        $this->app
            ->when(\Pic\AttachmentProcessor::class)
            ->needs(
                Contracts\AttachmentProcessor::class,
            )
            ->give(\Pic\AttachmentProcessor::class);

        $this->app
            ->when(\Pic\AttachmentProcessor::class)
            ->needs(Contracts\FileModifyer::class)
            ->give(\Pic\FileModifyer::class);

        $this->app
            ->when(\Pic\AttachmentProcessor::class)
            ->needs(Contracts\Validator::class)
            ->give(\Pic\Validator::class);

        $this->app
            ->when(\Pic\AttachmentProcessor::class)
            ->needs(
                Contracts\Repositories\AttachmentCreator::class,
            )
            ->give(\Pic\Repositories\PicCreator::class);

        $this->app->bind(
            \Pic\Contracts\Controllers\PicController::class,
            \Pic\Controllers\PicController::class,
        );

        $this->app->bind(
            \Pic\Contracts\Controllers\PicController::class,
            \Pic\Controllers\PicController::class,
        );

        $this->app
            ->when(\Pic\Controllers\PicController::class)
            ->needs(\Pic\Contracts\MutipleUploader::class)
            ->give(\Pic\MutipleUploader::class);

        $this->app->bind(
            \Pic\Contracts\Controllers\PicCKEditorController::class,
            \Pic\Controllers\PicCKEditorController::class,
        );

        $this->app->bind(
            Contracts\Uploader::class,
            \Pic\Uploader::class
        );

        $this->app->bind(
            \Pic\Contracts\MutipleUploader::class,
            \Pic\MutipleUploader::class,
        );

        $this->app->bind(
            \Pic\Contracts\Validator::class,
            \Pic\Validator::class,
        );

        $this->app
            ->when(\Pic\Validator::class)
            ->needs('$picClassName')
            ->give(\Pic\Repositories\Pic::class);

        $this->app
            ->when(\Pic\Repositories\PicCreator::class)
            ->needs('$picClassName')
            ->give(\Pic\Repositories\Pic::class);
    }
}
