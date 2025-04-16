<?php

namespace FF\ORM\Relations;

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
        // 取出 priority 最大值
        $priorityMaxFile = orm( $className, [
            'where' => [
                'fileableType' => $this->getMorphClass(),
                'fileableAttr' => $attrValue
            ],
            'orderBy' => [
                'priority' => 'DESC'
            ]
        ]);
        $priorityMax = $priorityMaxFile->priority;

        $Files = orm( $className, $ids);

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
        $Files = orm( $className, $ids );

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
        // 先清空所有圖片
        $Files = orm( $className, [
            'where' => [
                'fileableType' => $this->getMorphClass(),
                'fileableAttr' => $attrValue,
                'fileableId' => $this->id
            ],
            'count' => 100
        ]);
        
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