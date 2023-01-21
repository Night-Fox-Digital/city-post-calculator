<?php

namespace CityPost\Calculator\Adapters;

use CityPost\Calculator\Part;
use CityPost\Calculator\PartVersion;
use Illuminate\Support\Facades\Schema;

class VersionedPartAdapter implements PartAdapter {

	protected $supportsVersions = false;
	protected $partsByDate = [];

	public function __construct() {
		$this->supportsVersions = $this->hasVersionedParts();
	}

	public function loadPartsByDate($date) {
		$partVersions = PartVersion::with('part')->whereDate('effective_date', '<=', $date)->where(function($query) use ($date) {
			$query->whereNull('end_date')->orWhereDate('end_date', '>=', $date);
		})->get();
		$parts = [];
		foreach ($partVersions as $partVersion) {
			$parts[] = $this->convertPartVersionToPart($partVersion);
		}

		$this->partsByDate = $parts;
		return $parts;
	}

	public function getPartBySku(string $sku, $date) {
		if ($this->supportsVersions) {
			// Search by PartVersion with('part'), then override Part.sku_id to the PartVersion.sku_id. Return Part
			$partVersion = $this->forDate(PartVersion::with('part'), $date)->where('sku_id', $sku)->first();
			if ($partVersion) {
				return $this->convertPartVersionToPart($partVersion);
			}
		}

		return (new LegacyPartAdapter())->getPartBySku($sku, $date);
	}

	public function getPartByCalculatorId(string $calculatorId, $date) {
		if ($this->supportsVersions) {
			// Search by calculator id on Part, with(['part_versions' => where]), then override Part.sku to PartVersion.sku. Return Part
			$part = Part::with(['part_versions' => function($query) use ($date) {
				$this->forDate($query, $date);
			}])->where('sku', $calculatorId)->first();
			if ($part && count($part->part_versions) > 0) {
				$partVersion = $part->part_versions[0];
				$part->sku_id = $partVersion->sku_id;
				$part->part_version_id = $partVersion->id;
				return $part;
			}
		}

		return (new LegacyPartAdapter())->getPartByCalculatorId($calculatorId, $date);
	}

	protected function forDate($query, $date) {
		$query->whereDate('effective_date', '<=', $date)->where(function($q) use ($date) {
			$q->whereNull('end_date')->orWhereDate('end_date', '>=', $date);
		});

		return $query;
	}

	protected function convertPartVersionToPart($partVersion) {
		$part = $partVersion->part;
		$part->sku_id = $partVersion->sku_id;
		$part->part_version_id = $partVersion->id;
		return $part;
	}

	protected function hasVersionedParts() {
		return Schema::hasTable('part_versions');
	}
}
