<?php

namespace App\Entity;

use App\Entity\BaseEntity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(name="user")
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity("username")
 * @UniqueEntity("email")
 */
class User extends BaseEntity implements \Serializable, UserInterface, EquatableInterface
{
    /*
    ----------------------------------------------------------------------------
        Local data
    ----------------------------------------------------------------------------
     */

    /**
     * @var string
     * @ORM\Column(name="username", type="string", length=200, unique=true, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     * @Assert\Length(min=1, max=200)
     */
    protected $username;

    /**
     * @var string|null
     * @ORM\Column(name="password", type="string", length=128, unique=false, nullable=true)
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=128)
     */
    protected $password;

    /**
     * @var string|null
     * @ORM\Column(name="first_name", type="string", length=200, unique=false, nullable=true)
     * @Assert\Type(type="string")
     * @Assert\Length(min=1, max=200)
     */
    protected $firstName;

    /**
     * @var string|null
     * @ORM\Column(name="last_name", type="string", length=200, unique=false, nullable=true)
     * @Assert\Type(type="string")
     * @Assert\Length(min=1, max=200)
     */
    protected $lastName;

    /**
     * @var string|null
     * @ORM\Column(name="display_name", type="string", length=200, unique=false, nullable=true)
     * @Assert\Type(type="string")
     * @Assert\Length(min=1, max=200)
     */
    protected $displayName;

    /**
     * @var string|null
     * @ORM\Column(name="email", type="string", length=256, unique=true, nullable=true)
     * @Assert\Type(type="string")
     * @Assert\Length(min=3, max=256)
     * @Assert\Email()
     */
    protected $email;

    /**
     * @var \DateTimeImmutable|null
     * @ORM\Column(name="time_stamp_last_seen", type="datetime_immutable", unique=false, nullable=true)
     * @Assert\Type({"null", "\DateTimeImmutable"})
     */
    protected $timeStampLastSeen;



    /*
    ----------------------------------------------------------------------------
        Life cycle functions
    ----------------------------------------------------------------------------
     */

    /**
     * To string.
     * @return string
     */
    public function __toString(): string
    {
        if ($s = $this->getDisplayName()) {
            return $s;
        }

        if ($s = $this->getName()) {
            return $s;
        }

        return $this->getId();
    }



    /*
    ----------------------------------------------------------------------------
        Helper functions
    ----------------------------------------------------------------------------
     */

    /**
     * Get full name.
     * @return string
     */
    public function getName(): string
    {
        return trim(sprintf("%s %s", $this->getFirstName(), $this->getLastName()));
    }

    /**
     * Get mailer address.
     * @return \Symfony\Component\Mime\Address
     */
    public function getMailerAddress(): \Symfony\Component\Mime\Address
    {
        return new \Symfony\Component\Mime\Address($this->getEmail(), strval($this));
    }

    /**
     * Check if this user has a role.
     * @param  string $role Role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        if (!($role = trim($role))) {
            throw new \UnexpectedValueException("Role not specified.");
        }

        return \in_array($role, $this->getRoles());
    }



    /*
    ----------------------------------------------------------------------------
        Serialisable functions
    ----------------------------------------------------------------------------
     */

    /**
     * Serialise.
     * @return string Serialised
     */
    public function serialize(): string
    {
        return serialize([
            $this->id,
            $this->username,
            $this->password,
            $this->email,
        ]);
    }

    /**
     * Unserialise.
     * @param string Serialised
     */
    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->username,
            $this->password,
            $this->email,
        ) = unserialize($serialized);
    }



    /*
    ----------------------------------------------------------------------------
        UserInterface functions
    ----------------------------------------------------------------------------
     */

    /**
     * @var array List of roles granted.
     */
    protected $roles = [];

    /**
     * {@inheritdoc}
     */
    public function getRoles(): array
    {
        if (!\is_array($this->roles)) {
            return [];
        }

        sort($this->roles);
        return $this->roles;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
        $this->password = null;
    }



    /*
    ----------------------------------------------------------------------------
        EquatableInterface functions
    ----------------------------------------------------------------------------
     */

    /**
     * Check if two instances are equal.
     * @param  UserInterface $user Other user object
     * @return bool
     */
    public function isEqualTo(UserInterface $user): bool
    {
        if (!($user instanceof self)) {
            return false;
        }

        foreach (["username", "roles"] as $property) {
            $getProperty = "get" . ucfirst($property);

            if (!method_exists($user, $getProperty) || !method_exists($this, $getProperty)) {
                return false;
            }

            if ($user->$getProperty() !== $this->$getProperty()) {
                return false;
            }
        }

        return true;
    }



    /*
    ----------------------------------------------------------------------------
        Data functions
    ----------------------------------------------------------------------------
     */

     /**
      * Get username
      *
      * @return string
      */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Set username
     *
     * @param string
     *
     * @return self
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get password
     *
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Set password
     *
     * @param string|null
     *
     * @return self
     */
    public function setPassword(?string $password = null): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * Set firstName
     *
     * @param string|null
     *
     * @return self
     */
    public function setFirstName(?string $firstName = null): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * Set lastName
     *
     * @param string|null
     *
     * @return self
     */
    public function setLastName(?string $lastName = null): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get displayName
     *
     * @return string|null
     */
    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    /**
     * Set displayName
     *
     * @param string|null
     *
     * @return self
     */
    public function setDisplayName(?string $displayName = null): self
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * Get email
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Set email
     *
     * @param string|null
     *
     * @return self
     */
    public function setEmail(?string $email = null): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get timeStampLastSeen
     *
     * @return \DateTimeImmutable|null
     */
    public function getTimeStampLastSeen(): ?\DateTimeImmutable
    {
        return $this->timeStampLastSeen;
    }

    /**
     * Set timeStampLastSeen
     *
     * @param \DateTimeImmutable|null $timeStampLastSeen
     *
     * @return self
     */
    public function setTimeStampLastSeen(?\DateTimeImmutable $timeStampLastSeen = null): self
    {
        $this->timeStampLastSeen = $timeStampLastSeen;

        return $this;
    }
}
