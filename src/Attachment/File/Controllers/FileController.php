<?php

namespace FF\Attachment\File\Controllers;

use FF\Controllers\Base\FFController;
use Illuminate\Http\Request;
use FF\Attachment\File\Contracts\MutipleUploader;
use FF\Attachment\File\Contracts\Repositories\File;
use FF\Attachment\File\Contracts\Controllers\FileController as IFileController;

class FileController extends FFController implements IFileController
{
    public function __construct(protected MutipleUploader $mutipleUploader)
    {
    }

    public function upload(Request $request)
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
            'files' => $this->mutipleUploader->getAttachments(),
        ];
    }

    public function delete(Request $request)
    {
        $fileIds = $request->input('fileIds');

        if ($fileIds) {
            foreach ($fileIds as $key => $fileId) {
                $FileObj = orm(File::class, $fileId);
                $FileObj->delete();
            }

            $response['status'] = 'true';
            $response['errorMessage'] = '刪除成功';
            return $response;
        }

        $response['status'] = 'false';
        $response['errorMessage'] = '未知的錯誤';
        return $response;
    }

    public function download(int $id)
    {
        if (!$id) {
            return 'file not found';
        }

        $file = orm(File::class, $id);

        if (!$file->id) {
            return 'file not found';
        }

        return $file->download();
    }

    public function rename(Request $request)
    {
        $file = orm(File::class, $request->id);
        $file->title = $request->title;
        $file->save();

        return [
            'status' => true,
        ];
    }
}
