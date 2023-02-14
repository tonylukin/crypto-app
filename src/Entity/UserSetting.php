<?php

namespace App\Entity;

use App\Repository\UserSettingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSettingRepository::class)]
class UserSetting
{
    private const MIN_PRICES_COUNT_MUST_HAVE_BEFORE_ORDER = 48; // 24 hours
    private const MIN_FALLEN_PRICE_PERCENTAGE = 7; // разница между максимальным значением за последнее время и текущим при достижении дна
    private const MINIMAL_PROFIT_PERCENT = 2;
    private const MAX_DAYS_WAITING_FOR_PROFIT = 40;
    private const MINIMAL_PRICE_DIFF_PERCENT_AFTER_LAST_SELL = 8;
    private const MAX_PERCENT_DIFF_ON_MOVING = 3;
    private const LEGAL_MOVING_STEP_PERCENT = 5; // шаг цены за час, который считаем адекватным. шаг больше - это уже резкое падение или рост
    private const HOURS_EXTREMELY_SHORT_INTERVAL_FOR_PRICES = 4;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'userSetting')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $disableTrading = false;

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
}
