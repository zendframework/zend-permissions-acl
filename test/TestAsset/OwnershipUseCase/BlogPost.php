<?php
/**
 * @see       https://github.com/zendframework/zend-permissions-acl for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-permissions-acl/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Permissions\Acl\TestAsset\OwnershipUseCase;

use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\ProprietaryInterface;

class BlogPost implements ResourceInterface, ProprietaryInterface
{
    public $author = null;

    public function getResourceId()
    {
        return 'blogPost';
    }

    public function getOwnerId()
    {
        if ($this->author === null) {
            return null;
        }

        return $this->author->getOwnerId();
    }
}
