<?php

namespace FF\Attachment\File\Repositories;

class FileDeleter
{
    static function delete(string $className, array $ids)
    {
        $Files = $className::whereIn('id', $ids)->get();

        foreach( $Files as $File )
        {
            $File->fileable_id = 0;
            $File->fileable_type = '';
            $File->fileable_attr = '';
            $File->priority = 0;
            $File->save();
        }
    }
}