<?php

namespace App\Entity;

use App\Repository\UserRepository;
use App\Service\ExchangeCredentialsInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity('username')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, ExchangeCredentialsInterface
{
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLES = [
        self::ROLE_ADMIN,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private ?string $username;

    #[Assert\Choice(choices: User::ROLES, multiple: true)]
    #[ORM\Column(type: 'json', nullable: true)]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    private ?string $password;

    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: "user")]
    private Collection $orders;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $binanceApiKey = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $binanceApiSecret = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'], fetch: 'EAGER')]
    private ?UserSetting $userSetting = null;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
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
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = self::ROLE_USER;

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Order[]|Collection<Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function getBinanceApiKey(): ?string
    {
        return $this->binanceApiKey;
    }

    public function setBinanceApiKey(?string $binanceApiKey): self
    {
        $this->binanceApiKey = $binanceApiKey;

        return $this;
    }

    public function getBinanceApiSecret(): ?string
    {
        return $this->binanceApiSecret;
    }

    public function setBinanceApiSecret(?string $binanceApiSecret): self
    {
        $this->binanceApiSecret = $binanceApiSecret;

        return $this;
    }

    public function getHuobiApiKey(): ?string
    {
        return null;
    }

    public function getHuobiApiSecret(): ?string
    {
        return null;
    }

    public function getUserSetting(): UserSetting
    {
        if ($this->userSetting === null) {
            $this->userSetting = new UserSetting();
            $this->userSetting->setUser($this);
        }

        return $this->userSetting;
    }

    public function setUserSetting(UserSetting $userSetting): self
    {
        // set the owning side of the relation if necessary
        if ($userSetting->getUser() !== $this) {
            $userSetting->setUser($this);
        }

        $this->userSetting = $userSetting;

        return $this;
    }
}
