<?php

namespace FF\Attachment\Pic\Repositories;

class PicDeleter
{
    static function delete(string $className, array $ids): void
    {
        $Pics = $className::whereIn('id', $ids)->get();

        foreach( $Pics as $Pic )
        {
            $Pic->picableId = 0;
            $Pic->picableType = '';
            $Pic->picableAttr = '';
            $Pic->priority = 0;
            $Pic->save();
        }
    }
}