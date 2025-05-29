<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\Brand;
use App\Helpers\Helper;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserProfileController extends Controller
{
    use ApiResponse;
    // Method to update the profile of the logged-in user
    public function updateProfile(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . Auth::id(), // Ensure the new email is unique, but ignore current user
            'brand_name' => 'nullable|string|max:255', // Allow brand name to be updated for sellers
            'brand_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10048', // Allow brand image upload for sellers
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10048', // Allow cover image upload for sellers
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        // Get the logged-in user
        $user = Auth::user();

        // Update the user’s details
        $user->first_name = $request->first_name ?? $user->first_name; // Update first name
        $user->last_name = $request->last_name ?? $user->last_name; // Update last name
        $user->email = $request->email ?? $user->email;  // Update email

        // Check if the user is a seller and has a brand
        if ($user->api_role === 'seller') {
            // If the user is a seller, check if brand data is provided to update
            if ($request->has('brand_name')) {
                $brand = Brand::where('user_id', $user->id)->first();

                if (!$brand) {
                    // If no brand exists, create a new brand
                    $brand = new Brand();
                    $brand->user_id = $user->id;
                }

                // Update the brand's name
                $brand->name = $request->brand_name;

                // Handle brand image upload
                if ($request->hasFile('brand_image')) {
                    $brandImagePath = Helper::fileUpload($request->file('brand_image'), 'brand_images', $request->input('brand_name'));
                    $brand->brand_image = $brandImagePath;
                }

                // Handle cover image upload
                if ($request->hasFile('cover_image')) {
                    $coverImagePath = Helper::fileUpload($request->file('cover_image'), 'brand_images', $request->input('brand_name'));
                    $brand->cover_image = $coverImagePath;
                }

                // Save the brand data
                $brand->save();
            }
        }

        // Save the updated user data
        $user->save();

        $user = $user->load('brand');

        return $this->success($user, 'Profile updated successfully');
    }

    // Method to change the password of the logged-in user
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',  // Ensure new password and confirm password match
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        // Get the logged-in user
        $user = Auth::user();

        // Check if the current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error([], 'Current password is incorrect.', 422);
        }

        // Update the password if everything is valid
        $user->password = Hash::make($request->password); // Hash the new password
        $user->save();

        return $this->success([], 'Password updated successfully');
    }

    // Method to update the avatar of the logged-in user
    public function updateAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpg,jpeg,png,gif,svg|max:5048',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        // Get the logged-in user
        $user = Auth::user();

        // If the user already has an avatar, delete the old one using the helper function
        if ($user->avatar) {
            // Use the helper to delete the old avatar image
            Helper::fileDelete(public_path($user->avatar));
        }

        // Use the Helper class to handle the image upload
        $avatarPath = Helper::fileUpload($request->file('avatar'), 'avatars', 'user_' . $user->id);

        // If the upload failed, return an error
        if (!$avatarPath) {
            return response()->json(['error' => 'Failed to upload avatar image.'], 422);
        }

        // Update the user's avatar field in the database
        $user->avatar = $avatarPath;
        $user->save();

        return $this->success($user, 'Avatar updated successfully');
    }

    // Method to get the profile of the logged-in user
    public function getProfile()
    {
        // Get the logged-in user
        $user = Auth::user();
        
        $user = $user->load('brand');

        // Return the user's profile data
        return $this->success($user, 'User profile retrieved successfully');
    }
}
