<?php
/**
 * @see       https://github.com/zendframework/zend-permissions-acl for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-permissions-acl/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Permissions\Acl\TestAsset\ExpressionUseCase;

use Zend\Permissions\Acl\Resource\ResourceInterface;

class BlogPost implements ResourceInterface
{
    public $title;

    public $shortDescription;

    public $content;

    public $author;

    public function __construct(array $data = [])
    {
        foreach ($data as $property => $value) {
            $this->$property = $value;
        }
    }

    public function getResourceId()
    {
        return 'blogPost';
    }

    public function getShortDescription()
    {
        return $this->shortDescription;
    }

    public function getAuthorName()
    {
        return $this->author ? $this->author->username : '';
    }
}
