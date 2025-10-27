<?php
namespace App\Http\Controllers;

use App\Models\{Vendor, ProcessedRow};
use App\Support\Canonical;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function index(Request $request, Vendor $vendor)
    {
        abort_unless($vendor->export_csv, 403, 'Export disabled for vendor');

        $uploadId = $request->query('upload_id');
        $q = ProcessedRow::where('vendor_id',$vendor->id)
            ->when($uploadId, fn($qq)=>$qq->where('upload_id',$uploadId));

        $cols = array_values(array_filter(
            (array) $vendor->export_columns,
            fn ($col) => Canonical::isCanonical($col)
        ));

        if (empty($cols)) {
            $cols = Canonical::FIELDS;
        }

        return response()->streamDownload(function() use ($q, $cols) {
            $out = fopen('php://output','w');
            fputcsv($out, $cols);
            $q->orderBy('id')->chunk(1000, function($chunk) use ($out, $cols) {
                foreach ($chunk as $row) {
                    $canonical = [
                        'ProductName' => $row->product_name,
                        'Quantity'    => $row->quantity,
                        'Price'       => $row->price,
                        'SKU'         => $row->sku,
                    ];
                    $line = [];
                    foreach ($cols as $col) $line[] = $canonical[$col] ?? null;

                    fputcsv($out, $line);
                }
            });
            fclose($out);
        }, 'export.csv', [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="export.csv"',
        ]);
    }
}
