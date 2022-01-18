<?php

namespace App\Entity;

use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Entity\User;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Symfony\Component\Uid\Uuid;

/**
 * Base entity for abstraction.
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity("id")
 */
abstract class BaseEntity
{
    /*
    ----------------------------------------------------------------------------
        Constants & enumerators
    ----------------------------------------------------------------------------
     */

    /**
     * @const string String representation of a null GUID.
     */
    public const NULL_GUID = "00000000-0000-0000-0000-000000000000";

    /**
     * @const string
     * Define the preferred time zone to use when timestamps are persisted. This
     * should in most cases be "UTC" for maximum compatibility. If you don't
     * know what this means you likely won't need to change this constant.
     */
    public const DEFAULT_TIMEZONE = "UTC";



    /*
    ----------------------------------------------------------------------------
        Variables
    ----------------------------------------------------------------------------
     */

    /**
     * @var \DateTimeZone|null
     * Remember the working time zone of the user/application here. We will
     * always store dates/times in UTC for maximum compatibility. This is only
     * used to implicitly convert between storage and user/application working
     * time zones for logic/UI purposes. (This is not persisted in the entity.)
     */
    protected static $dateTimeZone;

    /** @var LoggerInterface|null Logger service. */
    protected static $logger;

    /** @var EntityManagerInterface|null Entity manager. */
    protected static $em;

    /** @var TranslatorInterface|null Translator service. */
    protected static $translator;

    /** @var User|null The current "working" user. */
    protected static $user;



    /*
    ----------------------------------------------------------------------------
        Local data
    ----------------------------------------------------------------------------
     */

    /**
     * @var string
     * @ORM\Column(name="id", type="guid", unique=true, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @Assert\NotBlank(groups={"Intricate"})
     * @Assert\Uuid(strict=true)
     */
    protected $id = self::NULL_GUID;

    /**
     * @var \DateTimeImmutable|null
     * @ORM\Column(name="time_stamp_created", type="datetime_immutable", unique=false, nullable=false)
     * @Assert\NotNull(groups={"Intricate"})
     * @Assert\Type("\DateTimeImmutable")
     */
    protected $timeStampCreated;

    /**
     * @var \DateTimeImmutable|null
     * @ORM\Column(name="time_stamp_modified", type="datetime_immutable", unique=false, nullable=true)
     * @Assert\NotNull(groups={"Intricate"})
     * @Assert\Type({"null", "\DateTimeImmutable"})
     */
    protected $timeStampModified;

    /**
     * @var \DateTimeImmutable|null
     * @ORM\Column(name="time_stamp_accessed", type="datetime_immutable", unique=false, nullable=true)
     * @Assert\NotNull(groups={"Intricate"})
     * @Assert\Type({"null", "\DateTimeImmutable"})
     */
    protected $timeStampAccessed;

    /**
     * @var \DateTimeImmutable|null
     * @ORM\Column(name="time_stamp_deleted", type="datetime_immutable", unique=false, nullable=true)
     * @Assert\Type({"null", "\DateTimeImmutable"})
     */
    protected $timeStampDeleted;

    /**
     * @var User|null
     * @ORM\ManyToOne(targetEntity="\App\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="creator_user_id", referencedColumnName="id", unique=false, nullable=true, onDelete="SET NULL")
     * @Assert\Type({"null", "\App\Entity\User"})
     */
    protected $creator;

    /**
     * @var User|null
     * @ORM\ManyToOne(targetEntity="\App\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="owner_user_id", referencedColumnName="id", unique=false, nullable=true, onDelete="SET NULL")
     * @Assert\Type({"null", "\App\Entity\User"})
     */
    protected $owner;

    /**
     * @var User|null
     * @ORM\ManyToOne(targetEntity="\App\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="deleted_by_user_id", referencedColumnName="id", unique=false, nullable=true, onDelete="SET NULL")
     * @Assert\Type({"null", "\App\Entity\User"})
     */
    protected $deleter;

    /**
     * @var string|null
     * @ORM\Column(name="delete_comment", type="string", length=200, unique=false, nullable=true)
     * @Assert\Type(type="string")
     * @Assert\Length(max=200)
     */
    protected $deleterComment;



