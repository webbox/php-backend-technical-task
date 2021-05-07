<?php

namespace App\Repository;

use App\Repository\BaseEntityRepository;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

use App\Entity\User;

class UserRepository extends BaseEntityRepository implements UserLoaderInterface
{
    /*
    ----------------------------------------------------------------------------
        Constants & enumerators
    ----------------------------------------------------------------------------
     */

    /**
     * @const string Alias for this entity in repository queries.
     */
    public const ENTITY_ALIAS = "user";

    /**
     * @const string Entity FQCN.
     */
    public const ENTITY_CLASS = User::class;



    /*
    ----------------------------------------------------------------------------
        Repository functions
    ----------------------------------------------------------------------------
     */

    /**
     * Load user for authentication.
     * @param  string    $usernameOrEmail User name or email address
     * @return User|null
     */
    public function loadUserByUsername(string $usernameOrEmail): ?User
    {
        $query = $this->createQueryBuilder(static::ENTITY_ALIAS)
            ->select(static::ENTITY_ALIAS)
            ->where(sprintf("%s.username = :usernameOrEmail", static::ENTITY_ALIAS))
            ->orWhere(sprintf("%s.email = :usernameOrEmail", static::ENTITY_ALIAS))
            ->setParameter("usernameOrEmail", $usernameOrEmail)
            ->getQuery()
        ;

        return $query->getOneOrNullResult();
    }
}
