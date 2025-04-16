<?php

namespace FF\Attachment\File\Repositories;

class FileSetter
{
    static function set(string $className, string $attrValue, array $ids, string $morphClass, int $primaryId)
    {
        $Files = $className::where('fileable_type', $morphClass)
            ->where('fileable_attr', $attrValue)
            ->where('fileable_id', $primaryId)
            ->get();

        foreach( $Files as $File )
        {
            $File->fileable_id = 0;
            $File->fileable_type = '';
            $File->fileable_attr = '';
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