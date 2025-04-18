<?php

namespace FF\Attachment\File\Repositories;

class FileAdder
{
    static function add(string $className, string $attrValue, array $ids, string $morphClass, int $primaryId)
    {
        $priorityMaxFile = $className::where('fileable_type', $morphClass)
            ->where('fileable_attr', $attrValue)
            ->orderBy('priority', 'DESC')
            ->first();
        $priorityMax = $priorityMaxFile ? $priorityMaxFile->priority : 0;

        $Files = $className::whereIn('id', $ids)->get();

        foreach( $Files as $key => $File )
        {
            $File->fileable_id = $primaryId;
            $File->fileable_type = $morphClass;
            $File->fileable_attr = $attrValue;
            $File->priority = $priorityMax + count($Files) - $key;
            $File->save();
        }
    }
}