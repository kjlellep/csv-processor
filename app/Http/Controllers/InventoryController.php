<?php
namespace App\Http\Controllers;

use App\Models\{Vendor, Upload, ProcessedRow};
use App\Services\RuleEngine;
use Illuminate\Http\Request;
use League\Csv\Reader;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function store(Request $request, Vendor $vendor, RuleEngine $engine)
    {
        $request->validate(['file'=>'required|file|mimes:csv,txt']);
        $file = $request->file('file');

        $upload = Upload::create([
            'vendor_id' => $vendor->id,
            'original_filename' => $file->getClientOriginalName(),
            'status' => 'PENDING',
        ]);

        $rules = $vendor->rules()->orderBy('position')->get();
        $rowsTotal=0;
        $rowsValid=0;
        $rowsSkipped=0;
        $errors=[];

        DB::transaction(function () use ($file, $vendor, $engine, $rules, $upload, &$rowsTotal, &$rowsValid, &$rowsSkipped, &$errors) {
            $csv = Reader::createFromPath($file->getRealPath());
            $csv->setDelimiter($vendor->csv_delimiter);
            $csv->setEnclosure($vendor->csv_enclosure);
            $csv->setEscape($vendor->csv_escape);
            $csv->setHeaderOffset($vendor->has_header ? 0 : null);

            if (!$vendor->has_header) abort(400, 'CSV without header not supported');

            foreach ($csv->getRecords() as $i => $assoc) {
                $rowsTotal++;
                try {
                    $row = $engine->mapToCanonical($assoc, $vendor);
                    $row = $engine->apply($row, $rules);

                    ProcessedRow::create([
                        'upload_id'    => $upload->id,
                        'vendor_id'    => $vendor->id,
                        'product_name' => $row['ProductName'] ?? null,
                        'quantity'     => (isset($row['Quantity']) && $row['Quantity'] !== '') ? (int) $row['Quantity'] : null,
                        'price'        => isset($row['Price']) && is_numeric($row['Price']) ? $row['Price'] : null,
                        'sku'          => $row['SKU'] ?? null,
                        'raw_source'   => $assoc,
                    ]);

                    $rowsValid++;
                } catch (\Throwable $e) {
                    $rowsSkipped++; $errors[] = ['row'=>$i+1,'message'=>$e->getMessage()];
                }
            }

            $upload->update([
                'rows_total'=>$rowsTotal,
                'processed_at'=>now(),
                'status'=>empty($errors)?'PROCESSED':'FAILED',
                'error_message'=>empty($errors)?null:substr(json_encode($errors),0,2000),
            ]);
        });

        return response()->json([
            'upload_id'=>$upload->id,
            'vendor_id'=>$vendor->id,
            'filename'=>$upload->original_filename,
            'rows_total'=>$rowsTotal,
            'rows_valid'=>$rowsValid,
            'rows_skipped'=>$rowsSkipped,
            'errors'=>$errors,
        ], 201);
    }
}
