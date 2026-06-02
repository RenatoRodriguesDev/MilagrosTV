<?php

namespace App\Http\Controllers;

use App\Models\ContentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContentRequestController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'tmdb_id'        => 'required|string',
            'type'           => 'required|in:movie,tv',
            'title'          => 'required|string|max:255',
            'original_title' => 'nullable|string|max:255',
            'poster_url'     => 'nullable|url',
            'year'           => 'nullable|integer',
        ]);

        $existing = ContentRequest::where([
            'user_id' => Auth::id(),
            'tmdb_id' => $data['tmdb_id'],
            'type'    => $data['type'],
        ])->first();

        if ($existing) {
            return response()->json([
                'ok'      => false,
                'message' => 'Já pediste este conteúdo.',
                'status'  => $existing->status,
            ]);
        }

        ContentRequest::create($data + ['user_id' => Auth::id()]);

        return response()->json(['ok' => true, 'message' => 'Pedido enviado! O administrador irá analisá-lo.']);
    }
}
