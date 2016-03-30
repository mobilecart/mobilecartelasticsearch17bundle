<?php

namespace MobileCart\ElasticSearch17Bundle\EventListener\Content;

use Symfony\Component\EventDispatcher\Event;

class ContentInsert
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

    public function onContentInsert(Event $event)
    {
        $entity = $event->getEntity();
        $this->getEntityService()->persist($entity);
    }

}
