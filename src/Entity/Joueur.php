<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\JoueurRepository")
 * @UniqueEntity(fields={"username"}, message="There is already an account with this username")
 */
class Joueur implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="text")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Partie", mappedBy="joueur1")
     */
    private $partiesJ1;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Partie", mappedBy="joueur2")
     */
    private $partiesJ2;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Partie", mappedBy="gagnant")
     */
    private $gagnants;

    /**
     * @ORM\Column(type="integer")
     */
    private $gagne;

    /**
     * @ORM\Column(type="integer")
     */
    private $perdu;

    public function __construct()
    {
        $this->partiesJ1 = new ArrayCollection();
        $this->partiesJ2 = new ArrayCollection();
        $this->gagnants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles = 'ROLE_USER';

        return json_decode($this->roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = json_encode($roles);

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return Collection|Partie[]
     */
    public function getPartiesJ1(): Collection
    {
        return $this->partiesJ1;
    }

    public function addPartiesJ1(Partie $partiesJ1): self
    {
        if (!$this->partiesJ1->contains($partiesJ1)) {
            $this->partiesJ1[] = $partiesJ1;
            $partiesJ1->setJoueur1($this);
        }

        return $this;
    }

    public function removePartiesJ1(Partie $partiesJ1): self
    {
        if ($this->partiesJ1->contains($partiesJ1)) {
            $this->partiesJ1->removeElement($partiesJ1);
            // set the owning side to null (unless already changed)
            if ($partiesJ1->getJoueur1() === $this) {
                $partiesJ1->setJoueur1(null);
            }
        }

        return $this;
    }




    public function getPartiesJ2(): Collection
    {
        return $this->partiesJ2;
    }

    public function addPartiesJ2(Partie $partiesJ2): self
    {
        if (!$this->partiesJ2->contains($partiesJ2)) {
            $this->partiesJ2[] = $partiesJ2;
            $partiesJ2->setJoueur2($this);
        }

        return $this;
    }

    public function removePartiesJ2(Partie $partiesJ2): self
    {
        if ($this->partiesJ2->contains($partiesJ2)) {
            $this->partiesJ2->removeElement($partiesJ2);
            // set the owning side to null (unless already changed)
            if ($partiesJ2->getJoueur2() === $this) {
                $partiesJ2->setJoueur2(null);
            }
        }

        return $this;
    }







    public function getToutesMesParties()
    {
        $parties = [];
        $parties[] = $this->getPartiesJ1();
        $parties[] = $this->getPartiesJ2();
        return $parties;
    }

    public function getGagne(): ?int
    {
        return $this->gagne;
    }

    public function setGagne(int $gagne): self
    {
        $this->gagne = $gagne;

        return $this;
    }

    public function getPerdu(): ?int
    {
        return $this->perdu;
    }

    public function setPerdu(int $perdu): self
    {
        $this->perdu = $perdu;

        return $this;
    }

}
