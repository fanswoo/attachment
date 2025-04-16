<?php

namespace FF\Attachment\Pic\Controllers;

use FF\Controllers\Base\FFController;
use Illuminate\Http\Request;
use FF\Attachment\Attachment\Contracts\Uploader;

class PicCKEditorController extends FFController
{
    public function __construct(protected Uploader $uploader)
    {
    }

    public function upload(Request $request)
    {
        $file = $request->file('upload');

        $this->uploader->setFile($file);
        $result = $this->uploader->upload();

        if (!$result) {
            return [
                'uploaded' => false,
                'message' => $this->uploader->getErrorMessage(),
            ];
        }

        return [
            'uploaded' => true,
            'url' => $this->uploader->getAttachment()->url(0, 0),
        ];
    }

    public function ckupload(Request $request)
    {
        $responseArr = [];

        $upload = $Request->file('upload');

        if (!$upload) {
            return [
                'error' => [
                    'message' => '未接收到圖片檔案',
                ],
            ];
        }

        $Pic = orm(Pic::class);
        $Pic->setFile($upload);
        $uploadStatus = $Pic->upload();

        if ($uploadStatus === true) {
            $responseArr['uploaded'] = true;
            $responseArr['url'] = $Pic->url();
            return json_encode($responseArr, JSON_UNESCAPED_UNICODE);
        } elseif ($uploadStatus === false) {
            $responseArr['error']['message'] = '未知的錯誤';
            return json_encode($responseArr, JSON_UNESCAPED_UNICODE);
        }

        $responseArr['error']['message'] = $uploadStatus;
        return json_encode($responseArr, JSON_UNESCAPED_UNICODE);
    }
}
