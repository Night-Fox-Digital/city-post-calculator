<?php

namespace CityPost\Calculator\Actions;

use CityPost\Calculator\CustomItem;

class CustomItemsInventory {
	/**
	 * @var CustomItem
	 */
	public $customItems;

	/**
	 * @var array (Record<sku, quantity>) - The calculated inventory usage for the quote
	 */
	public $inventoryByCalculatorId = [];

	/**
	 * CustomItemInventory constructor.
	 * @param CustomItem $customItem
	 */
	public function __construct($deal, $parts) {
		$this->customItems = $deal->custom_items;
		$this->calculate($parts);
	}

	protected function calculate($parts) {
		foreach ($parts as $part) {
			$this->inventoryByCalculatorId[$part->sku] = 0;
		}

		foreach ($this->customItems as $customItem) {
			foreach ($customItem->part_version as $partVersion) {
				$quantity = (int)$customItem->quantity;
				if ($partVersion->part->track_inventory === 'Yes' && $quantity > 0) {
					// Track Inventory
					$this->inventoryByCalculatorId[$partVersion->part->sku] += $quantity;
				}
			}
		}
	}

	public function getInventory() {
		return collect($this->inventoryByCalculatorId)->filter(function($count) {
			return $count > 0;
		});
	}
}
