<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

//Serializer group
use Symfony\Component\Serializer\Annotation\Groups;

use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;
/**
 * @Hateoas\Relation(
 *      "self",
 *      href = @Hateoas\Route(
 *          "book.get",
 *          parameters = { "idBook" = "expr(object.getId())" }
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getAllBook"),
 * )
 * @Hateoas\Relation(
 *     "up",
 *      href = @Hateoas\Route(
 *          "book.getAll"
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getAllBook")
 * )
 * @Hateoas\Relation(
 *     "update",
 *      href = @Hateoas\Route(
 *          "book.update",
 *          parameters = { "idBook" = "expr(object.getId())" }
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getAllBook")
 * )
 */

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getAll"])]
    #[Assert\NotBlank(message:"Un Livre doit avoir un titre")]
    #[Assert\NotNull(message:"Un Livre doit avoir un titre")]
    #[Assert\Length(min:5, minMessage: "Le titre d'un Livre doit forcement faire plus de {{limit}} characteres")]
    private ?string $title = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(["getAll"])]
    private ?\DateTimeInterface $publishingDate = null;

    #[ORM\Column(length: 25)]
    #[Groups(["getAll"])]
    private ?string $status = null;

    #[ORM\Column]
    #[Groups(["getAll"])]
    private ?int $totalPages = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getAll"])]
    private ?string $publisher = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["getAll"])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column]
    #[Groups(["getAll"])]
    private ?int $volume = null;

    #[ORM\ManyToOne(inversedBy: 'books')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["getAll"])]
    private ?Genre $genre = null;

    #[ORM\ManyToMany(targetEntity: Author::class, mappedBy: 'books')]
    #[Groups(["getAll"])]
    private Collection $authors;

    #[ORM\OneToMany(mappedBy: 'Book', targetEntity: Review::class)]
    #[Groups(["getAll"])]
    private Collection $reviews;

    #[ORM\Column(length: 255)]
    #[Groups(["getAll"])]
    #[Assert\NotBlank(message:"Un Livre doit avoir un ISBN")]
    #[Assert\NotNull(message:"Un Livre doit avoir un ISBN")]
    #[Assert\Length(min:10, minMessage: "L'ISBN d'un Livre doit forcement faire plus de {{limit}} characteres")]
    private ?string $ISBN = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[Groups(["getAll"])]
    private ?Picture $coverImage = null;

    public function __construct()
    {
        $this->authors = new ArrayCollection();
        $this->reviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPublishingDate(): ?\DateTimeInterface
    {
        return $this->publishingDate;
    }

    public function setPublishingDate(\DateTimeInterface $publishingDate): static
    {
        $this->publishingDate = $publishingDate;

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

    public function getTotalPages(): ?int
    {
        return $this->totalPages;
    }

    public function setTotalPages(int $totalPages): static
    {
        $this->totalPages = $totalPages;

        return $this;
    }

    public function getPublisher(): ?string
    {
        return $this->publisher;
    }

    public function setPublisher(string $publisher): static
    {
        $this->publisher = $publisher;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

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

    public function getVolume(): ?int
    {
        return $this->volume;
    }

    public function setVolume(int $volume): static
    {
        $this->volume = $volume;

        return $this;
    }

    public function getGenre(): ?Genre
    {
        return $this->genre;
    }

    public function setGenre(?Genre $genre): static
    {
        $this->genre = $genre;

        return $this;
    }

    /**
     * @return Collection<int, Author>
     */
    public function getAuthors(): Collection
    {
        return $this->authors;
    }

    public function addAuthor(Author $author): static
    {
        if (!$this->authors->contains($author)) {
            $this->authors->add($author);
            $author->addBook($this);
        }

        return $this;
    }

    public function removeAuthor(Author $author): static
    {
        if ($this->authors->removeElement($author)) {
            $author->removeBook($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setBook($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getBook() === $this) {
                $review->setBook(null);
            }
        }

        return $this;
    }

    public function getISBN(): ?string
    {
        return $this->ISBN;
    }

    public function setISBN(string $ISBN): static
    {
        $this->ISBN = $ISBN;

        return $this;
    }

    public function getCoverImage(): ?Picture
    {
        return $this->coverImage;
    }

    public function setCoverImage(?Picture $coverImage): static
    {
        $this->coverImage = $coverImage;

        return $this;
    }
}
