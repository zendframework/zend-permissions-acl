<?php
/**
 * @see       https://github.com/zendframework/zend-permissions-acl for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-permissions-acl/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Permissions\Acl\Assertion;

use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Role\RoleInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Assertion\Exception\InvalidAssertionException;
use Zend\Permissions\Acl\Exception\RuntimeException;

final class ExpressionAssertion implements AssertionInterface
{
    const OPERAND_CONTEXT_PROPERTY = '__context';

    const OPERATOR_EQ = '=';
    const OPERATOR_NEQ = '!=';
    const OPERATOR_LT = '<';
    const OPERATOR_LTE = '<=';
    const OPERATOR_GT = '>';
    const OPERATOR_GTE = '>=';
    const OPERATOR_IN = 'in';
    const OPERATOR_NIN = 'nin';
    const OPERATOR_REGEX = 'regex';

    /**
     * @var mixed
     */
    private $left;

    /**
     * @var string
     */
    private $operator;

    /**
     * @var mixed
     */
    private $right;

    /**
     * @var array
     */
    private $assertContext = [];

    /**
     * @var array
     */
    private static $validOperators = [
        self::OPERATOR_EQ,
        self::OPERATOR_NEQ,
        self::OPERATOR_LT,
        self::OPERATOR_LTE,
        self::OPERATOR_GT,
        self::OPERATOR_GTE,
        self::OPERATOR_IN,
        self::OPERATOR_NIN,
        self::OPERATOR_REGEX,
    ];

    private function __construct($left, $operator, $right)
    {
        $this->left = $left;
        $this->operator = $operator;
        $this->right = $right;
    }

    /**
     * @param mixed  $left
     * @param string $operator
     * @param mixed  $right
     * @return self
     */
    public static function fromProperties($left, $operator, $right)
    {
        $operator = strtolower($operator);

        self::validateOperand($left);
        self::validateOperator($operator);
        self::validateOperand($right);

        return new self($left, $operator, $right);
    }

    /**
     * @param array $expression
     * @throws InvalidAssertionException
     * @return self
     */
    public static function fromArray(array $expression)
    {
        $required = ['left', 'operator', 'right'];

        if (count(array_intersect_key($expression, array_flip($required))) < count($required)) {
            throw new InvalidAssertionException(
                "Expression assertion requires 'left', 'operator' and 'right' to be supplied"
            );
        }

        return self::fromProperties(
            $expression['left'],
            $expression['operator'],
            $expression['right']
        );
    }

    private static function validateOperand($operand)
    {
        if (is_array($operand) && isset($operand[self::OPERAND_CONTEXT_PROPERTY])) {
            if (! is_string($operand[self::OPERAND_CONTEXT_PROPERTY])) {
                throw new InvalidAssertionException('Expression assertion context operand must be string');
            }
        }
    }

    private static function validateOperator($operator)
    {
        if (! in_array($operator, self::$validOperators)) {
            throw new InvalidAssertionException('Provided expression assertion operator is not supported');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function assert(Acl $acl, RoleInterface $role = null, ResourceInterface $resource = null, $privilege = null)
    {
        $this->assertContext = [
            'acl' => $acl,
            'role' => $role,
            'resource' => $resource,
            'privilege' => $privilege,
        ];

        return $this->evaluate();
    }

    private function evaluate()
    {
        $left = $this->getLeftValue();
        $right = $this->getRightValue();

        return static::evaluateExpression($left, $this->operator, $right);
    }

    private function getLeftValue()
    {
        return $this->resolveOperandValue($this->left);
    }

    private function getRightValue()
    {
        return $this->resolveOperandValue($this->right);
    }

    private function resolveOperandValue($operand)
    {
        if (is_array($operand) && isset($operand[self::OPERAND_CONTEXT_PROPERTY])) {
            $contextProperty = $operand[self::OPERAND_CONTEXT_PROPERTY];

            if (strpos($contextProperty, '.') !== false) { //property path?
                list($objectName, $objectField) = explode('.', $contextProperty, 2);

                if (! isset($this->assertContext[$objectName])) {
                    throw new RuntimeException(sprintf(
                        "'%s' is not available in the assertion context",
                        $objectName
                    ));
                }

                try {
                    return $this->getObjectFieldValue($this->assertContext[$objectName], $objectField);
                } catch (\RuntimeException $ex) {
                    throw new RuntimeException(sprintf(
                        "'%s' property cannot be resolved on the '%s' object",
                        $objectField,
                        $objectName
                    ));
                }
            }

            if (! isset($this->assertContext[$contextProperty])) {
                throw new RuntimeException(sprintf(
                    "'%s' is not available in the assertion context",
                    $contextProperty
                ));
            }

            return $this->assertContext[$contextProperty];
        }

        return $operand;
    }

    private function getObjectFieldValue($object, $field)
    {
        $accessors = ['get', 'is'];

        $fieldAccessor = $field;

        if (false !== strpos($field, '_')) {
            $fieldAccessor = str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
        }

        foreach ($accessors as $accessor) {
            $accessor .= $fieldAccessor;

            if (! method_exists($object, $accessor)) {
                continue;
            }

            return $object->$accessor();
        }

        if (! property_exists($object, $field)) {
            throw new \RuntimeException('Object property cannot be resolved');
        }

        return $object->$field;
    }

    private static function evaluateExpression($left, $operator, $right)
    {
        switch ($operator) {
            case self::OPERATOR_EQ:
                return $left == $right;
            case self::OPERATOR_NEQ:
                return $left != $right;
            case self::OPERATOR_LT:
                return $left < $right;
            case self::OPERATOR_LTE:
                return $left <= $right;
            case self::OPERATOR_GT:
                return $left > $right;
            case self::OPERATOR_GTE:
                return $left >= $right;
            case self::OPERATOR_IN:
                return in_array($left, $right);
            case self::OPERATOR_NIN:
                return ! in_array($left, $right);
            case self::OPERATOR_REGEX:
                return (bool) preg_match($right, $left);
            default:
                throw new RuntimeException(sprintf(
                    'Unsupported expression assertion operator: %s',
                    $operator
                ));
        }
    }

    public function __sleep()
    {
        return [
            'left',
            'operator',
            'right',
        ];
    }
}
