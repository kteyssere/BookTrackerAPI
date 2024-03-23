<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["getAll"])]

    private ?string $Comment = null;

    #[ORM\ManyToOne]
    #[Groups(["getAll"])]

    private ?Persona $User = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[Groups(["getAllByReview"])]


    private ?Book $Book = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getAll"])]

    private ?string $title = null;

    #[ORM\Column(length: 25)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["getAll"])]

    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column]
    private ?int $likes = 0;

    public function __construct()
    {
        
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComment(): ?string
    {
        return $this->Comment;
    }

    public function setComment(string $Comment): static
    {
        $this->Comment = $Comment;

        return $this;
    }

    public function getUser(): ?Persona
    {
        return $this->User;
    }

    public function setUser(?Persona $User): static
    {
        $this->User = $User;

        return $this;
    }

    public function getBook(): ?Book
    {
        return $this->Book;
    }

    public function setBook(?Book $Book): static
    {
        $this->Book = $Book;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }


    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getLikes(): ?int
    {
        return $this->likes;
    }

    public function setLikes(int $likes): static
    {
        $this->likes = $likes;

        return $this;
    }

    public function addLike(): static
    {
        $this->likes++;

        return $this;
    }

    public function deleteLike(): static
    {
        $this->likes--;

        return $this;
    }


}
