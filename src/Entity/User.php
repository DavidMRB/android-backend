<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user_app`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var ?string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    private ?string $last_name = null;

    #[ORM\Column]
    private ?int $age = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private ?bool $is_active = false;

    #[ORM\Column(type: 'string', length: 64, unique: true, nullable: true)]
    private ?string $activationToken = null;

    /**
     * @var Collection<int, AccessToken>
     */
    #[ORM\OneToMany(targetEntity: AccessToken::class, mappedBy: 'user_token')]
    private Collection $accessTokens;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profileImageUrl = null;

    public function __construct()
    {
        $this->accessTokens = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): static
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(int $age): static
    {
        $this->age = $age;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): static
    {
        $this->is_active = $is_active;

        return $this;
    }

    public function getActivationToken(): ?string
    {
        return $this->activationToken;
    }

    public function setActivationToken(?string $activationToken): static
    {
        $this->activationToken = $activationToken;
        return $this;
    }

    /**
     * @return Collection<int, AccessToken>
     */
    public function getAccessTokens(): Collection
    {
        return $this->accessTokens;
    }

    public function addAccessToken(AccessToken $accessToken): static
    {
        if (!$this->accessTokens->contains($accessToken)) {
            $this->accessTokens->add($accessToken);
            $accessToken->setUserToken($this);
        }

        return $this;
    }

    public function removeAccessToken(AccessToken $accessToken): static
    {
        if ($this->accessTokens->removeElement($accessToken)) {
            // set the owning side to null (unless already changed)
            if ($accessToken->getUserToken() === $this) {
                $accessToken->setUserToken(null);
            }
        }

        return $this;
    }

    public function getProfileImageUrl(): ?string
    {
        return $this->profileImageUrl;
    }

    public function setProfileImageUrl(?string $profileImageUrl): self
    {
        $this->profileImageUrl = $profileImageUrl;
        return $this;
    }
}
