<?php

namespace MobileCart\ElasticSearch17Bundle\EventListener\ItemVar;

use Symfony\Component\EventDispatcher\Event;

class ItemVarUpdate
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

    public function onItemVarUpdate(Event $event)
    {
        $entity = $event->getEntity();
        $this->getEntityService()->persist($entity);
    }

}
