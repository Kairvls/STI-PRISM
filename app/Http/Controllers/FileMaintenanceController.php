<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\ItemCategory;
use App\Models\ItemSubCategory;
use App\Models\Uom;
use Illuminate\Http\Request;

class FileMaintenanceController extends Controller
{
    public const TABS = ['brands', 'uom', 'categories', 'subcategories'];

    public function index(Request $request)
    {
        $tab = $request->query('tab', 'brands');
        if (! in_array($tab, self::TABS, true)) {
            $tab = 'brands';
        }

        $data = [
            'tab' => $tab,
            'brands' => null,
            'uoms' => null,
            'categories' => null,
            'subcategories' => null,
            'parentCategories' => null,
            'summary' => [],
        ];

        if ($tab === 'brands') {
            $query = Brand::query();

            if ($request->filled('search')) {
                $query->where('brand_name', 'LIKE', '%' . $request->search . '%');
            }

            if ($request->filled('status')) {
                $query->where('brand_status', $request->status);
            }

            $data['brands'] = $query->orderBy('brand_name')->paginate(10)->withQueryString();
            $data['summary'] = [
                'total' => Brand::count(),
                'active' => Brand::where('brand_status', 'Active')->count(),
                'inactive' => Brand::where('brand_status', 'Inactive')->count(),
            ];
        }

        if ($tab === 'uom') {
            $query = Uom::query();

            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('uom_name', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('uom_description', 'LIKE', '%' . $request->search . '%');
                });
            }

            $data['uoms'] = $query->orderBy('uom_name')->paginate(10)->withQueryString();
            $data['summary'] = [
                'total' => Uom::count(),
            ];
        }

        if ($tab === 'categories') {
            $query = ItemCategory::query()->withCount('subcategories');

            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('item_category_name', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('item_category_description', 'LIKE', '%' . $request->search . '%');
                });
            }

            if ($request->filled('status')) {
                $query->where('item_category_status', $request->status);
            }

            $data['categories'] = $query->orderBy('item_category_name')->paginate(10)->withQueryString();
            $data['summary'] = [
                'total' => ItemCategory::count(),
                'active' => ItemCategory::where('item_category_status', 'Active')->count(),
                'inactive' => ItemCategory::where('item_category_status', 'Inactive')->count(),
            ];
        }

        if ($tab === 'subcategories') {
            $query = ItemSubCategory::query()
                ->leftJoin('item_categories_table', 'item_subcategories_table.item_category_id', '=', 'item_categories_table.item_category_id')
                ->select(
                    'item_subcategories_table.*',
                    'item_categories_table.item_category_name'
                );

            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('item_subcategories_table.item_subcategory_name', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('item_subcategories_table.item_subcategory_description', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('item_categories_table.item_category_name', 'LIKE', '%' . $request->search . '%');
                });
            }

            if ($request->filled('status')) {
                $query->where('item_subcategories_table.item_subcategory_status', $request->status);
            }

            if ($request->filled('category_id')) {
                $query->where('item_subcategories_table.item_category_id', $request->category_id);
            }

            $data['subcategories'] = $query
                ->orderBy('item_categories_table.item_category_name')
                ->orderBy('item_subcategories_table.item_subcategory_name')
                ->paginate(10)
                ->withQueryString();

            $data['parentCategories'] = ItemCategory::orderBy('item_category_name')->get();
            $data['summary'] = [
                'total' => ItemSubCategory::count(),
                'active' => ItemSubCategory::where('item_subcategory_status', 'Active')->count(),
                'inactive' => ItemSubCategory::where('item_subcategory_status', 'Inactive')->count(),
            ];
        }

        return view('purchaser.file-maintenance.index', $data);
    }
}
