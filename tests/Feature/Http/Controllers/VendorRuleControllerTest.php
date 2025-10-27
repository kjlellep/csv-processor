<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Vendor;
use App\Models\VendorRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorRuleControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeVendor(string $name='V'): Vendor
    {
        return Vendor::query()->forceCreate([
            'id'=>uuid_create(),
            'name'=>$name,
            'export_csv'=>true,
            'mappings'=>['Price'=>'price'],
            'export_columns'=>['Price'],
            'csv_delimiter'=>',',
            'csv_enclosure'=>'"',
            'csv_escape'=>'\\',
            'has_header'=>true
        ]);
    }

    public function testIndexListsVendorRules(): void
    {
        $vendor = $this->makeVendor();
        VendorRule::query()->forceCreate([
            'id'=>uuid_create(),
            'vendor_id'=>$vendor->id,
            'position'=>10,
            'type'=>'MULTIPLY',
            'target'=>'Price',
            'config'=>['factor'=>1.2],
            'enabled'=>true
        ]);

        $res = $this->getJson("/api/vendors/{$vendor->id}/rules");

        $res->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.type', 'MULTIPLY');
    }

    public function testStoreCreatesRuleForVendor(): void
    {
        $vendor = $this->makeVendor();

        $payload = [
            'position'=>10,
            'type'=>'REGEX',
            'target'=>'SKU',
            'config'=>['pattern'=>'^SKU-','replacement'=>''],
            'enabled'=>true
        ];

        $res = $this->postJson("/api/vendors/{$vendor->id}/rules", $payload);

        $res->assertCreated()
            ->assertJsonPath('vendor_id', $vendor->id)
            ->assertJsonPath('type', 'REGEX');

        $this->assertDatabaseHas('vendor_rules', ['vendor_id'=>$vendor->id,'type'=>'REGEX']);
    }

    public function testUpdateEditsRule(): void
    {
        $vendor = $this->makeVendor();
        $rule = VendorRule::query()->forceCreate([
            'id'=>uuid_create(),
            'vendor_id'=>$vendor->id,
            'position'=>5,
            'type'=>'REMOVE',
            'target'=>'Price',
            'config'=>[],
            'enabled'=>true
        ]);

        $res = $this->putJson("/api/vendors/{$vendor->id}/rules/{$rule->id}", [
            'target'=>'SKU','enabled'=>false
        ]);

        $res->assertOk()
            ->assertJsonPath('target', 'SKU')
            ->assertJsonPath('enabled', false);

        $this->assertDatabaseHas('vendor_rules', ['id'=>$rule->id,'target'=>'SKU','enabled'=>false]);
    }

    public function testUpdateRejectsRuleFromDifferentVendorWith404(): void
    {
        $vendor1 = $this->makeVendor('A');
        $vendor2 = $this->makeVendor('B');
        $rule = VendorRule::query()->forceCreate([
            'id'=>uuid_create(),
            'vendor_id'=>$vendor2->id,
            'position'=>1,
            'type'=>'REMOVE',
            'target'=>'Price',
            'config'=>[],
            'enabled'=>true
        ]);

        $res = $this->putJson("/api/vendors/{$vendor1->id}/rules/{$rule->id}", ['target'=>'SKU']);

        $res->assertStatus(404);
    }

    public function testDestroyDeletesRule(): void
    {
        $vendor = $this->makeVendor();
        $rule = VendorRule::query()->forceCreate([
            'id'=>uuid_create(),
            'vendor_id'=>$vendor->id,
            'position'=>3,
            'type'=>'MULTIPLY',
            'target'=>'Price',
            'config'=>['factor'=>2],
            'enabled'=>true
        ]);

        $res = $this->deleteJson("/api/vendors/{$vendor->id}/rules/{$rule->id}");

        $res->assertNoContent();
        $this->assertDatabaseMissing('vendor_rules', ['id'=>$rule->id]);
    }

    public function testDestroyRejectsRuleFromDifferentVendorWith404(): void
    {
        $vendor1 = $this->makeVendor('A');
        $vendor2 = $this->makeVendor('B');
        $rule = VendorRule::query()->forceCreate([
            'id'=>uuid_create(),
            'vendor_id'=>$vendor2->id,
            'position'=>9,
            'type'=>'REMOVE',
            'target'=>'Price',
            'config'=>[],
            'enabled'=>true
        ]);

        $res = $this->deleteJson("/api/vendors/{$vendor1->id}/rules/{$rule->id}");

        $res->assertStatus(404);
    }

    public function testReorderUpdatesPositions(): void
    {
        $vendor = $this->makeVendor();
        $rule1 = VendorRule::query()->forceCreate([
            'id'=>uuid_create(),
            'vendor_id'=>$vendor->id,
            'position'=>0,
            'type'=>'REMOVE',
            'target'=>'Price',
            'config'=>[],
            'enabled'=>true
        ]);
        $rule2 = VendorRule::query()->forceCreate([
            'id'=>uuid_create(),
            'vendor_id'=>$vendor->id,
            'position'=>1,
            'type'=>'MULTIPLY',
            'target'=>'Price',
            'config'=>['factor'=>2],
            'enabled'=>true
        ]);
        $rule3 = VendorRule::query()->forceCreate([
            'id'=>uuid_create(),
            'vendor_id'=>$vendor->id,
            'position'=>2,
            'type'=>'REGEX',
            'target'=>'SKU',
            'config'=>['pattern'=>'^SKU-','replacement'=>''],
            'enabled'=>true
        ]);

        $ids = [$rule3->id, $rule1->id, $rule2->id];

        $res = $this->postJson("/api/vendors/{$vendor->id}/rules/reorder", ['ids'=>$ids]);

        $res->assertOk();

        $this->assertDatabaseHas('vendor_rules', ['id'=>$rule3->id,'position'=>0]);
        $this->assertDatabaseHas('vendor_rules', ['id'=>$rule1->id,'position'=>1]);
        $this->assertDatabaseHas('vendor_rules', ['id'=>$rule2->id,'position'=>2]);
    }
}
