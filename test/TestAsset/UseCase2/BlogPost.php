<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Permissions\Acl\TestAsset\UseCase2;

use Zend\Permissions\Acl\Resource;
use Zend\Permissions\Acl\ProprietaryInterface;

class BlogPost implements Resource\ResourceInterface, ProprietaryInterface
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
