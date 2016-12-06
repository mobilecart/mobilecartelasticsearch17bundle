<?php

namespace MobileCart\ElasticSearch17Bundle\Entity;

use MobileCart\CoreBundle\CartComponent\ArrayWrapper;

class ItemVar extends ArrayWrapper
{
    public function getObjectTypeKey()
    {
        return \MobileCart\CoreBundle\Constants\EntityConstants::ITEM_VAR;
    }
}
