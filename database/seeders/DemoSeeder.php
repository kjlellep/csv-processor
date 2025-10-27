<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Vendor, VendorRule};

class DemoSeeder extends Seeder {
  public function run(): void {
    $vendor = Vendor::create([
      'name' => 'Foo',
      'export_csv' => true,
      'mappings' => [
        'ProductName' => 'ProductName',
        'Quantity'    => 'Quantity',
        'Price'       => 'Price',
        'SKU'         => 'SKU',
      ],
      'export_columns' => ['ProductName','Price','SKU', 'Quantity'],
    ]);

    VendorRule::create([
        'vendor_id'=>$vendor->id,
        'type'=>'MULTIPLY',
        'position'=>10,
        'target'=>'Price',
        'config'=>[
            'factor'=>1.24,'scale'=>2,'round'=>'half_up'
        ]
    ]);
    VendorRule::create([
        'vendor_id'=>$vendor->id,
        'type'=>'REGEX',
        'position'=>20,
        'target'=>'SKU',
        'config'=>[
            'pattern'=>'^[^0-9]+',
            'replacement'=>''
        ]
    ]);
    VendorRule::create([
        'vendor_id'=>$vendor->id,
        'type'=>'REMOVE',
        'position'=>30,
        'target'=>'Quantity',
        'config'=>[]
    ]);
  }
}
