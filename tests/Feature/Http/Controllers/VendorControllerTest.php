<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexListsVendorsOrderedByName(): void
    {
        Vendor::query()->forceCreate(['id'=>uuid_create(), 'name'=>'Zed']);
        Vendor::query()->forceCreate(['id'=>uuid_create(), 'name'=>'Alpha']);

        $res = $this->getJson('/api/vendors');

        $res->assertOk()
            ->assertJsonPath('0.name', 'Alpha')
            ->assertJsonPath('1.name', 'Zed');
    }

    public function testShowReturnsVendorWithRules(): void
    {
        $vendor = Vendor::query()->forceCreate([
            'id'=>uuid_create(),
            'name'=>'Test',
            'mappings'=>['ProductName'=>'name'],
            'export_columns'=>[],
            'csv_delimiter'=>',',
            'csv_enclosure'=>'"',
            'csv_escape'=>'\\',
            'has_header'=>true,
            'export_csv'=>true
        ]);

        $res = $this->getJson("/api/vendors/{$vendor->id}");

        $res->assertOk()
            ->assertJsonPath('id', $vendor->id)
            ->assertJsonStructure(['rules']);
    }

    public function testStoreCreatesVendorAndReturns201(): void
    {
        $payload = [
            'name' => 'New Vendor',
            'export_csv' => true,
            'mappings' => ['ProductName'=>'name','Price'=>'price'],
            'export_columns' => ['ProductName','Price'],
            'csv_delimiter' => ',',
            'csv_enclosure' => '"',
            'csv_escape' => '\\',
            'has_header' => true
        ];

        $res = $this->postJson('/api/vendors', $payload);

        $res->assertCreated()
            ->assertJsonPath('name', 'New Vendor');

        $this->assertDatabaseHas('vendors', ['name'=>'New Vendor']);
    }

    public function testUpdateModifiesVendor(): void
    {
        $vendor = Vendor::query()->forceCreate([
            'id'=>uuid_create(),
            'name'=>'Before',
            'csv_delimiter'=>',',
            'csv_enclosure'=>'"',
            'csv_escape'=>'\\',
            'has_header'=>true,
            'export_csv'=>true,
            'mappings'=>[],
            'export_columns'=>[]
        ]);

        $res = $this->putJson("/api/vendors/{$vendor->id}", ['name'=>'After']);

        $res->assertOk()
            ->assertJsonPath('name', 'After');

        $this->assertDatabaseHas('vendors', ['id'=>$vendor->id,'name'=>'After']);
    }

    public function testDestroyDeletesVendorAndReturns204(): void
    {
        $vendor = Vendor::query()->forceCreate([
            'id'=>uuid_create(),
            'name'=>'To delete',
            'csv_delimiter'=>',',
            'csv_enclosure'=>'"',
            'csv_escape'=>'\\',
            'has_header'=>true,
            'export_csv'=>true,
            'mappings'=>[],
            'export_columns'=>[]
        ]);

        $res = $this->deleteJson("/api/vendors/{$vendor->id}");

        $res->assertNoContent();
        $this->assertDatabaseMissing('vendors', ['id'=>$vendor->id]);
    }

    public function testStoreValidatesAndReturns422OnBadPayload(): void
    {
        $res = $this->postJson('/api/vendors', []);
        $res->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    }
}
