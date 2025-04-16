<?php

namespace FF\Attachment\Pic\Repositories;

class PicSetter
{
    static function set(string $className, string $attrValue, array $ids, string $morphClass, int $primaryId): void
    {
        $pics = $className::where('picableType', $morphClass)
            ->where('picableAttr', $attrValue)
            ->where('picableId', $primaryId)
            ->get();

        foreach ($pics as $pic) {
            $pic->picableId = 0;
            $pic->picableType = '';
            $pic->picableAttr = '';
            $pic->priority = 0;
            $pic->save();
        }

        PicAdder::add(
            $className,
            $attrValue,
            $ids,
            $morphClass,
            $primaryId
        );
    }
}