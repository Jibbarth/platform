<?php

namespace Oro\Bundle\ActionBundle\Configuration;

use Doctrine\Common\Collections\Collection;

interface ConfigurationValidatorInterface
{
    /**
     * @param array $configuration
     * @param Collection $errors
     */
    public function validate(array $configuration, Collection $errors = null);
}
