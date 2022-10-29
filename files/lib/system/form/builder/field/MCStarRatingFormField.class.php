<?php

namespace wcf\system\form\builder\field;

use InvalidArgumentException;

/**
 * @author      Florian Gail
 * @copyright   Florian Gail; 2018 - 2022; <https://www.mysterycode.de>
 */
class MCStarRatingFormField extends SingleSelectionFormField implements IMinimumFormField
{
    use TMinimumFormField;

    protected int $bestRating;

    public function __construct()
    {
        $this->minimum(1);
        $this->bestRating(5);
    }

    protected function generateStars(int $amount): string
    {
        $result = '';

        for ($i = 1; $i <= $amount; $i++) {
            $result .= '⭐';
        }

        return $result;
    }

    public function populate()
    {
        $ratings = [];
        for ($i = 0; $i <= $this->getBestRating(); $i++) {
            $ratings[$i] = $this->generateStars($i);
        }

        $this->options($ratings);
    }

    public function bestRating(int $bestRating): self
    {
        if ($this->getMinimum() !== null && $bestRating < $this->getMinimum()) {
            throw new InvalidArgumentException('Best rating cannot be less than minimum rating.');
        }

        $this->bestRating = $bestRating;

        return $this;
    }

    public function getBestRating(): int
    {
        return $this->bestRating;
    }
}
