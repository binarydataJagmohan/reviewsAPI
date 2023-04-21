<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewLikes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use DB;
use App\Mail\ReviewMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;



class ReviewController extends Controller
{
    public function save_review(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'review_by' => 'required|string',
            'review_to' => 'required',
            'description' => 'required|string',
            'total_rating' => 'required|numeric|min:1|max:5',
            //'avg_rating' => 'required|numeric|min:1|max:5',
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
            // $review->thumbs_up = $request->thumbs_up;
            // $review->thumbs_down = $request->thumbs_down;
            // Calculate the average rating
            $total_ratings = Review::where('review_to', $request->review_to)->sum('total_rating');
            $num_of_ratings = Review::where('review_to', $request->review_to)->count();
            if ($num_of_ratings > 0) {
                $avg_rating = round($total_ratings / $num_of_ratings, 1);
            } else {
                $avg_rating = 0;
            }
            $review->avg_rating = $avg_rating;

            $save_review = $review->save();
            //  $adminEmail = 'dev3.bdpl@gmail.com';
            // Mail::to($adminEmail)->send(new ReviewMail());
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

    public function my_reviews(Request $request, $id)
    {
        $user = User::find($id);

        $reviews = DB::table('reviews')
            ->join('users', 'reviews.review_by', '=', 'users.id')
            ->where('reviews.review_to', '=', $id)
            ->select('reviews.*', 'users.first_name')
            ->get();
        return response()->json(['data' => $user, 'data' => $reviews]);
    }

    public function get_all_reviews(Request $request)
    {
        try {
            // $reviewsdata = Review::select('reviews.id as review_id', 'reviews.*', 'users.*')
            //     ->leftJoin('users', 'reviews.review_to', '=', 'users.id')
            //     ->where('reviews.status', '!=', 'deleted')
            //     ->latest('reviews.created_at')
            //     ->get();

            $reviewsdata = Review::select('reviews.id as review_id', 'reviews.*', 'users.first_name as review_to_name', 'reviewer.first_name as review_by_name', 'users.group_name', 'users.company_name', 'users.position_title')
            ->leftJoin('users', 'reviews.review_to', '=', 'users.id')
            ->leftJoin('users as reviewer', 'reviews.review_by', '=', 'reviewer.id')
            ->where('reviews.status', '!=', 'deleted')
            ->latest('reviews.created_at')
            ->get();

            if ($reviewsdata->count() > 0) {
                return response()->json(['status' => true, 'message' => "Reviews data fetched successfully", 'data' => $reviewsdata], 200);
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
            $reviews = Review::where('first_name', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('company_name', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('last_name', 'LIKE', '%' . $searchTerm . '%')
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
            $reviewsdata = Review::select('reviews.id as review_id', 'reviews.*', 'users.*')
                ->leftJoin('users', 'reviews.review_to', '=', 'users.id')
                ->where('reviews.status', '!=', 'deleted')
                ->orderBy('thumbs_up', 'desc')->get();
            if ($reviewsdata->count() > 0) {
                return response()->json(['status' => true, 'message' => "Most liked reviews retrieved successfully", 'data' => $reviewsdata], 200);
            } else {
                return response()->json(['status' => false, 'message' => "No reviews data found", 'data' => ""], 200);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }


    public function like(Request $request)
    {
        $user = User::where('id', $request->userId)->first();
        // check if the user has already liked/disliked the review
        $existing_like = ReviewLikes::where(['user_id' => $user->id, 'review_id' => $request->reviewId])->first();
        if ($existing_like) {
            if ($existing_like->like_status == $request->isLiked) {
                $existing_like->delete();
                $current_likes = ReviewLikes::where(['review_id' => $request->reviewId, 'like_status' => 1])->count();
                Review::where('id', $request->reviewId)->update(['thumbs_up' => $current_likes]);
                $current_dislikes = ReviewLikes::where(['review_id' => $request->reviewId, 'like_status' => 0])->count();
                Review::where('id', $request->reviewId)->update(['thumbs_down' => $current_dislikes]);
            } else {
                // if the user has already liked/disliked the review, update the existing record
                $existing_like->like_status = $request->isLiked;
                $existing_like->save();
                $current_likes = ReviewLikes::where(['review_id' => $request->reviewId, 'like_status' => 1])->count();
                Review::where('id', $request->reviewId)->update(['thumbs_up' => $current_likes]);
                $current_dislikes = ReviewLikes::where(['review_id' => $request->reviewId, 'like_status' => 0])->count();
                Review::where('id', $request->reviewId)->update(['thumbs_down' => $current_dislikes]);
            }
        } else {
            // if the user hasn't liked/disliked the review yet, create a new record
            $like = new ReviewLikes();
            $like->user_id = $user->id;
            $like->review_id = $request->reviewId;
            $like->like_status = $request->isLiked;
            $like->save();
        }
        $review = Review::where('id', $request->reviewId)->first();
        if ($request->isLiked === 1) {
            $current_likes = ReviewLikes::where(['review_id' => $request->reviewId, 'like_status' => 1])->count();
            Review::where('id', $request->reviewId)->update(['thumbs_up' => $current_likes]);
        } else {
            $current_dislikes = ReviewLikes::where(['review_id' => $request->reviewId, 'like_status' => 0])->count();
            Review::where('id', $request->reviewId)->update(['thumbs_down' => $current_dislikes]);
        }
        
        $thumbsUp = Review::where('review_to', $request->userId)->sum('thumbs_up');
        $thumbsDown = Review::where('review_to', $request->userId)->sum('thumbs_down');
        $difference = $thumbsUp - $thumbsDown;
        
        $total = $thumbsUp + $thumbsDown;
        return $total;
        
        if ($total > 0) {
            $per = intval(min(100, abs($difference) * 100 / $total));
        } else {
            $per = 0;
        }
        
        $user->bunjee_score = $per;
        $user->save();
        
        $reviews = Review::join('users', 'users.id', 'reviews.review_to', 'reviews.id')
            ->select('reviews.*', 'users.*')
            ->where('review_to', $review->review_to)
            ->get();
        return response()->json(['message' => 'Like saved successfully', 'data' => $reviews, 'difference' => $difference, 'per' => $per]);
    }

    public function new_user_review(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'company_name' => 'required|string',
            'position_title' => 'required|string',
            'group_name' => 'required|string',
            'review_by' => 'required|string',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        //return response()->json($request->all());
        try {
            $user = new User();
            $user->first_name = $request->input('first_name');
            $user->last_name = $request->input('last_name');
            $user->company_name = $request->input('company_name');
            $user->position_title = $request->input('position_title');
            $user->group_name = $request->input('group_name');
            $user->save();

            $review = new Review();
            $review->review_by = $request->review_by;
            $review->review_to = $user->id; // Use the ID of the newly created user
            $review->description = $request->input('description');
            $review->total_rating = $request->input('rating');

            // Calculate the average rating
            $total_ratings = Review::where('review_to', $request->review_to)->sum('total_rating');
            $num_of_ratings = Review::where('review_to', $request->review_to)->count();
            if ($num_of_ratings > 0) {
                $avg_rating = round($total_ratings / $num_of_ratings, 1);
            } else {
                $avg_rating = 0;
            }
            $review->avg_rating = $avg_rating;
            $review->save();
            return response()->json([
                'status' => true,
                'message' => 'Review submitted successfully',
                'data' => $review
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => "There has been an error submitting the review."], 500);
        }
    }

    // public function getlikedislikes(Request $request, $user_id)
    // {
    //     $userId = $request->input('userId');
    //     // Get the total thumbs up for the given user
    //     $thumbsUp = Review::where('review_to', $user_id)->sum('thumbs_up');
    //     // Get the total thumbs down for the given user
    //     $thumbsDown = Review::where('review_to', $user_id)->sum('thumbs_down');
    //     // Calculate the difference between thumbs up and thumbs down
    //     $difference = $thumbsUp - $thumbsDown;
    //     $per = 0;
    //     $total = $thumbsUp + $thumbsDown;
    //     if ($total > 0) {
    //         $per = intval(min(100, abs($difference) * 100 / $total));
    //     }
    //     return response()->json(['difference' => $difference, 'per' => $per]);
    // }

}

