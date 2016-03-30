<?php

namespace MobileCart\ElasticSearch17Bundle\Entity;

use Elastica\Document;

interface ElasticSearchRepositoryInterface
{
    /**
     * @return array
     */
    public function getProperties();

    /**
     * @param $entity
     * @return Document
     */
    public function createDocument($entity);
}
