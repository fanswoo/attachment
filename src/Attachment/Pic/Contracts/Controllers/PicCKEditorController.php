<?php

namespace FF\Attachment\Pic\Contracts\Controllers;

use Illuminate\Http\Request;

interface PicCKEditorController
{
    public function upload(Request $request);

}
