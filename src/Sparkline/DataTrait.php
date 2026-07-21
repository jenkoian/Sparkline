<?php

namespace Davaxi\Sparkline;

trait DataTrait
{
    /**
     * @var int Base of value
     */
    protected $base;

    /**
     * Scalar float (legacy) or per-series arrays of per-point floor values.
     * @var float|float[][]
     */
    protected $originValue = 0;

    /**
     * @var array
     */
    protected $data = [
        [0, 0],
    ];

    /**
     * @param int $base Set base for values
     */
    public function setBase($base)
    {
        $this->base = $base;
    }

    /**
     * Set per-point floor values per series, mirroring setData().
     * Each argument is an array of floor values for one series.
     * A scalar call setOriginValue(5.0) is also still accepted for all-series use.
     *
     * @param float|array ...$seriesOrigins
     */
    public function setOriginValue(...$seriesOrigins)
    {
        if (count($seriesOrigins) === 1 && !is_array($seriesOrigins[0])) {
            $this->originValue = (float)$seriesOrigins[0];
            return;
        }

        $this->originValue = [];
        foreach ($seriesOrigins as $data) {
            $this->originValue[] = array_values((array)$data);
        }
    }

    /**
     * @param ...$allSeries
     */
    public function setData(...$allSeries)
    {
        $this->data = [];
        foreach ($allSeries as $data) {
            $this->addSeries($data);
        }
    }

    /**
     * @param array $data
     */
    public function addSeries(array $data)
    {
        $data = array_values($data);
        $count = count($data);
        if (!$count) {
            $this->data[] = [0, 0];

            return;
        }
        if ($count < static::MIN_DATA_LENGTH) {
            $this->data[] = array_fill(0, 2, $data[0]);

            return;
        }
        $this->data[] = $data;
    }

    /**
     * @return int
     */
    public function getSeriesCount(): int
    {
        return count($this->data);
    }

    /**
     * @param int $seriesIndex
     * @return array
     */
    public function getNormalizedData(int $seriesIndex = 0): array
    {
        $data = $this->data[$seriesIndex];
        foreach ($data as $i => $value) {
            $floor = $this->getOriginValueForPoint($seriesIndex, $i);
            $data[$i] = max(0, $value - $floor);
        }

        return $data;
    }

    /**
     * @param int $seriesIndex
     * @return array Floor values matching the series data length, or all-zeros if none set.
     */
    public function getOriginSeries(int $seriesIndex = 0): array
    {
        if (!is_array($this->originValue)) {
            return [];
        }

        return $this->originValue[$seriesIndex] ?? [];
    }

    /**
     * @param int $seriesIndex
     * @param int $pointIndex
     * @return float
     */
    protected function getOriginValueForPoint(int $seriesIndex, int $pointIndex): float
    {
        if (is_array($this->originValue)) {
            return (float)($this->originValue[$seriesIndex][$pointIndex] ?? 0);
        }

        return (float)$this->originValue;
    }

    /**
     * @param int $seriesIndex
     * @return array
     */
    public function getData(int $seriesIndex = 0): array
    {
        return $this->data[$seriesIndex];
    }

    /**
     * @param int $seriesIndex
     * @return int
     */
    public function getCount(int $seriesIndex = 0): int
    {
        return count($this->data[$seriesIndex]);
    }

    /**
     * @param int $seriesIndex
     * @return array
     */
    protected function getMaxValueWithIndex(int $seriesIndex = 0): array
    {
        $max = max($this->data[$seriesIndex]);
        $maxKeys = array_keys($this->data[$seriesIndex], $max);
        $maxIndex = end($maxKeys);
        if ($this->base) {
            $max = $this->base;
        }

        return [$maxIndex, $max];
    }

    /**
     * @param int $seriesIndex
     * @return float
     */
    protected function getMaxValue(int $seriesIndex = 0): float
    {
        if ($this->base) {
            return $this->base;
        }

        return max($this->data[$seriesIndex]);
    }

    /**
     * TODO: this could be cached somehow
     * @return float
     */
    protected function getMaxValueAcrossSeries(): float
    {
        if ($this->base) {
            return $this->base;
        }

        if (is_array($this->originValue)) {
            // Denominator is max(top) - min(floor) so the range fills the chart
            $topMax = max(array_map('max', $this->data));
            $floors = array_merge(...array_values($this->originValue));
            $floorMin = $floors ? min($floors) : 0;
            return $topMax - $floorMin;
        }

        $maxes = array_map('max', $this->data);
        return max($maxes);
    }

    protected function getMaxNumberOfDataPointsAcrossSerieses()
    {
        $counts = array_map('count', $this->data);
        return max($counts);
    }

    /**
     * @param int $seriesIndex
     * @return array
     */
    protected function getMinValueWithIndex(int $seriesIndex = 0): array
    {
        $min = min($this->data[$seriesIndex]);
        $minKey = array_keys($this->data[$seriesIndex], $min);
        $minIndex = end($minKey);

        return [$minIndex, $min];
    }

    /**
     * @param int $seriesIndex
     * @return array
     */
    protected function getExtremeValues(int $seriesIndex = 0): array
    {
        list($minIndex, $min) = $this->getMinValueWithIndex($seriesIndex);
        list($maxIndex, $max) = $this->getMaxValueWithIndex($seriesIndex);

        return [$minIndex, $min, $maxIndex, $max];
    }
}