    /*
    ----------------------------------------------------------------------------
        Life cycle functions
    ----------------------------------------------------------------------------
     */

    /**
     * Constructor.
     * @param User|null $creator Creator (user entity)
     * @param User|null $owner   Owner (user entity)
     */
    public function __construct(?User $creator = null, ?User $owner = null)
    {
        if (!(static::$dateTimeZone instanceof \DateTimeZone)) {
            static::$dateTimeZone = new \DateTimeZone(date_default_timezone_get());
        }

        $this->id       = (Uuid::v6())->toRfc4122();
        $this->creator  = $creator ?: self::s_getUser();
        $this->owner    = $owner;

        $this->onCreate();

        if (method_exists($this, "onConstruct")) {
            $this->onConstruct();
        }
    }

    /**
     * To string.
     * @return string
     */
    public function __toString()
    {
        return $this->id;
    }

    /**
     * On create event.
     *
     * @ORM\PrePersist
     *
     * @return void
     */
    public function onCreate(): void
    {
        $now = static::s_createDateTime("now", true);

        $this->timeStampCreated     = ($now instanceof \DateTimeImmutable ? $now : \DateTimeImmutable::createFromMutable($now));
        $this->timeStampModified    = null;
        $this->timeStampAccessed    = null;
        $this->timeStampDeleted     = null;
    }

    /**
     * On access event.
     *
     * @ORM\PostLoad
     *
     * @return void
     */
    public function onAccess(): void
    {
        $now = static::s_createDateTime("now", true);

        $this->timeStampAccessed    = ($now instanceof \DateTimeImmutable ? $now : \DateTimeImmutable::createFromMutable($now));
    }

    /**
     * On modify event.
     *
     * @ORM\PreUpdate
     *
     * @return void
     */
    public function onModify(): void
    {
        $now = static::s_createDateTime("now", true);

        $this->timeStampModified    = ($now instanceof \DateTimeImmutable ? $now : \DateTimeImmutable::createFromMutable($now));
    }

    /**
     * On hard delete event.
     *
     * @ORM\PreRemove
     *
     * @return void
     */
    public function onDelete(): void
    {
        $now = static::s_createDateTime("now", true);

        if (!($this->timeStampDeleted instanceof \DateTimeInterface)) {
            $this->timeStampDeleted = ($now instanceof \DateTimeImmutable ? $now : \DateTimeImmutable::createFromMutable($now));
        }

        if (!$this->deleterComment) {
            $this->deleterComment = "Entity shred pending.";
        }
    }



    /*
    ----------------------------------------------------------------------------
        Static functions
    ----------------------------------------------------------------------------
     */

    /**
     * Get the logging interface.
     * @return LoggerInterface|null Logger service
     */
    public static function s_getLogger(): ?LoggerInterface
    {
        return self::$logger;
    }

    /**
     * Set the logging interface.
     * @param  LoggerInterface|null $logger Logger service
     * @return void
     */
    public static function s_setLogger(?LoggerInterface $logger = null): void
    {
        self::$logger = $logger;
    }

    /**
     * Get the logging interface.
     * @return EntityManagerInterface|null Entity manager
     */
    public static function s_getEntityManager(): ?EntityManagerInterface
    {
        return self::$em;
    }

    /**
     * Set the logging interface.
     * @param  EntityManagerInterface|null $em Entity manager
     * @return void
     */
    public static function s_setEntityManager(?EntityManagerInterface $em = null): void
    {
        self::$em = $em;
    }

    /**
     * Get the translator interface.
     * @return TranslatorInterface|null Translator service
     */
    public static function s_getTranslator(): ?TranslatorInterface
    {
        return self::$translator;
    }

    /**
     * Set the translator interface.
     * @param  TranslatorInterface|null $translator Translator service
     * @return void
     */
    public static function s_setTranslator(?TranslatorInterface $translator = null): void
    {
        self::$translator = $translator;
    }

    /**
     * Get the working user.
     * @return User|null User entity
     */
    public static function s_getUser(): ?User
    {
        return self::$user;
    }

    /**
     * Set the working user.
     * @param  User|null $user User entity
     * @return void
     */
    public static function s_setUser(?User $user = null): void
    {
        self::$user = $user;
    }

