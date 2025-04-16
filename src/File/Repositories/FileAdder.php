<?php

namespace FF\Attachment\File\Repositories;

class FileAdder
{
    static function add(string $className, string $attrValue, array $ids, string $morphClass, int $primaryId)
    {
        $priorityMaxFile = $className::where('fileableType', $morphClass)
            ->where('fileableAttr', $attrValue)
            ->orderBy('priority', 'DESC')
            ->first();
        $priorityMax = $priorityMaxFile->priority;

        $Files = $className::whereIn('id', $ids)->get();

        foreach( $Files as $key => $File )
        {
            $File->fileableId = $primaryId;
            $File->fileableType = $morphClass;
            $File->fileableAttr = $attrValue;
            $File->priority = $priorityMax + count($Files) - $key;
            $File->save();
        }
    }
}