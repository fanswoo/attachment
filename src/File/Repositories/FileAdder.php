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

        $files = $className::whereIn('id', $ids)->get()->sortBy(function ($file) use ($ids) {;
            return array_search($file->id, $ids);
        });
        $files = $files->values();

        foreach( $files as $key => $file )
        {
            $file->fileable_id = $primaryId;
            $file->fileable_type = $morphClass;
            $file->fileable_attr = $attrValue;
            $file->priority = $priorityMax + count($files) - $key;
            $file->save();
        }
    }
}