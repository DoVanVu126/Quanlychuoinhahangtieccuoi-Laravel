<?php

namespace App\Http\Controllers;

use App\Models\SuggestionPackage;
use Illuminate\Http\Request;

class SuggestionPackageController extends Controller
{
    // GET /suggestion-packages or /suggestion-packages?restaurant_id=1
    public function index(Request $request)
    {
        $query = SuggestionPackage::query()->with(['foods', 'services', 'hall']);

        // filter by restaurant if provided
        if ($request->has('restaurant_id') && $request->restaurant_id) {
            $query->where('restaurant_id', $request->restaurant_id);
        }

        // filter by event type: match exact or packages that are generic (NULL)
        if ($request->filled('event_type')) {
            $etype = trim($request->query('event_type'));
            $query->where(function ($q) use ($etype) {
                $q->whereNull('event_type')->orWhere('event_type', $etype);
            });
        }

        // filter by number of tables: package.number_of_tables should be >= requested tables or NULL (no restriction)
        if ($request->filled('tables')) {
            $tables = (int) $request->query('tables');
            if ($tables > 0) {
                $query->where(function ($q) use ($tables) {
                    $q->whereNull('number_of_tables')->orWhere('number_of_tables', '>=', $tables);
                });
            }
        }

        $perPage = (int) $request->query('per_page', 20);
        $packages = $query->paginate($perPage);

        return response()->json($packages);
    }

    // GET /suggestion-packages/{id}
    public function show($id)
    {
        $pkg = SuggestionPackage::with(['foods', 'services', 'hall', 'restaurant'])->findOrFail($id);
        return response()->json($pkg);
    }

    // GET /restaurants/{id}/suggestion-packages
    public function byRestaurant(Request $request, $restaurantId)
    {
        $query = SuggestionPackage::with(['foods', 'services', 'hall'])
            ->where('restaurant_id', $restaurantId);

        if ($request->filled('event_type')) {
            $etype = trim($request->query('event_type'));
            $query->where(function ($q) use ($etype) {
                $q->whereNull('event_type')->orWhere('event_type', $etype);
            });
        }

        if ($request->filled('tables')) {
            $tables = (int) $request->query('tables');
            if ($tables > 0) {
                $query->where(function ($q) use ($tables) {
                    $q->whereNull('number_of_tables')->orWhere('number_of_tables', '>=', $tables);
                });
            }
        }

        $packages = $query->get();

        return response()->json($packages);
    }
}
