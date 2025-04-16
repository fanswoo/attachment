<?php

namespace FF\Attachment\File\Contracts\Controllers;

use Illuminate\Http\Request;

interface FileController
{
    public function upload(Request $request);

    public function delete(Request $request);

    public function download(int $id);

    public function rename(Request $request);
}
