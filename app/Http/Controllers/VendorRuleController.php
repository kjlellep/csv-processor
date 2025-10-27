<?php
namespace App\Http\Controllers;

use App\Models\{Vendor, VendorRule};
use App\Http\Requests\{StoreRuleRequest, UpdateRuleRequest};
use Illuminate\Http\Request;

class VendorRuleController extends Controller
{
    public function index(Vendor $vendor)
    {
        return $vendor->rules;
    }

    public function store(StoreRuleRequest $r, Vendor $vendor)
    {
        $data = $r->validated(); $data['vendor_id'] = $vendor->id;
        return response()->json(VendorRule::create($data), 201);
    }

    public function update(UpdateRuleRequest $r, Vendor $vendor, VendorRule $rule)
    {
        abort_unless($rule->vendor_id === $vendor->id, 404);
        $rule->update($r->validated()); return $rule->fresh();
    }

    public function destroy(Vendor $vendor, VendorRule $rule)
    {
        abort_unless($rule->vendor_id === $vendor->id, 404);
        $rule->delete(); return response()->noContent();
    }

    public function reorder(Request $r, Vendor $vendor)
    {
        $ids = $r->validate(['ids'=>'required|array|min:1','ids.*'=>'uuid'])['ids'];
        foreach ($ids as $i=>$id) VendorRule::where('vendor_id',$vendor->id)->where('id',$id)->update(['position'=>$i]);
        return $vendor->rules()->get();
    }
}
