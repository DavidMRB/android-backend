<?php

namespace App\Entity;

use App\Repository\AccessTokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccessTokenRepository::class)]
class AccessToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $token = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expires_at = null;

    #[ORM\ManyToOne(inversedBy: 'accessTokens')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user_token = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expires_at;
    }

    public function setExpiresAt(?\DateTimeImmutable $expires_at): static
    {
        $this->expires_at = $expires_at;

        return $this;
    }

    public function getUserToken(): ?User
    {
        return $this->user_token;
    }

    public function setUserToken(?User $user_token): static
    {
        $this->user_token = $user_token;

        return $this;
    }
}
