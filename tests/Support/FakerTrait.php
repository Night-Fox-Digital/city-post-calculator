<?php

namespace Tests\Support;

use Faker\Factory as FakerFactory;

trait FakerTrait {

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
}
