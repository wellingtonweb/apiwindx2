<?php

namespace Database\Seeders;

use App\Models\Terminal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::create([
            'name' => 'Api Sandbox',
            'email' => 'apiprod@windx.com.br',
            'email_verified_at' => now(),
            'password' => bcrypt('pVWZMmVeZrc9J#f!fF*1'), // password
            'remember_token' => Str::random(10),
        ]);
        /*
        Terminal::create([
            "name" => "WINDXPDV",
            "authorization_key" => (Str::uuid())->toString(),
            "ip_address" => "127.0.0.2",
            "remote_id" => "2134561123",
            "remote_password" => "dfasdf123",
            "active" => true,
            "responsible_name" => "Wellington",
            "contact_primary" => "28999489262",
            "contact_secondary" => null,
            "street" => "Avenida Simão Soares",
            "number" => null,
            "complement" => "SEDE WINDX",
            "district" => "Barra do Itapemirim",
            "city" => "Marataízes",
            "state" => "ES",
            "zipcode" => "29378000",
            "paygo_id" => "992",
            "paygo_login" => "807067",
            "paygo_password" => "D82E31A5"
        ]);
        */
    }
}
