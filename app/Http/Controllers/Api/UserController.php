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


class UserController extends Controller
{

    public function user_register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users',
                'password' => 'required|min:8|confirmed',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            } else {
                $data = $request->all();
                $data['password'] = Hash::make($request->password);
                $user = new User();
                $register  = $user->create($data);
                if ($register) {
                    return response()->json([
                        'status' => true,
                        'message' => 'User created successfully',
                        'user' => $register,
                    ]);
                } else {
                    return response()->json(['message' => "'There has been error for to register the user"], 404);
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
                'email' => 'required',
                'password' => 'required',
            ]);
            $credentials = $request->only('email', 'password');
            $token = Auth::attempt($credentials);
            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please enter correct credentials!.',
                ], 401);
            }
            $user = Auth::user();
            return response()->json([
                'status' => true,
                'message' => 'user Loggedin successfully',
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
}
