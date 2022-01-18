<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use App\Service\FilterService;

/**
 * Base entity repository for abstraction.
 * @template T
 * @extends ServiceEntityRepository<T>
 */
abstract class BaseEntityRepository extends ServiceEntityRepository
{
    /*
    ----------------------------------------------------------------------------
        Constants & enumerators
    ----------------------------------------------------------------------------
     */

    /** @const class-string|null Fully qualified class name for this entity. (Expected to be overridden in all repository classes.) */
    public const ENTITY_FQCN = null;

    /** @const string|null Alias for this entity in repository queries. (Expected to be overridden in all repository classes.) */
    public const ENTITY_ALIAS = null;

    /** @const int Do not include soft deleted entities. */
    public const INCLUDE_DELETED_NO = 0;

    /** @const int Include soft deleted entities. */
    public const INCLUDE_DELETED_YES = 1;

    /** @const int Exclusively include soft deleted entities. (Do not show non-deleted entities.) */
    public const INCLUDE_DELETED_EXCLUSIVE = 2;



    /*
    ----------------------------------------------------------------------------
        Life cycle functions
    ----------------------------------------------------------------------------
     */

    /**
     * Constructor.
     * @param ManagerRegistry $registry Doctrine entity manager registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        if (null === static::ENTITY_FQCN || !($entityFqcn = trim(static::ENTITY_FQCN))) {
            throw new \DomainException("Repository entity class not defined.");
        }

        if (null === static::ENTITY_ALIAS || !($entityAlias = trim(static::ENTITY_ALIAS))) {
            throw new \DomainException("Repository entity alias not defined.");
        }

        if (!class_exists($entityFqcn)) {
            throw new \DomainException("Repository entity class not found.");
        }

        /** @var class-string<T> $entityFqcn */
        parent::__construct($registry, $entityFqcn);
    }



    /*
    ----------------------------------------------------------------------------
        Helper functions
    ----------------------------------------------------------------------------
     */

    /**
     * Validate an array used to search entities.
     * @param  array<string, mixed>|null $criteria Associative list of fields to order by as "field" => value
     * @return void
     */
    protected function validateCriteria(?array $criteria = null): void
    {
        if (null === $criteria) {
            return;
        }

        foreach ($criteria as $field => &$order) {
            if (!is_string($field)) {
                throw new \InvalidArgumentException("Field must be a string.");
            }

            if (!($field = trim($field))) {
                throw new \InvalidArgumentException("Field not specified.");
            }
        }

        return;
    }

    /**
     * Validate an array used to order entities.
     * @param  array<string, string>|null $orderBy Associative list of fields to order by as "field" => "ASC"|"DESC"
     * @return void
     */
    protected function validateOrderBy(array $orderBy = null): void
    {
        if (null === $orderBy) {
            return;
        }

        foreach ($orderBy as $field => &$order) {
            if (!is_string($field)) {
                throw new \InvalidArgumentException("Field must be a string.");
            }

            if (!($field = trim($field))) {
                throw new \InvalidArgumentException("Field not specified.");
            }

            if (!is_string($order)) {
                throw new \InvalidArgumentException("Order must be a string.");
            }

            if (!($order = strtoupper(trim($order)))) {
                throw new \InvalidArgumentException("Order not specified.");
            }

            if (!in_array($order, ["ASC", "DESC"])) {
                throw new \InvalidArgumentException(sprintf("Order \"%s\" invalid.", $order));
            }
        }

        return;
    }

    /**
     * Handle include soft-deleted entities option for a query builder.
     * @param  QueryBuilder $qb             Query builder
     * @param  int          $includeDeleted Include soft-deleted entities
     * @return self<T>
     */
    protected function handleIncludeDeleted(QueryBuilder $qb, int $includeDeleted): self
    {
        switch ($includeDeleted) {
            case self::INCLUDE_DELETED_NO:
                $qb->andWhere(sprintf("%s.timeStampDeleted IS NULL", static::ENTITY_ALIAS));
                break;

            case self::INCLUDE_DELETED_YES:
                // No need to amend the query.
                break;

            case self::INCLUDE_DELETED_EXCLUSIVE:
                $qb->andWhere(sprintf("%s.timeStampDeleted IS NOT NULL", static::ENTITY_ALIAS));
                break;

            default:
                throw new \UnexpectedValueException(sprintf("Include soft-deleted entities option %d invalid.", $includeDeleted));
        }

        return $this;
    }



    /*
    ----------------------------------------------------------------------------
        Repository functions
    ----------------------------------------------------------------------------
     */

    /**
     * Find all entities in the repository.
     * @param  array<string, string>|null $orderBy        Associative list of fields to order by as "field" => "ASC"|"DESC"
     * @param  int                        $includeDeleted Include soft-deleted entities
     * @return array<object>                              Matched entities
     */
    public function findAll(?array $orderBy = [], int $includeDeleted = self::INCLUDE_DELETED_NO): array
    {
        $this->validateOrderBy($orderBy);

        $qb = $this->createQueryBuilder(static::ENTITY_ALIAS);
        $this->handleIncludeDeleted($qb, $includeDeleted);

        if (\is_array($orderBy)) {
            foreach ($orderBy as $field => $order) {
                $qb->addOrderBy(sprintf("%s.%s", static::ENTITY_ALIAS, $field), $order);
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find entities by a set criteria.
     * @param  array<string, mixed>       $criteria       Associative list of fields to search by as "field" => value
     * @param  array<string, string>|null $orderBy        Associative list of fields to order by as "field" => "ASC"|"DESC"
     * @param  int|null                   $limit          Limit number of results
     * @param  int|null                   $offset         Start from this row index
     * @param  int                        $includeDeleted Include soft-deleted entities
     * @return array<object>                              Matched entities
     */
    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null, int $includeDeleted = self::INCLUDE_DELETED_NO): array
    {
        $this->validateCriteria($criteria);
        $this->validateOrderBy($orderBy);

        // These are not type hinted to remain compatible with Doctrine's function.
        $limit  = intval($limit);
        $offset = intval($offset);

        $qb = $this->createQueryBuilder(static::ENTITY_ALIAS);
        $this->handleIncludeDeleted($qb, $includeDeleted);

        if (\is_array($criteria)) {
            foreach ($criteria as $field => $value) {
                $qb
                    ->andWhere(sprintf("%s.%s = :%s", static::ENTITY_ALIAS, $field, $field))
                    ->setParameter($field, $value)
                ;
            }
        }

        if (\is_array($orderBy)) {
            foreach ($orderBy as $field => $order) {
                $qb->addOrderBy(sprintf("%s.%s", static::ENTITY_ALIAS, $field), $order);
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find an entity by a set criteria.
     * @param  array<string, mixed>       $criteria       Associative list of fields to search by as "field" => value
     * @param  array<string, string>|null $orderBy        Associative list of fields to order by as "field" => "ASC"|"DESC"
     * @param  int                        $includeDeleted Include soft-deleted entities (0 = false, 1 = true, 2 = exclusive)
     * @return T|null                                     Matched entity, or null if not found
     */
    public function findOneBy(array $criteria, ?array $orderBy = null, int $includeDeleted = self::INCLUDE_DELETED_NO)
    {
        $this->validateCriteria($criteria);
        $this->validateOrderBy($orderBy);

        $qb = $this->createQueryBuilder(static::ENTITY_ALIAS);
        $this->handleIncludeDeleted($qb, $includeDeleted);

        if (\is_array($criteria)) {
            foreach ($criteria as $field => $value) {
                $qb
                    ->andWhere(sprintf("%s.%s = :%s", static::ENTITY_ALIAS, $field, $field))
                    ->setParameter($field, $value)
                ;
            }
        }

        if (\is_array($orderBy)) {
            foreach ($orderBy as $field => $order) {
                $qb->addOrderBy(sprintf("%s.%s", static::ENTITY_ALIAS, $field), $order);
            }
        }

        // Although it would be common sense to limit the results to 1, it would
        // be wise to throw an exception if somehow multiple results exist.
        // $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find an entity by a set criteria.
     * @param  Criteria           $criteria       Instance of a Doctrine search criteria
     * @param  int                $includeDeleted Include soft-deleted entities
     * @return Collection<int, T>                 Matched entities
     */
    public function matching(Criteria $criteria, int $includeDeleted = self::INCLUDE_DELETED_NO): Collection
    {
        // Pass through to the standard Doctrine function
        $matches = parent::matching($criteria);

        switch ($includeDeleted) {
            case self::INCLUDE_DELETED_NO:
                foreach ($matches as $match) {
                    if ($match->isDeleted()) {
                        $matches->removeElement($match);
                    }
                }
                break;

            case self::INCLUDE_DELETED_YES:
                // No need to amend the collection.
                break;

            case self::INCLUDE_DELETED_EXCLUSIVE:
                foreach ($matches as $match) {
                    if (!$match->isDeleted()) {
                        $matches->removeElement($match);
                    }
                }
                break;
        }

        return $matches;
    }
}
