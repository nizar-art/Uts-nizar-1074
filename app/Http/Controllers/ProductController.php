<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        $data = JWT::decode($request->bearerToken(), new Key(env('JWT_SECRET_KEY'), 'HS256'));
        $user = User::find($data->id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|required',
            'price' => 'required|numeric',
            'image' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'expired_at' => 'requared|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages())->setStatusCode(422);
        }

       $validated = $validator->validated();

        $userId = $user->id;
        $userEmail = $user->email;

        if (!$userId) {
            return response()->json(['message' => 'User ID not found', 401]);
        }

        $validated['modified_by'] = $userEmail;

        $product = Product::create($validated);
        
        return response()->json([
            'message' => 'Data berhasil disimpan',
            'data' => $product
        ], 200);

    }
    function showAll(){
        $product = Product::all();
        return response()->json([
            'msg' => 'Data Produk Keseluruhan',
            'data' => $product
        ],200);

    }

    function showById($id){
        $product = Product::where('id', $id)->first();
        if ($product) {
            return response()->json([
                'msg' => 'Data Produk Dengan ID: '.$id,
                'data' => $product
            ],200);
        }
        return response()->json([
           'msg' => 'Data Produk dengan ID: '.$id.' Tidak Ditemukan'
        ],404);
    }
    function showByName($product_name){
        $product = Product::where('product_name', 'LIKE','%'.$product_name.'%')->get();
        if ($product->count() > 0 ) {
            return response()->json([
                'msg' => 'Data Produk Dengan Nama Yang Mirip: '.$product_name,
                'data' => $product
            ],200);
        }
        return response()->json([
           'msg' => 'Data Produk dengan Nama Yang Mirip: '.$product_name.' Tidak Ditemukan'
        ],404);
    }
    
    public function update(Request $request, $id)
    {
        $data = JWT::decode($request->bearerToken(), new Key(env('JWT_SECRET'), 'HS256'));
        $user = User::find($data->id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'description' => 'required',
            'price' => 'required|numeric',
            'image' => 'required|image|mines:jpeg,png,jpg,gif,svg|max2048'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages())->setStatusCode(422);
        }

        $validated = $validator->validated();

        $userId = $user->id;
        $userEmail = $user->email;

                if (!$userId) {
            return response()->json(['message' => 'User ID not found', 401]);
        }

        $validated['modified_by'] = $userEmail;

        $product = Product::findOrFail($id);
        if ($product) {
            Product::where('id', $id)->update($validated);
            return response()->json([
                'message' => 'Data berhasil diupdate'
            ])->setStatusCode(200);
        }
        return response()->json(['data dengan id (' . $id . ')tidak di  temukan']);
    }

    public function delete($id){
        $product = Product::find ($id);

        if ($product) {
            Product::where('id', $id)->delete();

            return response()->json([
                'msg' => 'Data produk dengan ID: '.$id.' berhasil dihapus' 
            ], 200);
        }

        return response()->json([
            'msg' => 'Data produk dengan ID: '.$id.' tidak ditemukan', 
        ],400);
    }

    public function restore ($id) {
        $product = Product::onlyTrashed()->where ($id);
        $product ->restore();

        return response()->json("Data dengan id: ( $id berhasil dipulihkan", 200);
    }
}