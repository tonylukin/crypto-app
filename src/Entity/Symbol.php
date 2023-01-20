<?php

namespace App\Entity;

use App\Repository\SymbolRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

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
    #[Groups(['order_price_details'])]
    private ?string $name = null;

    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'symbol')]
    private Collection $orders;

    #[ORM\OneToMany(targetEntity: UserSymbol::class, mappedBy: 'symbol', orphanRemoval: true)]
    private Collection $userSymbols;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
        $this->userSymbols = new ArrayCollection();
    }

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

    /**
     * @see SymbolRepository::getActiveList()
     * @return Collection<Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    /**
     * @return Collection<UserSymbol>
     */
    public function getUserSymbols(): Collection
    {
        return $this->userSymbols;
    }

    public function addUserSymbol(Symbol $symbol): void
    {
        if (!$this->userSymbols->contains($symbol)) {
            $this->userSymbols->add($symbol);
        }
    }
}
