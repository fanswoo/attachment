<?php

namespace Tests\Unit\Pic;

use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use FF\Attachment\Pic\Contracts\StorageUploader;
use FF\Attachment\Pic\PathGetter;

class PicForceDeleteTest extends TestCase
{
    public function testForceDeleteRemovesRecordFromDatabase()
    {
        Storage::fake('testing');
        Storage::fake('public');
        
        // Set the upload disk configuration
        config(['attachment.upload_disk' => 'public']);

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

        // Create a test image and upload it
        $fakePic = UploadedFile::fake()->image('test.jpg', 100, 100);
        Storage::disk('testing')->putFileAs('/file', $fakePic, 'test.jpg');

        $storageUploader = app(StorageUploader::class);
        $storageUploader->setFile('testing', 'file/test.jpg', 'public');
        $storageUploader->upload();

        $pic = $storageUploader->getAttachment();
        $picId = $pic->id;

        // Verify the record exists
        $this->assertNotNull($pic);
        $this->assertEquals('test.jpg', $pic->file_name);

        // Verify files exist in storage
        $pathGetter = new PathGetter();
        $pathGetter->setParameter(
            id: $pic->id,
            fileName: $pic->file_name,
            md5: $pic->md5,
            fileType: $pic->file_type
        );

        Storage::disk('public')->assertExists($pathGetter->getFullPath());

        // Check that variant files exist (based on TestingPic::getScaleSizes())
        $scaleSizes = TestingPic::getScaleSizes();
        foreach ($scaleSizes as $scaleSize) {
            $variantPath = $pathGetter->getFullPathVariant(
                width: $scaleSize['width'],
                height: $scaleSize['height'],
                fileType: $scaleSize['fileType'] ?? null
            );
            Storage::disk('public')->assertExists($variantPath);
        }

        // Force delete the record
        $result = $pic->forceDelete();

        // Verify the deletion was successful
        $this->assertTrue($result, 'forceDelete() should return true');

        // Verify the record is completely removed from database (not just soft deleted)
        $deletedPic = TestingPic::withTrashed()->find($picId);
        $this->assertNull($deletedPic, 'Record should be completely removed from database');

        // Verify files are deleted from storage
        Storage::disk('public')->assertMissing($pathGetter->getFullPath());

        // Verify variant files are deleted
        foreach ($scaleSizes as $scaleSize) {
            $variantPath = $pathGetter->getFullPathVariant(
                width: $scaleSize['width'],
                height: $scaleSize['height'],
                fileType: $scaleSize['fileType'] ?? null
            );
            Storage::disk('public')->assertMissing($variantPath);
        }
    }

    public function testForceDeleteWithMissingFileInfo()
    {
        Storage::fake('public');

        // Create a pic record directly without files
        $pic = new TestingPic();
        $pic->title = 'test.jpg';
        $pic->file_name = '';  // Missing file name
        $pic->md5 = '';        // Missing md5
        $pic->file_type = 'image/jpeg';
        $pic->file_size = '0'; // Required field
        $pic->save();

        $picId = $pic->id;

        // Force delete should still work even with missing file info
        $result = $pic->forceDelete();

        $this->assertTrue($result, 'forceDelete() should return true even with missing file info');

        // Verify the record is removed from database
        $deletedPic = TestingPic::withTrashed()->find($picId);
        $this->assertNull($deletedPic, 'Record should be completely removed from database');
    }

    public function testForceDeleteWithInvalidStorageDisk()
    {
        // Set invalid storage disk in config
        config(['attachment.upload_disk' => '']);

        // Create a pic record
        $pic = new TestingPic();
        $pic->title = 'test.jpg';
        $pic->file_name = 'test.jpg';
        $pic->md5 = 'testmd5';
        $pic->file_type = 'image/jpeg';
        $pic->file_size = '1024'; // Required field
        $pic->save();

        $picId = $pic->id;

        // Force delete should still work even with invalid storage config
        $result = $pic->forceDelete();

        $this->assertTrue($result, 'forceDelete() should return true even with invalid storage config');

        // Verify the record is removed from database
        $deletedPic = TestingPic::withTrashed()->find($picId);
        $this->assertNull($deletedPic, 'Record should be completely removed from database');
    }

    public function testSoftDeleteVsForceDelete()
    {
        Storage::fake('testing');
        Storage::fake('public');
        
        // Set the upload disk configuration
        config(['attachment.upload_disk' => 'public']);

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

        // Create two test images
        $fakePic1 = UploadedFile::fake()->image('test1.jpg', 100, 100);
        $fakePic2 = UploadedFile::fake()->image('test2.jpg', 100, 100);

        Storage::disk('testing')->putFileAs('/file', $fakePic1, 'test1.jpg');
        Storage::disk('testing')->putFileAs('/file', $fakePic2, 'test2.jpg');

        // Upload first pic
        $storageUploader1 = app(StorageUploader::class);
        $storageUploader1->setFile('testing', 'file/test1.jpg', 'public');
        $storageUploader1->upload();
        $pic1 = $storageUploader1->getAttachment();

        // Upload second pic
        $storageUploader2 = app(StorageUploader::class);
        $storageUploader2->setFile('testing', 'file/test2.jpg', 'public');
        $storageUploader2->upload();
        $pic2 = $storageUploader2->getAttachment();

        $pic1Id = $pic1->id;
        $pic2Id = $pic2->id;

        // Soft delete first pic
        $pic1->delete();

        // Verify first pic is soft deleted
        $softDeletedPic = TestingPic::withTrashed()->find($pic1Id);
        $this->assertNotNull($softDeletedPic, 'Soft deleted record should still exist');
        $this->assertNotNull($softDeletedPic->deleted_at, 'deleted_at should be set');

        // Verify first pic is not found in normal queries
        $normalQuery = TestingPic::find($pic1Id);
        $this->assertNull($normalQuery, 'Soft deleted record should not appear in normal queries');

        // Force delete second pic
        $result = $pic2->forceDelete();
        $this->assertTrue($result);

        // Verify second pic is completely removed
        $forceDeletedPic = TestingPic::withTrashed()->find($pic2Id);
        $this->assertNull($forceDeletedPic, 'Force deleted record should be completely removed');

        // Now force delete the soft deleted pic
        $result = $softDeletedPic->forceDelete();
        $this->assertTrue($result);

        // Verify it's completely removed
        $finalCheck = TestingPic::withTrashed()->find($pic1Id);
        $this->assertNull($finalCheck, 'Force deleted record should be completely removed');
    }
}