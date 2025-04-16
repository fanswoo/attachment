<?php

namespace FF\Attachment\Relations;

trait ORMPic
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
            $this->addPics($className, $attrValue, $ids);
        }
        else if( $action === 'delete' )
        {
            $this->deletePics($className, $ids);
        }
        else if( $action === 'set' )
        {
            $this->setPics($className, $attrValue, $ids);
        }
        else
        {
            return $this->morphMany($className, 'picable')->where('picableAttr', $attrValue)->orderByDesc('priority');
        }
    }

    private function addPics($className, $attrValue, $ids = []): void
    {
        if(empty($ids) || empty($ids[0])) {
            return;
        }

        $priorityMaxPic = $className::where('picableType', $this->getMorphClass())
            ->where('picableAttr', $attrValue)
            ->orderBy('priority', 'DESC')
            ->first();
        $priorityMax = $priorityMaxPic->priority;

        $pics = $className::whereIn('id', $ids)->get();

        foreach( $pics as $key => $pic )
        {
            $pic->picableId = $this->getPrimaryId();
            $pic->picableType = $this->getMorphClass();
            $pic->picableAttr = $attrValue;
            $pic->priority = $priorityMax + count($pics) - $key;
            $pic->save();
        }
    }

    private function deletePics($className, $ids = []): void
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

    private function setPics($className, $attrValue, $ids = []): void
    {
        $pics = $className::where('picableType', $this->getMorphClass())
            ->where('picableAttr', $attrValue)
            ->where('picableId', $this->id)
            ->get();
        
        foreach( $pics as $pic )
        {
            $pic->picableId = 0;
            $pic->picableType = '';
            $pic->picableAttr = '';
            $pic->priority = 0;
            $pic->save();
        }
        
        // 然後增加圖片
        $this->addPics($className, $attrValue, $ids);
    }

}