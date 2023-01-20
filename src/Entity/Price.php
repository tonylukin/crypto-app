<?php

namespace App\Entity;

use App\Repository\PriceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PriceRepository::class)]
#[ORM\UniqueConstraint(name: "ix_price_datetime_symbol", fields: ["datetime", "symbol"])]
class Price
{
    public const EXCHANGE_BINANCE = Order::EXCHANGE_BINANCE;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['price_details'])]
    private ?\DateTimeInterface $datetime;

    #[ORM\Column(type: 'float')]
    #[Groups(['price_details'])]
    private ?float $price;

    #[ORM\Column(type: 'smallint')]
    private int $exchange = self::EXCHANGE_BINANCE;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Symbol")]
    private Symbol $symbol;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDatetime(): ?\DateTimeInterface
    {
        return $this->datetime;
    }

    public function setDatetime(\DateTimeInterface $datetime): self
    {
        $this->datetime = $datetime;

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

    public function getSymbol(): Symbol
    {
        return $this->symbol;
    }

    public function setSymbol(Symbol $symbol): self
    {
        $this->symbol = $symbol;

        return $this;
    }

    public function getExchange(): int
    {
        return $this->exchange;
    }

    public function setExchange(int $exchange): self
    {
        $this->exchange = $exchange;

        return $this;
    }
}
