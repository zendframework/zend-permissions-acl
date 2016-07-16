<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace ZendTest\Permissions\Acl\TestAsset\UseCase2;

use Zend\Permissions\Acl\Resource\ResourceInterface;

class BlogPost implements ResourceInterface
{
    public $title;

    public $short_description;

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
        return $this->short_description;
    }

    public function getAuthorName()
    {
        return $this->author ? $this->author->username : '';
    }
}
