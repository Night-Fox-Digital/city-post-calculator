<?php

namespace CityPost\Calculator\Adapters;

interface PartAdapter {
	public function loadPartsByDate($date);
	public function getPartBySku(string $sku, $date);
	public function getPartByCalculatorId(string $calculatorId, $date);
}
