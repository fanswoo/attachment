<?php

namespace FF\Attachment\Pic\Repositories;

class PicDeleter
{
    static function delete(string $className, array $ids): void
    {
        $Pics = $className::whereIn('id', $ids)->get();

        foreach( $Pics as $Pic )
        {
            $Pic->picable_id = 0;
            $Pic->priority = 0;
            $Pic->save();
        }
    }
}