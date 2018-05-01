# Ownership Assertions

- Since 2.7.0

When setting up permissions for an application, site owners common will want to
allow roles to manipulate resources owned by the user with that role.  For
example, a blog author should have permission to _write_ new posts, and also to
_modify_ his or her **own** posts, but **not** posts of other authors.

To accomodate this use case, we provide two interfaces:

- **`Zend\Acl\ProprietaryInterface`** is applicable to _resources_ and _roles_.
  It provides information about the _owner_ of an object. Objects implementing
  this interface are used in conjunction with the `OwnershipAssertion`.

- **`Zend\Acl\Assertion\OwnershipAssertion`** ensures that a resource is owned
  by a specific role by comparing it to owners provided by
  `ProprietaryInterface` implementations.

### Example

Consider the following entities:

```php
namespace MyApp\Entity;

use Zend\Permissions\Acl\ProprietaryInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Role\RoleInterface;

class User implements RoleInterface, ProprietaryInterface
{
    protected $id;

    protected $role = 'guest';

    public function __construct($id, $role)
    {
        $this->id = $id;
        $this->role = $role;
    }

    public function getRoleId()
    {
        return $this->role;
    }

    public function getOwnerId()
    {
        return $this->id;
    }
}

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
```

The `User` marks itself as an _owner_ by implementing `ProprietaryInterface`;
its `getOwnerId()` method will return the user identifier provided during
instantiation.

A `BlogPost` marks itself as a resource and an _owner_ by also implementing
`ProprietaryInterface`; in its case, it returns the author identifier, if
present, but `null` otherwise.

Now let's wire these up into an ACL:

```php
namespace MyApp;

use MyApp\Entity;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Assertion\OwnershipAssertion;

$acl = new Acl();
$acl->addRole('guest');
$acl->addRole('member', 'guest');
$acl->addRole('author', 'member');
$acl->addRole('admin');

$acl->addResource('blogPost');
$acl->addResource('comment');

$acl->allow('guest', 'blogPost', 'view');
$acl->allow('guest', 'comment', array('view', 'submit'));
$acl->allow('author', 'blogPost', 'write');
$acl->allow('author', 'blogPost', 'edit', new OwnershipAssertion());
$acl->allow('admin');

$author1 = new User(1, 'author');
$author2 = new User(2, 'author');

$blogPost = new BlogPost();
$blogPost->author = $author1;
```

The takeaways from the above should be:

- An `author` can _write_ blog posts, and _edit_ posts it owns.
- `$author1` and `$author2` are both authors.
- `$author1` is the author of `$blogPost`.

Knowing these facts, we can expect the following assertion results:

```php
$acl->isAllowed($author1, 'blogPost', 'write'); // true
$acl->isAllowed($author1, $blogPost, 'edit');   // true
$acl->isAllowed($author2, 'blogPost', 'write'); // true
$acl->isAllowed($author2, $blogPost, 'edit');   // false
```
