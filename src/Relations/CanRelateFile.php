<?php

namespace FF\Attachment\Relations;

use FF\Attachment\File\Repositories\FileAdder;
use FF\Attachment\File\Repositories\FileDeleter;
use FF\Attachment\File\Repositories\FileSetter;

trait CanRelateFile
{
    public function relationFile($className, $attrValue, $action = NULL, $ids = [])
    {
        $ids = $ids ? $ids : [];
        if( $action === 'add' )
        {
            FileAdder::add(
                $className,
                $attrValue,
                $ids,
                static::class,
                $this->{$this->primaryKey}
            );
        }
        else if( $action === 'delete' )
        {
            FileDeleter::delete(
                $className,
                $ids
            );
        }
        else if( $action === 'set' )
        {
            FileSetter::set(
                $className,
                $attrValue,
                $ids,
                static::class,
                $this->{$this->primaryKey}
            );
        }
        else
        {
            return $this->morphMany($className, 'fileable')->where('fileableAttr', $attrValue)->orderByDesc('priority');
        }
    }


}