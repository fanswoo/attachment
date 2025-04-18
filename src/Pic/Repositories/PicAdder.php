<?php

namespace FF\Attachment\Pic\Repositories;

class PicAdder
{
    static function add(string $className, string $attrValue, array $ids, string $morphClass, int $primaryId): void
    {
        if(empty($ids) || empty($ids[0])) {
            return;
        }

        $priorityMaxPic = $className::where('picable_type', $morphClass)
            ->where('picable_attr', $attrValue)
            ->orderBy('priority', 'DESC')
            ->first();
        $priorityMax = $priorityMaxPic ? $priorityMaxPic->priority : 0;

        $pics = $className::whereIn('id', $ids)->get();

        foreach( $pics as $key => $pic )
        {
            $pic->picable_id = $primaryId;
            $pic->picable_type = $morphClass;
            $pic->picable_attr = $attrValue;
            $pic->priority = $priorityMax + count($pics) - $key;
            $pic->save();
        }
    }
}