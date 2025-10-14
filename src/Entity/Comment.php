<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $text = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatesAt = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    private ?user $user = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    private ?Post $posts = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatesAt(): ?\DateTimeImmutable
    {
        return $this->updatesAt;
    }

    public function setUpdatesAt(?\DateTimeImmutable $updatesAt): static
    {
        $this->updatesAt = $updatesAt;

        return $this;
    }

    public function getUser(): ?user
    {
        return $this->user;
    }

    public function setUser(?user $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getPosts(): ?Post
    {
        return $this->posts;
    }

    public function setPosts(?Post $posts): static
    {
        $this->posts = $posts;

        return $this;
    }
}
