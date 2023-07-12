<?php

namespace App\Entity;

use App\Repository\UserSymbolRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserSymbolRepository::class)]
class UserSymbol
{
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['order_price_details'])]
    private bool $active = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $riskable = false;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $totalPrice;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Symbol::class, inversedBy: 'userSymbols', cascade: ['PERSIST'])]
    private ?Symbol $symbol = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?float $upperThreshold = null;

    #[ORM\Column(nullable: true)]
    private ?float $lowerThreshold = null;

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
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

    public function getSymbol(): ?Symbol
    {
        return $this->symbol;
    }

    public function setSymbol(?Symbol $symbol): self
    {
        $this->symbol = $symbol;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getUpperThreshold(): ?float
    {
        return $this->upperThreshold;
    }

    public function setUpperThreshold(?float $upperThreshold): self
    {
        $this->upperThreshold = $upperThreshold;

        return $this;
    }

    public function getLowerThreshold(): ?float
    {
        return $this->lowerThreshold;
    }

    public function setLowerThreshold(?float $lowerThreshold): self
    {
        $this->lowerThreshold = $lowerThreshold;

        return $this;
    }
}
