<?php

namespace FF\Attachment\Attachment;

use Illuminate\Support\ServiceProvider;

class AttachmentProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerAttachment();
        $this->registerFile();
        $this->registerPic();
    }

    private function registerAttachment()
    {
        $this->app->bind(
            \FF\Attachment\Attachment\Contracts\Validator::class,
            \FF\Attachment\File\Validator::class,
        );
    }

    private function registerFile()
    {
        $this->app->bind(
            \FF\Attachment\File\Contracts\StorageUploader::class,
            \FF\Attachment\File\StorageUploader::class,
        );

        $this->app
            ->when(\FF\Attachment\File\FileModifyer::class)
            ->needs(\FF\Attachment\Attachment\Contracts\PathGetter::class)
            ->give(\FF\Attachment\File\PathGetter::class);

        $this->app
            ->when(\FF\Attachment\File\StorageUploader::class)
            ->needs(
                \FF\Attachment\Attachment\Contracts\AttachmentProcessor::class,
            )
            ->give(\FF\Attachment\File\AttachmentProcessor::class);

        $this->app
            ->when(\FF\Attachment\File\Uploader::class)
            ->needs(
                \FF\Attachment\Attachment\Contracts\AttachmentProcessor::class,
            )
            ->give(\FF\Attachment\File\AttachmentProcessor::class);

        $this->app
            ->when(\FF\Attachment\File\AttachmentProcessor::class)
            ->needs(\FF\Attachment\Attachment\Contracts\FileModifyer::class)
            ->give(\FF\Attachment\File\FileModifyer::class);

        $this->app
            ->when(\FF\Attachment\File\AttachmentProcessor::class)
            ->needs(\FF\Attachment\Attachment\Contracts\Validator::class)
            ->give(\FF\Attachment\File\Validator::class);

        $this->app
            ->when(\FF\Attachment\File\AttachmentProcessor::class)
            ->needs(
                \FF\Attachment\Attachment\Contracts\Repositories\AttachmentCreator::class,
            )
            ->give(\FF\Attachment\File\Repositories\FileCreator::class);

        $this->app->bind(
            \FF\Attachment\File\Contracts\Controllers\FileController::class,
            \FF\Attachment\File\Controllers\FileController::class,
        );

        $this->app
            ->when(\FF\Attachment\File\Controllers\FileController::class)
            ->needs(\FF\Attachment\File\Contracts\MutipleUploader::class)
            ->give(\FF\Attachment\File\MutipleUploader::class);

        $this->app->bind(
            \FF\Attachment\File\Contracts\MutipleUploader::class,
            \FF\Attachment\File\MutipleUploader::class,
        );

        $this->app
            ->when(\FF\Attachment\File\Validator::class)
            ->needs('$fileClassName')
            ->give(\FF\Attachment\File\Repositories\File::class);

        $this->app->bind(
            \FF\Attachment\File\Contracts\Repositories\File::class,
            \FF\Attachment\File\Repositories\File::class,
        );

        $this->app
            ->when(\FF\Attachment\File\Repositories\FileCreator::class)
            ->needs('$fileClassName')
            ->give(\FF\Attachment\File\Repositories\File::class);
    }

    private function registerPic()
    {
        $this->app->bind(
            \FF\Attachment\Pic\Contracts\UrlUploader::class,
            \FF\Attachment\Pic\UrlUploader::class,
        );

        $this->app->bind(
            \FF\Attachment\Pic\Contracts\StorageUploader::class,
            \FF\Attachment\Pic\StorageUploader::class,
        );

        $this->app
            ->when(\FF\Attachment\Pic\FileModifyer::class)
            ->needs(\FF\Attachment\Attachment\Contracts\PathGetter::class)
            ->give(\FF\Attachment\Pic\PathGetter::class);

        $this->app
            ->when(\FF\Attachment\Pic\StorageUploader::class)
            ->needs(
                \FF\Attachment\Attachment\Contracts\AttachmentProcessor::class,
            )
            ->give(\FF\Attachment\Pic\AttachmentProcessor::class);

        $this->app
            ->when(\FF\Attachment\Pic\UrlUploader::class)
            ->needs(
                \FF\Attachment\Attachment\Contracts\AttachmentProcessor::class,
            )
            ->give(\FF\Attachment\Pic\AttachmentProcessor::class);

        $this->app
            ->when(\FF\Attachment\Pic\Uploader::class)
            ->needs(
                \FF\Attachment\Attachment\Contracts\AttachmentProcessor::class,
            )
            ->give(\FF\Attachment\Pic\AttachmentProcessor::class);

        $this->app
            ->when(\FF\Attachment\Pic\AttachmentProcessor::class)
            ->needs(
                \FF\Attachment\Attachment\Contracts\AttachmentProcessor::class,
            )
            ->give(\FF\Attachment\Pic\AttachmentProcessor::class);

        $this->app
            ->when(\FF\Attachment\Pic\AttachmentProcessor::class)
            ->needs(\FF\Attachment\Attachment\Contracts\FileModifyer::class)
            ->give(\FF\Attachment\Pic\FileModifyer::class);

        $this->app
            ->when(\FF\Attachment\Pic\AttachmentProcessor::class)
            ->needs(\FF\Attachment\Attachment\Contracts\Validator::class)
            ->give(\FF\Attachment\Pic\Validator::class);

        $this->app
            ->when(\FF\Attachment\Pic\AttachmentProcessor::class)
            ->needs(
                \FF\Attachment\Attachment\Contracts\Repositories\AttachmentCreator::class,
            )
            ->give(\FF\Attachment\Pic\Repositories\PicCreator::class);

        $this->app->bind(
            \FF\Attachment\Pic\Contracts\Controllers\PicController::class,
            \FF\Attachment\Pic\Controllers\PicController::class,
        );

        $this->app->bind(
            \FF\Attachment\Pic\Contracts\Controllers\PicController::class,
            \FF\Attachment\Pic\Controllers\PicController::class,
        );

        $this->app
            ->when(\FF\Attachment\Pic\Controllers\PicController::class)
            ->needs(\FF\Attachment\Pic\Contracts\MutipleUploader::class)
            ->give(\FF\Attachment\Pic\MutipleUploader::class);

        $this->app->bind(
            \FF\Attachment\Pic\Contracts\Controllers\PicCKEditorController::class,
            \FF\Attachment\Pic\Controllers\PicCKEditorController::class,
        );

        $this->app->bind(
            \FF\Attachment\Attachment\Contracts\Uploader::class,
            \FF\Attachment\Pic\Uploader::class
        );

        $this->app->bind(
            \FF\Attachment\Pic\Contracts\MutipleUploader::class,
            \FF\Attachment\Pic\MutipleUploader::class,
        );

        $this->app->bind(
            \FF\Attachment\Pic\Contracts\Validator::class,
            \FF\Attachment\Pic\Validator::class,
        );

        $this->app
            ->when(\FF\Attachment\Pic\Validator::class)
            ->needs('$picClassName')
            ->give(\FF\Attachment\Pic\Repositories\Pic::class);

        $this->app
            ->when(\FF\Attachment\Pic\Repositories\PicCreator::class)
            ->needs('$picClassName')
            ->give(\FF\Attachment\Pic\Repositories\Pic::class);
    }
}
