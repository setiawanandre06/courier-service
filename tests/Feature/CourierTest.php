<?php

namespace Tests\Feature;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CourierTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_can_list_all_couriers(): void
    {
        // Create 3 dummy couriers
        $couriers = Courier::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/couriers');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_can_search_couriers_by_name_with_fuzzy_search(): void
    {
        // Arrange
        Courier::factory()->create(['name' => 'Budiono Hadi Agung']);
        Courier::factory()->create(['name' => 'Gunawan Hardianto']);
        Courier::factory()->create(['name' => 'Prayogo Susilo']);

        // Act & Assert fuzzy match with single keyword
        $response1 = $this->getJson('/api/couriers?search=Budi+Agung');
        $response1->assertStatus(200)
            ->assertJsonCount(1, 'data'); // Budiono Hadi Agung

        // Act & Assert with space-separated multiple keywords
        $response2 = $this->getJson('/api/couriers?search=Guna+Hardi');
        $response2->assertStatus(200)
            ->assertJsonCount(1, 'data'); // Gunawan Hardianto
    }

    public function test_can_filter_couriers_by_level(): void
    {
        // Create dummy couriers with level 1-4
        Courier::factory()->create(['level' => 1]);
        Courier::factory()->create(['level' => 2]);
        Courier::factory()->create(['level' => 3]);
        Courier::factory()->create(['level' => 4]);

        // Act
        $response = $this->getJson('/api/couriers?level=2,3');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // Make sure response contain level 2 and 3 only
        $levels = collect($response->json('data'))->pluck('level')->toArray();
        $this->assertContains(2, $levels);
        $this->assertContains(3, $levels);
        $this->assertNotContains(1, $levels);
        $this->assertNotContains(4, $levels);
    }

    public function test_can_sort_couriers_by_different_fields(): void
    {
        // Arrange
        $courierA = Courier::factory()->create(['name' => 'Budiono Hadi Agung', 'level' => 3]);
        $courierB = Courier::factory()->create(['name' => 'Gunawan Hardianto', 'level' => 1]);
        $courierC = Courier::factory()->create(['name' => 'Prayogo Susilo', 'level' => 2]);

        // Sort by Name (Default)
        $responseDefault = $this->getJson('/api/couriers');
        $responseDefault->assertStatus(200);
        $namesDefault = collect($responseDefault->json('data'))->pluck('name')->toArray();
        $this->assertEquals(['Budiono Hadi Agung', 'Gunawan Hardianto', 'Prayogo Susilo'], $namesDefault);

        // Sort by Level (add param sort=level)
        $responseSortLevel = $this->getJson('/api/couriers?sort=level');
        $responseSortLevel->assertStatus(200);
        $namesSortLevel = collect($responseSortLevel->json('data'))->pluck('name')->toArray();
        $this->assertEquals(['Gunawan Hardianto', 'Prayogo Susilo', 'Budiono Hadi Agung'], $namesSortLevel);
    }

    public function test_can_paginate_couriers(): void
    {
        // Arrange
        Courier::factory()->count(25)->create();

        // Act
        $response = $this->getJson('/api/couriers?per_page=10');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total']
            ]);
        
        $this->assertEquals(25, $response->json('meta.total'));
        $this->assertEquals(10, $response->json('meta.per_page'));
    }

    // Create Courier Test
    public function test_can_create_courier_with_valid_data(): void
    {
        // Arrange data
        $data = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone_number' => $this->faker->unique()->phoneNumber(),
            'vehicle_type' => $this->faker->randomElement(['Motorcycle', 'Car']),
            'vehicle_plate' => strtoupper($this->faker->unique()->bothify('?? #### ??')),
            'level' => $this->faker->numberBetween(1, 5),
        ];

        // Act
        $response = $this->postJson('/api/couriers', $data);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonFragment([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'vehicle_type' => $data['vehicle_type'],
            'vehicle_plate' => $data['vehicle_plate'],
            'level' => $data['level'],
        ]);

        // Verify in database
        $this->assertDatabaseHas('couriers', $data);
    }

    // Create Courier Test
    public function test_cannot_create_courier_with_missing_fields(): void
    {
        // Arrange empty payload
        $data = []; 

        // Act
        $response = $this->postJson('/api/couriers', $data);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'phone_number', 'vehicle_type', 'vehicle_plate', 'level']);
    }

    // Attempt to Courier with Duplicate Unique Fields
    public function test_cannot_create_courier_with_duplicate_unique_fields(): void
    {
        // Arrange data
        $existingCourier = Courier::factory()->create();

        $data = [
            'name' => $this->faker->name(),
            'email' => $existingCourier->email,
            'phone_number' => $existingCourier->phone_number,
            'vehicle_type' => $this->faker->randomElement(['Motorcycle', 'Car']),
            'vehicle_plate' => $existingCourier->vehicle_plate,
            'level' => $this->faker->numberBetween(1, 5),
        ];

        // Act
        $response = $this->postJson('/api/couriers', $data);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'phone_number', 'vehicle_plate']);
    }

    public function test_can_show_courier_details(): void
    {
        // Create a dummy courier
        $existingCourier = Courier::factory()->create();

        // Act
        $response = $this->getJson('/api/couriers/' . $existingCourier->id);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $existingCourier->id,
            'name' => $existingCourier->name,
            'email' => $existingCourier->email,
            'phone_number' => $existingCourier->phone_number,
            'vehicle_type' => $existingCourier->vehicle_type,
            'vehicle_plate' => $existingCourier->vehicle_plate,
            'level' => $existingCourier->level,
        ]);
    }

    public function test_cannot_show_non_existing_courier_details(): void
    {
        // Act
        $response = $this->getJson('/api/couriers/9999');

        // Assert
        $response->assertStatus(404);
    }

    public function test_can_update_courier_details(): void
    {
        // Create a dummy courier
        $existingCourier = Courier::factory()->create();

        $data = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone_number' => $this->faker->unique()->phoneNumber(),
            'vehicle_type' => $this->faker->randomElement(['Motorcycle', 'Car']),
            'vehicle_plate' => strtoupper($this->faker->unique()->bothify('?? #### ??')),
            'level' => $this->faker->numberBetween(1, 5),
        ];

        // Act
        $response = $this->putJson('/api/couriers/' . $existingCourier->id, $data);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $existingCourier->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'vehicle_type' => $data['vehicle_type'],
            'vehicle_plate' => $data['vehicle_plate'],
            'level' => $data['level'],
        ]);

        // Verify in database
        $this->assertDatabaseHas('couriers', $data);
    }

    public function test_cannot_update_non_existing_courier_details(): void
    {
        // Act
        $response = $this->putJson('/api/couriers/9999', [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone_number' => $this->faker->unique()->phoneNumber(),
            'vehicle_type' => $this->faker->randomElement(['Motorcycle', 'Car']),
            'vehicle_plate' => strtoupper($this->faker->unique()->bothify('?? #### ??')),
            'level' => $this->faker->numberBetween(1, 5),
        ]);

        // Assert
        $response->assertStatus(404);
    }

    public function test_can_partially_update_courier_details(): void
    {
        // Arrange data
        $existingCourier = Courier::factory()->create();

        $data = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
        ];

        // Act
        $response = $this->patchJson('/api/couriers/' . $existingCourier->id, $data);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $existingCourier->id,
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        // Verify in database
        $this->assertDatabaseHas('couriers', $data);
    }

    public function test_cannot_update_courier_with_duplicate_fields(): void
    {
        // Create a dummy courier
        $existingCourier = Courier::factory()->create();
        $courier = Courier::factory()->create();

        $data = [
            'email' => $existingCourier->email,
            'phone_number' => $existingCourier->phone_number,
            'vehicle_plate' => $existingCourier->vehicle_plate,
        ];

        $response = $this->patchJson('/api/couriers/' . $courier->id, $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'phone_number', 'vehicle_plate']);
    }

    public function test_can_delete_courier(): void
    {
        // Create a dummy courier
        $existingCourier = Courier::factory()->create();

        // Act
        $response = $this->deleteJson("/api/couriers/{$existingCourier->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Courier deleted successfully'
            ]);

        $this->assertDatabaseMissing('couriers', [
            'id' => $existingCourier->id,
        ]);
    }

    public function test_cannot_delete_non_existing_courier(): void
    {
        // Act
        $response = $this->deleteJson('/api/couriers/9999');

        // Assert
        $response->assertStatus(404);
    }
}