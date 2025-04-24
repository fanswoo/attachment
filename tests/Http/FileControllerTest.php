<?php

namespace Tests\Http\Api;

use Tests\DuskTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileControllerTest extends DuskTestCase
{
    public function testUploadFile()
    {
        Storage::fake('local_public');

        $file = UploadedFile::fake()
            ->image('avatar.jpg', 200, 200)
            ->size(200);

        $data = [
            'files' => [
                0 => $file,
            ],
        ];
        $response = $this->postJson('api/file/upload', $data);
        $this->assertCount(1, $response->json('files'));

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'files' => [
                '*' => [
                    'id',
                    'user_id',
                    'file_name',
                    'file_size',
                    'file_type',
                    'priority',
                    'md5',
                    'fileable_id',
                    'fileable_type',
                    'fileable_attr',
                    'created_at',
                    'updated_at',
                    'downloadUrl',
                ],
            ],
        ]);

        $downloadUrl = $response->json('files.0.downloadUrl');

        $response = $this->get($downloadUrl);
        $response->assertStatus(200);
        $this->assertStringContainsString(
            'attachment;',
            $response->headers->get('content-disposition'),
        );

    }
}
