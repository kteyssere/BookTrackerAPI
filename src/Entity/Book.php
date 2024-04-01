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
    #[Groups(["getAllListBooks", "getAllFiltered"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getAll", "getAllListBooks", "getAllFiltered"])]
    #[Assert\NotBlank(message:"A Book must have a title")]
    #[Assert\NotNull(message:"A Book must have a title")]
    #[Assert\Length(min:5, minMessage: "The title of a Book must necessarily be more than {{limit}} characters")]
    private ?string $title = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(["getAll"])]
    #[Assert\NotBlank(message:"A Book must have a publication date")]
    #[Assert\NotNull(message:"A Book must have a publication date")]
    private ?\DateTimeInterface $publishingDate = null;

    #[ORM\Column(length: 25)]
    #[Groups(["getAll"])]
    private ?string $status = null;

    #[ORM\Column]
    #[Groups(["getAll", "getAllFiltered"])]
    private ?int $totalPages = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getAll"])]
    private ?string $publisher = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["getAll", "getAllFiltered"])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToMany(targetEntity: Author::class, mappedBy: 'books')]
    #[Groups(["getAll"])]
    private Collection $authors;

    #[ORM\Column(length: 255)]
    #[Groups(["getAll"])]

    private ?string $isbn13 = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getAll"])]
    private ?string $isbn10 = null;

    #[ORM\Column(type: Types::ARRAY)]
    #[Groups(["getAll", "getAllListBooks", "getAllFiltered"])]
    private array $imageLinks = [];

    #[ORM\ManyToMany(targetEntity: Category::class, mappedBy: 'books')]
    #[Groups(["getAll"])]
    private Collection $categories;

    #[ORM\OneToMany(mappedBy: 'book', targetEntity: Progression::class)]
    #[Groups(["getAll"])]
    private Collection $progressions;

    #[ORM\OneToMany(mappedBy: 'book', targetEntity: Review::class)]
    #[Groups(["getAll"])]
    private Collection $reviews;

    public function __construct()
    {
        $this->authors = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->progressions = new ArrayCollection();
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

    public function addAuthors(array $authors): static
    {
        foreach ($authors as $author) {
            if($author instanceof Author){
                if (!$this->authors->contains($author)) {
                    $this->authors->add($author);
                    $author->addBook($this);
                }
            }
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

    public function getIsbn13(): ?string
    {
        return $this->isbn13;
    }

    public function setIsbn13(string $isbn13): static
    {
        $this->isbn13 = $isbn13;

        return $this;
    }

    public function getIsbn10(): ?string
    {
        return $this->isbn10;
    }

    public function setIsbn10(string $isbn10): static
    {
        $this->isbn10 = $isbn10;

        return $this;
    }

    public function getImageLinks(): array
    {
        return $this->imageLinks;
    }

    public function setImageLinks(array $imageLinks): static
    {
        $this->imageLinks = $imageLinks;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->addBook($this);
        }

        return $this;
    }

    public function addCategories(array $categories): static
    {
        foreach ($categories as $category) {
            if($category instanceof Category){
                if (!$this->categories->contains($category)) {
                    $this->categories->add($category);
                    $category->addBook($this);
                }
            }
        }
        return $this;
    }

    public function removeCategory(Category $category): static
    {
        if ($this->categories->removeElement($category)) {
            $category->removeBook($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Progression>
     */
    public function getProgressions(): Collection
    {
        return $this->progressions;
    }

    public function addProgression(Progression $progression): static
    {
        if (!$this->progressions->contains($progression)) {
            $this->progressions->add($progression);
            $progression->setBook($this);
        }

        return $this;
    }

    public function removeProgression(Progression $progression): static
    {
        if ($this->progressions->removeElement($progression)) {
            // set the owning side to null (unless already changed)
            if ($progression->getBook() === $this) {
                $progression->setBook(null);
            }
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
}
