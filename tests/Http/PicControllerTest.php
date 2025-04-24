<?php

namespace Tests\Http\Api;

use Tests\DuskTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PicControllerTest extends DuskTestCase
{
    public function testBasic(): void
    {
        Storage::fake('local_public');

        $pic = UploadedFile::fake()
            ->image('avatar.jpg', 100, 100)
            ->size(100);

        $pic2 = UploadedFile::fake()
            ->image('avatar2.jpg', 200, 200)
            ->size(200);

        $data = [
            'files' => [
                0 => $pic,
                1 => $pic2,
            ],
        ];
        $response = $this->postJson('api/pic/upload', $data);

        $response->assertJson([
            'pics' => [['title' => 'avatar.jpg'], ['title' => 'avatar2.jpg']],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'pics' => [
                '*' => [
                    'id',
                    'user_id',
                    'title',
                    'file_name',
                    'file_size',
                    'file_type',
                    'priority',
                    'md5',
                    'picable_id',
                    'picable_type',
                    'picable_attr',
                    'created_at',
                    'updated_at',
                    'url' => ['w0h0', 'w50h50'],
                    'downloadUrl',
                ],
            ],
        ]);
    }
}