    /**
     * Check if a string is a valid UUID.
     * @param  string                  $string  String to check
     * @param  array<int, string>|null $matches Optionally output matched segments
     * @return bool                             True if valid, false if invalid
     */
    public static function s_isUUID(string $string, ?array &$matches = null): bool
    {
        if (36 !== strlen($string)) {
            return false;
        }

        $matches = [];
        if (
            !preg_match("/^([a-f0-9]{8})\-([a-f0-9]{4})\-([a-f0-9]{4})\-([a-f0-9]{4})\-([a-f0-9]{12})$/i", $string, $matches)
            || !\is_array($matches)
            // || 6 !== count($matches) // Apparently always true..!
        ) {
            return false;
        }

        return true;
    }

    /**
     * Get a shorter representation of a UUID if possible.
     * @param  string|Uuid $uuid           UUID string in full, or UUID instance
     * @param  bool        $throwOnBadUuid Throw an exception if the UUID is invalid
     * @return string                      UUID string shortened if possible
     */
    public static function s_getShortUUID(string|Uuid $uuid, bool $throwOnBadUuid = false): string
    {
        if ($uuid instanceof Uuid) {
            $uuid = strval($uuid);
        }

        $uuidBits = [];
        if (
            !static::s_isUUID($uuid, $uuidBits)
            || !\is_array($uuidBits)
            // || count($uuidBits) < 4 // Apparently always true..!
        ) {
            if ($throwOnBadUuid) {
                throw new \UnexpectedValueException("UUID invalid.");
            }

            return $uuid;
        }

        return sprintf("%s-%s", trim($uuidBits[1]), trim($uuidBits[3]));
    }

    /**
     * Get the storage time zone.
     * @return \DateTimeZone Time zone
     */
    public static function s_getStorageDateTimeZone(): \DateTimeZone
    {
        return new \DateTimeZone(!empty(self::DEFAULT_TIMEZONE) ? self::DEFAULT_TIMEZONE : "UTC");
    }

    /**
     * Create a \DateTime or \DateTimeImmutable using the storage time zone. (Useful for constructors.)
     * @param  string                       $time      Date/time string
     * @param  bool                         $immutable Create an immutable instance
     * @return \DateTime|\DateTimeImmutable
     */
    public static function s_createDateTime(string $time = "now", bool $immutable = true): \DateTime|\DateTimeImmutable
    {
        return $immutable ? new \DateTimeImmutable($time, static::s_getStorageDateTimeZone()) : new \DateTime($time, static::s_getStorageDateTimeZone());
    }

    /**
     * Set the user/application working time zone.
     * @param  \DateTimeZone|null $dateTimeZone Time zone, or null to system default
     * @return void
     */
    public static function s_setWorkingDateTimeZone(\DateTimeZone $dateTimeZone = null): void
    {
        static::$dateTimeZone = $dateTimeZone instanceof \DateTimeZone ? $dateTimeZone : new \DateTimeZone(date_default_timezone_get());
    }

    /**
     * Get the user/application working time zone.
     * @return \DateTimeZone|null Time zone, or null if unset
     */
    public static function s_getWorkingDateTimeZone(): ?\DateTimeZone
    {
        return static::$dateTimeZone instanceof \DateTimeZone ? static::$dateTimeZone : null;
    }

    /**
     * Read a date/time instance in the user/application working time zone. (Convert from the storage time zone.)
     * This should be used for every entity "get" functions referencing a persisted date, time, and date/time property.
     * @param  \DateTime|\DateTimeImmutable|null $dateTime Date/time instance in the storage time zone
     * @return \DateTime|\DateTimeImmutable|null           Date/time instance in the user/application working time zone
     */
    protected static function s_readDateTimeZone(\DateTime|\DateTimeImmutable|null $dateTime = null): \DateTime|\DateTimeImmutable|null
    {
        if (!($dateTime instanceof \DateTime) && !($dateTime instanceof \DateTimeImmutable)) {
            // Allow null to be specified, so every `get` function doesn't need to do this check
            return null;
        }

        if (!($dateTimeZone = static::s_getWorkingDateTimeZone())) {
            // Target time zone unset
            return $dateTime;
        }

        if ($dateTime->getTimeZone() == $dateTimeZone) {
            // Time zone already matches
            return $dateTime;
        }

        $dateTimeConverted = clone $dateTime;
        $dateTimeConverted->setTimezone($dateTimeZone);

        return $dateTimeConverted;
    }

