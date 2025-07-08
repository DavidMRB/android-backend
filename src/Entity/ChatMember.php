<?php

namespace App\Entity;

use App\Repository\ChatMemberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChatMemberRepository::class)]
class ChatMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'chatMembers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Chat $chat = null;

    #[ORM\ManyToOne(inversedBy: 'chatMembers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user_associated = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChat(): ?Chat
    {
        return $this->chat;
    }

    public function setChat(?Chat $chat): static
    {
        $this->chat = $chat;

        return $this;
    }

    public function getUserAssociated(): ?User
    {
        return $this->user_associated;
    }

    public function setUserAssociated(?User $user_associated): static
    {
        $this->user_associated = $user_associated;

        return $this;
    }
}
