<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AboutUs;

class AboutUsSeeder extends Seeder
{
    public function run(): void
    {
        AboutUs::updateOrCreate([
            'id' => 1
        ], [
            'title' => 'Holistic Wellness Center Lagos',
            'content' => 'Welcome to Optimal Consult, the leading wellness center in Lagos Nigeria. We believe in the power of natural healing and preventive healthcare. Our clinic provides a serene environment for your journey toward complete physical and mental well-being.' . "\n\n" . 'Whether you are looking for drug-free treatment in Lagos or simply want to optimize your health through natural methods, our team is here to guide you. We focus on treating the root cause of ailments, not just the symptoms.',
            'button_text' => 'Learn More',
            'button_link' => 'tel:08067131990',
        ]);
    }
}
