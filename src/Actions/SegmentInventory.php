<?php

namespace CityPost\Calculator\Actions;

use CityPost\Calculator\Exceptions\MissingPartException;
use CityPost\Calculator\LegacyPart;

class SegmentInventory {

	public $width;
	public $height;
	public $color;

	public $railing;
	public $segment;

	/**
	 * @var LegacyPart[]
	 */
	public $parts;

	/**
	 * @var array (Record<sku, quantity>) - The calculated inventory usage for the quote
	 */
	public $inventoryByCalculatorId = [];

	/**
	 * @var Record<sku, numPosts>
	 */
	public $segmentPostTypes = [];

	/**
	 * @var array - Array of errors that occurred that would prevent an accurate quote
	 */
	public $errors = [];

	/**
	 * @param LegacyPart[] $parts
	 * @param Railing $railing
	 * @param Segment $segment
	 */
	public function __construct($railing, $segment, $parts) {
		$this->railing = $railing;
		$this->segment = $segment;
		$this->parts = $parts;
		$this->height = (int)preg_replace('/[^0-9\\.]/u', '', $segment['height']);
		$this->width = (float) $segment->width;
		$this->color = $railing->color;


		foreach ($parts as $part) {
			$this->inventoryByCalculatorId[$part->sku] = 0;
		}
		$this->execute();
	}

	public function getInventory() {
		return collect($this->inventoryByCalculatorId)->filter(function($count) {
			return $count > 0;
		});
	}

	protected function execute() {
		/**
		 * Things to calculate
		 */
		$this->calculateLength();
		$this->calculatePosts();
		$this->calculateToprails();
		$this->calculateToprailBrackets();
		$this->calculateBracketScrews();
		$this->calculateEndCaps();
		$this->calculateEndFittings();
		$this->calculateFasteners();
		$this->calculateBeveledWashers();
	}

	public function calculateLength() {
		$multiplier = $this->height === 36 ? 11 : 13;
		$this->addInventory(
			'CBL-18',
			($multiplier * $this->width * 1.1)
		);
	}

	public function totalPosts() {
		$num = 0;
		foreach ($this->segmentPostTypes as $n) {
			$num += $n;
		}

		return $num;
	}

	protected function calculatePosts() {
		$finishPostTypes = [
			'Standard' => ['Horizontal', 'Standard'], // Purpose: Horizontal, Corner: Standard
			'Horizontal to Stair' => ['Stairs', 'Standard'], // Purpose: Stair, Corner: Standard
			'Horizontal/Stair Corner' => ['Stairs', 'Corner'], // Purpose: Stair, Corner: Corner
			'Horizontal Corner' => ['Horizontal', 'Corner'], //Purpose: Horizontal, Corner: Corner
		];

		$startPostTypes = [
			'Standard' => ['Horizontal', 'Standard'], // Purpose: Horizontal
			'Standard (Horizontal)' => ['Horizontal', 'Standard'], // Purpose: Horizontal,
			'Standard (Stairs)' => ['Stairs', 'Standard'], // Purpose: Stairs
		];

		if ($this->segment->type === 'Stairs') {
			$finishPostTypes['Standard'][0] = 'Stairs';
			$startPostTypes['Standard'][0] = 'Stairs';
		}

		$standardPostPart = $this->getPostPart($this->segment->type);

		if (!$standardPostPart) return [];

		$segmentPostTypes = [];
		$standardPosts = ceil($this->width / 6) - 1;
		$segmentPostTypes[$standardPostPart->sku] = $standardPosts;

		if ($this->segment->start_post !== 'None') {
			if (isset($startPostTypes[$this->segment->start_post])) {
				$startPostPart = $this->getPostPart(...$startPostTypes[$this->segment->start_post]);
				if ($startPostPart) {
					if (!isset($segmentPostTypes[$startPostPart->sku])) {
						$segmentPostTypes[$startPostPart->sku] = 0;
					}
					$segmentPostTypes[$startPostPart->sku]++;
				}
			}
		}

		if ($this->segment->finish_post !== 'None') {
			if (isset($finishPostTypes[$this->segment->finish_post])) {
				$finishPostPart = $this->getPostPart(...$finishPostTypes[$this->segment->finish_post]);
				if ($finishPostPart) {
					if (!isset($segmentPostTypes[$finishPostPart->sku])) {
						$segmentPostTypes[$finishPostPart->sku] = 0;
					}
					$segmentPostTypes[$finishPostPart->sku]++;
				}
			}
		}

		$this->segmentPostTypes = $segmentPostTypes;

		foreach ($this->segmentPostTypes as $sku => $num) {
			$this->addInventory($sku, $num);
		}
	}

	protected function calculateToprails() {
		if ($this->segment->top_rail === 'No Toprail') return;
		$topRailLength = (int)$this->segment->toprail_length;
		if (!$topRailLength) $topRailLength = 10;

		$size = $this->is3InchToprail() ? 13 : 12;
		$color = $this->mapColor($this->color);

		$numTopRailSticks = ceil($this->width / $topRailLength);

		$this->addInventory("TPRL-MTL-${size}-${color}-${topRailLength}", $numTopRailSticks);

		$numPosts = $this->totalPosts();

		$this->addInventory(
			($this->color === 'White') ? 'TPRL-SCRW-ST12-ZINC' : 'TPRL-SCRW-ST12',
			$numPosts * 4
		);

		$this->calculateSleeves($numTopRailSticks);
	}

