<?php

namespace App\Factory;

use App\Entity\User\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<User>
 */
final class UserFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    public static function class(): string
    {
        return User::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        $firstName = self::faker()->firstName();
        $lastName = self::faker()->lastName();
        return [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => strtolower($firstName) . '_' . strtolower($lastName) . '@mailinator.com',
            'password' => '123456',
            'roles' => [self::faker()->randomElement(['ROLE_USER', 'ROLE_ADMIN', 'ROLE_MENTOR'])]
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function (User $user) {
                $user->setPassword($this->passwordHasher->hashPassword(
                    $user,
                    $user->getPassword()
                ));
            })->andPersist();
    }
}
