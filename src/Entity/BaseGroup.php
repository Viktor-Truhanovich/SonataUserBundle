<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Entity;

use Sonata\UserBundle\Model\Group as AbstractedGroup;

/**
 * Represents a Base Group Entity.
 */
class BaseGroup extends AbstractedGroup
{
    /**
     * Represents a string representation.
     */
    public function __toString(): string
    {
        return $this->getName() ?: '';
    }
}
