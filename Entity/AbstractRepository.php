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
     * @return mixed
     */
    public function findBy(array $filters)
    {
        $params = [
            'type' => $this->getObjectType(),
            //'search' => '*',
            'filters' => $filters,
        ];

        // todo : paginator logic, for retrieving all

        return $this->getClient()->getDataFromResult($this->getClient()->search($params));
    }

    /**
     * @param $entity
     * @return Document
     */
    public function createDocument($entity)
    {
        if (in_array($this->getObjectType(), [
            EntityConstants::ITEM_VAR,
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
                    $features[$itemVar->getCode()] = $varSetVar->getData();
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
                        case 'select':
                            $docData[$facetKey] = $varValue->getValue();
                            break;
                        case 'multiselect':

                            $value = isset($docData[$facetKey])
                                ? array_merge($docData[$facetKey], [$varValue->getValue()])
                                : [$varValue->getValue()];

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
