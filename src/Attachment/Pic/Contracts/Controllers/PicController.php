<?php

namespace FF\Attachment\Pic\Contracts\Controllers;

use Illuminate\Http\Request;

interface PicController
{
    public function upload(Request $request);

    public function delete(Request $request);

    public function download(int $id);

    public function rename(Request $request);
}