	/**
	 * Brackets should always been included even if there is no toprail
	 */
	protected function calculateToprailBrackets() {
		$numPosts = $this->totalPosts();

		$color = $this->mapColor($this->color);
		if ($color === 'BRZ') $color = 'BLK';

		$standardBrackets = $numPosts;
		if ($this->segment->finish_post === 'Horizontal Corner') {
			$standardBrackets -= 1;
			$this->addInventory(
				"TPRL-BRCKT-90-DEG-${color}",
				1
			);
		}

		$this->addInventory(
			"TPRL-STNDRD-BRCKT-${color}",
			$standardBrackets
		);
	}

	protected function calculateEndCaps() {
		if ($this->segment->end_sleeve_type === 'Horizontal to Stair') return;

		$size = $this->is3InchToprail() ? 13 : 12;

		$color = $this->mapColor($this->color);
		if ($color === 'BRZ') $color = 'BLK';

		$sku = "END-CAP-${size}-${color}";

		$this->addInventory($sku, 2);
	}

	protected function calculateEndFittings() {
		$multiplier = $this->height === 36 ? 11 : 13;
		$this->addInventory('END-FTNGS-STND-SNGL', $multiplier * 2);
	}

	protected function calculateFasteners() {
		$segment = $this->segment;

		if ($segment->surface === 'Concrete') {
			$sku = 'CNCRT-FSTNR-3';
		} else if ($segment->mount === 'Fascia' || ($this->height === 42 && $segment->mount === 'Base')) {
			$sku = $this->color === 'White' ? 'ZNC-LAG-FSTNR-5' : 'HDLK-FSTNR-5';
		} else {
			$sku = $this->color === 'White' ? 'ZNC-LAG-FSTNR-3' : 'HDLK-FSTNR-278';
		}

		$numBoltsPerSegment = 4;
		if ($segment->mount === 'Fascia' && $segment->type === 'Stairs') {
			$numBoltsPerSegment = 2;
		}

		$this->addInventory($sku, $this->totalPosts() * $numBoltsPerSegment);
	}

	protected function calculateBeveledWashers() {
		if ($this->segment->type === 'Stairs') {
			$multiplier = $this->height === 36 ? 11 : 13;

			$this->addInventory('BVL-WSHR', $multiplier * 2);
		}
	}

	/**
	 * Bracket screws should always be included even if there is no toprail
	 */
	protected function calculateBracketScrews() {
		$sku = 'SCRW-BRCKT';
		if ($this->color === 'White') {
			$sku = 'BRCKT-SCRW-ZNC';
		}

		$this->addInventory($sku, $this->totalPosts() * 2);
	}

	protected function calculateSleeves($numTopRailSticks) {
		$numSleeveScrews = 0;

		$color = $this->mapColor($this->color);
		if ($this->segment->top_rail !== 'No Toprail') {
			if ($numTopRailSticks > 1) {
				$numStraightSleeves = $numTopRailSticks - 1;
				$numSleeveScrews += $numStraightSleeves * 4;
				$this->addInventory(
					"SLV-STRT-${color}",
					$numStraightSleeves
				);
			}
		}

		if ($this->segment->end_sleeve_type && $this->segment->end_sleeve_type !== 'End Cap') {
			// Add Finish Sleeve
			$sku = null;
			if ($this->segment->end_sleeve_type === 'Horizontal to Stair') {
				if ($this->segment->turn_direction === 'None') {
					if (!empty($this->segment->sleeve_upper_lower)) {
						$direction = $this->segment->sleeve_upper_lower === 'Upstairs' ? 'U' : 'L';
						$sku = "SLV-HS${direction}-${color}";
					} else {
						$sku = "SLV-STRT-${color}";
					}
				}
				else {
					$size = $this->is3InchToprail() ? 13 : 12;
					$sku = "SLV-90-DEG-HS${size}-${color}";
				}
			}
			else if (!empty($this->segment->turn_degrees)) {
				$digits = filter_var($this->segment->turn_degrees, FILTER_SANITIZE_NUMBER_INT);
				$sku = "SLV-${digits}-DEG-${color}";
			}

			if ($sku) {
				$numSleeveScrews += 4;
				$this->addInventory($sku, 1);
			}
		}

		$this->addInventory(
			$this->color === 'White' ? 'SLV-SCRW-ST8-ZNC' : 'SLV-SCRW-ST8',
			$numSleeveScrews
		);
	}

	protected function addInventory($sku, $count) {
		if (!isset($this->inventoryByCalculatorId[$sku])) {
			$this->inventoryByCalculatorId[$sku] = 0;
		}

		$this->inventoryByCalculatorId[$sku] += $count;
	}

	protected function mapColor($color) {
		$colorMapper = [
			'Black' => 'BLK',
			'White' => 'WHT',
			'Grey' => 'GRY',
			'Bronze' => 'BRZ',
		];

		return $colorMapper[$color] ?? 'BLK';
	}

	protected function getPostPart($purpose = 'Horizontal', $corner = 'Standard') {
		try {
			return PostParts::get($this->railing, $this->segment, $purpose, $corner);
		} catch (MissingPartException $e) {
			$this->errors[] = [
				'type' => 'calculator',
				'message' => $e->getMessage(),
				'details' => $e->parameters
			];
		}

		return null;
	}

	protected function is3InchToprail() {
		if ($this->segment->top_rail === 'Aluminum 2"') {
			return false;
		}

		return true;
	}
}
