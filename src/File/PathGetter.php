<?php

namespace FF\Attachment\File;

use FF\Attachment\Contracts\PathGetter as IPathGetter;
use FF\Attachment\PathGetter as AttachmentPathGetter;

class PathGetter extends AttachmentPathGetter implements IPathGetter
{
    public static function getRootPath(): string
    {
        return 'file';
    }
}
