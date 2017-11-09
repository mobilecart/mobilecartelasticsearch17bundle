<?php

namespace MobileCart\ElasticSearch17Bundle\Entity;

use Elastica\Document;
use MobileCart\CoreBundle\Repository\CartRepositoryInterface;
use MobileCart\ElasticSearch17Bundle\Service\ElasticSearchClient;

class CategoryRepository
    extends AbstractRepository
    implements CartRepositoryInterface, ElasticSearchRepositoryInterface
{
    /**
     * @return bool
     */
    public function isEAV()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function hasImages()
    {
        return true;
    }

    /**
     * @return array
     */
    public function getSortableFields()
    {
        return [
            'id' => 'ID',
            'created_at' => 'Created At',
            'page_title' => 'Page Title',
            'name' => 'Name',
            'slug' => 'Slug',
        ];
    }

    /**
     * @return array
     */
    public function getFilterableFields()
    {
        return [
            [
                'code'  => 'id',
                'label' => 'ID',
                'type'  => 'number',
            ],
            [
                'code'  => 'parent_category_id',
                'label' => 'Parent ID',
                'type'  => 'number',
            ],
            [
                'code'  => 'name',
                'label' => 'Name',
                'type'  => 'string',
            ],
            [
                'code'  => 'created_at',
                'label' => 'Created At',
                'type'  => 'date',
            ],
            [
                'code'  => 'sort_order',
                'label' => 'Sort Order',
                'type'  => 'number',
            ],
            [
                'code'  => 'page_title',
                'label' => 'Page Title',
                'type'  => 'string',
            ],
            [
                'code'  => 'slug',
                'label' => 'Slug',
                'type'  => 'string',
            ],
        ];
    }

    /**
     * @return mixed|string
     */
    public function getSearchField()
    {
        return 'name';
    }

    /**
     * @return int|mixed
     */
    public function getSearchMethod()
    {
        return self::SEARCH_METHOD_LIKE;
    }

    /**
     * @return Product
     */
    public function getInstance()
    {
        return new Category();
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return array(
            'id'         => array('type' => 'integer', 'include_in_all' => false),
            'parent_category_id'  => array('type' => 'integer', 'include_in_all' => false),
            'sort_order'  => array('type' => 'integer', 'include_in_all' => false),
            'name'       => array('type' => 'string', 'index' => 'analyzed'),
            'slug'       => array('type' => 'string', 'index' => 'analyzed'),
            'content'    => array('type' => 'string', 'index' => 'analyzed'),
            'created_at' => array('type' => 'date', 'include_in_all' => false),
            'page_title'       => array('type' => 'string', 'index' => 'analyzed'),
            'meta_description' => array('type' => 'string', 'index' => 'analyzed'),
            'meta_title'       => array('type' => 'string', 'index' => 'analyzed'),
            'meta_keywords'    => array('type' => 'string', 'index' => 'analyzed'),
        );
    }
}
