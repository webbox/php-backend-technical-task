<?php

namespace App\Repository;

use App\Repository\BaseEntityRepository;

use App\Entity\User;

/**
 * @template-extends BaseEntityRepository<\App\Entity\User>
 */
class UserRepository extends BaseEntityRepository
{
    /*
    ----------------------------------------------------------------------------
        Constants & enumerators
    ----------------------------------------------------------------------------
     */

    /** @const class-string Fully qualified class name for this entity. */
    public const ENTITY_FQCN = User::class;

    /** @const string Alias for this entity in repository queries. */
    public const ENTITY_ALIAS = "user";



    /*
    ----------------------------------------------------------------------------
        Repository functions
    ----------------------------------------------------------------------------
     */

    /**
     * Load user for authentication.
     * @param  string    $identifier User name or email address
     * @return User|null
     */
    public function loadUserByIdentifier(string $identifier): ?User
    {
        $query = $this->createQueryBuilder(static::ENTITY_ALIAS)
            ->select(static::ENTITY_ALIAS)
            ->where(sprintf("%s.username = :usernameOrEmail", static::ENTITY_ALIAS))
            ->orWhere(sprintf("%s.email = :usernameOrEmail", static::ENTITY_ALIAS))
            ->setParameter("usernameOrEmail", $identifier)
            ->getQuery()
        ;

        return $query->getOneOrNullResult();
    }
}
