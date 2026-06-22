<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contact;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    use ApiResponse;

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string'
        ]);

        $contact = Contact::create($validated);

        return $this->successResponse($contact, 'Pesan Anda berhasil dikirim', 201);
    }
}
