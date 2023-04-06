<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Auth;


class ReviewController extends Controller
{
    public function save_review(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'review_by' => 'required|string',
            'review_to' => 'required|string',
            'description' => 'required|string',
            'total_rating' => 'required|numeric|min:1|max:5',
            'avg_rating' => 'required|numeric|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        // Check if user try to rate themselves
         if ($request->review_by == $request->review_to) {
        return response()->json([
            'status' => false,
            'message' => 'You cannot rate yourself',
        ], 422);
    }

      // Check if user has already reviewed this person
    $existing_review = Review::where('review_by', $request->review_by)
        ->where('review_to', $request->review_to)
        ->first();

    if ($existing_review) {
        return response()->json([
            'status' => false,
            'message' => 'You have already reviewed this person',
        ], 422);
    }
        try {
            //return $request->input();
            $review = new Review();
            $review->review_by = $request->review_by;
            $review->review_to = $request->review_to;
            $review->description = $request->description;
            $review->total_rating = $request->total_rating;
            $review->avg_rating = $request->avg_rating;
            $review->thumbs_up = $request->thumbs_up;
            $review->thumbs_down = $request->thumbs_down;
            $save_review = $review->save();
            if ($save_review) {
                return response()->json([
                    'status' => true,
                    'message' => 'Reviews submit successfully',
                ]);
            } else {
                return response()->json(['status' => true, 'message' => "There has been error for to submit review."], 404);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function get_all_reviews(Request $request)
    {
        try {
            $reviewsdata = Review::where('status', '!=', 'deleted')
                ->get();
            if ($reviewsdata->count() > 0) {
                return response()->json(['status' => true, 'message' => "reviews data fetch successfully", 'data' => $reviewsdata], 200);
            } else {
                return response()->json(['status' => false, 'message' => "No reviews data found", 'data' => ""], 200);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function delete_reviews(Request $request)
    {
        try {
            $review = Review::where('id', $request->id)->update([
                'status' => 'deleted'
            ]);
            if ($review) {
                return response()->json(['status' => true, 'message' => 'reviews has been deleted successfully!'], 200);
            } else {
                return response()->json(['erroe' => false, 'message' => 'There has been error for updating the status of single reviews data!'], 200);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function get_review_by_id($id)
    {
        try {
            $review = Review::where('id', $id)->where('status', '!=', 'deleted')->first();
            if ($review) {
                return response()->json([
                    'status' => true,
                    'message' => "Review data fetched successfully",
                    'data' => $review,
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => "Review not found",
                    'data' => null,
                ], 404);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function get_all_review_by($id)
    {
        try {
            $reviews = Review::where('review_by', $id)->where('status', '!=', 'deleted')->get();
            if ($reviews->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => "No reviews found for review_by ID {$id}",
                    'data' => null,
                ], 404);
            }
            return response()->json([
                'status' => true,
                'message' => "Reviews data fetched successfully",
                'data' => $reviews,
            ], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function get_all_review_to($id)
    {
        try {
            $reviews = Review::where('review_to', $id)->where('status', '!=', 'deleted')->get();
            if ($reviews->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => "No reviews found for review_to ID {$id}",
                    'data' => null,
                ], 404);
            }
            return response()->json([
                'status' => true,
                'message' => "Reviews data fetched successfully",
                'data' => $reviews,
            ], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function search_all_reviews(Request $request)
{
    try {
        $searchTerm = $request->input('search_term');

        // Search for reviews that match the search term in the name, company, or group fields
        $reviews = Review::where('name', 'LIKE', '%'.$searchTerm.'%')
                         ->orWhere('company', 'LIKE', '%'.$searchTerm.'%')
                         ->orWhere('group', 'LIKE', '%'.$searchTerm.'%')
                         ->get();

        return response()->json([
            'status' => true,
            'message' => 'Search results',
            'data' => $reviews,
        ]);
    } catch (\Exception $e) {
        throw new HttpException(500, $e->getMessage());
    }
}


    public function most_recent_reviews(Request $request)
    {
        try {
            $num_reviews = $request->input('num_reviews', 10); // default to 10 if not provided
            $reviews = Review::where('status', '!=', 'deleted')
                ->orderBy('created_at', 'desc')
                ->take($num_reviews)
                ->get();
            if ($reviews->isEmpty()) {
                return response()->json(['status' => false, 'message' => 'No reviews found']);
            } else {
                return response()->json(['status' => true, 'message' => 'Most recent reviews fetched successfully', 'data' => $reviews]);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function most_liked_reviews(Request $request)
    {
        try {
            $reviews = Review::orderBy('thumbs_up', 'desc')->take(10)->get();
            return response()->json([
                'status' => true,
                'message' => 'Most liked reviews retrieved successfully',
                'data' => $reviews,
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
}





