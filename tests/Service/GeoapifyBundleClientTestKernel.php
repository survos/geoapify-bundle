<?php

declare(strict_types=1);

namespace Survos\GeoapifyBundle\Tests\Service;

use Survos\GeoapifyBundle\SurvosGeoapifyBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class GeoapifyBundleClientTestKernel extends Kernel
{
    public function registerBundles(): array
    {
        return [
            new SurvosGeoapifyBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
    }

    public function getCacheDir(): string
    {
        return __DIR__.'/cache/'.spl_object_hash($this);
    }
}
