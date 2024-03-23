<?php

namespace App\Entity;

use App\Repository\PersonaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PersonaRepository::class)]
class Persona
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull(message:"Vous devez saisir un nom")]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable:true)]
    #[Groups(["getAll"])]


    private ?string $surname = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull(message:"Vous devez saisir un email")]
    #[Assert\Email(message:"L'email {{ value }} n'est pas valide")]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable:true)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(length: 24)]
    private ?string $status = null;

    #[ORM\Column]
    private ?bool $anonymous = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable:true)]
    private ?\DateTimeInterface $birthdate = null;

    #[ORM\Column(nullable: true)]
    private ?int $gender = null;

    #[ORM\OneToMany(mappedBy: 'persona', targetEntity: User::class)]
    private Collection $user;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Picture $profilePicture = null;

    #[ORM\ManyToMany(targetEntity: Conversation::class, mappedBy: 'participants')]
    private Collection $conversations;

    #[ORM\OneToMany(mappedBy: 'persona', targetEntity: ListBook::class)]
    private Collection $listBook;


    public function __construct()
    {
        $this->user = new ArrayCollection();
        $this->conversations = new ArrayCollection();
        $this->listBook = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): static
    {
        $this->surname = $surname;

        return $this;
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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isAnonymous(): ?bool
    {
        return $this->anonymous;
    }

    public function setAnonymous(bool $anonymous): static
    {
        $this->anonymous = $anonymous;

        return $this;
    }

    public function getBirthdate(): ?\DateTimeInterface
    {
        return $this->birthdate;
    }

    public function setBirthdate(\DateTimeInterface $birthdate): static
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    public function getGender(): ?int
    {
        return $this->gender;
    }

    public function setGender(int $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUser(): Collection
    {
        return $this->user;
    }

    public function addUser(User $user): static
    {
        if (!$this->user->contains($user)) {
            $this->user->add($user);
            $user->setPersona($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->user->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getPersona() === $this) {
                $user->setPersona(null);
            }
        }

        return $this;
    }

    public function getProfilePicture(): ?Picture
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?Picture $profilePicture): static
    {
        $this->profilePicture = $profilePicture;

        return $this;
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function getConversations(): Collection
    {
        return $this->conversations;
    }

    public function addConversation(Conversation $conversation): static
    {
        if (!$this->conversations->contains($conversation)) {
            $this->conversations->add($conversation);
            $conversation->addParticipant($this);
        }

        return $this;
    }

    public function removeConversation(Conversation $conversation): static
    {
        if ($this->conversations->removeElement($conversation)) {
            $conversation->removeParticipant($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, ListBook>
     */
    public function getListBook(): Collection
    {
        return $this->listBook;
    }

    public function addListBook(ListBook $listBook): static
    {
        if (!$this->listBook->contains($listBook)) {
            $this->listBook->add($listBook);
            $listBook->setPersona($this);
        }

        return $this;
    }

    public function removeListBook(ListBook $listBook): static
    {
        if ($this->listBook->removeElement($listBook)) {
            // set the owning side to null (unless already changed)
            if ($listBook->getPersona() === $this) {
                $listBook->setPersona(null);
            }
        }

        return $this;
    }

}
