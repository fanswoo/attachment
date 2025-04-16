<?php

namespace FF\Attachment\Pic\Controllers;

use FF\Controllers\Base\FFController;
use Illuminate\Http\Request;
use FF\Attachment\Pic\Contracts\MutipleUploader;
use FF\Attachment\Pic\Contracts\Repositories\Pic;
use FF\Attachment\Pic\Contracts\Controllers\PicController as IPicController;

class PicController extends FFController implements IPicController
{
    public function __construct(protected MutipleUploader $mutipleUploader)
    {
    }

    public function upload(Request $request): array
    {
        $files = $request->file('files');

        $this->mutipleUploader->setFiles($files);
        $result = $this->mutipleUploader->upload();

        if (!$result) {
            return [
                'status' => false,
                'message' => $this->mutipleUploader->getErrorMessage(),
            ];
        }

        return [
            'status' => true,
            'pics' => $this->mutipleUploader->getAttachments(),
        ];
    }

    public function delete(Request $request): array
    {
        $picIds = $request->input('picIds');

        if ($picIds) {
            foreach ($picIds as $key => $picId) {
                $PicObj = orm(Pic::class, $picId);
                $PicObj->delete();
            }

            $response['status'] = 'true';
            $response['errorMessage'] = '刪除成功';
            return $response;
        }

        $response['status'] = 'false';
        $response['errorMessage'] = '未知的錯誤';
        return $response;
    }

    public function download(int $id): string
    {
        if (!$id) {
            return 'pic not found';
        }

        $pic = orm(Pic::class, $id);

        if (!$pic->id) {
            return 'pic not found';
        }

        return $pic->download();
    }

    public function rename(Request $request): array
    {
        $pic = orm(Pic::class, $request->id);
        $pic->title = $request->title;
        $pic->save();

        return [
            'status' => true,
        ];
    }
}
