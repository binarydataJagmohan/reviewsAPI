<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;
use DB;
use Tymon\JWTAuth\Facades\JWTAuth;



class UserController extends Controller
{

    public function user_register(Request $request)
        {
            try {
                $validator = Validator::make($request->all(), [
                    'first_name' => 'required|string|min:2|max:255',
                    'last_name' => 'required|string|max:255',
                    'email' => 'required|email|max:255|unique:users',
                    'password' => 'required|min:8|confirmed',
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Validation error',
                        'errors' => $validator->errors(),
                    ]);
                } else {
                    $data = $request->all();
                    $data['password'] = Hash::make($request->password);
                    $data['view_password'] = $request->password;
                    $user = new User();
                    $register  = $user->create($data);

                    if ($register) {
                        $token = JWTAuth::fromUser($register);

                        $register->makeHidden(['view_password']);

                        return response()->json([
                            'status' => true,
                            'message' => 'Registration has been done successfully',
                            'user' => $register,
                            'token' => $token,
                        ]);
                    } else {
                        return response()->json(['message' => "'There has been error for to register the user"]);
                    }
                }
            } catch (\Exception $e) {
                throw new HttpException(500, $e->getMessage());
            }
        }
        public function user_login(Request $request)
        {
            try {
                $request->validate([
                    'email' => 'required|email',
                    'password' => 'required',
                ]);

                $email = $request->input('email');
                $password = $request->input('password');

                $user = User::where('email', $email)->first();

                if (!$user) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Email does not exist!',
                    ]);
                }

                if (!Hash::check($password, $user->password)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Incorrect password!',
                    ]);
                }

                 $credentials = $request->only(['email', 'password']);

                if (!$token = JWTAuth::attempt($credentials)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid email or password!',
                    ]);
                }

                $user = Auth::user();

                return response()->json([
                    'status' => true,
                    'message' => 'User logged in successfully',
                    'user' => $user,
                    'authorisation' => [
                        'token' => $token,
                        'type' => 'bearer',
                    ]
                ]);
                
            } catch (\Exception $e) {
                throw new HttpException(500, $e->getMessage());
            }
        }

        protected function respondWithToken($token)
        {
             return response([
                 'accesss_token' => $token,
                 'token_type' => 'bearer',
                 'expires_in' => auth()->factory()->getTTL()*60,
                 'user' => auth()->user()
             ]);
        }



    public function update_profile_data(Request $request)
    {
        try {
            $user = User::find($request->id);
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->phone = $request->phone;
            $user->company_name = $request->company_name;
            $user->group_name = $request->group_name;
            $user->position_title = $request->position_title;
            $user->location = $request->location;

            if ($request->hasFile('profile_pic')) {
                $randomNumber = mt_rand(1000000000, 9999999999);
                $imagePath = $request->file('profile_pic');
                $imageName = $randomNumber . $imagePath->getClientOriginalName();
                $imagePath->move('public/images/profile', $imageName);
                $user->profile_pic = $imageName;
            }

            $savedata = $user->save();

            if ($savedata) {
                return response()->json(['status' => true, 'message' => "Profile has been updated successfully"], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been an error updating the profile", 'data' => ""], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function user_own_reviews(Request $request)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();
            
            // Get the user's ID
            $userId = $user->id;

            // Query the reviews table for reviews by the user
            $reviews = Review::where('review_by', $userId)->get();

            // Return the result as a JSON response
            return response()->json([
                'status' => true,
                'reviews' => $reviews
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
      
    }

    public function get_user_profile_data(Request $request,$id)
    {
        try {
             $reviewsdata = Review::join('users','users.id','reviews.review_to')->where('users.id',$id)->get();
            
            $userdata = User::where('id', $id)->where('status', '!=', 'deleted')->first();
            if($userdata){
                return response()->json(['status' => true, 'message' => "user data fetch successfully!", 'data' => $userdata,'reviews'=>$reviewsdata], 200);
            }else {
                return response()->json(['status' => false, 'message' => "No user data found", 'data' => ""], 200);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function search(Request $request)
{
    $query = $request->input('q');

    $users = User::where('first_name', 'like', "%$query%")
        ->orWhere('company_name', 'like', "%$query%")
        ->orWhere('position_title', 'like', "%$query%")
        ->get();
    
    if ($users->isEmpty()) {
        return response()->json([
            'message' => 'No results found.'
        ], 404);
    }
    
    return response()->json([
        'results' => $users
    ]);
}


public function user_review(Request $request, $id)
{
    try {
        // Find the user with the specified ID
        $user = User::findOrFail($id);



        // Fetch the reviews data for the specified user
        $reviewsdata = Review::join('users','users.id','reviews.review_to')->where('users.id',$id)->get();
        
          return $reviewsdata;

        if ($reviewsdata->count() > 0) {
            // Return the user data and reviews data
            return response()->json(['status' => true, 'message' => "Data fetched successfully", 'data' => $user, $reviewsdata], 200);
        } else {
            return response()->json(['status' => false, 'message' => "No reviews data found for the specified user", 'user' => $user, 'reviews' => []], 200);
        }
    } catch (\Exception $e) {
        throw new HttpException(500, $e->getMessage());
    }
}


  

}