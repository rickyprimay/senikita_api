<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArtProvince;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ArtProvinceController extends Controller
{
    public function index()
    {
        $artProvinces = ArtProvince::all();
        return response()->json([
            'status' => 'success',
            'data' => $artProvinces
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'subtitle' => 'required',
            'longitude' => 'required',
            'latitude' => 'required'
        ]);

        $artProvince = ArtProvince::find($id);

        if (!$artProvince) {
            return response()->json([
                'status' => 'error',
                'message' => 'Art Province not found'
            ], 404);
        }

        $artProvince->update($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $artProvince
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'subtitle' => 'required',
            'longitude' => 'required',
            'latitude' => 'required'
        ]);

        $artProvince = ArtProvince::create($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $artProvince
        ]);
    }

    public function delete($id)
    {
        $artProvince = ArtProvince::find($id);

        if (!$artProvince) {
            return response()->json([
                'status' => 'error',
                'message' => 'Art Province not found'
            ], 404);
        }

        $artProvince->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Art Province deleted'
        ]);
    }

    public function show($id)
    {
        $artProvince = ArtProvince::with('artProvinceDetails')->find($id);

        if (!$artProvince) {
            return response()->json([
                'status' => 'error',
                'message' => 'Art Province not found'
            ], 404);
        }

        $artProvince->artProvinceDetails->each(function ($detail) {
            if ($detail->image) {
                $detail->image = asset($detail->image);
            }
        });

        // CONVER STRING TO ENTER GEMINI
        $artProvinceData = $artProvince->toArray();
        $artProvinceString = '';

        foreach ($artProvinceData as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $detail) {
                    $artProvinceString .= implode(' ', (array)$detail); // Gabungkan setiap detail
                }
            } else {
                $artProvinceString .= $value . ' '; // Gabungkan atribut
            }
        }

        $query = "Anda adalah asisten yang akan memberikan penjelasan singkat
        kebudayaan dari daerah yang akan saya berikan dibawah ini. Fokuskan pada penjelasan singkat setiap budanya.
        Tolong buatkan kata kata nya secara friendly dan jangan terlalu banyak ( buatlah lebih singkat ) : " . $artProvinceString;

        $content = $this->generateContent($query);


        return response()->json([
            'status' => 'success',
            'data' => [
                'art_province' => $artProvince,
                'content' => $content
            ]
        ]);
    }

    public function generateContent($query)
    {
        $apiKey = env('GEMINI_API_KEY');

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $query
                        ]
                    ]
                ]
            ]
        ]);

        if ($response->successful()) {
            // dd($response->json()['candidates'][0]['content']['parts'][0]['text']);
            return $response->json()['candidates'][0]['content']['parts'][0]['text'];
        }

        // Jika gagal, kembalikan pesan error
        return 'Failed to generate content';
    }
}
