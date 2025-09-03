<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Rank;

class RankSeeder extends Seeder
{
    public function run(): void {
        Rank::create([
            'name' => 'Bronce',
            'required_referrals' => 10,
            'reward_description' => 'Bono de $50.000 COP',
            'reward_amount' => 50000,
        ]);
        Rank::create([
            'name' => 'Plata',
            'required_referrals' => 25,
            'reward_description' => 'Bono de $150.000 COP',
            'reward_amount' => 150000,
        ]);
    }
}