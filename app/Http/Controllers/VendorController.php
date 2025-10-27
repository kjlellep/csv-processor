<?php
namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Http\Requests\{StoreVendorRequest, UpdateVendorRequest};

class VendorController extends Controller
{
    public function index()
    {
        return Vendor::orderBy('name')->get();
    }

    public function show(Vendor $vendor)
    {
        return $vendor->load('rules');
    }

    public function store(StoreVendorRequest $request)
    {
        return response()->json(Vendor::create($request->validated()), 201);
    }

    public function update(UpdateVendorRequest $request, Vendor $vendor)
    {
        $vendor->update($request->validated()); return $vendor->fresh('rules');
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->delete(); return response()->noContent();
    }
}
