<?php

namespace App\Http\Controllers\Api\Auth;

use Carbon\Carbon;
use App\Models\User;
use App\Mail\OtpMail;
use App\Models\Brand;
use App\Helpers\Helper;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Traits\Mail as TraitsMail;

class AuthController extends Controller
{
    use TraitsMail;
    use ApiResponse;
    // Register Music User (Fan or Artist)
    public function registerMusicUser(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'api_role' => 'required|in:fan,artist', // Using api_role (Fan or Artist)
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Create the Music user with 'music' user_type and the selected api_role (Fan or Artist)
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Hash the password
            'user_type' => 'music', // Mark this user as a Music-related user
            'api_role' => $request->api_role, // Fan or Artist (api_role)
            'avatar' => 'uploads/avatar/profile.jpg',
            'is_verified' => false, // Indicates user is not verified yet
        ]);

        // Generate a 6-digit OTP
        $otp = rand(100000, 999999);

        // Save OTP and expiration time (valid for 10 minutes)
        $user->otp = $otp;
        $user->otp_expires_at = Carbon::now()->addMinutes(10); // OTP valid for 10 minutes
        $user->save();

        // Send OTP to user's email
        //Mail::to($user->email)->send(new OtpMail($otp));
        $this->OtpMailGun($user->email, 'Verify Your Email Address', 'Don\'t share your OTP with anyone', $user->otp);

        // Generate the token (JWT)
        $token = JWTAuth::fromUser($user);

        // Prepare response data
        $responseData = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'user_type' => $user->user_type,
            'api_role' => $user->api_role, // Include api_role in response
            'otp' => $user->otp, // Optionally include OTP in response for testing purposes
            'otp_expires_at' => $user->otp_expires_at,
            'avatar' => asset($user->avatar), // Include avatar URL
            'is_verified' => $user->is_verified, // Include is_verified status
            'token' => $token,
        ];

        return response()->json([
            'message' => 'User registered successfully. Please check your email for the OTP.',
            'data' => $responseData
        ], 201);
    }

    public function loginMusicUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        // // Authenticate the user
        if (!auth()->attempt($request->only('email', 'password'))) {
            return $this->error(null, 'Invalid credentials.', 401);
        }

        $user = auth()->user();

        // Check if the user is verified
        if (!$user->is_verified) {
            return $this->error(null, 'Account not verified.', 401);
        }

        // Generate the token
        $token = JWTAuth::fromUser($user);

        // Convert user to array and add the token
        $userArray = $user->toArray();
        $userArray['token'] = $token;

        return $this->success($userArray, 'Login successful', 200);
    }

    public function verifyOtpMusic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $user = User::where('email', $request->email)->first();

        // Check if OTP matches and hasn't expired
        if ($user->otp === $request->otp && Carbon::now()->lt($user->otp_expires_at)) {
            // Mark user as verified
            $user->is_verified = true;
            $user->otp = null; // Clear the OTP
            $user->otp_expires_at = null; // Clear expiration time
            $user->email_verified_at = Carbon::now();
            $user->save();

            return $this->success($user, 'OTP verified successfully.');
        }

        return $this->error(null, 'Invalid or expired OTP.', 422);
    }

    public function resendOtpMusic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $user = User::where('email', $request->email)->first();

        // Generate a new 6-digit OTP
        $otp = rand(100000, 999999);

        // Update OTP and expiration time
        $user->otp = $otp;
        $user->otp_expires_at = Carbon::now()->addMinutes(10);
        $user->save();

        // Resend OTP to user's email
        //Mail::to($user->email)->send(new OtpMail($otp));
        $this->OtpMailGun($user->email, 'OTP has been resent successfully.', 'Don\'t share your OTP with anyone', $user->otp);

        return $this->success(null, 'OTP has been resent successfully.');
    }  

    public function requestPasswordResetMusic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $user = User::where('email', $request->email)->first();

        // Generate a 6-digit OTP
        $otp = rand(100000, 999999);

        // Save OTP and expiration time
        $user->otp = $otp;
        $user->otp_expires_at = Carbon::now()->addMinutes(10);
        $user->save();

        // Send OTP to user's email
        //Mail::to($user->email)->send(new OtpMail($otp));
        $this->OtpMailGun($user->email, 'Password reset OTP sent to your email.', 'Don\'t share your OTP with anyone', $user->otp);

        return $this->success(null, 'Password reset OTP sent to your email.');
    }

    public function verifyPasswordResetOtpMusic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user->otp === $request->otp && Carbon::now()->lt($user->otp_expires_at)) {
            // OTP is valid, allow the user to proceed
            return $this->success(null, 'OTP verified successfully.');
        }

        return $this->error(null, 'Invalid or expired OTP.', 422);
    }

    public function setNewPasswordMusic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $user = User::where('email', $request->email)->first();

        // Update the password and clear the OTP
        $user->password = Hash::make($request->password);
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();

        return $this->success(null, 'Password has been reset successfully.');
    }

    public function resendResetOtpMusic(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Generate a new 6-digit OTP for password reset
        $otp = rand(100000, 999999);

        // Update OTP and expiration time
        $user->otp = $otp;
        $user->otp_expires_at = Carbon::now()->addMinutes(10);
        $user->save();

        // Resend OTP to user's email for password reset
        //Mail::to($user->email)->send(new OtpMail($otp));
        $this->OtpMailGun($user->email, 'Password reset OTP has been resent successfully.', 'Don\'t share your OTP with anyone', $user->otp);

        return $this->success(null, 'Password reset OTP has been resent successfully.');
    }


    // merchandise user registration, login, etc. here


    public function registerMerchandiseUser(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'api_role' => 'required|in:shopper,seller', // Using api_role (Fan or Artist)
            'brand_name' => 'required_if:api_role,seller|string|max:255',
            'brand_image' => 'required_if:api_role,seller|image|mimes:jpeg,png,jpg,gif|max:10048',
            'cover_image' => 'required_if:api_role,seller|image|mimes:jpeg,png,jpg,gif|max:10048',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Create the Music user with 'music' user_type and the selected api_role (Fan or Artist)
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Hash the password
            'user_type' => 'merchandise', // Mark this user as a Music-related user
            'api_role' => $request->api_role, // Fan or Artist (api_role)
            'avatar' => 'uploads/avatar/profile.jpg',
            'is_verified' => false, // Indicates user is not verified yet
        ]);

        if ($request->api_role === 'seller') {
            // Handle brand creation for seller
            $brand = new Brand();
            $brand->user_id = $user->id;
            $brand->name = $request->brand_name;

            $bandImagePath = null;
            $bandCoverImagePath = null;

            if ($request->hasFile('brand_image')) {
                $bandImagePath = Helper::fileUpload($request->file('brand_image'), 'brand_images', $request->input('brand_name'));
            }

            if ($request->hasFile('cover_image')) {
                $bandCoverImagePath = Helper::fileUpload($request->file('cover_image'), 'cover_images', $request->input('brand_name'));
            }

            $brand->brand_image = asset($bandImagePath);
            $brand->cover_image = asset($bandCoverImagePath);

            $brand->save();
        }

        // Generate a 6-digit OTP
        $otp = rand(100000, 999999);

        // Save OTP and expiration time (valid for 10 minutes)
        $user->otp = $otp;
        $user->otp_expires_at = Carbon::now()->addMinutes(10); // OTP valid for 10 minutes
        $user->save();

        // Send OTP to user's email
        //Mail::to($user->email)->send(new OtpMail($otp));
        $this->OtpMailGun($user->email, 'Please verify your email address.', 'Don\'t share your OTP with anyone', $user->otp);

        // Generate the token (JWT)
        $token = JWTAuth::fromUser($user);

        // Prepare response data
        $responseData = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'user_type' => $user->user_type,
            'api_role' => $user->api_role, // Include api_role in response
            'otp' => $user->otp, // Optionally include OTP in response for testing purposes
            'otp_expires_at' => $user->otp_expires_at,
            'avatar' => asset($user->avatar), // Include avatar URL
            'is_verified' => $user->is_verified, // Include is_verified status
            'token' => $token,
        ];

        return response()->json([
            'message' => 'User registered successfully. Please check your email for the OTP.',
            'data' => $responseData
        ], 201);
    }

    public function loginMerchandiseUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        // // Authenticate the user
        if (!auth()->attempt($request->only('email', 'password'))) {
            return $this->error(null, 'Invalid credentials.', 401);
        }

        $user = auth()->user();

        // Check if the user is verified
        if (!$user->is_verified) {
            return $this->error(null, 'Account not verified.', 401);
        }

        // Generate the token
        $token = JWTAuth::fromUser($user);

        // Convert user to array and add the token
        $userArray = $user->toArray();
        $userArray['token'] = $token;

        return $this->success($userArray, 'Login successful', 200);
    }

    public function verifyOtpMerchandise(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $user = User::where('email', $request->email)->first();

        // Check if OTP matches and hasn't expired
        if ($user->otp === $request->otp && Carbon::now()->lt($user->otp_expires_at)) {
            // Mark user as verified
            $user->is_verified = true;
            $user->otp = null; // Clear the OTP
            $user->otp_expires_at = null; // Clear expiration time
            $user->email_verified_at = Carbon::now();
            $user->save();

            return $this->success($user, 'OTP verified successfully.');
        }

        return $this->error(null, 'Invalid or expired OTP.', 422);
    }

    public function resendOtpMerchandise(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $user = User::where('email', $request->email)->first();

        // Generate a new 6-digit OTP
        $otp = rand(100000, 999999);

        // Update OTP and expiration time
        $user->otp = $otp;
        $user->otp_expires_at = Carbon::now()->addMinutes(10);
        $user->save();

        // Resend OTP to user's email
        //Mail::to($user->email)->send(new OtpMail($otp));
        $this->OtpMailGun($user->email, 'OTP has been resent successfully.', 'Don\'t share your OTP with anyone', $user->otp);

        return $this->success(null, 'OTP has been resent successfully.');
    }

    public function requestPasswordResetMerchandise(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $user = User::where('email', $request->email)->first();

        // Generate a 6-digit OTP
        $otp = rand(100000, 999999);

        // Save OTP and expiration time
        $user->otp = $otp;
        $user->otp_expires_at = Carbon::now()->addMinutes(10);
        $user->save();

        // Send OTP to user's email
        //Mail::to($user->email)->send(new OtpMail($otp));
        $this->OtpMailGun($user->email, 'Password reset OTP sent to your email.', 'Don\'t share your OTP with anyone', $user->otp);

        return $this->success(null, 'Password reset OTP sent to your email.');
    }

    public function verifyPasswordResetOtpMerchandise(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user->otp === $request->otp && Carbon::now()->lt($user->otp_expires_at)) {
            // OTP is valid, allow the user to proceed
            return $this->success(null, 'OTP verified successfully.');
        }

        return $this->error(null, 'Invalid or expired OTP.', 422);
    }

    public function setNewPasswordMerchandise(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $user = User::where('email', $request->email)->first();

        // Update the password and clear the OTP
        $user->password = Hash::make($request->password);
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();

        return $this->success(null, 'Password has been reset successfully.');
    }

    public function resendResetOtpMerchandise(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Generate a new 6-digit OTP for password reset
        $otp = rand(100000, 999999);

        // Update OTP and expiration time
        $user->otp = $otp;
        $user->otp_expires_at = Carbon::now()->addMinutes(10);
        $user->save();

        // Resend OTP to user's email for password reset
        //Mail::to($user->email)->send(new OtpMail($otp));
        $this->OtpMailGun($user->email, 'Password reset OTP has been resent successfully.', 'Don\'t share your OTP with anyone', $user->otp);

        return $this->success(null, 'Password reset OTP has been resent successfully.');
    }
}
