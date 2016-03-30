<?php

namespace MobileCart\ElasticSearch17Bundle\EventListener\Product;

use Symfony\Component\EventDispatcher\Event;

class ProductDelete
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

    public function onProductDelete(Event $event)
    {
        $entity = $event->getEntity();
        $this->getEntityService()->remove($entity);
    }

}
