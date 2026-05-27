<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreCourierRequest;
use App\Http\Requests\UpdateCourierRequest;
use App\Http\Resources\CourierResource;
use App\Models\Courier;

class CourierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // build query with filters
        $query = Courier::query();

        // search by name (fuzzy match)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $keywords = explode(' ', $search);

            $query->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->where('name', 'like', '%' . $word . '%');
                }
            });
        }

        // filter by level (comma-separated: ?level=2,3)
        if ($request->filled('level')) {
            $levels = explode(',', $request->input('level'));
            $levels = array_map('trim', $levels);
            $query->whereIn('level', $levels);
        }

        // sorting (default by name, override with ?sort=created_at or ?sort=joined_at)
        $sortBy = $request->input('sort', 'name');
        $sortBy = in_array($sortBy, ['name', 'created_at', 'joined_at', 'level']) 
            ? $sortBy 
            : 'name';
        $query->orderBy($sortBy, 'asc');

        // pagination (15 per page, changeable with ?per_page=25)
        $perPage = $request->input('per_page', 15);
        $couriers = $query->paginate($perPage);

        return CourierResource::collection($couriers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCourierRequest $request)
    {
        // create a new courier
        $courier = Courier::create($request->validated());
        return new CourierResource($courier);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // show details of a courier
        $courier = Courier::findOrFail($id);
        return new CourierResource($courier);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCourierRequest $request, string $id)
    {
        // update a courier
        $courier = Courier::findOrFail($id);
        $courier->update($request->validated());
        return new CourierResource($courier);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // delete a courier
        $courier = Courier::findOrFail($id);
        $courier->delete();
        return response()->json(['message' => 'Courier deleted successfully']);
    }
}