    /**
     * Write a date/time instance in the storage time zone. (Convert from the user/application working time zone.)
     * This should be used for every entity "set" functions referencing a persisted date, time, and date/time property.
     * @param  \DateTime|\DateTimeImmutable|null $dateTime Date/time instance in the user/application working time zone
     * @return \DateTime|\DateTimeImmutable|null           Date/time instance in the storage time zone
     */
    protected static function s_writeDateTimeZone(\DateTime|\DateTimeInterface|null $dateTime = null): \DateTime|\DateTimeInterface|null
    {
        if (!($dateTime instanceof \DateTime) && !($dateTime instanceof \DateTimeImmutable)) {
            // Allow null to be specified, so every `set` function doesn't need to do this check
            return null;
        }

        $dateTimeZone = static::s_getStorageDateTimeZone();
        /* Apparently never true..!
        if (!($dateTimeZone = static::s_getStorageDateTimeZone())) {
            // Target time zone unset
            return $dateTime;
        }
         */

        if ($dateTime->getTimeZone() == $dateTimeZone) {
            // Time zone already matches
            return $dateTime;
        }

        $dateTimeConverted = clone $dateTime;
        $dateTimeConverted->setTimezone($dateTimeZone);

        return $dateTimeConverted;
    }

    /**
     * Cast a DateTime to DateTimeImmutable if it's not already one, but allow for null to pass though.
     * @param  \DateTime|\DateTimeImmutable|null $dateTime Date/time instance
     * @return \DateTimeImmutable|null                     Date/time instance, but immutable only
     */
    protected static function s_castDateTimeOrNullToImmutable(\DateTime|\DateTimeInterface|null $dateTime = null): ?\DateTimeImmutable
    {
        if ($dateTime instanceof \DateTimeImmutable) {
            // Already there.
            return $dateTime;
        }

        if ($dateTime instanceof \DateTime) {
            // Convert to mutable.
            return \DateTimeImmutable::createFromMutable($dateTime);
        }

        // Allow null to be specified, so every `set` function doesn't need to do this check
        return null;
    }



    /*
    ----------------------------------------------------------------------------
        Helper functions
    ----------------------------------------------------------------------------
     */

    /**
     * Soft delete this entity.
     * @param  User|null   $user    User whom deleted this entity
     * @param  string|null $comment An optional comment or reason for deletion
     * @return self
     */
    public function delete(?User $user = null, ?string $comment = null): self
    {
        $now = static::s_createDateTime("now", true);

        $this->timeStampDeleted = ($now instanceof \DateTimeImmutable ? $now : \DateTimeImmutable::createFromMutable($now));
        $this->deleter          = $user;
        $this->deleterComment   = $comment ? trim($comment) : null;

        return $this;
    }

    /**
     * Restore this soft deleted entity.
     * @return self
     */
    public function undelete(): self
    {
        $this->timeStampDeleted = null;
        $this->deleter          = null;
        $this->deleterComment   = null;

        return $this;
    }

    /**
     * Check if this entity has been soft deleted.
     * @return bool True if deleted, false if not
     */
    public function isDeleted(): bool
    {
        return ($this->timeStampDeleted instanceof \DateTimeInterface);
    }

    /**
     * Create a log message.
     * (Handles checking if a logger interface is available.)
     * @param  string               $level   Level
     * @param  string               $message Message
     * @param  array<string, mixed> $context Context
     * @return void
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        if (self::s_getLogger() instanceof LoggerInterface) {
            self::s_getLogger()->log($level, $message, array_merge([
                "_entityClass"  => get_class($this),
                "_entityId"     => $this->getId(),
            ], $context));
        }
    }

    /**
     * Check if this entity is persisted, as in, really is saved in the underlying database.
     * @return bool|null
     */
    public function isPersisted(): ?bool
    {
        if (self::s_getEntityManager() instanceof EntityManagerInterface) {
            return self::s_getEntityManager()->getUnitOfWork()->isInIdentityMap($this);
        }

        // Without access to the entity manager we don't know.
        return null;
    }

