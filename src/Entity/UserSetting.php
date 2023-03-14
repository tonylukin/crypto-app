<?php

namespace App\Entity;

use App\Repository\UserSettingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSettingRepository::class)]
class UserSetting
{
    private const MIN_PRICES_COUNT_MUST_HAVE_BEFORE_ORDER = 48; // 24 hours
    private const MIN_FALLEN_PRICE_PERCENTAGE = 10; // разница между максимальным значением за последнее время и текущим при достижении дна
    private const FALLEN_PRICE_INTERVAL_HOURS = 48; // период для отчета максимального значения цены при падении, "последнее время" для константы выше
    private const MINIMAL_PROFIT_PERCENT = 2;
    private const MAX_DAYS_WAITING_FOR_PROFIT = 40;
    private const MINIMAL_PRICE_DIFF_PERCENT_AFTER_LAST_SELL = 8;
    private const MAX_PERCENT_DIFF_ON_MOVING = 3;
    private const LEGAL_MOVING_STEP_PERCENT = 4; // шаг цены за час, который считаем адекватным. шаг больше - это уже резкое падение или рост
    private const HOURS_EXTREMELY_SHORT_INTERVAL_FOR_PRICES = 4;
    private const DAYS_INTERVAL_MIN_PRICE_ON_DISTANCE = 7; // интервал в днях для проверки падения цены, которая происходит на коротком взлете ("ситуация в TWT")

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'userSetting')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $disableTrading = false;

    #[ORM\Column(nullable: false, options: ['default' => Order::EXCHANGE_BINANCE], type: 'smallint')]
    private int $useExchange = Order::EXCHANGE_BINANCE;

    #[ORM\Column(nullable: true)]
    private ?float $minFallenPricePercent = null;

    #[ORM\Column(nullable: true)]
    private ?float $minProfitPercent = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxDaysWaitingForProfit = null;

    #[ORM\Column(nullable: true)]
    private ?int $minPricesCountMustHaveBeforeOrder = null;

    #[ORM\Column(nullable: true)]
    private ?float $maxPercentDiffOnMoving = null;

    #[ORM\Column(nullable: true)]
    private ?float $legalMovingStepPercent = null;

    #[ORM\Column(nullable: true)]
    private ?int $hoursExtremelyShortIntervalForPrices = null;

    #[ORM\Column(nullable: true)]
    private ?float $minPriceDiffPercentAfterLastSell = null;

    #[ORM\Column(nullable: true)]
    private ?int $fallenPriceIntervalHours = null;

    #[ORM\Column(nullable: true)]
    private ?int $daysIntervalMinPriceOnDistance = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $binanceApiKey = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $binanceApiSecret = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $huobiApiKey = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $huobiApiSecret = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUseExchange(): int
    {
        return $this->useExchange;
    }

    public function setUseExchange(int $useExchange): UserSetting
    {
        $this->useExchange = $useExchange;
        return $this;
    }

    public function isDisableTrading(): bool
    {
        return $this->disableTrading;
    }

    public function setDisableTrading(bool $disableTrading): self
    {
        $this->disableTrading = $disableTrading;

        return $this;
    }

    public function getMinFallenPricePercent(): float
    {
        return $this->minFallenPricePercent ?? self::MIN_FALLEN_PRICE_PERCENTAGE;
    }

    public function setMinFallenPricePercent(?float $minFallenPricePercent): self
    {
        $this->minFallenPricePercent = $minFallenPricePercent;

        return $this;
    }

    public function getMinProfitPercent(): float
    {
        return $this->minProfitPercent ?? self::MINIMAL_PROFIT_PERCENT;
    }

    public function setMinProfitPercent(?float $minProfitPercent): self
    {
        $this->minProfitPercent = $minProfitPercent;

        return $this;
    }

    public function getMaxDaysWaitingForProfit(): int
    {
        return $this->maxDaysWaitingForProfit ?? self::MAX_DAYS_WAITING_FOR_PROFIT;
    }

    public function setMaxDaysWaitingForProfit(?int $maxDaysWaitingForProfit): self
    {
        $this->maxDaysWaitingForProfit = $maxDaysWaitingForProfit;

        return $this;
    }

    public function getMinPricesCountMustHaveBeforeOrder(): int
    {
        return $this->minPricesCountMustHaveBeforeOrder ?? self::MIN_PRICES_COUNT_MUST_HAVE_BEFORE_ORDER;
    }

    public function setMinPricesCountMustHaveBeforeOrder(?int $minPricesCountMustHaveBeforeOrder): self
    {
        $this->minPricesCountMustHaveBeforeOrder = $minPricesCountMustHaveBeforeOrder;

        return $this;
    }

    public function getMaxPercentDiffOnMoving(): float
    {
        return $this->maxPercentDiffOnMoving ?? self::MAX_PERCENT_DIFF_ON_MOVING;
    }

    public function setMaxPercentDiffOnMoving(?float $maxPercentDiffOnMoving): self
    {
        $this->maxPercentDiffOnMoving = $maxPercentDiffOnMoving;

        return $this;
    }

    public function getLegalMovingStepPercent(): float
    {
        return $this->legalMovingStepPercent ?? self::LEGAL_MOVING_STEP_PERCENT;
    }

    public function setLegalMovingStepPercent(?float $legalMovingStepPercent): self
    {
        $this->legalMovingStepPercent = $legalMovingStepPercent;

        return $this;
    }

    public function getHoursExtremelyShortIntervalForPrices(): int
    {
        return $this->hoursExtremelyShortIntervalForPrices ?? self::HOURS_EXTREMELY_SHORT_INTERVAL_FOR_PRICES;
    }

    public function setHoursExtremelyShortIntervalForPrices(?int $hoursExtremelyShortIntervalForPrices): self
    {
        $this->hoursExtremelyShortIntervalForPrices = $hoursExtremelyShortIntervalForPrices;

        return $this;
    }

    public function getMinPriceDiffPercentAfterLastSell(): float
    {
        return $this->minPriceDiffPercentAfterLastSell ?? self::MINIMAL_PRICE_DIFF_PERCENT_AFTER_LAST_SELL;
    }

    public function setMinPriceDiffPercentAfterLastSell(?float $minPriceDiffPercentAfterLastSell): self
    {
        $this->minPriceDiffPercentAfterLastSell = $minPriceDiffPercentAfterLastSell;

        return $this;
    }

    public function getFallenPriceIntervalHours(): int
    {
        return $this->fallenPriceIntervalHours ?? self::FALLEN_PRICE_INTERVAL_HOURS;
    }

    public function setFallenPriceIntervalHours(?int $fallenPriceIntervalHours): self
    {
        $this->fallenPriceIntervalHours = $fallenPriceIntervalHours;

        return $this;
    }

    public function getDaysIntervalMinPriceOnDistance(): int
    {
        return $this->daysIntervalMinPriceOnDistance ?? self::DAYS_INTERVAL_MIN_PRICE_ON_DISTANCE;
    }

    public function setDaysIntervalMinPriceOnDistance(?int $daysIntervalMinPriceOnDistance): UserSetting
    {
        $this->daysIntervalMinPriceOnDistance = $daysIntervalMinPriceOnDistance;

        return $this;
    }

    public function getBinanceApiKey(): ?string
    {
        return $this->binanceApiKey;
    }

    public function setBinanceApiKey(?string $binanceApiKey): UserSetting
    {
        $this->binanceApiKey = $binanceApiKey;

        return $this;
    }

    public function getBinanceApiSecret(): ?string
    {
        return $this->binanceApiSecret;
    }

    public function setBinanceApiSecret(?string $binanceApiSecret): UserSetting
    {
        $this->binanceApiSecret = $binanceApiSecret;

        return $this;
    }

    public function getHuobiApiKey(): ?string
    {
        return $this->huobiApiKey;
    }

    public function setHuobiApiKey(?string $huobiApiKey): UserSetting
    {
        $this->huobiApiKey = $huobiApiKey;

        return $this;
    }

    public function getHuobiApiSecret(): ?string
    {
        return $this->huobiApiSecret;
    }

    public function setHuobiApiSecret(?string $huobiApiSecret): UserSetting
    {
        $this->huobiApiSecret = $huobiApiSecret;

        return $this;
    }
}
