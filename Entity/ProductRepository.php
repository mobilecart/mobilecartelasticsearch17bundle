<?php

namespace MobileCart\ElasticSearch17Bundle\Entity;

use Elastica\Document;
use MobileCart\CoreBundle\Repository\CartRepositoryInterface;
use MobileCart\CoreBundle\Entity\Product as DoctrineProduct;
use MobileCart\ElasticSearch17Bundle\Service\ElasticSearchClient;

class ProductRepository
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
            'sort_order' => 'Sort Order',
            'type' => 'Product Type',
            'sku' => 'SKU',
            'price' => 'Price',
            'special_price' => 'Special Price',
            'qty' => 'Qty',
            'is_in_stock' => 'In Stock',
            'is_taxable' => 'Taxable',
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
            [
                'code'  => 'type',
                'label' => 'Product Type',
                'type'  => 'number',
                'choices' => [
                    [
                        'value' => 1,
                        'label' => 'Simple'
                    ],
                    [
                        'value' => 2,
                        'label' => 'Configurable',
                    ],
                ],
            ],
            [
                'code'  => 'sku',
                'label' => 'SKU',
                'type'  => 'string',
            ],
            [
                'code'  => 'price',
                'label' => 'Price',
                'type'  => 'number',
            ],
            [
                'code'  => 'special_price',
                'label' => 'Special Price',
                'type'  => 'number',
            ],
            [
                'code'  => 'qty',
                'label' => 'Qty',
                'type'  => 'number',
            ],
            array(
                'code'  => 'is_in_stock',
                'label' => 'In Stock',
                'type'  => 'boolean',
                'choices' => array(
                    array(
                        'value' => 0,
                        'label' => 'No',
                    ),
                    array(
                        'value' => 1,
                        'label' => 'Yes',
                    ),
                ),
            ),
            array(
                'code'  => 'is_enabled',
                'label' => 'Enabled',
                'type'  => 'boolean',
                'choices' => array(
                    array(
                        'value' => 0,
                        'label' => 'No',
                    ),
                    array(
                        'value' => 1,
                        'label' => 'Yes',
                    ),
                ),
            ),
            array(
                'code'  => 'is_taxable',
                'label' => 'Taxable',
                'type'  => 'boolean',
                'choices' => array(
                    array(
                        'value' => 0,
                        'label' => 'No',
                    ),
                    array(
                        'value' => 1,
                        'label' => 'Yes',
                    ),
                ),
            ),
        ];

    }

    /**
     * @return Product
     */
    public function getInstance()
    {
        return new Product();
    }

    /**
     * @return mixed|string
     */
    public function getSearchField()
    {
        return 'fulltext_search';
    }

    /**
     * @return int|mixed
     */
    public function getSearchMethod()
    {
        return self::SEARCH_METHOD_FULLTEXT;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return [
            'id'           => array('type' => 'integer', 'include_in_all' => false),
            'sku'          => array('type' => 'string', 'include_in_all' => false),
            'name'         => array('type' => 'string', 'index' => 'analyzed'),
            'slug'         => array('type' => 'string', 'index' => 'analyzed'),
            'content'      => array('type' => 'string', 'index' => 'analyzed'),
            'short_description' => array('type' => 'string', 'index' => 'analyzed'),
            'category_ids' => array('type' => 'integer', 'index' => 'analyzed'),
            'store_ids'    => array('type' => 'integer', 'index' => 'analyzed'),
            'tags'         => array('type' => 'string', 'index' => 'analyzed'),
            'price'        => array('type' => 'float', 'index' => 'not_analyzed', 'include_in_all' => false),
            'qty'        => array('type' => 'integer', 'index' => 'not_analyzed', 'include_in_all' => false),
            'sort_order'        => array('type' => 'integer', 'index' => 'not_analyzed', 'include_in_all' => false),
            'created_at'   => array('type' => 'date', 'include_in_all' => false),
//            '_boost'  => array('type' => 'float', 'include_in_all' => false),
            'is_in_stock'  => array('type' => 'boolean'),
            'visibility'   => array('type' => 'integer'),
            'config'       => array('type' => 'string', 'index' => 'not_analyzed'),
            'type'         => array('type' => 'integer', 'index' => 'analyzed'),
        ];
    }

    /**
     * @param $entity
     * @return Document
     */
    public function createDocument($entity)
    {
        $document = parent::createDocument($entity);
        $docData = $document->getData();
        $docData['category_ids'] = $entity->getCategoryIds();
        $entity->reconfigure();
        $docData['config'] = $entity->getConfig();
        $createdAt = $entity->getCreatedAt();
        if ($createdAt instanceof \DateTime) {
            $createdAt = $createdAt->format(\DateTime::ISO8601);
            $docData['created_at'] = $createdAt;
        }
        return new Document($entity->getId(), $docData);
    }
}
