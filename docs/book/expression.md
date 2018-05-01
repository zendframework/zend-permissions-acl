# Expression Assertions

- Since 2.7.0

Many custom assertions are doing basic comparisons:

- Equality of a role property to a value or property of the resource.
- Other comparisons (`>`, `<`, `in_array`, etc.) of a role property to a value
  or values (potentially a property of the resource).
- Regular expressions.

While these can be easily accommodated by the `CallbackAssertion`, such
assertions have one notable problem: they cannot be easily serialized.

To facilitate such assertions, we now provide
`Zend\Permissions\Acl\Assertion\ExpressionAssertion`. This class provides two
static factory methods for creating an instance, each expecting the following:

- The left operand
- An operator
- The right operand

When the assertion is executed, it uses the operator to determine how to compare
the two operands, and thus answer the assertion.

## Operands

The operands can be any PHP value.

Additionally, they can be an associative array containing the key
`ExpressionAssertion::OPERAND_CONTEXT_PROPERTY` (`__context`), with a string
value.

That value can be one of the following:

- A string matching the values "acl", "privilege", "role", or "resource", with
  the latter two being most common. When one of these is provided, the
  corresponding argument to the `assert()` method will be used.

- A dot-separated string with the first segment being one of the above values,
  and the second being a property or field of that object. The
  `ExpressionAssertion` will test for:

  - a method matching `get<field>()`
  - a method matching `is<field>()`
  - a public property named `<field>`

  in that specific order. In the first two cases, `<field>` will be normalized
  to WordCase when creating the method name to test.

## Operators

`ExpressionAssertion` supports the following operators:

```php
    const OPERATOR_EQ     = '=';
    const OPERATOR_NEQ    = '!=';
    const OPERATOR_LT     = '<';
    const OPERATOR_LTE    = '<=';
    const OPERATOR_GT     = '>';
    const OPERATOR_GTE    = '>=';
    const OPERATOR_IN     = 'in';
    const OPERATOR_NIN    = '!in';
    const OPERATOR_REGEX  = 'regex';
    const OPERATOR_NREGEX = '!regex';
    const OPERATOR_SAME   = '===';
    const OPERATOR_NSAME  = '!==';
```

In most cases, these will operate using the operators as listed above, with the
following exceptions:

- `OPERATOR_EQ` will use `==` as the comparison operator; `OPERATOR_NEQ` will
  likewise use `!=`.
- `OPERATOR_IN` and `OPERATOR_NIN` use `in_array()` (with the latter negating
  the result), both doing strict comparisons. The right hand operand is expected
  to be the array in which to look for results, and the left hand operand is
  expected to be the needle to look for.
- `OPERATOR_REGEX` and `OPERATOR_NREGEX` will perform a `preg_match()`
  operation, using the right hand operand as the regular expression, and the
  left hand operand as the value to compare.

## Constructors

The constructor of `ExpressionAssertion` is private. Instead, you will use one
of two static methods in order to create instances:

- `fromProperties($left, $operator, $right)`
- `fromArray(array $expression)` (expects keys for "left", "operator", and "right")

When creating expressions manually, the first is generally the best choice. When
storing expressions in configuration or a database, the latter is useful, as you
can pass a row of data at a time to the method to get expression instances.

## Examples

First, we'll define both a role and a resource:

```php
namespace Blog\Entity;

use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Role\RoleInterface;

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

class User implements RoleInterface
{
    public $username;

    public $role = 'guest';

    public $age;

    public function __construct(array $data = [])
    {
        foreach ($data as $property => $value) {
            $this->$property = $value;
        }
    }

    public function getRoleId()
    {
        return $this->role;
    }

    public function isAdult()
    {
        return $this->age >= 18;
    }
}
```

Next, let's define some assertions.

```php
use Zend\Permissions\Acl\Assertion\ExpressionAssertion;

// Username of role must be "test":
// Will access $username property on the role instance.
$isTestUser = ExpressionAssertion::fromProperties(
  [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.username'],
  '===',
  'test'
);


// Role must be at least 18 years old:
// Will execute `isAdult()` on the role instance.
$isOfLegalAge = ExpressionAssertion::fromProperties(
  [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.adult'],
  '===',
  true
);

// Must have edited text:
// Will do a regex comparison on the shortDescription of the blog post
// to ensure we do not have filler text.
$isEditedDescription = ExpressionAssertion::fromArray([
  'left'     => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'resource.shortDescription'],
  'operator' => '!regex',
  'right'    => '/lorem ipsum/i',
]);
```
