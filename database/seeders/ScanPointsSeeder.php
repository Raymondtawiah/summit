<?php

namespace Database\Seeders;

use App\Models\ScanPoint;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ScanPointsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $scanPoints = [
            ['name' => 'Main Entrance', 'location' => 'Main Hall Entry', 'description' => 'Primary entry point for summit attendees', 'status' => 'active'],
            ['name' => 'Bus Boarding', 'location' => 'Parking Lot B', 'description' => 'Bus departure and arrival point', 'status' => 'active'],
            ['name' => 'Accommodation', 'location' => 'Residence Hall', 'description' => 'Check-in for overnight accommodations', 'status' => 'active'],
            ['name' => 'Meals', 'location' => 'Dining Hall', 'description' => 'Meal service entry and exit', 'status' => 'active'],
            ['name' => 'Main Hall', 'location' => 'Main Auditorium', 'description' => 'General session attendance', 'status' => 'active'],
            ['name' => 'Workshop A', 'location' => 'Room 101', 'description' => 'Workshop session A', 'status' => 'active'],
            ['name' => 'Workshop B', 'location' => 'Room 102', 'description' => 'Workshop session B', 'status' => 'active'],
            ['name' => 'Closing Session', 'location' => 'Main Auditorium', 'description' => 'Final summit session', 'status' => 'active'],
        ];

        foreach ($scanPoints as $scanPoint) {
            ScanPoint::updateOrCreate(
                ['name' => $scanPoint['name']],
                $scanPoint
            );
        }
    }
}