    /**
     * Translates the given message.
     * @param  string                $id         The message id (may also be an object that can be cast to string)
     * @param  array<string, string> $parameters An array of parameters for the message
     * @param  string|null           $domain     The domain for the message or null to use the default
     * @return string                            The translated string
     */
    protected function trans(string $id, array $parameters = [], ?string $domain = null): string
    {
        if (self::s_getTranslator() instanceof TranslatorInterface) {
            return self::s_getTranslator()->trans($id, $parameters, $domain);
        }

        // Without access to the translator service we can't translate.
        return $id;
    }



    /*
    ----------------------------------------------------------------------------
        Serialisation functions
    ----------------------------------------------------------------------------
     */

    /** @var array<string> Serialisable properties, devolved to each entity class. */
    protected $serialisableProperties = [];

    /** @var array<string> Serialisable properties of which are \DateTimeInterface, devolved to each entity class. */
    protected $serialisableDateTimeProperties = [];

    /** @var array<string> Serialisable properties of which are foreign entities, devolved to each entity class. */
    protected $serialisableForeignProperties = [];

    /**
     * Get a list of serialisable properties.
     * @return array<string>
     */
    public function getSerialisableProperties(): array
    {
        return array_merge([
            "id",
            "timeStampCreated",
            "timeStampModified",
            "timeStampAccessed",
            "timeStampDeleted",
            "creator",
            "owner",
            "deleter",
            "deleterComment",
        ], \is_array($this->serialisableProperties) && !empty($this->serialisableProperties) ? $this->serialisableProperties : []);
    }

    /**
     * Get a list of serialisable properties of which are \DateTimeInterface.
     * @return array<string>
     */
    public function getSerialisableDateTimeProperties(): array
    {
        return array_merge([
            "timeStampCreated",
            "timeStampModified",
            "timeStampAccessed",
            "timeStampDeleted",
        ], \is_array($this->serialisableDateTimeProperties) && !empty($this->serialisableDateTimeProperties) ? $this->serialisableDateTimeProperties : []);
    }

    /**
     * Get a list of serialisable properties of which are foreign entities.
     * @return array<string>
     */
    public function getSerialisableForeignProperties(): array
    {
        return \is_array($this->serialisableForeignProperties) && !empty($this->serialisableForeignProperties) ? $this->serialisableForeignProperties : [];
    }

    /**
     * Get a serialised output for this entity.
     * @return array<string, mixed>
     */
    public function getSerialised(): array
    {
        $serialised = [];

        foreach ($this->getSerialisableProperties() as $property) {
            $propertyGet = sprintf("get%s", ucfirst($property));
            if (!method_exists($this, $propertyGet)) {
                continue;
            }

            $value = $this->$propertyGet();

            if (in_array($property, $this->getSerialisableForeignProperties())) {
                if (!is_object($value) && !\is_array($value) && null !== $value) {
                    throw new \UnexpectedValueException(sprintf("Entity property \"%s\" was expected to be a collection, array, object, or null. %s given.", $property, gettype($value)));
                }

                if ($value instanceof Collection || \is_array($value)) {
                    $serialised[$property] = [];
                    /** @var object $foreignEntity */
                    foreach ($value as $foreignEntity) {
                        if (is_subclass_of($foreignEntity, self::class) && method_exists($foreignEntity, "getId")) {
                            $serialised[$property][] = $foreignEntity->getId();
                        }
                    }
                } elseif (is_object($value) && is_subclass_of($value, self::class) && method_exists($value, "getId")) {
                    $serialised[$property] = $value->getId();
                }
                continue;
            }

            if (in_array($property, $this->getSerialisableDateTimeProperties())) {
                if (!($value instanceof \DateTimeInterface) && null !== $value) {
                    throw new \UnexpectedValueException(sprintf("Entity property \"%s\" was expected to be a date/time interface or null. %s given.", $property, gettype($value)));
                }

                $serialised[$property] = ($value instanceof \DateTimeInterface ? $value->format("Y-m-d H:i:s") : null);
                continue;
            }

            $serialised[$property] = is_scalar($value) ? $value : serialize($value);
        }

        return $serialised;
    }



    /*
    ----------------------------------------------------------------------------
        Data functions
    ----------------------------------------------------------------------------
     */

