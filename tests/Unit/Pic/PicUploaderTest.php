<?php

namespace Tests\Unit\Pic;

use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use FF\Attachment\Pic\Contracts\StorageUploader;
use FF\Attachment\Pic\PathGetter;
use FF\Attachment\Pic\PicFitResizer;
use FF\Attachment\Pic\PicReduceResizer;
use FF\Attachment\Pic\PicHandler;
use DB;

class PicUploaderTest extends TestCase
{

    public function testPathGetter()
    {
        $pathGetter = new PathGetter();
        $pathGetter->setParameter(id: 1, fileName: 'test.jpg', md5: 'qwerty');

        $this->assertEquals(
            'pic/00/00/00/01-qwerty.jpg',
            $pathGetter->getFullPath(),
        );

        $this->assertEquals('pic/00/00/00', $pathGetter->getDirectory());

        $this->assertEquals('01-qwerty.jpg', $pathGetter->getFileName());

        $pathGetter = new PathGetter();
        $pathGetter->setParameter(
            id: 1,
            fileName: 'test.jpg',
            md5: 'qwerty',
            fileType: 'image/png',
        );
        $this->assertEquals(
            $pathGetter->getFullPath(),
            'pic/00/00/00/01-qwerty.png',
        );
    }

    public function testUploadFromFilePath()
    {
        Storage::fake('testing');
        Storage::fake('public');

        $app = app();
        $app->bind(
            \FF\Attachment\Pic\Contracts\Repositories\Pic::class,
            TestingPic::class,
        );

        $app->when(\FF\Attachment\Pic\Validator::class)
            ->needs(\FF\Attachment\Pic\Contracts\Repositories\Pic::class)
            ->give(TestingPic::class);

        $app->when(\FF\Attachment\Pic\Repositories\PicCreator::class)
            ->needs('$picClassName')
            ->give(TestingPic::class);

        $width = 100;
        $height = 100;
        $fakePic = UploadedFile::fake()->image('test.jpg', $width, $height);
        $size = $fakePic->getSize();

        Storage::disk('testing')->putFileAs('/file', $fakePic, 'test.jpg');

        $storageUploader = app(StorageUploader::class);
        $storageUploader->setFile('testing', 'file/test.jpg', 'public');
        $uploadResult = $storageUploader->upload();

        $this->assertTrue($uploadResult);

        $pic = $storageUploader->getAttachment();

        $this->assertEquals($pic->id, 1);
        $this->assertEquals($pic->title, 'test.jpg');
        $this->assertEquals($pic->file_name, 'test.jpg');
        $this->assertEquals($pic->file_size, $size);
        $this->assertEquals($pic->file_type, 'image/jpeg');

        $this->assertEquals(
            $pic->url(0, 0),
            '/storage/pic/00/00/00/01-' . $pic->md5 . '.jpg',
        );
        $this->assertEquals(
            $pic->url(50, 50),
            '/storage/pic/00/00/00/01-' . $pic->md5 . '-w50h50.jpg',
        );
        $this->assertEquals(
            $pic->url(500, 500, 'image/png'),
            '/storage/pic/00/00/00/01-' . $pic->md5 . '-w500h500.png',
        );
        $this->assertEquals($pic->url, [
            'w0h0' => '/storage/pic/00/00/00/01-' . $pic->md5 . '.jpg',
            'w50h50' => '/storage/pic/00/00/00/01-' . $pic->md5 . '-w50h50.jpg',
            'w500h500' =>
                '/storage/pic/00/00/00/01-' . $pic->md5 . '-w500h500.png',
        ]);
        $this->assertEquals(
            $pic->downloadUrl,
            'http://localhost/api/pic/download/1',
        );

        $pathGetter = new PathGetter();
        $pathGetter->setParameter(
            id: $pic->id,
            fileName: 'test.jpg',
            md5: $pic->md5,
        );

        Storage::disk('public')->assertExists($pathGetter->getFullPath());

        $visibility = Storage::disk('public')->getVisibility(
            $pathGetter->getFullPath(),
        );

        $this->assertEquals('public', $visibility);
    }

