<?php

namespace MobileCart\ElasticSearch17Bundle\Service;

use Elastica\ResultSet;
use Elastica\Query;
use MobileCart\CoreBundle\Constants\EntityConstants;
use MobileCart\CoreBundle\Service\AbstractSearchService;
use MobileCart\CoreBundle\Service\SearchServiceInterface;

class ElasticSearchService
    extends AbstractSearchService
    implements SearchServiceInterface
{
    /**
     * @var
     */
    protected $entityService;

    /**
     * @var
     */
    private $debugQuery;

    /**
     * @param $entityService
     * @return $this
     */
    public function setEntityService($entityService)
    {
        $this->entityService = $entityService;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEntityService()
    {
        return $this->entityService;
    }

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->getEntityService()->getClient();
    }

    /**
     * @return string
     */
    public function getFacetPrefix()
    {
        return $this->getEntityService()->getFacetPrefix();
    }

    /**
     * @param ResultSet $rs
     * @return array
     */
    public function getIdsFromResult(ResultSet $rs)
    {
        return $this->getClient()->getIdsFromResult($rs);
    }

    /**
     * @param ResultSet $rs
     * @return array
     */
    public function getFacetCountsFromResult(ResultSet $rs)
    {
        if ($rs->getFacets()) {
            $facets = $rs->getFacets();
            if ($facets) {

                /*
                foreach($facets as $k => $v) {

                    if (substr($k, 0, strlen($this->getFacetPrefix())) == $this->getFacetPrefix()) {
                        $k2 = str_replace($this->getFacetPrefix(), '', $k);
                        $facets[$k2] = $v;
                        unset($facets[$k]);
                    }
                } //*/

                foreach($facets as $k => $v) {

                    $terms = $facets[$k]['terms'];
                    if ($terms) {
                        foreach($terms as $i => $termData) {
                            $term = $termData['term'];
                            $facets[$k]['terms'][$i]['urlValue'] = $this->getEntityService()->slugify($term);
                        }
                    }
                }
            }
            $this->facetCounts = $facets;
            $this->populateFacetLinks();
            return $this->facetCounts;
        }

        return [];
    }

    /**
     * @return array
     */
    public function executeFacetCounts()
    {
        if ($this->getExecutedFacetCounts()) {
            return $this->facetCounts;
        }

        // load facets

        $this->vars = $this->getEntityService()
            ->getRepository(EntityConstants::ITEM_VAR)->findBy([
                'is_facet' => 1
            ]);

        $facets = [];
        if ($this->vars) {
            foreach($this->vars as $itemVar) {
                $facets[] = ElasticSearchClient::FACET_PREFIX . $itemVar->getCode();
            }
            $this->facets = $facets;
        }

        $this->getFacetCountsFromResult($this->getClient()->search([
            'search'  => $this->getQuery(),
            'facets'  => $this->getFacets(),
            'filters' => $this->getFacetFilters(),
        ]));

        $this->setExecutedFacetCounts(true);

        return $this->facetCounts;
    }

    /**
     *
     * @return array|mixed
     */
    public function search()
    {
        if (!$this->getQuery()) {
            //$this->setQuery('*');
        }

        // todo : ordering

        $repo = $this->getEntityService()->getRepository($this->getObjectType());
        $sortable = $repo->getSortableFields();

        $facetCounts = $this->executeFacetCounts();
        $facetQuery = json_encode($this->getClient()->getLastQuery()->toArray());

        $rs = $this->getClient()->search([
            'type'     => $this->getObjectType(),
            'search'   => $this->getQuery(),
            'filters'  => $this->getFacetFilters(),
            'page'     => $this->getPage(),
            'limit'    => $this->getLimit(),
            'sort_by'  => $this->getSortBy(),
            'sort_dir' => $this->getSortDir(),
        ]);

        $searchQuery = json_encode($this->getClient()->getLastQuery()->toArray());

        $this->result = [
            'facetCounts'  => $facetCounts,
            'entities'     => $this->getClient()->getDataFromResult($rs),
            'total'        => $rs->getTotalHits(),
            'pages'        => ceil($rs->getTotalHits() / $this->getLimit()),
            'sortable'     => $sortable,
            'advFilters'   => $this->getAdvFilters(),
            'facetFilters' => $this->getActiveFacetUrlData(),
            'searchQuery'  => $searchQuery,
            'facetQuery'   => $facetQuery,
        ];

        return $this->result;
    }
}
