<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function create(Request $request)
{
    // Decode the JWT and get the user
    $data = JWT::decode($request->bearerToken(), new Key(env('JWT_SECRET_KEY'), 'HS256'));
    $user = User::find($data->id);

    // Validate the request
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'description' => 'required|string',
        'price' => 'required|numeric',
        'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'category_id' => 'required|string', // Validasi nama kategori
        'expired_at' => 'required|date',
    ]);

    // Jika validasi gagal, kembalikan pesan kesalahan
    if ($validator->fails()) {
        return response()->json($validator->messages())->setStatusCode(422);
    }

    // Dapatkan data yang divalidasi
    $validated = $validator->validated();

    // Dapatkan user ID dan email
    $userId = $user->id;
    $userEmail = $user->email;

    // Jika user ID tidak ditemukan, kembalikan error
    if (!$userId) {
        return response()->json(['message' => 'User ID not found'], 401);
    }

    // Setel kolom 'modified_by'
    $validated['modified_by'] = $userEmail;

    // Handle upload gambar jika ada gambar yang diberikan
    if ($request->hasFile('image')) {
        $validated['image'] = $request->file('image')->store('images', 'public');
    }

    // Temukan kategori berdasarkan nama atau buat baru jika tidak ditemukan
    $category = Category::firstOrCreate(['name' => $validated['category_id']]);

    // Periksa apakah kategori berhasil ditemukan atau dibuat
    if (!$category) {
        return response()->json(['message' => 'Failed to find or create category'], 500);
    }

    // Atur kolom 'category_id' dengan ID kategori yang ditemukan atau baru dibuat
    $validated['category_id'] = $category->id;

    // Buat produk dengan data yang divalidasi
    $product = Product::create($validated);
    
    // Kembalikan respons sukses dengan data produk yang dibuat
    return response()->json([
        'message' => 'Data berhasil disimpan',
        'data' => $product
    ], 200);
}
    
    public function read(){
        $product = Product::all();
        return response()->json([
            'msg' => 'Data Produk Keseluruhan',
            'data' => $product
        ],200);

    }
    
    public function update(Request $request, $id)
{
    $data = JWT::decode($request->bearerToken(), new Key(env('JWT_SECRET_KEY'), 'HS256'));
    $user = User::find($data->id);

    // Validasi request
    $validator = Validator::make($request->all(), [
        'name' => 'sometimes|max:255',
        'description' => 'sometimes|string',
        'price' => 'sometimes|numeric',
        'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'category_id' => 'sometimes|string|exists:categories,id', // Mengubah validasi ke id kategori
        'expired_at' => 'sometimes|date',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->messages())->setStatusCode(422);
    }

    $validated = $validator->validated();

    $userId = $user->id;
    $userEmail = $user->email;

    if (!$userId) {
        return response()->json(['message' => 'User ID not found'], 401);
    }

    $validated['modified_by'] = $userEmail;

    $product = Product::findOrFail($id);
    if ($product) {
        // Handle upload gambar jika ada gambar yang diberikan
        if ($request->hasFile('image')) {
            // Hapus gambar lama jika ada
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            // Simpan gambar baru dan dapatkan path-nya
            $validated['image'] = $request->file('image')->store('images', 'public');
        }

        // Setel kategori_id ke id kategori yang ditemukan
        if (isset($validated['category_id'])) {
            $category = Category::find($validated['category_id']);
            if (!$category) {
                return response()->json(['message' => 'Category not found'], 404);
            }
            $validated['category_id'] = $category->id;
        }

        // Hapus 'name' karena tidak dibutuhkan dalam model Product
        unset($validated['name']);

        // Perbarui produk dengan data yang divalidasi
        $product->update($validated);
        return response()->json([
            'message' => 'Data berhasil diupdate'
        ], 200);
    }

    return response()->json(['message' => 'Data dengan id (' . $id . ') tidak ditemukan'], 404);
}

public function delete($id)
    {
        $product = Product::find($id);

        if ($product) {
            $product->delete();

            return response()->json([
                'msg' => 'Data produk dengan ID: ' . $id . ' berhasil dihapus'
            ], 200);
        }

        return response()->json([
            'msg' => 'Data produk dengan ID: ' . $id . ' tidak ditemukan',
        ], 404);
    }
}