    /**
     * Get id
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    /**
     * Get timeStampCreated
     *
     * @return \DateTimeImmutable|null
     */
    public function getTimeStampCreated(): ?\DateTimeImmutable
    {
        return static::s_castDateTimeOrNullToImmutable(static::s_readDateTimeZone($this->timeStampCreated));
    }

    /**
     * Set timeStampCreated
     *
     * @param \DateTimeImmutable $timeStampCreated
     *
     * @return self
     */
    public function setTimeStampCreated(\DateTimeImmutable $timeStampCreated): self
    {
        $dt = static::s_writeDateTimeZone($timeStampCreated);
        $this->timeStampCreated = ($dt instanceof \DateTimeImmutable ? $dt : null);

        return $this;
    }

    /**
     * Get timeStampModified
     *
     * @return \DateTimeImmutable|null
     */
    public function getTimeStampModified(): ?\DateTimeImmutable
    {
        return static::s_castDateTimeOrNullToImmutable(static::s_readDateTimeZone($this->timeStampModified));
    }

    /**
     * Set timeStampModified
     *
     * @param \DateTimeImmutable|null $timeStampModified
     *
     * @return self
     */
    public function setTimeStampModified(?\DateTimeImmutable $timeStampModified = null): self
    {
        $dt = static::s_writeDateTimeZone($timeStampModified);
        $this->timeStampModified = ($dt instanceof \DateTimeImmutable ? $dt : null);

        return $this;
    }

    /**
     * Get timeStampAccessed
     *
     * @return \DateTimeImmutable|null
     */
    public function getTimeStampAccessed(): ?\DateTimeImmutable
    {
        return static::s_castDateTimeOrNullToImmutable(static::s_readDateTimeZone($this->timeStampAccessed));
    }

    /**
     * Set timeStampAccessed
     *
     * @param \DateTimeImmutable|null $timeStampAccessed
     *
     * @return self
     */
    public function setTimeStampAccessed(\DateTimeImmutable $timeStampAccessed = null): self
    {
        $dt = static::s_writeDateTimeZone($timeStampAccessed);
        $this->timeStampAccessed = ($dt instanceof \DateTimeImmutable ? $dt : null);

        return $this;
    }

    /**
     * Get timeStampDeleted
     *
     * @return \DateTimeImmutable|null
     */
    public function getTimeStampDeleted(): ?\DateTimeImmutable
    {
        return static::s_castDateTimeOrNullToImmutable(static::s_readDateTimeZone($this->timeStampDeleted));
    }

    /**
     * Set timeStampDeleted
     *
     * @param \DateTimeImmutable|null $timeStampDeleted
     *
     * @return self
     */
    public function setTimeStampDeleted(?\DateTimeImmutable $timeStampDeleted = null): self
    {
        $dt = static::s_writeDateTimeZone($timeStampDeleted);
        $this->timeStampDeleted = ($dt instanceof \DateTimeImmutable ? $dt : null);

        return $this;
    }

    /**
     * Get creator
     *
     * @return User|null
     */
    public function getCreator(): ?User
    {
        return $this->creator;
    }

    /**
     * Set creator
     *
     * @param User|null $creator
     *
     * @return self
     */
    public function setCreator(?User $creator = null): self
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get owner
     *
     * @return User|null
     */
    public function getOwner(): ?User
    {
        return $this->owner;
    }

    /**
     * Set owner
     *
     * @param User|null $owner
     *
     * @return self
     */
    public function setOwner(?User $owner = null): self
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get deleter
     *
     * @return User|null
     */
    public function getDeleter(): ?User
    {
        return $this->deleter;
    }

    /**
     * Set deleter
     *
     * @param User|null $deleter
     *
     * @return self
     */
    public function setDeleter(?User $deleter = null): self
    {
        $this->deleter = $deleter;

        return $this;
    }

    /**
     * Get deleterComment
     *
     * @return string|null
     */
    public function getDeleterComment(): ?string
    {
        return $this->deleterComment;
    }

    /**
     * Set deleterComment
     *
     * @param string|null $deleterComment
     *
     * @return self
     */
    public function setDeleterComment(?string $deleterComment): self
    {
        $this->deleterComment = $deleterComment;

        return $this;
    }
}
