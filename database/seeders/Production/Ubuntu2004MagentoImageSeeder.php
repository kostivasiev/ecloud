<?php

namespace Database\Seeders\Production;

use Database\Seeders\Images\Magento\Ubuntu2004MagentoCache;
use Database\Seeders\Images\Magento\Ubuntu2004MagentoDb;
use Database\Seeders\Images\Magento\Ubuntu2004MagentoWeb;
use Database\Seeders\Images\Magento\Ubuntu2004MagentoWebDb;
use Illuminate\Database\Seeder;

class Ubuntu2004MagentoImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(Ubuntu2004MagentoCache::class);
        $this->call(Ubuntu2004MagentoDb::class);
        $this->call(Ubuntu2004MagentoWeb::class);
        $this->call(Ubuntu2004MagentoWebDb::class);

    }
}
