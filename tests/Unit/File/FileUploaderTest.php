<?php

namespace Tests\Unit\File;

use FF\Attachment\File\Contracts\StorageUploader;
use FF\Attachment\File\PathGetter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploaderTest extends TestCase
{
    public function testPathGetter()
    {
        $pathGetter = new PathGetter();
        $pathGetter->setParameter(id: 1, fileName: 'test.jpg', md5: 'qwertyui');

        $this->assertEquals(
            'file/00/00/00/01-qwertyui.jpg',
            $pathGetter->getFullPath(),
        );

        $this->assertEquals('file/00/00/00', $pathGetter->getDirectory());

        $this->assertEquals('01-qwertyui.jpg', $pathGetter->getFileName());
    }

    public function testUploadFromFilePath()
    {
        Storage::fake('testing');
        Storage::fake('local_public');

        $width = 100;
        $height = 100;
        $fakePic = UploadedFile::fake()->image('test.jpg', $width, $height);
        $size = $fakePic->getSize();

        Storage::disk('testing')->putFileAs('/file', $fakePic, 'test.jpg');

        $storageUploader = app(StorageUploader::class);
        $storageUploader->setFile(
            'testing',
            'file/test.jpg',
            'local_public',
        );
        $uploadResult = $storageUploader->upload();

        $this->assertTrue($uploadResult);

        $file = $storageUploader->getAttachment();

        $this->assertEquals($file->id, 1);
        $this->assertEquals($file->title, 'test.jpg');
        $this->assertEquals($file->file_name, 'test.jpg');
        $this->assertEquals($file->file_size, $size);
        $this->assertEquals($file->file_type, 'image/jpeg');
        $this->assertEquals(
            $file->downloadUrl,
            'http://localhost/api/file/download/1',
        );

        $pathGetter = new PathGetter();
        $pathGetter->setParameter(
            id: $file->id,
            fileName: 'test.jpg',
            md5: $file->md5,
        );

        Storage::disk('local_public')->assertExists(
            $pathGetter->getFullPath(),
        );

        $visibility = Storage::disk('local_public')->getVisibility(
            $pathGetter->getFullPath(),
        );

        $this->assertEquals('public', $visibility);
    }

    // public function testSameTimeStorageDiskAndFilePathError()
    // {
    //     $this->expectErrorMessage(
    //         '$storageDisk and $filePath must be set at the same time.',
    //     );

    //     $storageUploader = new StorageUploader();
    //     $storageUploader->setFile(filePath: 'file/test.jpg');
    // }

    // public function testFilePathNotFoundError()
    // {
    //     $this->expectErrorMessage('file path not found');
    //     new StorageUploader(
    //         storageDisk: 'storage',
    //         filePath: 'file/fails-name.jpg',
    //     );
    // }
}
