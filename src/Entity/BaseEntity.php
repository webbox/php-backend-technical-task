<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Component\Uid\Uuid;
use Symfony\Bridge\Doctrine\IdGenerator\UuidV6Generator;

use App\Entity\User;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */
abstract class BaseEntity
{
    /*
    ----------------------------------------------------------------------------
        Local data
    ----------------------------------------------------------------------------
     */

    /**
     * @var Uuid
     * @ORM\Column(name="id", type="uuid", unique=true, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidV6Generator::class)
     * @Assert\Uuid(strict=true)
     */
    protected $id;

    /**
     * @var \DateTimeImmutable
     * @ORM\Column(name="time_stamp_created", type="datetime_immutable", unique=false, nullable=false)
     * @Assert\NotNull()
     * @Assert\Type("\DateTimeInterface")
     */
    protected $timeStampCreated;

    /**
     * @var \DateTimeImmutable|null
     * @ORM\Column(name="time_stamp_modified", type="datetime_immutable", unique=false, nullable=true)
     * @Assert\Type({"null", "\DateTimeImmutable"})
     */
    protected $timeStampModified;

    /**
     * @var \DateTimeImmutable|null
     * @ORM\Column(name="time_stamp_accessed", type="datetime_immutable", unique=false, nullable=true)
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
     * @var \App\Entity\User|null
     * @ORM\ManyToOne(targetEntity="\App\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="creator_user_id", referencedColumnName="id", unique=false, nullable=true, onDelete="SET NULL")
     * @Assert\Type({"null", "\App\Entity\User"})
     */
    protected $creator;

    /**
     * @var \App\Entity\User|null
     * @ORM\ManyToOne(targetEntity="\App\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="owner_user_id", referencedColumnName="id", unique=false, nullable=true, onDelete="SET NULL")
     * @Assert\Type({"null", "\App\Entity\User"})
     */
    protected $owner;

    /**
     * @var \App\Entity\User|null
     * @ORM\ManyToOne(targetEntity="\App\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="deleted_by_user_id", referencedColumnName="id", unique=false, nullable=true, onDelete="SET NULL")
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
     * @param \App\Entity\User|null $creator Creator (user entity)
     * @param \App\Entity\User|null $owner   Owner (user entity)
     */
    public function __construct(?\App\Entity\User $creator = null, ?\App\Entity\User $owner = null)
    {
        // $this->id       = Uuid::v6();
        $this->creator  = $creator;
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
        $this->timeStampCreated     = new \DateTimeImmutable();
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
        $this->timeStampAccessed    = new \DateTimeImmutable();
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
        $this->timeStampModified    = new \DateTimeImmutable();
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
        if (!($this->timeStampDeleted instanceof \DateTimeInterface)) {
            $this->timeStampDeleted = new \DateTimeImmutable();
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
     * Check if a string is a valid UUID.
     * @param  string     $string   String to check
     * @param  array|null &$matches Optionally output matched segments
     * @return bool                 True if valid, false if invalid
     */
    public static function s_isUUID(string $string, ?array &$matches = null): bool
    {
        if (36 !== strlen($string)) {
            return false;
        }

        $matches = [];
        if (
            !preg_match("/^([a-f0-9]{8})\-([a-f0-9]{4})\-([a-f0-9]{4})\-([a-f0-9]{4})\-([a-f0-9]{12})$/i", $string, $matches)
            || !is_array($matches)
            || 6 !== count($matches)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Get a shorter representation of a UUID if possible.
     * @param  string|Uuid $uuid           UUID string in full, or UUID instance (PHP 8+ can enforce a union type)
     * @param  bool        $throwOnBadUuid Throw an exception if the UUID is invalid
     * @return string                      UUID string shortened if possible
     */
    public static function s_getShortUUID($uuid, bool $throwOnBadUuid = false): string
    {
        if ($uuid instanceof Uuid || (is_scalar($uuid) && !is_string($uuid))) {
            $uuid = strval($uuid);
        }

        if (!is_string($uuid)) {
            throw new \UnexpectedValueException("UUID is not a string.");
        }

        $uuidBits = [];
        if (!static::s_isUUID($uuid, $uuidBits) || !is_array($uuidBits) || count($uuidBits) < 4) {
            if ($throwOnBadUuid) {
                throw new \UnexpectedValueException("UUID invalid.");
            }

            return $uuid;
        }

        return sprintf("%s-%s", trim($uuidBits[1]), trim($uuidBits[3]));
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
    public function delete(?string $user = null, ?string $comment = null): self
    {
        $this->timeStampDeleted = new \DateTimeImmutable();
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



    /*
    ----------------------------------------------------------------------------
        Data functions
    ----------------------------------------------------------------------------
     */

    /**
     * Get id
     *
     * @return Uuid|null
     */
    public function getId(): ?Uuid
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
        return $this->timeStampCreated;
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
        $this->timeStampCreated = $timeStampCreated;

        return $this;
    }

    /**
     * Get timeStampModified
     *
     * @return \DateTimeImmutable|null
     */
    public function getTimeStampModified(): ?\DateTimeImmutable
    {
        return $this->timeStampModified;
    }

    /**
     * Set timeStampModified
     *
     * @param \DateTimeImmutable $timeStampModified
     *
     * @return self
     */
    public function setTimeStampModified(\DateTimeImmutable $timeStampModified): self
    {
        $this->timeStampModified = $timeStampModified;

        return $this;
    }

    /**
     * Get timeStampAccessed
     *
     * @return \DateTimeImmutable|null
     */
    public function getTimeStampAccessed(): ?\DateTimeImmutable
    {
        return $this->timeStampAccessed;
    }

    /**
     * Set timeStampAccessed
     *
     * @param \DateTimeImmutable $timeStampAccessed
     *
     * @return self
     */
    public function setTimeStampAccessed(\DateTimeImmutable $timeStampAccessed): self
    {
        $this->timeStampAccessed = $timeStampAccessed;

        return $this;
    }

    /**
     * Get timeStampDeleted
     *
     * @return \DateTimeImmutable|null
     */
    public function getTimeStampDeleted(): ?\DateTimeImmutable
    {
        return $this->timeStampDeleted;
    }

    /**
     * Set timeStampDeleted
     *
     * @param \DateTimeImmutable|null $timeStampDeleted
     *
     * @return self
     */
    public function setTimeStampDeleted(?\DateTimeImmutable $timeStampDeleted): self
    {
        $this->timeStampDeleted = $timeStampDeleted;

        return $this;
    }

    /**
     * Get creator
     *
     * @return string|null
     */
    public function getCreator(): ?string
    {
        return $this->creator;
    }

    /**
     * Set creator
     *
     * @param string|null $creator
     *
     * @return self
     */
    public function setCreator(?string $creator = null): self
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get owner
     *
     * @return string|null
     */
    public function getOwner(): ?string
    {
        return $this->owner;
    }

    /**
     * Set owner
     *
     * @param string|null $owner
     *
     * @return self
     */
    public function setOwner(?string $owner = null): self
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get deleter
     *
     * @return string|null
     */
    public function getDeleter(): ?string
    {
        return $this->deleter;
    }

    /**
     * Set deleter
     *
     * @param string|null $deleter
     *
     * @return self
     */
    public function setDeleter(?string $deleter = null): self
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
