<?php

namespace MobileCart\ElasticSearch17Bundle\Entity;

use MobileCart\CoreBundle\CartComponent\ArrayWrapper;

class Content extends ArrayWrapper
{
    public function getObjectTypeName()
    {
        return \MobileCart\CoreBundle\Constants\EntityConstants::CONTENT;
    }
}
