<?php

namespace App\Tests\Functional\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

abstract class BaseFunctionalTestCase extends ApiTestCase
{
    use Factories;
    use ResetDatabase;

    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->resetDatabase();
    }

    protected function tearDown(): void
    {
        $this->entityManager->clear();
        parent::tearDown();
    }

    protected function resetDatabase(): void
    {
    }
}
