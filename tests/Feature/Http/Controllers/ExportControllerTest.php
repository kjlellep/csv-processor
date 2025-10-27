<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\ProcessedRow;
use App\Models\Vendor;
use App\Models\Upload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeVendor(array $over = []): Vendor
    {
        return Vendor::query()->forceCreate(array_merge([
            'name'           => 'V',
            'export_csv'     => true,
            'mappings'       => ['ProductName' => 'name', 'Quantity' => 'qty', 'Price' => 'price', 'SKU' => 'sku'],
            'export_columns' => [],
            'csv_delimiter'  => ',',
            'csv_enclosure'  => '"',
            'csv_escape'     => '\\',
            'has_header'     => true
        ], $over));
    }

    private function makeUpload($vendorId): Upload
    {
        return Upload::query()->forceCreate(array_merge([
            'vendor_id' => $vendorId,
            'original_filename' => 'file.csv',
            'rows_total' => 2,
            'status' => 'PROCESSED'
        ]));
    }

    private function linesToArrays(string $csv): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($csv));
        $rows  = [];
        foreach ($lines as $line) {
            if ($line === '') continue;
            $rows[] = str_getcsv($line, ',','"','\\');
        }
        return $rows;
    }

    public function testExportReturnsCsvWithDefaultColumnsAndRows(): void
    {
        $vendor = $this->makeVendor();
        $upload = $this->makeUpload($vendor->id);

        ProcessedRow::query()->forceCreate([
            'upload_id'    => $upload->id,
            'vendor_id'    => $vendor->id,
            'product_name' => 'Widget',
            'quantity'     => 2,
            'price'        => '12.40',
            'sku'          => '001',
            'raw_source'   => ['name'=>'Widget']
        ]);

        ProcessedRow::query()->forceCreate([
            'upload_id'    => $upload->id,
            'vendor_id'    => $vendor->id,
            'product_name' => 'Gadget',
            'quantity'     => null,
            'price'        => null,
            'sku'          => '002',
            'raw_source'   => ['name'=>'Gadget']
        ]);

        $res  = $this->get("/api/vendors/{$vendor->id}/export");
        $res->assertOk();

        $this->assertStringStartsWith('text/csv', $res->headers->get('Content-Type'));
        $this->assertSame('attachment; filename=export.csv', $res->headers->get('Content-Disposition'));

        $csv  = $res->streamedContent();
        $rows = $this->linesToArrays($csv);

        $this->assertSame(['ProductName','Quantity','Price','SKU'], $rows[0]);

        $data = [];
        for ($i = 1; $i < count($rows); $i++) {
            $data[$rows[$i][3]] = $rows[$i];
        }

        $this->assertArrayHasKey('001', $data);
        $this->assertArrayHasKey('002', $data);

        $this->assertSame(['Widget','2','12.40','001'], $data['001']);
        $this->assertSame(['Gadget','','','002'], $data['002']);
    }

    public function testExportHonorsExportColumnsAndFiltersNonCanonical(): void
    {
        $vendor = $this->makeVendor(['export_columns' => ['SKU','Foo','ProductName']]);
        $upload = $this->makeUpload($vendor->id);

        ProcessedRow::query()->forceCreate([
            'upload_id'    => $upload->id,
            'vendor_id'    => $vendor->id,
            'product_name' => 'Thing',
            'quantity'     => 5,
            'price'        => '9.99',
            'sku'          => 'X-1',
            'raw_source'   => []
        ]);

        $res  = $this->get("/api/vendors/{$vendor->id}/export");
        $res->assertOk();

        $rows = $this->linesToArrays($res->streamedContent());

        $this->assertSame(['SKU','ProductName'], $rows[0]);

        $this->assertSame(['X-1','Thing'], $rows[1]);
    }

    public function testExportCanBeFilteredByUploadId(): void
    {
        $vendor = $this->makeVendor();
        $upload1 = $this->makeUpload($vendor->id);
        $upload2 = $this->makeUpload($vendor->id);

        ProcessedRow::query()->forceCreate([
            'upload_id'    => $upload1->id,
            'vendor_id'    => $vendor->id,
            'product_name' => 'Keep',
            'quantity'     => 1,
            'price'        => '1.00',
            'sku'          => 'K-1',
            'raw_source'   => []
        ]);

        ProcessedRow::query()->forceCreate([
            'upload_id'    => $upload2->id,
            'vendor_id'    => $vendor->id,
            'product_name' => 'Skip',
            'quantity'     => 1,
            'price'        => '2.00',
            'sku'          => 'S-1',
            'raw_source'   => []
        ]);

        $res  = $this->get("/api/vendors/{$vendor->id}/export?upload_id={$upload1->id}");
        $res->assertOk();

        $rows = $this->linesToArrays($res->streamedContent());

        $this->assertCount(2, $rows);
        $this->assertSame(['ProductName','Quantity','Price','SKU'], $rows[0]);
        $this->assertSame(['Keep','1','1.00','K-1'], $rows[1]);
    }

    public function testExportDisabledVendorReturns403(): void
    {
        $vendor = $this->makeVendor(['export_csv' => false]);

        $res = $this->getJson("/api/vendors/{$vendor->id}/export");

        $res->assertStatus(403)
            ->assertJson([
                'error' => [
                    'status' => 403,
                    'code'   => 'forbidden',
                    'message'=> 'Export disabled for vendor'
                ]
            ]);
    }
}
