<?php

namespace Tests\Unit\Pic;

use Tests\TestCase;
use FF\Attachment\Pic\Contracts\UrlUploader;
use Illuminate\Support\Facades\Storage;

class UrlUploaderTest extends TestCase
{
    public function testUploadFromUrl()
    {
        Storage::fake('local_public');

        $urlUploader = app(UrlUploader::class);
        $urlUploader->setFile(
            'https://www.google.com/images/branding/googlelogo/2x/googlelogo_color_272x92dp.png',
        );
        $uploadResult = $urlUploader->upload();

        $this->assertTrue($uploadResult);

        $pic = $urlUploader->getAttachment();

        $this->assertEquals($pic->id, 1);
        $this->assertEquals($pic->title, 'googlelogo_color_272x92dp.png');
        $this->assertEquals($pic->file_name, 'googlelogo_color_272x92dp.png');
        $this->assertEquals($pic->file_size, 13504);
        $this->assertEquals($pic->file_type, 'image/png');
    }

    public function testUploadIgnoreSSL()
    {
        Storage::fake('local_public');

        $urlUploader = app(UrlUploader::class);
        $urlUploader->setFile(
            url: 'https://www.google.com/images/branding/googlelogo/2x/googlelogo_color_272x92dp.png',
            verifySSL: true,
        );
        $uploadResult = $urlUploader->upload();

        $this->assertTrue($uploadResult);

        $pic = $urlUploader->getAttachment();

        $this->assertEquals($pic->id, 1);
        $this->assertEquals($pic->title, 'googlelogo_color_272x92dp.png');
        $this->assertEquals($pic->file_name, 'googlelogo_color_272x92dp.png');
        $this->assertEquals($pic->file_size, 13504);
        $this->assertEquals($pic->file_type, 'image/png');
    }
}
