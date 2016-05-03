<?php

namespace MobileCart\ElasticSearch17Bundle\Entity;

use Elastica\Document;
use MobileCart\CoreBundle\Constants\EntityConstants;
use MobileCart\ElasticSearch17Bundle\Service\ElasticSearchClient;

class AbstractRepository
{

    protected $client;

    protected $objectType;

    public function __construct($client, $objectType)
    {
        $this->client = $client;
        $this->objectType = $objectType;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getObjectType()
    {
        return $this->objectType;
    }

    /**
     * @param $str
     * @return mixed
     */
    public function slugify($str)
    {
        return str_replace('--', '-', strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $str))));
    }

    /**
     * @return mixed
     */
    public function findAll()
    {
        $params = [
            'type' => $this->getObjectType(),
            //'search' => '*',
        ];

        // todo : paginator logic, for retrieving all

        return $this->getClient()->getDataFromResult($this->getClient()->search($params));
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function find($id)
    {
        $params = [
            'type' => $this->getObjectType(),
            //'search' => '*',
            'filters' => ['id' => $id],
            'page' => 1,
            'limit' => 1,
        ];

        $objects = $this->getClient()->getDataFromResult($this->getClient()->search($params));

        return isset($objects[0])
            ? $objects[0]
            : null;
    }

    /**
     * @param array $filters
     * @return mixed
     */
    public function findOneBy(array $filters)
    {
        $params = [
            'type' => $this->getObjectType(),
            //'search' => '*',
            'filters' => $filters,
            'page' => 1,
            'limit' => 1,
        ];

        $objects = $this->getClient()->getDataFromResult($this->getClient()->search($params));

        return isset($objects[0])
            ? $objects[0]
            : null;
    }

    /**
     * @param array $filters
     * @param array $orderBy
     * @param null $limit
     * @param null $offset
     * @return mixed
     */
    public function findBy(array $filters, array $orderBy = null, $limit = null, $offset = null)
    {
        if (is_null($limit)) {
            $limit = 15;
        }

        $page = 1;
        if ($offset > 0) {
            $page = ceil($offset / $limit) - 1;
        }

        $params = [
            'type' => $this->getObjectType(),
            //'search' => '*',
            'filters' => $filters,
            'page' => $page,
            'limit' => $limit,
        ];

        if ($orderBy && count($orderBy) > 0) {

            foreach($orderBy as $sortBy => $sortDir) {

                if (!in_array($sortDir, ['asc', 'desc'])) {
                    $sortDir = 'asc';
                }

                $params['sort_by']  = $sortBy;
                $params['sort_dir'] = $sortDir;
                break;
            }
        }

        return $this->getClient()->getDataFromResult($this->getClient()->search($params));
    }

    /**
     * @param $entity
     * @return Document
     */
    public function createDocument($entity)
    {
        if ($this->getObjectType() == EntityConstants::ITEM_VAR) {

            $docData = $entity->getBaseData();
            $objectTypes = [];

            $varSetVars = $entity->getItemVarSetVars();
            if ($varSetVars) {
                foreach($varSetVars as $varSetVar) {
                    $objectType = $varSetVar->getItemVarSet()->getObjectType();
                    if (!in_array($objectType, $objectTypes)) {
                        $objectTypes[] = $objectType;
                    }
                }
                $docData['object_type'] = $objectTypes;
            }

            return new Document($entity->getId(), $docData);
        } elseif (in_array($this->getObjectType(), [
            EntityConstants::ITEM_VAR_SET,
            EntityConstants::ITEM_VAR_SET_VAR,
            EntityConstants::ITEM_VAR_OPTION,
            EntityConstants::ITEM_VAR_OPTION_DECIMAL,
            EntityConstants::ITEM_VAR_OPTION_DATETIME,
            EntityConstants::ITEM_VAR_OPTION_INT,
            EntityConstants::ITEM_VAR_OPTION_TEXT,
            EntityConstants::ITEM_VAR_OPTION_VARCHAR,
        ])) {
            return new Document($entity->getId(), $entity->getBaseData());
        }

        $docData = $entity->getData();
        $varSet = $entity->getItemVarSet();
        $varValues = [];
        $features = [];
        if ($varSet) {
            $varSetVars = $varSet->getItemVarSetVars();
            if ($varSetVars) {
                foreach($varSetVars as $varSetVar) {
                    $itemVar = $varSetVar->getItemVar();
                    $features[$itemVar->getCode()] = $itemVar->getData();
                }
            }
            $varValues = $entity->getVarValues();
        }

        if ($varValues) {
            foreach($varValues as $varValue) {

                $var = $varValue->getItemVar();
                $varCode = $var->getCode();

                $facetKey = ElasticSearchClient::FACET_PREFIX . $varCode;
                $searchKey = ElasticSearchClient::SEARCH_PREFIX . $varCode;

                if (
                    in_array($varValue->getItemVar()->getFormInput(), ['select', 'multiselect'])
                    && isset($features[$varCode]['is_facet'])
                    && $features[$varCode]['is_facet']
                ) {

                    switch($var->getFormInput()) {
                        case 'text':
                        case 'select':
                            $docData[$facetKey] = $this->slugify($varValue->getValue());
                            break;
                        case 'multiselect':

                            $value = isset($docData[$facetKey])
                                ? array_merge($docData[$facetKey], [$varValue->getValue()])
                                : [$varValue->getValue()];

                            if ($value) {
                                foreach($value as $k => $v) {
                                    $value[$k] = $this->slugify($v);
                                }
                            }

                            $docData[$facetKey] = $value;

                            break;
                        default:

                            break;
                    }
                }

                if (
                    isset($features[$varCode]['is_searchable'])
                    && $features[$varCode]['is_searchable']
                ) {

                    switch($varValue->getItemVar()->getFormInput()) {
                        case 'text':
                        case 'select':
                            $docData[$searchKey] = $varValue->getValue();
                            break;
                        case 'multiselect':

                            $value = isset($docData[$searchKey])
                                ? array_merge($docData[$searchKey], [$varValue->getValue()])
                                : [$varValue->getValue()];

                            $docData[$searchKey] = $value;

                            break;
                        default:

                            break;
                    }
                }
            }
        }

        return new Document($entity->getId(), $docData);
    }

    /**
     * @param Document $document
     * @return $this
     */
    public function persistDocument(Document $document)
    {
        $index = $this->getClient()->getIndex($this->getClient()->getRootIndex());
        $esType = $index->getType($this->getObjectType());
        $esType->addDocuments([$document]);
        return $this;
    }

    /**
     * @param $entity
     * @return $this
     */
    public function persistEntity($entity)
    {
        return $this->persistDocument($this->createDocument($entity));
    }
}
