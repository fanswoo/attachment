<?php

namespace FF\Attachment\Relations;

trait ORMFile
{

    public function relationFile($className, $attrValue, $action = NULL, $ids = [])
    {
        $ids = $ids ? $ids : [];
        if( $action === 'add' )
        {
            return $this->addFiles($className, $attrValue, $ids);
        }
        else if( $action === 'delete' )
        {
            return $this->deleteFiles($className, $ids);
        }
        else if( $action === 'set' )
        {
            return $this->setFiles($className, $attrValue, $ids);
        }
        else
        {
            return $this->morphMany($className, 'fileable')->where('fileableAttr', $attrValue)->orderByDesc('priority');
        }
    }

    private function addFiles($className, $attrValue, $ids = [])
    {
        $priorityMaxFile = $className::where('fileableType', $this->getMorphClass())
            ->where('fileableAttr', $attrValue)
            ->orderBy('priority', 'DESC')
            ->first();
        $priorityMax = $priorityMaxFile->priority;

        $Files = $className::whereIn('id', $ids)->get();

        foreach( $Files as $key => $File )
        {
            $File->fileableId = $this->getPrimaryId();
            $File->fileableType = $this->getMorphClass();
            $File->fileableAttr = $attrValue;
            $File->priority = $priorityMax + count($Files) - $key;
            $File->save();
        }
    }

    private function deleteFiles($className, $ids = [])
    {
        $Files = $className::whereIn('id', $ids)->get();

        foreach( $Files as $File )
        {
            $File->fileableId = 0;
            $File->fileableType = '';
            $File->fileableAttr = '';
            $File->priority = 0;
            $File->save();
        }
    }

    private function setFiles($className, $attrValue, $ids = [])
    {
        $Files = $className::where('fileableType', $this->getMorphClass())
            ->where('fileableAttr', $attrValue)
            ->where('fileableId', $this->id)
            ->get();
        
        foreach( $Files as $File )
        {
            $File->fileableId = 0;
            $File->fileableType = '';
            $File->fileableAttr = '';
            $File->priority = 0;
            $File->save();
        }
        
        // 然後增加圖片
        $this->addFiles($className, $attrValue, $ids);
    }

}