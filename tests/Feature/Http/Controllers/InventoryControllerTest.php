<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\ProcessedRow;
use App\Models\Upload;
use App\Models\Vendor;
use App\Models\VendorRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class InventoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testStoreProcessesCsvAndCreatesProcessedRows(): void
    {
        $vendor = Vendor::query()->forceCreate([
            'name'          => 'Test Vendor',
            'csv_delimiter' => ',',
            'csv_enclosure' => '"',
            'csv_escape'    => '\\',
            'has_header'    => true,
            'mappings'      => [
                'ProductName' => 'name',
                'Quantity'    => 'qty',
                'Price'       => 'price',
                'SKU'         => 'sku'
            ]
        ]);

        VendorRule::query()->forceCreate([
            'vendor_id' => $vendor->id,
            'position'  => 10,
            'type'      => 'MULTIPLY',
            'target'    => 'Price',
            'config'    => ['factor' => 1.24, 'scale' => 2, 'round' => 'half_up'],
            'enabled'   => true
        ]);

        VendorRule::query()->forceCreate([
            'vendor_id' => $vendor->id,
            'position'  => 20,
            'type'      => 'REGEX',
            'target'    => 'SKU',
            'config'    => ['pattern' => '^SKU-', 'replacement' => '', 'flags' => ''],
            'enabled'   => true
        ]);

        $csv = <<<CSV
name,qty,price,sku
Widget,2,10,SKU-001
Gadget,,n/a,SKU-002
CSV;

        $file = UploadedFile::fake()->createWithContent('inventory.csv', $csv);

        $res = $this->postJson("/api/vendors/{$vendor->id}/inventory", ['file' => $file]);

        $res->assertCreated()
            ->assertJson([
                'vendor_id'   => $vendor->id,
                'filename'    => 'inventory.csv',
                'rows_total'  => 2,
                'rows_valid'  => 2,
                'rows_skipped'=> 0,
                'errors'      => []
            ]);

        $upload = Upload::first();
        $this->assertNotNull($upload);
        $this->assertSame('PROCESSED', $upload->status);
        $this->assertSame(2, $upload->rows_total);

        $this->assertDatabaseCount(ProcessedRow::class, 2);

        $first = ProcessedRow::query()->orderBy('id')->first();
        $this->assertSame($vendor->id, $first->vendor_id);
        $this->assertSame('Widget', $first->product_name);
        $this->assertSame(2, $first->quantity);
        $this->assertSame('12.40', $first->price);
        $this->assertSame('001', $first->sku);

        $second = ProcessedRow::query()->orderBy('id', 'desc')->first();
        $this->assertSame('Gadget', $second->product_name);
        $this->assertNull($second->quantity);
        $this->assertNull($second->price);
        $this->assertSame('002', $second->sku);
    }

    public function testStoreFailsWhenVendorHasNoHeader(): void
    {
        $vendor = Vendor::query()->forceCreate([
            'name'          => 'NoHeader Vendor',
            'csv_delimiter' => ',',
            'csv_enclosure' => '"',
            'csv_escape'    => '\\',
            'has_header'    => false,
            'mappings'      => [
                'ProductName' => 'name',
                'Quantity'    => 'qty',
                'Price'       => 'price',
                'SKU'         => 'sku'
            ]
        ]);

        $csv = <<<CSV
Widget,2,10,SKU-001
CSV;

        $file = UploadedFile::fake()->createWithContent('noheader.csv', $csv);

        $res = $this->postJson("/api/vendors/{$vendor->id}/inventory", ['file' => $file]);

        $res->assertStatus(400)
            ->assertJson([
                'error' => [
                    'status' => 400,
                    'code' => 'bad_request',
                    'message' => 'CSV without header not supported'
                ]
            ]);

        $upload = Upload::first();
        $this->assertNotNull($upload);
        $this->assertSame('PENDING', $upload->status);
        $this->assertNull($upload->processed_at);
    }
}
