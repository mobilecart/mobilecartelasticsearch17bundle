<?php

namespace MobileCart\ElasticSearch17Bundle\Service;

use Elastica\Type\Mapping;
use Elastica\Client;
use Elastica\Query;
use Elastica\Query\QueryString;
use Elastica\ResultSet;
use Elastica\Facet\Terms;
use MobileCart\CoreBundle\CartComponent\ArrayWrapper;

class ElasticSearchClient extends Client
{
    /**
     * @var string
     */
    protected $rootIndex = 'mobilecart';

    /**
     * @var string
     */
    const FACET_PREFIX = 'attribute_facet_';

    /**
     * @var string
     */
    const SEARCH_PREFIX = 'attribute_search_';

    /**
     * @var string
     */
    protected $valueSep = ',';

    /**
     * @var
     */
    protected $lastQuery;

    /**
     * @param $index
     * @return $this
     */
    public function setRootIndex($index)
    {
        $this->rootIndex = $index;
        return $this;
    }

    /**
     * @return string
     */
    public function getRootIndex()
    {
        return $this->rootIndex;
    }

    /**
     * @param $objectType
     * @return \Elastica\Type
     */
    public function getIndexType($objectType)
    {
        return $this->getIndex($this->getRootIndex())->getType($objectType);
    }

    /**
     * @return string
     */
    public function createRootIndex()
    {
        $index = $this->getIndex($this->getRootIndex());

        // Create the new index
        return $index->create(
            array(
                'number_of_shards' => 4,
                'number_of_replicas' => 1,
                'analysis' => array(
                    'analyzer' => array(
                        'indexAnalyzer' => array(
                            'type' => 'custom',
                            'tokenizer' => 'standard',
                            'filter' => array('lowercase', 'mySnowball')
                        ),
                        'searchAnalyzer' => array(
                            'type' => 'custom',
                            'tokenizer' => 'standard',
                            'filter' => array('standard', 'lowercase', 'mySnowball')
                        )
                    ),
                    'filter' => array(
                        'mySnowball' => array(
                            'type' => 'snowball',
                            'language' => 'English'
                        )
                    )
                )
            ),
            true
        );
    }

    /**
     * @return string
     */
    public function getFacetPrefix()
    {
        return self::FACET_PREFIX;
    }

    /**
     * @return string
     */
    public function getSearchPrefix()
    {
        return self::SEARCH_PREFIX;
    }

    /**
     * @return string
     */
    public function getValueSep()
    {
        return $this->valueSep;
    }

    /**
     * @return mixed
     */
    public function getLastQuery()
    {
        return $this->lastQuery;
    }

    /**
     * @param $objectType
     * @param array $properties
     * @return bool
     */
    public function createMapping($objectType, array $properties)
    {
        $index = $this->getIndex($this->getRootIndex());

        //Create a type
        $indexType = $index->getType($objectType);

        // Define mapping
        $mapping = new Mapping();
        $mapping->setType($indexType);
        $mapping->setParam('index_analyzer', 'indexAnalyzer');
        $mapping->setParam('search_analyzer', 'searchAnalyzer');

        // Define boost field
//        $mapping->setParam('_boost', array('name' => '_boost', 'null_value' => 1.0));

        /**
         * Set the default, on beforehand known, properties of the document type.
         */
        $mapping->setProperties($properties);

        /**
         * Set the dynamic mapping, based on these rules:
         * http://www.elasticsearch.org/guide/reference/mapping/root-object-type.html
         */
        $mapping->setParam('dynamic_templates', array(
            // used for faceting and filtering of the new attributes
            array('attribute_facet' => array(
                'match' => $this->getFacetPrefix().'*',
                'mapping' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed',
                    'include_in_all' => false,
                ),
            )),
            // used when a user performs a full text search.
            array('attribute_search' => array(
                'match' => $this->getSearchPrefix().'*',
                'mapping' => array(
                    'type' => 'string',
                    'index' => 'analyzed',
                    'omit_norms' => true,
                ),
            )),
        ));

