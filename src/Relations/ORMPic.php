<?php

namespace FF\ORM\Relations;

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

        // 取出 priority 最大值
        $priorityMaxPic = orm( $className, [
            'where' => [
                'picableType' => $this->getMorphClass(),
                'picableAttr' => $attrValue
            ],
            'orderBy' => [
                'priority' => 'DESC'
            ]
        ]);
        $priorityMax = $priorityMaxPic->priority;

        $pics = orm( $className, $ids);

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
        $Pics = orm( $className, $ids);

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
        // 先清空所有圖片
        $pics = orm( $className, [
            'where' => [
                'picableType' => $this->getMorphClass(),
                'picableAttr' => $attrValue,
                'picableId' => $this->id
            ],
            'count' => 100
        ]);
        
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