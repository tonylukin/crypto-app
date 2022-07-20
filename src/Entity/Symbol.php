<?php

namespace App\Entity;

use App\Repository\SymbolRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SymbolRepository::class)]
#[ORM\UniqueConstraint(name: "ix_symbol_name", fields: ["name"])]
class Symbol
{
    public const DEFAULT_TOTAL_PRICE_USD = 100;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 32)]
    private ?string $name = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $active = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $riskable = false;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $totalPrice = null;

    #[ORM\OneToMany(targetEntity: "App\Entity\Order", mappedBy: "symbol")]
    private Collection $orders;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Если валюта рисковая, значит ее можно покупать на подъеме, надеясь, что она еще больше вырастет
     */
    public function isRiskable(): ?bool
    {
        return $this->riskable;
    }

    public function setRiskable(bool $riskable): self
    {
        $this->riskable = $riskable;

        return $this;
    }

    public function getTotalPrice(): ?float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(?float $totalPrice): self
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    /**
     * @see SymbolRepository::getActiveList()
     * @return Collection<Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }
}
