<?php

namespace MobileCart\ElasticSearch17Bundle\Service;

use MobileCart\CoreBundle\Service\AbstractEntityService;
use MobileCart\CoreBundle\Constants\EntityConstants;
use Elastica\Client;
use Elastica\Index;
use Elastica\Document;
use Elastica\Query;

class ElasticSearchEntityService
    extends AbstractEntityService
{
    /**
     * @var ElasticSearchClient $client
     */
    protected $client;

    /**
     * SQL-driven Entity Service
     *  for copying entities from DB into ES
     *
     * @var
     */
    protected $rdbmsEntityService;

    /**
     * Map of VarSet's and Var's , for reference during indexing
     *
     * @var array
     */
    protected $varSetMap;

    /**
     * @var string
     */
    protected $valueSep = ',';

    /**
     * @param Client $client
     * @return $this
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return mixed
     */
    public function getFacetPrefix()
    {
        return $this->getClient()->getFacetPrefix();
    }

    /**
     * @return mixed
     */
    public function getSearchPrefix()
    {
        return $this->getClient()->getSearchPrefix();
    }

    /**
     * @param $entityService
     * @return $this
     */
    public function setRdbmsEntityService($entityService)
    {
        $this->rdbmsEntityService = $entityService;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRdbmsEntityService()
    {
        return $this->rdbmsEntityService;
    }

    /**
     * @param $objectType
     * @return mixed
     */
    public function getRepository($objectType)
    {
        $class = $this->repos[$objectType];
        return new $class($this->getClient(), $objectType);
    }

    /**
     * @param $objectType
     * @return mixed
     */
    public function getInstance($objectType)
    {
        return $this->getRepository($this->getObjectRepository($objectType))
            ->getInstance();
    }

    /**
     * @param string $objectType
     * @param int $id
     * @return mixed
     */
    public function find($objectType, $id)
    {
        return $this->getRepository($objectType)->find($id);
    }

    /**
     * @param $objectType
     * @param array $params
     * @param array $orderBy
     * @param null $limit
     * @param null $offset
     * @return mixed
     */
    public function findBy($objectType, $params = [], array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->getRepository($objectType)->findBy($params, $orderBy, $limit, $offset);
    }

    /**
     * @param $objectType
     * @param array $params
     * @param array $orderBy
     * @return mixed
     */
    public function findOneBy($objectType, array $params, array $orderBy = null)
    {
        return $this->getRepository($objectType)->findOneBy($params, $orderBy);
    }

    /**
     * @param $objectType
     * @return array
     */
    public function findAll($objectType)
    {
        return $this->getRepository($objectType)->findAll();
    }

    /**
     * Remove Document from Index
     *
     * @param $entity
     * @param string $objectType
     * @return mixed
     */
    public function remove($entity, $objectType = '')
    {
        $index = $this->getClient()->getIndex($this->getClient()->getRootIndex());
        $type = $index->getType($entity->getObjectTypeName());
        $type->deleteById($entity->getId());
        $index->refresh();
        return $this;
    }

    /**
     * Insert or Update Document in Index
     *
     * @param $entity
     * @param string $objectType
     * @return mixed
     */
    public function persist($entity, $objectType = '')
    {
        if (!$objectType) {
            $objectType = $entity->getObjectTypeName();
        }

        return $this->getRepository($objectType)->persistEntity($entity);
    }

    /**
     * Stub function to prevent bugs when switching between Entity Services
     *
     * @param $objectType
     * @param $entity
     * @param array $data
     * @return $this
     */
    public function persistVariants($objectType, $entity, array $data)
    {
        // nothing to do since NoSQL doesnt need EAV
        return $this;
    }

    /**
     * @param $objectType
     * @return array
     */
    public function getObjectTypeItemVars($objectType)
    {
        return $this->getRepository(EntityConstants::ITEM_VAR)->findBy([
            'object_type' => $objectType,
        ],
        null,
        1000,
        0
        );
    }

    /**
     * @return bool
     */
    public function mapAll()
    {
        if (!$this->repos) {
            return false;
        }

        $objectTypes = [];
        foreach($this->repos as $objectType => $objectRepository) {
            $this->getClient()->createMapping($objectType, $this->getRepository($objectType)->getProperties());
            $objectTypes[] = $objectType;
        }

        return $objectTypes;
    }

    /**
     * @return bool
     */
    public function indexAllObjectTypes()
    {
        if (!$this->repos) {
            return false;
        }

        $objectTypes = [];
        if ($this->repos) {
            foreach($this->repos as $objectType => $objectRepository) {
                $this->indexAllObjectType($objectType);
                $objectTypes[] = $objectType;
            }
        }

        return $objectTypes;
    }

    /**
     * @param $objectType
     * @return $this
     */
    public function indexAllObjectType($objectType)
    {
        $entities = $this->getRdbmsEntityService()->findAll($objectType);
        if ($entities) {
            foreach($entities as $entity) {
                $this->getRepository($objectType)->persistEntity($entity);
            }
        }

        return $this;
    }
}
