<?php

namespace Unit\Actions;

use CityPost\Calculator\Actions\QuoteCalculator;
use Faker\Factory as FakerFactory;
use PHPUnit\Framework\TestCase;
//use Tests\Support\FakerTrait;

class QuoteCalculatorTest extends TestCase {

	public function fakeDeal()
	{
		$faker = FakerFactory::create();

		return [
			'customer' => [
				'reseller' => $faker->boolean,
			],
			'railings' => [
				[
					'id' => $faker->randomNumber(),
					'name' => $faker->word,
					'length' => $faker->randomNumber(),
					'height' => $faker->randomNumber(),
					'quantity' => $faker->randomNumber(),
					'sections' => $faker->randomNumber(),
					'ends' => $faker->randomNumber(),
					'corners' => $faker->randomNumber(),
					'installation' => $faker->randomNumber(),
					'segment' => [
						[
							'id' => $faker->randomNumber(),
							'height' => $faker->randomNumber(),
							'width' => $faker->randomNumber(),
						],
						// Add more segments as needed
					],
				],
				// Add more railings as needed
			],
			'custom_items' => [
				[
					'name' => $faker->word,
					'quantity' => $faker->randomNumber(),
					'cost' => $faker->randomNumber(),
					'tracks_inventory' => 'Yes',
				],
				[
					'name' => $faker->word,
					'quantity' => $faker->randomNumber(),
					'cost' => $faker->randomNumber(),
					'tracks_inventory' => 'No',
				],
				// Add more custom items as needed
			],
		];
	}

	public function testTotalLinealFeet() {
		$calculator = new QuoteCalculator($this->fakeDeal(), 'direct');

		// Test the initial value of totalLinealFeet
		$this->assertEquals(0, $calculator->totalLinealFeet());

		// Add some railings to the calculator and test the updated value of totalLinealFeet
		$calculator->linealFeetPerRailing[1] = 10;
		$calculator->linealFeetPerRailing[2] = 20;
		$this->assertEquals(30, $calculator->totalLinealFeet());
	}

	public function testCalculateRailingCost() {

	}

	public function testGetInventory() {

	}

	public function testGetSegmentPrice() {

	}
}
