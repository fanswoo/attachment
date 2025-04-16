<?php

namespace FF\Attachment\Pic\Repositories;

class PicAdder
{
    static function add(string $className, string $attrValue, array $ids, string $morphClass, int $primaryId): void
    {
        if(empty($ids) || empty($ids[0])) {
            return;
        }

        $priorityMaxPic = $className::where('picableType', $morphClass)
            ->where('picableAttr', $attrValue)
            ->orderBy('priority', 'DESC')
            ->first();
        $priorityMax = $priorityMaxPic->priority;

        $pics = $className::whereIn('id', $ids)->get();

        foreach( $pics as $key => $pic )
        {
            $pic->picableId = $primaryId;
            $pic->picableType = $morphClass;
            $pic->picableAttr = $attrValue;
            $pic->priority = $priorityMax + count($pics) - $key;
            $pic->save();
        }
    }
}