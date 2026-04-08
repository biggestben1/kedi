<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'title' => 'Naturopathic Clinic',
                'description' => 'Experience natural health care in Nigeria with our expert-led naturopathic treatments tailored to your body\'s specific needs.',
                'icon' => '🌿',
                'sort_order' => 1,
            ],
            [
                'title' => 'Massage Therapy',
                'description' => 'Premier massage therapy in Lagos designed for stress relief therapy and musculoskeletal relaxation using natural oils.',
                'icon' => '👐',
                'sort_order' => 2,
            ],
            [
                'title' => 'Hydrotherapy',
                'description' => 'Advanced hydrotherapy treatment in Nigeria to detoxify and improve circulation through therapeutic water applications.',
                'icon' => '💧',
                'sort_order' => 3,
            ],
            [
                'title' => 'Natural Pain Relief',
                'description' => 'Effective natural pain relief in Lagos for chronic conditions, migraines, and physical injuries without reliance on synthetic drugs.',
                'icon' => '✨',
                'sort_order' => 4,
            ],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
