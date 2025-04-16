<?php

namespace FF\Attachment;

use FF\Attachment\Contracts\AttachmentProcessor as IAttachmentProcessor;
use FF\Attachment\Contracts\FileModifyer;
use FF\Attachment\Contracts\Repositories\Attachment;
use FF\Attachment\Contracts\Repositories\AttachmentCreator;
use FF\Attachment\Contracts\Validator;

abstract class AttachmentProcessor implements IAttachmentProcessor
{
    private ?Attachment $attachment = null;

    private string $errorMessage = '';

    private string $file;

    private int $fileSize;

    private string $originPathName;

    private string $fileName;

    private string $title;

    private string $fileType;

    private ?string $uploadDisk = null;

    public function __construct(
        protected Validator $validator,
        private FileModifyer $fileModifyer,
        protected AttachmentCreator $attachmentCreator
    ) {
    }

    public function setFile(
        string $file,
        int $fileSize,
        string $originPathName,
        string $fileName,
        string $title,
        string $fileType,
        string $uploadDisk = null
    ): void {
        $this->file = $file;
        $this->fileSize = $fileSize;
        $this->originPathName = $originPathName;
        $this->fileName = $fileName;
        $this->title = $title;
        $this->fileType = $fileType;

        if ($uploadDisk) {
            $this->uploadDisk = $uploadDisk;
        }
    }

    public function run(): bool
    {
        if ($this->errorMessage) {
            return false;
        }

        if (!$this->validateFile()) {
            return false;
        }

        $createResult = $this->createFileData();

        if ($createResult === false) {
            return false;
        }

        $moveResult = $this->modifyFile();

        if (!$moveResult) {
            $this->attachment->delete();
            return $moveResult;
        }

        return true;
    }

    public function validateFile(): bool
    {
        $result = $this->validator->validate(
            file: $this->file,
            fileSize: $this->fileSize,
            originPathName: $this->originPathName,
            fileName: $this->fileName,
            title: $this->title,
        );

        if (!$result) {
            $this->errorMessage = $this->validator->getErrorMessage();
            return false;
        }

        return true;
    }

    private function createFileData(): bool
    {
        $result = $this->attachmentCreator->create(
            fileName: $this->fileName,
            title: $this->title,
            fileType: $this->fileType,
            fileSize: $this->fileSize,
        );

        if (!$result) {
            $this->errorMessage = $this->attachmentCreator->getErrorMessage();
            return false;
        }

        $this->attachment = $this->attachmentCreator->getORM();

        return true;
    }

    private function modifyFile()
    {
        return $this->fileModifyer->modify(
            attachment: $this->attachment,
            file: $this->file,
            fileType: $this->fileType,
            uploadDisk: $this->uploadDisk,
        );
    }

    public function setUploadDisk(string $uploadDisk)
    {
        $this->uploadDisk = $uploadDisk;
    }

    public function getAttachment(): Attachment
    {
        return $this->attachment;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }
}