    public function testPicResizer()
    {
        Storage::fake('testing');
        Storage::fake('public');

        $width = 100;
        $height = 100;
        $fakePic = UploadedFile::fake()->image('test.jpg', $width, $height);
        Storage::disk('testing')->putFileAs('/file', $fakePic, 'test.jpg');
        $picFile = Storage::disk('testing')->get('file/test.jpg');

        $picHandler = new PicHandler();
        $picHandler->setPic($picFile);
        $picHandler->setUploadDisk('public');
        $picHandler->setFullSavePath('test.jpg');

        $size = $picHandler->getOriginSize();
        $this->assertEquals($size['width'], 100);
        $this->assertEquals($size['height'], 100);

        $picFitResizer = new PicFitResizer();
        $picFitResizer->setPicHandler($picHandler);
        $picFitResizer->createPic(
            width: 200,
            height: 200,
            fileType: 'image/jpeg',
        );

        Storage::disk('public')->assertExists('test.jpg');
        $picPath = Storage::disk('public')->path('test.jpg');

        $size = getimagesize($picPath);
        $width = $size[0];
        $height = $size[1];

        $this->assertEquals($width, 200);
        $this->assertEquals($height, 200);

        $picFitResizer = new PicFitResizer();
        $picFitResizer->setPicHandler($picHandler);
        $picFitResizer->createPic(
            width: 50,
            height: 50,
            fileType: 'image/jpeg',
        );

        Storage::disk('public')->assertExists('test.jpg');
        $picPath = Storage::disk('public')->path('test.jpg');

        $size = getimagesize($picPath);
        $width = $size[0];
        $height = $size[1];

        $this->assertEquals($width, 50);
        $this->assertEquals($height, 50);

        $picReduceResizer = new PicReduceResizer();
        $picReduceResizer->setPicHandler($picHandler);
        $picReduceResizer->createPic(
            width: 50,
            height: 50,
            fileType: 'image/jpeg',
        );

        Storage::disk('public')->assertExists('test.jpg');
        $picPath = Storage::disk('public')->path('test.jpg');

        $size = getimagesize($picPath);
        $width = $size[0];
        $height = $size[1];

        $this->assertEquals($width, 50);
        $this->assertEquals($height, 50);
    }

    public function testPicReduceResizer()
    {
        Storage::fake('testing');
        Storage::fake('public');

        $width = 100;
        $height = 50;
        $fakePic = UploadedFile::fake()->image('test.jpg', $width, $height);
        Storage::disk('testing')->putFileAs('/file', $fakePic, 'test.jpg');
        $picFile = Storage::disk('testing')->get('file/test.jpg');

        $picHandler = new PicHandler();
        $picHandler->setPic($picFile);
        $picHandler->setUploadDisk('public');
        $picHandler->setFullSavePath('test.jpg');

        $picReduceResizer = new PicReduceResizer();
        $picReduceResizer->setPicHandler($picHandler);
        $picReduceResizer->createPic(
            width: 200,
            height: 200,
            fileType: 'image/jpeg',
        );

        $picPath = Storage::disk('public')->path('test.jpg');

        $size = getimagesize($picPath);
        $width = $size[0];
        $height = $size[1];

        $this->assertEquals($width, 200);
        $this->assertEquals($height, 100);

        $picReduceResizer = new PicReduceResizer();
        $picReduceResizer->setPicHandler($picHandler);
        $picReduceResizer->createPic(
            width: 50,
            height: 50,
            fileType: 'image/jpeg',
        );

        $picPath = Storage::disk('public')->path('test.jpg');

        $size = getimagesize($picPath);
        $width = $size[0];
        $height = $size[1];

        $this->assertEquals($width, 50);
        $this->assertEquals($height, 25);

        Storage::fake('testing');
        Storage::fake('public');

        $width = 50;
        $height = 100;
        $fakePic = UploadedFile::fake()->image('test.jpg', $width, $height);
        Storage::disk('testing')->putFileAs('/file', $fakePic, 'test.jpg');
        $picFile = Storage::disk('testing')->get('file/test.jpg');

        $picHandler = new PicHandler();
        $picHandler->setPic($picFile);
        $picHandler->setUploadDisk('public');
        $picHandler->setFullSavePath('test.jpg');

        $picReduceResizer = new PicReduceResizer();
        $picReduceResizer->setPicHandler($picHandler);
        $picReduceResizer->createPic(
            width: 200,
            height: 200,
            fileType: 'image/jpeg',
        );

        $picPath = Storage::disk('public')->path('test.jpg');

        $size = getimagesize($picPath);
        $width = $size[0];
        $height = $size[1];

        $this->assertEquals($width, 100);
        $this->assertEquals($height, 200);

        $picReduceResizer = new PicReduceResizer();
        $picReduceResizer->setPicHandler($picHandler);
        $picReduceResizer->createPic(
            width: 50,
            height: 50,
            fileType: 'image/jpeg',
        );

        $picPath = Storage::disk('public')->path('test.jpg');

        $size = getimagesize($picPath);
        $width = $size[0];
        $height = $size[1];

        $this->assertEquals($width, 25);
        $this->assertEquals($height, 50);
    }

    public function testProviderBindDifferentPic()
    {
        Storage::fake('testing');
        Storage::fake('public');

        $width = 100;
        $height = 100;
        $fakePic = UploadedFile::fake()->image('test.jpg', $width, $height);

        Storage::disk('testing')->putFileAs('/file', $fakePic, 'test.jpg');

        $storageUploader = app(StorageUploader::class);
        $storageUploader->setPicClassName(TestingPic::class);
        $storageUploader->setFile('testing', 'file/test.jpg', 'public');
        $storageUploader->upload();

        $pic = $storageUploader->getAttachment();

        $pathGetter = new PathGetter();
        $pathGetter->setParameter(
            id: $pic->id,
            fileName: 'test.jpg',
            md5: $pic->md5,
        );

        Storage::disk('public')->assertExists($pathGetter->getFullPath());

        Storage::disk('public')->assertExists(
            $pathGetter->getFullPathVariant(width: 50, height: 50),
        );

        Storage::disk('public')->assertExists(
            $pathGetter->getFullPathVariant(
                width: 500,
                height: 500,
                fileType: 'image/png',
            ),
        );
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
