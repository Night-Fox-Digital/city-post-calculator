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
	public $itemsByPartVersionId = [];

	/**
	 * CustomItemInventory constructor.
	 * @param CustomItem $customItem
	 */
	public function __construct($deal, $parts) {
		$this->customItems = $deal->custom_items;
		$this->calculate($parts);
	}

	protected function calculate($parts) {
		foreach ($parts as $partVersionId => $part) {
			$this->itemsByPartVersionId[$partVersionId] = 0;
		}

		foreach ($this->customItems as $customItem) {
			foreach ($customItem->part_version as $partVersion) {
				$quantity = (int)$customItem->quantity;
				if ($partVersion->part->track_inventory === 'Yes' && $quantity > 0) {
					// Track Inventory
					if (!isset($this->itemsByPartVersionId[$partVersion->id])) {
						$this->itemsByPartVersionId[$partVersion->id] = 0;
					}
					$this->itemsByPartVersionId[$partVersion->id] += $quantity;
				}
			}
		}

	}

	public function getInventory() {
		return collect($this->itemsByPartVersionId)->filter(function($count) {
			return $count > 0;
		});
	}
}
