<?php

namespace FF\Attachment\File\Repositories;

class FileSetter
{
    static function set(string $className, string $attrValue, array $ids, string $morphClass, int $primaryId)
    {
        $Files = $className::where('fileableType', $morphClass)
            ->where('fileableAttr', $attrValue)
            ->where('fileableId', $primaryId)
            ->get();

        foreach( $Files as $File )
        {
            $File->fileableId = 0;
            $File->fileableType = '';
            $File->fileableAttr = '';
            $File->priority = 0;
            $File->save();
        }

        FileAdder::add(
            $className,
            $attrValue,
            $ids,
            $morphClass,
            $primaryId
        );
    }
}