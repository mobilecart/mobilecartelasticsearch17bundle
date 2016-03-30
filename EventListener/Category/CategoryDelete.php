<?php

namespace MobileCart\ElasticSearch17Bundle\EventListener\Category;

use Symfony\Component\EventDispatcher\Event;

class CategoryDelete
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

    public function onCategoryDelete(Event $event)
    {
        $entity = $event->getEntity();
        $this->getEntityService()->remove($entity);
    }

}
