<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:rooms,name',
            'icon' => 'required|string|max:50',
        ]);

        \App\Models\Room::create($validated);

        return response()->json(['success' => true]);
    }
}
