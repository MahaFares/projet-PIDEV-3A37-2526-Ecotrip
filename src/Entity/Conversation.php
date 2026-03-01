<?php

namespace App\Entity;

use App\Repository\ConversationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
class Conversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $sessionId = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $messageUser = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $reponseBot = null;

    #[ORM\Column]
    private ?\DateTime $dateMessage = null;

    #[ORM\ManyToOne]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): static
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    public function getMessageUser(): ?string
    {
        return $this->messageUser;
    }

    public function setMessageUser(string $messageUser): static
    {
        $this->messageUser = $messageUser;

        return $this;
    }

    public function getReponseBot(): ?string
    {
        return $this->reponseBot;
    }

    public function setReponseBot(string $reponseBot): static
    {
        $this->reponseBot = $reponseBot;

        return $this;
    }

    public function getDateMessage(): ?\DateTime
    {
        return $this->dateMessage;
    }

    public function setDateMessage(\DateTime $dateMessage): static
    {
        $this->dateMessage = $dateMessage;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
