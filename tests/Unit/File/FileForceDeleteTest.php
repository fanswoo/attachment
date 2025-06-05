<?php

namespace Tests\Unit\File;

use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use FF\Attachment\File\Contracts\StorageUploader;
use FF\Attachment\File\PathGetter;
use FF\Attachment\File\Repositories\File;

class FileForceDeleteTest extends TestCase
{
    public function testForceDeleteRemovesRecordFromDatabase()
    {
        Storage::fake('testing');
        Storage::fake('local_public');
        
        // Set the upload disk configuration
        config(['attachment.upload_disk' => 'local_public']);

        // Create a test file and upload it (use image like the working test)
        $fakeFile = UploadedFile::fake()->image('test.jpg', 100, 100);
        $size = $fakeFile->getSize();
        Storage::disk('testing')->putFileAs('/file', $fakeFile, 'test.jpg');

        $storageUploader = app(StorageUploader::class);
        $storageUploader->setFile('testing', 'file/test.jpg', 'local_public');
        $uploadResult = $storageUploader->upload();
        
        $this->assertTrue($uploadResult);

        $file = $storageUploader->getAttachment();
        $fileId = $file->id;

        // Verify the record exists
        $this->assertNotNull($file);
        $this->assertEquals('test.jpg', $file->file_name);

        // Verify file exists in storage
        $pathGetter = new PathGetter();
        $pathGetter->setParameter(
            id: $file->id,
            fileName: $file->file_name,
            md5: $file->md5,
            fileType: $file->file_type
        );

        Storage::disk('local_public')->assertExists($pathGetter->getFullPath());

        // Force delete the record
        $result = $file->forceDelete();

        // Verify the deletion was successful
        $this->assertTrue($result, 'forceDelete() should return true');

        // Verify the record is completely removed from database (not just soft deleted)
        $deletedFile = File::withTrashed()->find($fileId);
        $this->assertNull($deletedFile, 'Record should be completely removed from database');

        // Verify file is deleted from storage
        Storage::disk('local_public')->assertMissing($pathGetter->getFullPath());
    }

    public function testForceDeleteWithMissingFileInfo()
    {
        Storage::fake('local_public');

        // Create a file record directly without files
        $file = new File();
        $file->title = 'test.txt';
        $file->file_name = '';  // Missing file name
        $file->md5 = '';        // Missing md5
        $file->file_type = 'text/plain';
        $file->file_size = '0'; // Required field
        $file->save();

        $fileId = $file->id;

        // Force delete should still work even with missing file info
        $result = $file->forceDelete();

        $this->assertTrue($result, 'forceDelete() should return true even with missing file info');

        // Verify the record is removed from database
        $deletedFile = File::withTrashed()->find($fileId);
        $this->assertNull($deletedFile, 'Record should be completely removed from database');
    }

    public function testForceDeleteWithInvalidStorageDisk()
    {
        // Set invalid storage disk in config
        config(['attachment.upload_disk' => '']);

        // Create a file record
        $file = new File();
        $file->title = 'test.txt';
        $file->file_name = 'test.txt';
        $file->md5 = 'testmd5';
        $file->file_type = 'text/plain';
        $file->file_size = '1024'; // Required field
        $file->save();

        $fileId = $file->id;

        // Force delete should still work even with invalid storage config
        $result = $file->forceDelete();

        $this->assertTrue($result, 'forceDelete() should return true even with invalid storage config');

        // Verify the record is removed from database
        $deletedFile = File::withTrashed()->find($fileId);
        $this->assertNull($deletedFile, 'Record should be completely removed from database');
    }

    public function testSoftDeleteVsForceDelete()
    {
        Storage::fake('testing');
        Storage::fake('local_public');
        
        // Set the upload disk configuration
        config(['attachment.upload_disk' => 'local_public']);

        // Create two test files
        $fakeFile1 = UploadedFile::fake()->image('test1.jpg', 100, 100);
        $fakeFile2 = UploadedFile::fake()->image('test2.jpg', 100, 100);

        Storage::disk('testing')->putFileAs('/file', $fakeFile1, 'test1.jpg');
        Storage::disk('testing')->putFileAs('/file', $fakeFile2, 'test2.jpg');

        // Upload first file
        $storageUploader1 = app(StorageUploader::class);
        $storageUploader1->setFile('testing', 'file/test1.jpg', 'local_public');
        $storageUploader1->upload();
        $file1 = $storageUploader1->getAttachment();

        // Upload second file
        $storageUploader2 = app(StorageUploader::class);
        $storageUploader2->setFile('testing', 'file/test2.jpg', 'local_public');
        $storageUploader2->upload();
        $file2 = $storageUploader2->getAttachment();

        $file1Id = $file1->id;
        $file2Id = $file2->id;

        // Soft delete first file
        $file1->delete();

        // Verify first file is soft deleted
        $softDeletedFile = File::withTrashed()->find($file1Id);
        $this->assertNotNull($softDeletedFile, 'Soft deleted record should still exist');
        $this->assertNotNull($softDeletedFile->deleted_at, 'deleted_at should be set');

        // Verify first file is not found in normal queries
        $normalQuery = File::find($file1Id);
        $this->assertNull($normalQuery, 'Soft deleted record should not appear in normal queries');

        // Force delete second file
        $result = $file2->forceDelete();
        $this->assertTrue($result);

        // Verify second file is completely removed
        $forceDeletedFile = File::withTrashed()->find($file2Id);
        $this->assertNull($forceDeletedFile, 'Force deleted record should be completely removed');

        // Now force delete the soft deleted file
        $result = $softDeletedFile->forceDelete();
        $this->assertTrue($result);

        // Verify it's completely removed
        $finalCheck = File::withTrashed()->find($file1Id);
        $this->assertNull($finalCheck, 'Force deleted record should be completely removed');
    }

    public function testForceDeleteWithLargeFile()
    {
        Storage::fake('testing');
        Storage::fake('local_public');
        
        // Set the upload disk configuration
        config(['attachment.upload_disk' => 'local_public']);

        // Create a larger test file
        $fakeFile = UploadedFile::fake()->image('large_test.jpg', 1000, 1000); // Large image
        Storage::disk('testing')->putFileAs('/file', $fakeFile, 'large_test.jpg');

        $storageUploader = app(StorageUploader::class);
        $storageUploader->setFile('testing', 'file/large_test.jpg', 'local_public');
        $storageUploader->upload();

        $file = $storageUploader->getAttachment();
        $fileId = $file->id;

        // Verify the file is large (adjust expectation for fake file)
        $this->assertGreaterThan(10000, (int)$file->file_size, 'File should be reasonably large');
        $this->assertEquals('image/jpeg', $file->file_type);

        // Verify file exists in storage
        $pathGetter = new PathGetter();
        $pathGetter->setParameter(
            id: $file->id,
            fileName: $file->file_name,
            md5: $file->md5,
            fileType: $file->file_type
        );

        Storage::disk('local_public')->assertExists($pathGetter->getFullPath());

        // Force delete the large file
        $result = $file->forceDelete();

        // Verify the deletion was successful
        $this->assertTrue($result, 'forceDelete() should return true for large files');

        // Verify the record is completely removed from database
        $deletedFile = File::withTrashed()->find($fileId);
        $this->assertNull($deletedFile, 'Large file record should be completely removed from database');

        // Verify file is deleted from storage
        Storage::disk('local_public')->assertMissing($pathGetter->getFullPath());
    }
}