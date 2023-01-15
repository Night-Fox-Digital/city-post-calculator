<?php

namespace CityPost\Calculator\Adapters;

use CityPost\Calculator\Part;

class LegacyPartAdapter implements PartAdapter {

	// Legacy parts don't utilize the date, they are static
	public function getPartBySku(string $sku, $date) {
		return Part::where('sku',$sku)->first();
	}

	public function getPartByCalculatorId(string $calculatorId, $date) {
		return $this->getPartBySku($calculatorId, $date);
	}

	public function loadPartsByDate($date) {
		return Part::get();
	}
}
