<?php

namespace App\DataFixtures;

use App\Entity\Price;
use App\Entity\Symbol;
use App\Entity\User;
use App\Entity\UserSymbol;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PriceFixture extends Fixture
{
    public const PRICE_TO_TOP_SYMBOL = 'SOLBUSD';
    public const PRICE_TO_BOTTOM_SYMBOL = 'SOL2BUSD';
    public const PRICE_TOP_BOTTOM_TOP_SYMBOL = 'ETHBUSD';
    public const NOT_RECENTLY_CHANGED_PRICE_SYMBOL = 'ETH2BUSD';
    public const RECENTLY_CHANGED_PRICE_SYMBOL = 'BTCBUSD';
    public const RECENTLY_CHANGED_PRICE_WITH_PLATO_SYMBOL = 'ADABUSD';
    public const TWT_RISING_ON_INTERVAL_AND_THEN_FALLING_ON_DISTANCE_SYMBOL = 'TWTUSDT';
    private const SYMBOLS = [
        self::PRICE_TO_TOP_SYMBOL,
        self::PRICE_TO_BOTTOM_SYMBOL,
        self::PRICE_TOP_BOTTOM_TOP_SYMBOL,
        self::NOT_RECENTLY_CHANGED_PRICE_SYMBOL,
        self::RECENTLY_CHANGED_PRICE_SYMBOL,
        self::RECENTLY_CHANGED_PRICE_WITH_PLATO_SYMBOL,
        self::TWT_RISING_ON_INTERVAL_AND_THEN_FALLING_ON_DISTANCE_SYMBOL,
    ];

    private const CHANGING_PRICE_15_PERCENT_UP = [[28.23,'SOLBUSD'], [29.05,'SOLBUSD'], [29.41,'SOLBUSD'], [29.38,'SOLBUSD'], [29.36,'SOLBUSD'], [29.74,'SOLBUSD'], [30.98,'SOLBUSD'], [29.9,'SOLBUSD'], [29.92,'SOLBUSD'], [29.14,'SOLBUSD'], [29.07,'SOLBUSD'], [29.3,'SOLBUSD'], [29.51,'SOLBUSD'], [30.23,'SOLBUSD'], [30.99,'SOLBUSD'], [30.63,'SOLBUSD'], [30.28,'SOLBUSD'], [28.32,'SOLBUSD'], [28.87,'SOLBUSD'], [26.95,'SOLBUSD'], [26.19,'SOLBUSD'], [27.47,'SOLBUSD'], [28.55,'SOLBUSD'], [27.13,'SOLBUSD'], [28.11,'SOLBUSD'], [28.59,'SOLBUSD'], [28.3,'SOLBUSD'], [28.17,'SOLBUSD'], [28.06,'SOLBUSD'], [28.21,'SOLBUSD'], [27.89,'SOLBUSD'], [27,'SOLBUSD'], [27.03,'SOLBUSD'], [26.45,'SOLBUSD'], [26.82,'SOLBUSD'], [27.37,'SOLBUSD'], [27.61,'SOLBUSD'], [26.2,'SOLBUSD'], [28,'SOLBUSD'], [27.81,'SOLBUSD'], [28.42,'SOLBUSD'], [28.78,'SOLBUSD'], [28.41,'SOLBUSD'], [29.31,'SOLBUSD'], [29.53,'SOLBUSD'], [30.57,'SOLBUSD'], [31.26,'SOLBUSD'], [31.93,'SOLBUSD'], [31.94,'SOLBUSD'], [32.53,'SOLBUSD'], [32.88,'SOLBUSD'], [33.34,'SOLBUSD'], [33.55,'SOLBUSD'], [33.96,'SOLBUSD'], [33.47,'SOLBUSD'], [31.87,'SOLBUSD'], [32.5,'SOLBUSD'], [32.72,'SOLBUSD'], [32.59,'SOLBUSD'], [32.56,'SOLBUSD'], [32.99,'SOLBUSD'], [33.12,'SOLBUSD'], [32.15,'SOLBUSD'], [31.71,'SOLBUSD'], [31.68,'SOLBUSD'], [32.27,'SOLBUSD'], [31.66,'SOLBUSD'], [32.96,'SOLBUSD'], [33.98,'SOLBUSD'], [33.91,'SOLBUSD'], [34.58,'SOLBUSD'], [34.35,'SOLBUSD'], [33.77,'SOLBUSD'], [34.09,'SOLBUSD'], [34.44,'SOLBUSD'], [33.87,'SOLBUSD'], [33.95,'SOLBUSD'], [33.74,'SOLBUSD'], [33.91,'SOLBUSD'], [35.14,'SOLBUSD'], [34.8,'SOLBUSD'], [35.44,'SOLBUSD'], [36.39,'SOLBUSD'], [36.79,'SOLBUSD'], [36.49,'SOLBUSD'], [37.67,'SOLBUSD'],];

    private const CHANGING_PRICE_TOP_BOTTOM_TOP = [1085.49, 1087.03, 1074.85, 1081.08, 1077.76, 1078.62, 1076.88, 1018.5, 1002.43, 989.04, 993.62, 1004.87, 1003.99, 999.14, 998.52, 991.59, 988.12, 981.44, 960.95, 937.23, 913.73, 902.89, 941.74, 990.6, 993.29, 989.88, 960.07, 949.67, 951.65, 965.57, 944.42, 954.56, 954.26, 966.15, 1062.52, 1054.16, 1032.01, 1019.41, 1033.41, 1043.41, 1053.41, 1063.41, 1073.41, 1083.41,];

    private const NOT_RECENTLY_CHANGED_PRICE = [1590.19, 1569.75, 1561.56, 1563.94, 1576.37, 1589.29, 1575.2, 1576.33, 1589.56, 1585.29, 1578.03, 1543.95, 1536.93,];
    // diff between NOT_RECENTLY_CHANGED_PRICE in the last price 1590.19 which means that it started rising up
    private const RECENTLY_CHANGED_PRICE = [1569.75, 1561.56, 1563.94, 1576.37, 1589.29, 1575.2, 1576.33, 1589.56, 1585.29, 1578.03, 1543.95, 1536.93,];

    private const RECENTLY_CHANGED_PRICE_WITH_PLATO = [0.3487, 0.3548, 0.351, 0.3452, 0.3429, 0.3504, 0.3722, 0.3722, 0.3709,];

    private const TWT_RISING_ON_INTERVAL_AND_THEN_FALLING_ON_DISTANCE = [1.4811, 1.49, 1.4823, 1.4797, 1.4778, 1.4768, 1.4844, 1.4847, 1.4876, 1.4922, 1.4932, 1.4945, 1.4823, 1.488, 1.4925, 1.4899, 1.489, 1.4864, 1.4847, 1.4933, 1.4893, 1.4889, 1.4906, 1.4871, 1.48, 1.4912, 1.4864, 1.4808, 1.4803, 1.4778, 1.487, 1.4883, 1.4937, 1.4842, 1.485, 1.493, 1.4897, 1.5196, 1.517, 1.5167, 1.5427, 1.5405, 1.5453, 1.5336, 1.5378, 1.5457, 1.5445, 1.542, 1.5387, 1.5452, 1.5517, 1.5532, 1.5622, 1.5776, 1.5836, 1.5708, 1.5732, 1.584, 1.5904, 1.5829, 1.5851, 1.587, 1.61, 1.667, 1.67, 1.6748, 1.6796, 1.5742, 1.5785, 1.5238, 1.5549, 1.5759, 1.5878, 1.5838, 1.5945, 1.575, 1.5847, 1.5844, 1.5755, 1.5797, 1.5936, 1.5841, 1.576, 1.5772, 1.5702, 1.5445, 1.5531, 1.5642, 1.5559, 1.5593, 1.5601, 1.5579, 1.5617, 1.5706, 1.565, 1.5798, 1.5787, 1.5694, 1.5707, 1.5522, 1.5552, 1.553, 1.5437, 1.5463, 1.5556, 1.5565, 1.5531, 1.5556, 1.5585, 1.5532, 1.554, 1.537, 1.5451, 1.5358, 1.5279, 1.5201, 1.5301, 1.5249, 1.5252, 1.5417, 1.5326, 1.5255, 1.5189, 1.5241, 1.5209, 1.5271, 1.5426, 1.5451, 1.5477, 1.5562, 1.5522, 1.579, 1.5664, 1.5689, 1.5704, 1.5557, 1.5712, 1.5683, 1.5706, 1.5694, 1.5637, 1.5604, 1.5705, 1.5532, 1.5554, 1.5468, 1.5551, 1.5674, 1.5705, 1.5954, 1.5887, 1.5753, 1.5771, 1.577, 1.5843, 1.588, 1.5937, 1.5919, 1.6056, 1.593, 1.5885, 1.5573, 1.5432, 1.5574, 1.5537, 1.556, 1.5559, 1.5603, 1.5588, 1.5578, 1.5607, 1.5584, 1.5578, 1.5524, 1.5523, 1.5538, 1.5513, 1.5625, 1.5557, 1.5555, 1.5496, 1.5514, 1.5461, 1.5453, 1.5469, 1.5462, 1.547, 1.549, 1.5434, 1.5442, 1.5352, 1.5396, 1.5404, 1.5423, 1.5367, 1.5252, 1.5186, 1.5297, 1.5331, 1.5359, 1.5389, 1.5255, 1.5379, 1.5349, 1.5342, 1.5372, 1.533, 1.5371, 1.5394, 1.546, 1.544, 1.5462, 1.545, 1.5417, 1.5454, 1.5441, 1.5462, 1.5494, 1.5472, 1.5529, 1.5525, 1.5506, 1.5546, 1.5546, 1.5673, 1.5614, 1.5536, 1.5533, 1.555, 1.5552, 1.5582, 1.5506, 1.5504, 1.543, 1.5435, 1.5403, 1.5407, 1.5449, 1.547, 1.5466, 1.545, 1.5436, 1.5403, 1.5384, 1.5415, 1.5425, 1.5375, 1.54, 1.5403, 1.53, 1.5346, 1.5383, 1.5377, 1.5319, 1.527, 1.5361, 1.5311, 1.5255, 1.5314, 1.5372, 1.5368, 1.5306, 1.5249, 1.5253, 1.5247, 1.53, 1.5283, 1.5339, 1.5335, 1.5385, 1.5345, 1.5316, 1.5351, 1.5358, 1.532, 1.5368, 1.5357, 1.5397, 1.5492, 1.5456, 1.547, 1.5439, 1.545, 1.556, 1.558, 1.5616, 1.5541, 1.5523, 1.551, 1.5534, 1.5519, 1.5531, 1.5492, 1.5446, 1.553, 1.544, 1.5502, 1.5503, 1.5531, 1.5504, 1.5537, 1.5552, 1.555, 1.5513, 1.5444, 1.5471, 1.5421, 1.543, 1.5219, 1.5261, 1.5347, 1.5345, 1.5379, 1.5275, 1.5358, 1.5418, 1.5374, 1.5418, 1.5418, 1.5423, 1.5483, 1.5426, 1.5399, 1.5454, 1.5469, 1.5391, 1.5294, 1.5303, 1.5267, 1.5297, 1.5243, 1.5253, 1.5279, 1.5344, 1.5324, 1.5328, 1.5417, 1.5358, 1.538, 1.5344, 1.5104, 1.3983, 1.4218, 1.4234, 1.4299, 1.4468, 1.4438, 1.4496, 1.4423, 1.4274, 1.4136, 1.4138, 1.4177, 1.4267, 1.4288, 1.4208, 1.4023, 1.4077, 1.4133, 1.4258, 1.4155, 1.418, 1.4236, 1.4234, 1.4189, 1.4322, 1.4336, 1.4304, 1.4327, 1.4339, 1.433, 1.4313, 1.4332, 1.4333, 1.4317, 1.4196, 1.4262, 1.4309, 1.4268, 1.421, 1.4148, 1.4161, 1.4099, 1.4035, 1.4139, 1.4093, 1.4191, 1.4259, 1.4304, 1.4445, 1.4391, 1.4348, 1.4201, 1.4154, 1.4319, 1.4367, 1.4403, 1.4535, 1.4505, 1.446, 1.4347, 1.4407, 1.442, 1.4453, 1.4491, 1.4495, 1.4471, 1.4532, 1.4532, 1.466, 1.4614, 1.4652, 1.468, 1.4643, 1.4451, 1.4336, 1.4375, 1.4318, 1.4285, 1.439, 1.4317, 1.4396, 1.4387, 1.4342, 1.4373, 1.4293, 1.4347, 1.4331, 1.434, 1.4357, 1.4408, 1.4409, 1.4354, 1.442, 1.4435, 1.4406, 1.4398, 1.4418, 1.4599, 1.4545, 1.4602, 1.4609, 1.465, 1.4632, 1.4649, 1.4827, 1.5071, 1.5012, 1.5092, 1.5168, 1.515, 1.5175, 1.5267, 1.5122, 1.5153, 1.522, 1.5097, 1.5167, 1.5071, 1.5075, 1.51, 1.517, 1.5183, 1.5155, 1.5117, 1.5143, 1.5269, 1.5435, 1.5366, 1.535, 1.5337, 1.5218, 1.5397, 1.5207, 1.5073, 1.508, 1.5134, 1.5216, 1.5212, 1.5168, 1.5152, 1.5076, 1.5177, 1.5249, 1.5215, 1.5328, 1.5528, 1.5544, 1.5663, 1.5626, 1.5624, 1.5568, 1.5518, 1.5527, 1.5463, 1.5363, 1.5383, 1.5447, 1.5404, 1.521, 1.5129, 1.5092, 1.5146, 1.5035, 1.506, 1.5031, 1.499, 1.4989, 1.5009, 1.4993, 1.4998, 1.5016, 1.5108, 1.5363, 1.5271, 1.5223, 1.5185, 1.5182, 1.5232, 1.524, 1.5166, 1.5141, 1.5243, 1.5238, 1.5205, 1.5134, 1.5236, 1.5176, 1.5174, 1.5209, 1.528, 1.5148, 1.5222, 1.5277, 1.528, 1.5403, 1.5439, 1.5342, 1.528, 1.5259, 1.5214, 1.5135, 1.4914, 1.4915, 1.5069, 1.4935, 1.4991, 1.5111, 1.514, 1.5258, 1.5167, 1.521, 1.5152, 1.5194, 1.5273, 1.5212, 1.5228, 1.5207, 1.529, 1.5252, 1.522, 1.5273, 1.5256, 1.5169, 1.5215, 1.5171, 1.5148, 1.518, 1.5197, 1.5248, 1.5262, 1.5311, 1.5304, 1.5381, 1.5467, 1.5394, 1.5283, 1.5264, 1.5203, 1.5332, 1.5385, 1.523, 1.5306, 1.5252, 1.5342, 1.5326, 1.5283, 1.5348, 1.5186, 1.528, 1.5295, 1.5252, 1.5311, 1.5277, 1.5236, 1.5284, 1.5276, 1.5282, 1.525, 1.5193, 1.5282, 1.5368, 1.5368, 1.5418, 1.5515, 1.5763, 1.551, 1.5499, 1.5486, 1.5531, 1.5428, 1.5465, 1.5428, 1.5443, 1.54, 1.5303, 1.5324, 1.5365, 1.5312, 1.536, 1.5271, 1.5265, 1.5226, 1.5311, 1.5158, 1.5208, 1.5238, 1.5247, 1.5275, 1.5287, 1.534, 1.5329, 1.533, 1.5319, 1.5321, 1.5313, 1.5303, 1.5259, 1.5182, 1.5198, 1.4985, 1.4561, 1.4678, 1.4771, 1.481, 1.4779, 1.4897, 1.4787, 1.4907, 1.496, 1.497, 1.4961, 1.4896, 1.4893, 1.4896, 1.4901, 1.488, 1.496, 1.5074, 1.5021, 1.5028, 1.4892, 1.4877, 1.4842, 1.4808, 1.4717, 1.4764, 1.4743, 1.4625, 1.4725, 1.4752, 1.4838, 1.4722, 1.4649, 1.4522, 1.46, 1.4644, 1.4713, 1.4679, 1.4601, 1.4702, 1.47, 1.4759, 1.475, 1.4776, 1.4886, 1.49, 1.495, 1.5253, 1.5164, 1.4957, 1.508, 1.5094, 1.5093, 1.527, 1.5312, 1.5235, 1.5352, 1.5213, 1.5245, 1.5429, 1.5316, 1.5265, 1.5179, 1.5269, 1.5148, 1.5231, 1.5151, 1.5089, 1.5043, 1.5035, 1.5011, 1.5011, 1.5011, 1.4919, 1.4948, 1.504, 1.515, 1.5191, 1.5143, 1.531, 1.533, 1.5177, 1.528, 1.5177, 1.5183, 1.5101, 1.5134, 1.5064, 1.5228, 1.5108, 1.519, 1.516, 1.5185, 1.5236, 1.5223, 1.5223, 1.5187, 1.5151, 1.5092, 1.5085, 1.5037, 1.4967, 1.497, 1.4833, 1.4943, 1.5001, 1.4999, 1.4973, 1.5061, 1.5043, 1.5149, 1.5193, 1.5221, 1.5267, 1.5355, 1.5322, 1.5213, 1.5208, 1.5207, 1.5175, 1.516, 1.5152, 1.5245, 1.5205, 1.5213, 1.512, 1.5102, 1.5102, 1.5058, 1.524, 1.5667, 1.5917, 1.5814, 1.6191, 1.6685, 1.6918, 1.7902, 1.7834, 1.7538, 1.7777, 1.7306, 1.68, 1.6918, 1.6882, 1.6987, 1.719, 1.7323, 1.7, 1.7774, 1.7545, 1.7349, 1.754, 1.7474, 1.7291, 1.7383, 1.7956, 1.7732, 1.7523, 1.7614, 1.7609, 1.751, 1.7554, 1.7386, 1.7384, 1.7232, 1.7185, 1.7041, 1.7405, 1.7415, 1.768, 1.74, 1.7592, 1.7359, 1.7212, 1.697, 1.7077, 1.705, 1.691, 1.7105, 1.7119, 1.7375, 1.742, 1.7223, 1.732, 1.7218, 1.7116, 1.7224, 1.7287, 1.72, 1.768, 1.7638, 1.75, 1.7724, 1.7613, 1.7609, 1.7742, 1.7434, 1.7294, 1.7449, 1.7287, 1.7276, 1.7338, 1.7222, 1.7262, 1.726, 1.7394, 1.7343, 1.7337, 1.7352, 1.7354, 1.7556, 1.7351, 1.7313, 1.7513, 1.7518, 1.7447, 1.7534, 1.7721, 1.7538, 1.7525, 1.7505, 1.7535, 1.7736, 1.768, 1.8039, 1.8219, 1.8292, 1.8151, 1.8484, 1.8224, 1.7906, 1.8103, 1.7981, 1.7912, 1.8028, 1.7846, 1.787, 1.7771, 1.7862, 1.7868, 1.7883, 1.7687, 1.7546, 1.7369, 1.7219, 1.7378, 1.7222, 1.7036, 1.6965, 1.6875];

    public function load(ObjectManager $manager): void
    {
        $currentDate = new \DateTime();
        $currentDate->modify('+15 minutes')->setTime($currentDate->format('H'), 0);

        $symbols = $this->createSymbols($manager);

        $date = clone $currentDate;
        foreach (array_reverse(self::CHANGING_PRICE_15_PERCENT_UP) as $item) {
            [$priceValue, $symbol] = $item;
            $price = $this->createPriceEntity($priceValue, $symbols[$symbol], $date);
            $manager->persist($price);
        }
        $manager->flush();

        $date = clone $currentDate;
        foreach (self::CHANGING_PRICE_15_PERCENT_UP as $item) {
            [$priceValue] = $item;
            $price = $this->createPriceEntity($priceValue, $symbols[self::PRICE_TO_BOTTOM_SYMBOL], $date);
            $manager->persist($price);
        }
        $manager->flush();

        $date = clone $currentDate;
        foreach (self::CHANGING_PRICE_TOP_BOTTOM_TOP as $priceValue) {
            $price = $this->createPriceEntity($priceValue, $symbols[self::PRICE_TOP_BOTTOM_TOP_SYMBOL], $date);
            $manager->persist($price);
        }
        $manager->flush();

        $date = clone $currentDate;
        foreach (self::NOT_RECENTLY_CHANGED_PRICE as $priceValue) {
            $price = $this->createPriceEntity($priceValue, $symbols[self::NOT_RECENTLY_CHANGED_PRICE_SYMBOL], $date);
            $manager->persist($price);
        }
        $manager->flush();

        $date = clone $currentDate;
        foreach (self::RECENTLY_CHANGED_PRICE as $priceValue) {
            $price = $this->createPriceEntity($priceValue, $symbols[self::RECENTLY_CHANGED_PRICE_SYMBOL], $date);
            $manager->persist($price);
        }
        $manager->flush();

        $date = clone $currentDate;
        foreach (array_reverse(self::RECENTLY_CHANGED_PRICE_WITH_PLATO) as $priceValue) {
            $price = $this->createPriceEntity($priceValue, $symbols[self::RECENTLY_CHANGED_PRICE_WITH_PLATO_SYMBOL], $date);
            $manager->persist($price);
        }
        $manager->flush();

        $date = clone $currentDate;
        foreach (array_reverse(self::TWT_RISING_ON_INTERVAL_AND_THEN_FALLING_ON_DISTANCE) as $priceValue) {
            $price = $this->createPriceEntity($priceValue, $symbols[self::TWT_RISING_ON_INTERVAL_AND_THEN_FALLING_ON_DISTANCE_SYMBOL], $date);
            $manager->persist($price);
        }
        $manager->flush();
    }

    private function createPriceEntity(float $priceValue, Symbol $symbol, \DateTimeInterface $currentDate): Price
    {
        $price = new Price();
        $price
            ->setPrice($priceValue)
            ->setSymbol($symbol)
            ->setDatetime(clone $currentDate->modify('-15 minutes'))
        ;
        return $price;
    }

    private function createSymbols(ObjectManager $manager): array
    {
        $user = $manager->getRepository(User::class)->findOneBy([]);
        /** @var Symbol[] $data */
        $data = [];
        foreach (self::SYMBOLS as $symbolName) {
            $data[$symbolName] = (new Symbol())
                ->setName($symbolName)
            ;
            $userSymbol = (new UserSymbol())
                ->setUser($user)
                ->setSymbol($data[$symbolName])
            ;
            $manager->persist($data[$symbolName]);
            $manager->persist($userSymbol);
        }

        return $data;
    }
}
