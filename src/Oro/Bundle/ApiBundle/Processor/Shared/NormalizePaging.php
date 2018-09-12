<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Removes paging properties (FirstResult and MaxResults) from the Criteria object
 * if MaxResults equals to -1, that means "unlimited".
 */
class NormalizePaging implements ProcessorInterface
{
    const UNLIMITED_RESULT = -1;

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $criteria = $context->getCriteria();
        if (null === $criteria) {
            // the criteria object does not exist
            return;
        }

        // check if a paging is disabled
        if (self::UNLIMITED_RESULT === $criteria->getMaxResults()) {
            $criteria->setFirstResult(null);
            $criteria->setMaxResults(null);
        }
    }
}
