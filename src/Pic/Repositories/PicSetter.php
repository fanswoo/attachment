<?php

namespace FF\Attachment\Pic\Repositories;

class PicSetter
{
    static function set(string $className, string $attrValue, array $ids, string $morphClass, int $primaryId): void
    {
        $pics = $className::where('picable_type', $morphClass)
            ->where('picable_attr', $attrValue)
            ->where('picable_id', $primaryId)
            ->get();

        foreach ($pics as $pic) {
            $pic->picable_id = 0;
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