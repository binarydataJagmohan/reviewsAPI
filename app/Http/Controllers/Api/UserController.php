<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Review;
use App\Models\EditProfile;
use App\Models\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;
use DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Mail\NewUserRegistered;
use App\Mail\UserLoggedIn;




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
                // $data['slug'] = Str::slug($request->first_name.'-'.$request->last_name); // Combine first and last name for slug
                $slug = Str::slug($request->first_name . '-' . $request->last_name);
                $slugCount = 1;
                while (User::where('slug', $slug)->count() > 0) {
                    $slug = Str::slug($request->first_name . '-' . $request->last_name . '-' . $slugCount);
                    $slugCount++;
                }
                $data['slug'] = $slug;

                $user = new User();
                $register  = $user->create($data);

                if ($register) {
                    $token = JWTAuth::fromUser($register);

                    $register->makeHidden(['view_password']);
                    // Mail::to('dev3.bdpl@gmail.com')->send(new NewUserRegistered($register));

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
            //  Mail::to($user->email)->send(new UserLoggedIn($user));

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
            'expires_in' => auth()->factory()->getTTL() * 60,
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

    public function get_user_profile_data_id(Request $request, $id)
    {
        try {

            $reviewsdata = Review::join('users', 'users.id', 'reviews.review_by')
                ->select('reviews.*', "reviews.id as review_id", 'users.*')
                ->where(['reviews.review_to' => $id, 'reviews.status' => 'active'])
                ->get();
            $userdata = User::where('id', $id)->where('status', '!=', 'deleted')->first();
            if ($userdata) {
                return response()->json(['status' => true, 'message' => "user data fetch successfully!", 'data' => $userdata, 'reviews' => $reviewsdata], 200);
            } else {
                return response()->json(['status' => false, 'message' => "No user data found", 'data' => ""], 200);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function get_user_profile_data(Request $request, $slug)
    {
        try {
            // return $slug;
            $userdata = User::where('slug', $slug)->where('status', '!=', 'deleted')->first();
            $reviewsdata = Review::join('users', 'users.id', 'reviews.review_by')
                ->select('reviews.*', "reviews.id as review_id", 'users.*')
                ->where(['reviews.review_to' => $userdata->id, 'reviews.status' => 'active'])
                ->get();

            if ($userdata) {
                return response()->json(['status' => true, 'message' => "user data fetch successfully!", 'data' => $userdata, 'reviews' => $reviewsdata], 200);
            } else {
                return response()->json(['status' => false, 'message' => "No user data found", 'data' => ""], 200);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

        public function get_all_users(Request $request)
    {
        try {
            $allusers = User::where('status', 'active')->where('user_type', '=', 'user')->get();
            if ($allusers->count() > 0) {
                return response()->json(['status' => true, 'message' => "All users fetched successfully", 'data' => $allusers], 200);
            } else {
                return response()->json(['status' => false, 'message' => "No users found", 'data' => ""], 200);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    // $users = DB::table('reviews')
    //         ->join('users as user_1', 'reviews.review_by', '=', 'user_1.id')
    //         ->join('users as user_2', 'reviews.review_to', '=', 'user_2.id')
    //         ->select('reviews.*', 'user_1.first_name as user_1_name', 'user_2.first_name as user_2_name')
    //         ->where('user_1.id', $id)
    //         ->first();

    public function search(Request $request)
    {
        $query = $request->input('q');

        $users = User::where('first_name', 'like', "%$query%")
            ->orWhere('last_name', 'like', "%$query%")
            ->orWhere('company_name', 'like', "%$query%")
            ->orWhere('position_title', 'like', "%$query%")
            ->orWhere('bunjee_score', 'like', "%$query%")
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
            $reviewsdata = Review::join('users', 'users.id', 'reviews.review_to')->where('users.id', $id)->get();
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

    public function forgetpassword(Request $request)
    {
        // return $request->all();
        try {
            $user =  User::where('email', $request->email)->get();
            if (count($user) > 0) {
                $token = Str::random(40);
                $domain = 'http://localhost:3000';
                $url = $domain . '/resetpassword?token=' . $token;
                $data['url'] = $url;
                $data['email'] = $request->email;
                $data['title'] = "password reset";
                $data['body'] = "Please click on below link to reset your password";
                Mail::send('forgetpassword', ['data' => $data], function ($message) use ($data) {
                    $message->to($data['email'])->subject($data['title']);
                });
                $datetime = Carbon::now()->format('Y-m-d H:i:s');
                PasswordReset::updateOrCreate(
                    ['email' => $request->email],
                    [
                        'email' => $request->email,
                        'token' => $token,
                        'created_at' => $datetime,
                    ]
                );
                return response()->json(['success' => true, 'msg' => 'Please check your email!']);
            } else {
                return response()->json(['success' => false, 'msg' => 'user not found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()]);
        }
    }

    public function resetPasswordLoad(Request $request)
    {
        $resetData = PasswordReset::where('token', $request->token)->first();

        if ($resetData) {
            $user = User::Where('email', $resetData['email'])->first();
            return view('resetpassword', compact('user'));
        } else {
            return view('404');
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'password' => 'required|string|min:8',
            ]);
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json(['success' => true, 'msg' => 'User not found'], 404);
            }
            $user->password = Hash::make($request->password);
            $user->save();
            PasswordReset::where('email', $user->email)->delete();
            return response()->json(['success' => true, 'msg' => 'Password reset successful'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => true, 'msg' => 'Password reset failed'], 500);
        }
    }

    public function review_by_me(Request $request, $id)
    {
        $users = DB::table('users')
            ->join('reviews', 'users.id', '=', 'reviews.review_by')
            ->select('users.*')
            ->where('reviews.review_by', $id)
            ->distinct()
            ->get();

        return response()->json([
            'users' => $users
        ]);
    }


    public function edit_profile_data(Request $request)
    {
        try {
            $editprofiledata = EditProfile::where('user_id', $request->user_id)->first();
            if (!$editprofiledata) {
                $user = new EditProfile();
                $user->user_id = $request->user_id;
                $user->set_name = $request->set_name;
                $user->font_color = $request->font_color;
                $user->background_color = $request->background_color;
                $savedata = $user->save();
            } else {
                $editprofiledata->set_name = $request->set_name;
                $editprofiledata->font_color = $request->font_color;
                $editprofiledata->background_color = $request->background_color;
                $savedata = $editprofiledata->save();
            }
            if ($savedata) {
                return response()->json(['status' => true, 'message' => "Profile has been updated successfully"], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been an error updating the profile", 'data' => ""], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function get_edit_profile_data(Request $request, $id)
    {
        try {
            $editprofiledata = EditProfile::where('user_id', $id)->first();
            if ($editprofiledata) {
                return response()->json(['status' => true, 'message' => "Edit data fetch successfully!", 'data' => $editprofiledata], 200);
            } else {
                return response()->json(['status' => false, 'message' => "No user data found", 'data' => ""], 200);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function get_bunjee_score(Request $request)
    {
        try {
            $users = User::select('users.*', 'reviews.*')
                ->join('reviews', 'users.id', '=', 'reviews.review_to')
                ->orderBy('users.bunjee_score', 'desc')
                ->get();
            if ($users->isNotEmpty()) {
                $highest_bunjee_score = $users->first()->bunjee_score;
                return response()->json(['status' => true, 'message' => "Bunjee score data fetched successfully!", 'data' => $users, 'highest_bunjee_score' => $highest_bunjee_score], 200);
            } else {
                return response()->json(['status' => false, 'message' => "No bunjee score data found", 'data' => ""], 200);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
}
