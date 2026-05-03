<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\OccurrenceCategoryRequest;
use App\Http\Resources\OccurrenceCategoryResource;
use Illuminate\Support\Facades\DB;
use App\Models\Rating;
use App\Models\RatingRequest;
use App\Models\CriteriaRating;
use App\Models\InventoryOrder;
use Carbon\Carbon;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

class RatingController extends Controller
{

    public function rate(Request $request, $rateableType, $rateableId)
    {
        // Fetch the predefined criteria based on the rateableType (e.g., 'order', 'product')
        $criteria = CriteriaRating::where('type', $rateableType)->get(); 

        // Validate that the criteria submitted is in the predefined set
        $criteriaIds = $criteria->pluck('id')->toArray();

        // Validate input: Ensure the criteria submitted is in the predefined set
        $request->validate([
            'criteria' => 'required|array',
            'criteria.*' => 'in:' . implode(',', $criteriaIds), // Ensure all criterion IDs are valid
            'review' => 'nullable|string|max:1000', // Optional review text
        ]);

        // Check if the rateable model is valid
        $rateableModel = $this->getRateableModel($rateableType, $rateableId);
        if (!$rateableModel) {
            return response()->json(['error' => 'Invalid model for rating.'], 400);
        }

        // Check if the user has already rated this item
        $existingRating = Rating::where('rateable_id', $rateableId)
            ->where('rateable_type', $rateableType)
            ->where('user_id', auth()->id)
            ->first();

        if ($existingRating) {
            return response()->json(['error' => 'You have already rated this item.'], 400);
        }

        // Create the rating with predefined criteria
        $ratingData = [];
        foreach ($criteria as $criterion) {
            $ratingData[$criterion->name] = $request->criteria[$criterion->id] ?? null;
        }

        $rating = Rating::create([
            'user_id' => auth()->id,
            'rateable_type' => $rateableType,
            'rateable_id' => $rateableId,
            'criteria' => $ratingData, // Store the ratings for each predefined criterion
            'review' => $request->review,
        ]);

        // In the RatingController after submitting the rating
        $ratingRequest = RatingRequest::where('user_id', auth()->id)
            ->where('rateable_type', $rateableType)
            ->where('rateable_id', $rateableId)
            ->first();

        if ($ratingRequest) {
            $ratingRequest->status = 'completed';
            $ratingRequest->save();
        }

        // return response()->json(['message' => 'Thank you for your feedback!', 'rating' => $rating], 200);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ]
        ]);

    }

    /**
     * Show the form for creating a new resource.
     *
     */

    public function create($rateableType, $rateableId)
    {
        abort_if( !auth()->user()->can('add_occurrence_categories'), 403, '');

        // Fetch the predefined criteria for the specific rateableType (order or product)
        $criteria = CriteriaRating::where('type', $rateableType)->get();

        return Inertia::render('RateForm', [
            'criteria' => $criteria,
            'rateableType' => $rateableType,
            'rateableId' => $rateableId,
        ]);
    }


    private function getRateableModel($type, $id)
    {
        switch ($type) {
            case 'order':
                return InventoryOrder::find($id);
            // Add more cases for other models if needed
            default:
                return null;
        }
    }
    
     

    /**
     * Display the specified resource.
     *
     */
    public function show($id)
    {
        //
    }

}
