<?php

namespace App\Entity;

use App\Repository\PriceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PriceRepository::class)]
#[ORM\Index(name: "ix_price_datetime_symbol", fields: ["datetime", "symbol"])]
class Price
{
    public const EXCHANGE_BINANCE = Order::EXCHANGE_BINANCE;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $datetime;

    #[ORM\Column(type: 'float')]
    private ?float $price;

    #[ORM\Column(type: 'string', length: 16)]
    private ?string $symbol;

    #[ORM\Column(type: 'string', length: 16)]
    private ?string $exchange = self::EXCHANGE_BINANCE;

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

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;

        return $this;
    }

    public function getExchange(): ?string
    {
        return $this->exchange;
    }

    public function setExchange(string $exchange): self
    {
        $this->exchange = $exchange;

        return $this;
    }
}
