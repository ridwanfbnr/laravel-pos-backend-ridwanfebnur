<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Mendapatkan semua data products
        $products = DB::table('products')
            ->when($request->input('name'), function ($query, $name) {
                return $query->where('name', 'like', '%' . $name . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        // Mengurutkan berdasarkan created_at secara menurun/desc

        return view('pages.products.index', compact('products'));
    }

    public function create()
    {
        return view('pages.products.create');
    }

     public function store(Request $request)
    {
        $data = $request->all();

        // Simpan gambar dan dapatkan URL-nya
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('public/images'); // Simpan gambar di storage
            $imageUrl = asset(str_replace('public', 'storage', $imagePath)); // Dapatkan URL gambar
            $data['image'] = $imageUrl;
        } else {
            $data['image'] = "";
        }

        \App\Models\Product::create($data);
        return redirect()->route('product.index')->with('success', 'Product successfully created');
    }

    public function edit($id)
    {
        $product = \App\Models\Product::findOrFail($id);
        return view('pages.products.edit', compact('product'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();
        $product = \App\Models\Product::findOrFail($id);

        // Menghapus gambar lama jika ada gambar baru yang diunggah
        if ($request->hasFile('image')) {
            // Hapus gambar lama dari storage jika ada
            if ($product->image) {
                $oldImagePath = str_replace(asset('storage'), 'public', $product->image);
                if (Storage::exists($oldImagePath)) {
                    Storage::delete($oldImagePath);
                }
            }

            // Simpan gambar baru di storage
            $newImagePath = $request->file('image')->store('public/images');
            $newImageUrl = asset(str_replace('public', 'storage', $newImagePath));
            $data['image'] = $newImageUrl;
        } else {
            // Jika tidak ada gambar baru diunggah, gunakan gambar yang sudah ada
            $data['image'] = $product->image;
        }

        $product->update($data);
        return redirect()->route('product.index')->with('success', 'Product successfully updated');
    }

    public function destroy($id)
    {
        $product = \App\Models\Product::findOrFail($id);

        // Menghapus gambar dari storage jika ada
        if ($product->image) {
            // Mengambil path dari URL gambar
            $imagePath = str_replace(asset('storage'), 'public', $product->image);

            // Menghapus file gambar dari storage
            if (Storage::exists($imagePath)) {
                Storage::delete($imagePath);
            }
        }

        $product->delete();
        return redirect()->route('product.index')->with('success', 'Product successfully deleted');
    }
}