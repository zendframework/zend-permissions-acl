<?php
/**
 * @see       https://github.com/zendframework/zend-permissions-acl for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-permissions-acl/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Permissions\Acl\TestAsset\OwnershipUseCase;

use Zend\Permissions\Acl\Resource\ResourceInterface;

class Comment implements ResourceInterface
{
    public function getResourceId()
    {
        return 'comment';
    }
}
