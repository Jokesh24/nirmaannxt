<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Banner;
use App\Models\BannerType;
use Exception;
use File;

class BannerController extends Controller
{
    public function add(Request $request)
    {
        $bannerTypes = BannerType::orderBy('type_name', 'ASC')->get();

        $query = Banner::with('bannerType')->orderBy('id', 'DESC');

        // Filters
        if ($request->filled('search')) {
            $query->where('banner_name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('banner_type')) {
            $query->where('banner_type_id', $request->banner_type);
        }
        if ($request->filled('status')) {
            $status = ($request->status == 'Active') ? 1 : 0;
            $query->where('status', $status);
        }

        $banners = $query->paginate(10);
        return view('admin.banners.add_banner', compact('bannerTypes', 'banners'));
    }

    public function store(Request $request)
    {
        // In the store method
        $request->validate([
            'banner_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]+$/u' // Only letters and spaces allowed
            ],
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'banner_type' => 'required',
            'status' => 'required'
        ], [
            'banner_name.regex' => 'The banner name must only contain letters and spaces (no numbers allowed).'
        ]);

        // In the update method (Add validation here as well for consistency)
        $request->validate([
            'banner_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]+$/u'
            ],
        ], [
            'banner_name.regex' => 'The banner name must only contain letters and spaces.'
        ]);

        try {
            $filename = null;
            if ($request->hasFile('image')) {
                $path = public_path('uploads/banners');
                if (!file_exists($path))
                    mkdir($path, 0755, true);
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move($path, $filename);
            }

            Banner::create([
                'banner_name' => $request->banner_name,
                'image' => $filename,
                'banner_type_id' => $request->banner_type,
                'status' => ($request->status == 'Active') ? 1 : 0,
            ]);

            return redirect()->route('admin.banners.add')->with('success', 'Banner created successfully.');
        } catch (Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $banner = Banner::findOrFail($id);
        return response()->json($banner);
    }

    public function update(Request $request)
    {
        try {
            $banner = Banner::findOrFail($request->banner_id);
            $banner->banner_name = $request->banner_name;
            $banner->banner_type_id = $request->banner_type;
            $banner->status = ($request->status == 'Active') ? 1 : 0;

            if ($request->hasFile('image')) {
                $oldPath = public_path('uploads/banners/' . $banner->image);
                if (File::exists($oldPath))
                    File::delete($oldPath);

                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/banners'), $filename);
                $banner->image = $filename;
            }

            $banner->save();
            return redirect()->back()->with('success', 'Banner updated successfully.');
        } catch (Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $banner = Banner::findOrFail($id);
            $path = public_path('uploads/banners/' . $banner->image);
            if (File::exists($path))
                File::delete($path);
            $banner->delete();
            return redirect()->back()->with('success', 'Banner deleted successfully.');
        } catch (Exception $e) {
            return back()->with('error', 'Delete failed.');
        }
    }

    public function storeType(Request $request)
    {
        try {
            $type = BannerType::create(['type_name' => $request->type_name, 'status' => 1]);
            return response()->json(['success' => true, 'all' => BannerType::all()]);
        } catch (Exception $e) {
            return response()->json(['success' => false]);
        }
    }
}