<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle;

use PhpErrorInsightBundle\DependencyInjection\PhpErrorInsightExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class PhpErrorInsightBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new PhpErrorInsightExtension();
        }

        return $this->extension instanceof ExtensionInterface ? $this->extension : null;
    }
}
