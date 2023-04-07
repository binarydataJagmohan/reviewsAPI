<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Review;
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
                // $data['view_password'] = $request->password;
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
            'expires_in' => auth()->factory()->getTTL() * 60
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



    public function getCurrentUserData(Request $request)
    {
        try {
            $user_id = $request->id;
            $userdata = User::where('id', $user_id)->where('status', '!=', 'deleted')->first();

            if ($userdata) {
                return response()->json(['status' => true, 'message' => "user data fetch successfully!", 'data' => $userdata], 200);
            } else {
                return response()->json(['status' => false, 'message' => "No user data found", 'data' => ""], 200);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
}
