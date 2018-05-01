<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Permissions\Acl;

/**
 * Applicable to Resources and Roles. Provides information about the owner of
 * some object. Used in conjunction with the Ownership assertion.
 *
 * @author Nikola Posa <posa.nikola@gmail.com>
 */
interface ProprietaryInterface
{
    /**
     * @return mixed
     */
    public function getOwnerId();
}
