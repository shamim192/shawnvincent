<?php

namespace App\Http\Controllers\Api\Backend;

use App\Models\Contact;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ContactController extends Controller
{
    use ApiResponse;

    public function store(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject'   => 'required|string|max:100',
            'message'   => 'required|string|max:1000'
        ]);

        // Store the contact form data in the database
      $data =  Contact::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'subject' => $request->input('subject'),
            'message' => $request->input('message'),
        ]);

        return $this->success($data, 'Contact form submitted successfully.');
    }
}
