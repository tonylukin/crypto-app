<?php

namespace App\Entity;

use App\Repository\LastPriceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LastPriceRepository::class)]
#[ORM\Index(name: "ix_last_price_user_symbol", fields: ["user", "symbol"])]
class LastPrice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Symbol $symbol = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column(nullable: true)]
    private ?float $maxPrice = null;

    #[ORM\Column(nullable: true)]
    private ?float $minPrice = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $lowest = true;

    #[ORM\Column]
    private \DateTimeImmutable $created_at;

    #[ORM\Column]
    private \DateTimeImmutable $updated_at;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $attempt = 0;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeImmutable $periodDate = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $periodMinutes = null;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getSymbol(): ?Symbol
    {
        return $this->symbol;
    }

    public function setSymbol(?Symbol $symbol): self
    {
        $this->symbol = $symbol;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getMaxPrice(): ?float
    {
        return $this->maxPrice;
    }

    public function setMaxPrice(?float $maxPrice): LastPrice
    {
        $this->maxPrice = $maxPrice;
        return $this;
    }

    public function getMinPrice(): ?float
    {
        return $this->minPrice;
    }

    public function setMinPrice(?float $minPrice): LastPrice
    {
        $this->minPrice = $minPrice;
        return $this;
    }

    public function isLowest(): bool
    {
        return $this->lowest;
    }

    public function setLowest(bool $lowest): self
    {
        $this->lowest = $lowest;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getAttempt(): int
    {
        return $this->attempt;
    }

    public function increaseAttempt(): self
    {
        $this->attempt++;

        return $this;
    }

    public function getPeriodMinutes(): ?int
    {
        return $this->periodMinutes;
    }

    public function setPeriodMinutes(?int $periodMinutes): LastPrice
    {
        $this->periodMinutes = $periodMinutes;
        return $this;
    }

    public function setPeriodDate(?\DateTimeImmutable $periodDate): self
    {
        $this->periodDate = $periodDate;

        return $this;
    }

    public function getPeriodDate(): ?\DateTimeImmutable
    {
        return $this->periodDate;
    }
}
