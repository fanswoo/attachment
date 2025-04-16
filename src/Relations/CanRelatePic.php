<?php

namespace FF\Attachment\Relations;

use FF\Attachment\Pic\Repositories\PicAdder;
use FF\Attachment\Pic\Repositories\PicDeleter;
use FF\Attachment\Pic\Repositories\PicSetter;

trait CanRelatePic
{
    public function morphPics(
        ?string $className = '',
        ?string $attrValue = '',
        ?string $action = NULL,
        ?array $ids = [],
    )
    {
        $ids = $ids ?? [];
        if( $action === 'add' )
        {
            PicAdder::add(
                $className,
                $attrValue,
                $ids,
                static::class,
                $this->{$this->primaryKey}
            );
        }
        else if( $action === 'delete' )
        {
            PicDeleter::delete(
                $className,
                $ids
            );
        }
        else if( $action === 'set' )
        {
            PicSetter::set(
                $className,
                $attrValue,
                $ids,
                static::class,
                $this->{$this->primaryKey}
            );
        }
        else
        {
            return $this->morphMany($className, 'picable')->where('picableAttr', $attrValue)->orderByDesc('priority');
        }
    }

}