        // Send mapping to type
        $mapping->send();
        return true;
    }

    /**
     * @param ResultSet $rs
     * @return array
     */
    public function getDataFromResult(ResultSet $rs)
    {
        $objects = [];
        if ($rs->getResults()) {
            foreach($rs->getResults() as $result) {
                /** @var Result $result */
                $hit = $result->getHit();
                $data = $hit['_source'];
                foreach($data as $k => $v) {
                    if (
                        is_int(strpos($k, self::FACET_PREFIX))
                        || is_int(strpos($k, self::SEARCH_PREFIX))
                    ) {
                        unset($data[$k]);
                    }
                }
                $o = new ArrayWrapper($data);
                $objects[] = $o;
            }
        }
        return $objects;
    }

    /**
     * @param ResultSet $rs
     * @return array
     */
    public function getIdsFromResult(ResultSet $rs)
    {
        $ids = [];
        if ($rs->getResults()) {
            foreach($rs->getResults() as $result) {
                /** @var Result $result */
                $hit = $result->getHit();
                $ids[] = $hit['_source']['id'];
            }
        }
        return $ids;
    }

    /**
     * @param array $params
     * @return \Elastica\ResultSet
     */
    public function search(array $params = [])
    {
        $search = isset($params['search']) ? $params['search'] : '';
        $facets = isset($params['facets']) ? $params['facets'] : [];
        $filters = isset($params['filters']) ? $params['filters'] : [];
        $page = (int) isset($params['page']) ? $params['page'] : 1;
        $limit = isset($params['limit']) ? $params['limit'] : 15;
        if ($limit < 1) {
            $limit = 1;
        }

        $sortBy = isset($params['sort_by']) ? $params['sort_by'] : '';
        $sortDir = isset($params['sort_dir']) ? $params['sort_dir'] : '';

        // Build Query objects and Search
        $indexName = $this->getRootIndex();
        if (isset($params['type'])) {
            $indexName .= '/' . $params['type'];
        }

        /** @var Index $index */
        $index = $this->getIndex($indexName);

        $query = new Query();

        $qsTerms = [];

        if ($facets) {
            foreach($facets as $field) {
                $facet = new Terms($field);
                $facet->setField($field);
                $facet->setSize(15);
                $query->addFacet($facet);
            }
        }

        if ($search) {
            $qsTerms[] = $search;
        }

        if ($filters) {
            foreach($filters as $facetCode => $value) {
                if (is_array($value)) {

                    //TODO : we might want to iterate into arrays more here

                    foreach($value as $i => $aValue) {
                        //$value[$i] = "{$facetCode}:{$aValue}";
                    }

                    $qsTerms[] = '(' . implode(' OR ', $value) . ')';
                } else if (is_int(strpos($value, $this->valueSep))) {
                    $values = explode($this->valueSep, $value);
                    if (count($values) > 1) {

                        //TODO : test this
                        foreach($values as $i => $aValue) {
                            //$values[$i] = "{$facetCode}:{$aValue}";
                        }

                        $qsTerms[] = '(' . implode(' OR ', $values) . ')';
                    } else {
                        $qsTerms[] = "{$facetCode}:{$values[0]}";
                    }

                } else {
                    $qsTerms[] = "{$facetCode}:{$value}";
                }
            }
        }

        if ($qsTerms) {
            $qString = new QueryString();
            $qString->setQuery(implode(' AND ', $qsTerms));
            $query->setQuery($qString);
        }

        if ($page) {
            $from = ($page - 1) * $limit;
            $query->setFrom($from);
        }

        if ($limit) {
            $query->setSize($limit);
        }

        if ($sortBy) {
            $sortDir = ($sortDir == 'desc') ? 'desc' : 'asc';
            $query->setSort([$sortBy => $sortDir]);
        }

        $this->lastQuery = $query;

        return $index->search($query);
    }
}
