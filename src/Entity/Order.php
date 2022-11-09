<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[ORM\Index(name: "ix_order_symbol_status_exchange", fields: ["symbol", "status", "exchange"])]
class Order
{
    public const EXCHANGE_BINANCE = 'binance';

    public const STATUS_BUY = 'buy';
    public const STATUS_SELL = 'sale';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['order_price_details'])]
    private ?int $id;

    #[ORM\Column(type: 'string', length: 16)]
    private string $exchange = self::EXCHANGE_BINANCE;

    #[ORM\Column(type: 'float')]
    private ?float $quantity;

    #[ORM\Column(type: 'float')]
    #[Groups(['order_price_details'])]
    private ?float $price;

    #[ORM\Column(type: 'string', length: 16)]
    private string $status = self::STATUS_BUY;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $profit;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['order_price_details'])]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Groups(['order_price_details'])]
    private ?float $sellPrice = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true, options: ['default' => null])]
    #[Groups(['order_price_details'])]
    private ?\DateTimeImmutable $sellDate = null;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Symbol", inversedBy: "orders")]
    #[Groups(['order_price_details'])]
    private Symbol $symbol;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $buyReason = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $sellReason = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getExchange(): ?string
    {
        return $this->exchange;
    }

    public function setExchange(string $exchange): self
    {
        $this->exchange = $exchange;

        return $this;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(float $quantity): self
    {
        $this->quantity = $quantity;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getProfit(): ?float
    {
        return $this->profit;
    }

    public function setProfit(?float $profit): self
    {
        $this->profit = $profit;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getSellPrice(): ?float
    {
        return $this->sellPrice;
    }

    public function setSellPrice(?float $sellPrice): self
    {
        $this->sellPrice = $sellPrice;

        return $this;
    }

    public function getSellDate(): ?\DateTimeInterface
    {
        return $this->sellDate;
    }

    public function setSellDate(?\DateTimeInterface $sellDate): self
    {
        $this->sellDate = $sellDate;

        return $this;
    }

    public function getBuyReason(): ?string
    {
        return $this->buyReason;
    }

    public function setBuyReason(?string $buyReason): self
    {
        $this->buyReason = $buyReason;

        return $this;
    }

    public function getSellReason(): ?string
    {
        return $this->sellReason;
    }

    public function setSellReason(?string $sellReason): self
    {
        $this->sellReason = $sellReason;

        return $this;
    }
}
