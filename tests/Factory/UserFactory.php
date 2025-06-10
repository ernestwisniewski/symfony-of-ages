<?php

namespace App\Tests\Factory;

use App\Infrastructure\Generic\Account\Doctrine\User;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<User>
 */
final class UserFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array
    {
        return [
            'email' => self::faker()->unique()->safeEmail(),
            'roles' => ['ROLE_USER'],
            'password' => '$2y$13$tU7cG7xMZo7P8A6jvzEH6.j2zJtlcE8V0YkRHx5mEZf8zR3TwU4xK', // 'password'
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }
} 