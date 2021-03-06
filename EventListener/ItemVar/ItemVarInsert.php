<?php

namespace MobileCart\ElasticSearch17Bundle\EventListener\ItemVar;

use Symfony\Component\EventDispatcher\Event;

class ItemVarInsert
{
    /**
     * @var
     */
    protected $entityService;

    public function setEntityService($entityService)
    {
        $this->entityService = $entityService;
        return $this;
    }

    public function getEntityService()
    {
        return $this->entityService;
    }

    public function onItemVarInsert(Event $event)
    {
        $entity = $event->getEntity();
        $this->getEntityService()->persist($entity);
    }

}
