<?php

namespace Database\Seeders;

use App\Models\Website;
use Illuminate\Database\Seeder;

class WebsiteSeeder extends Seeder
{
    public function run(): void
    {
        $sampleWebsites = [
            [
                'name' => 'PSCA Official Portal',
                'url' => 'https://psca.gop.pk',
                'requires_login' => false,
                'expected_text' => 'Punjab Safe Cities Authority',
                'is_active' => true,
            ],
            [
                'name' => 'Punjab E-Challan System',
                'url' => 'https://echallan.psca.gop.pk',
                'requires_login' => false,
                'expected_text' => 'Punjab Safe Cities Authority',
                'is_active' => true,
            ],
            [
                'name' => 'Government of the Punjab',
                'url' => 'https://punjab.gov.pk',
                'requires_login' => false,
                'expected_text' => 'Punjab',
                'is_active' => true,
            ],
            [
                'name' => 'Punjab Police Official',
                'url' => 'https://punjabpolice.gov.pk',
                'requires_login' => false,
                'expected_text' => 'Punjab Police',
                'is_active' => true,
            ],
            [
                'name' => 'PSCA Admin Application Portal',
                'url' => 'https://psca.gop.pk/login',
                'requires_login' => true,
                'login_url' => 'https://psca.gop.pk/login',
                'username' => 'admin@psca.gop.pk',
                'password' => 'SafeCity2026!',
                'username_field' => '#email',
                'password_field' => '#password',
                'submit_button' => '#submit',
                'expected_text' => 'Dashboard',
                'is_active' => true,
            ],
        ];

        foreach ($sampleWebsites as $data) {
            Website::firstOrCreate(
                ['url' => $data['url']],
                $data
            );
        }
    }
}
