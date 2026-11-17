<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TermsAndCondition;
use App\Models\AboutNirmaanNxt;

class ContentController extends Controller
{
    /**
     * Dynamically map the route {type} to the correct Model and Title
     */
    private function getModelData($type)
    {
        if ($type === 'terms') {
            return ['model' => new TermsAndCondition(), 'title' => 'Terms and Conditions'];
        } elseif ($type === 'about') {
            return ['model' => new AboutNirmaanNxt(), 'title' => 'About Nirmaan NXT'];
        }
        abort(404, 'Content type not found.');
    }

    public function manage(Request $request, $type)
    {
        $data = $this->getModelData($type);
        $query = $data['model']::orderBy('id', 'desc');

        if ($request->filled('status')) {
            $status = $request->status == 'Active' ? 1 : ($request->status == 'Inactive' ? 0 : null);
            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        $records = $query->paginate(10);
        $title = $data['title'];

        return view('admin.content.manage', compact('records', 'title', 'type'));
    }

    public function add($type)
    {
        $data = $this->getModelData($type);
        $title = 'Add ' . $data['title'];
        $record = null; // Null because it's a new entry

        return view('admin.content.form', compact('title', 'type', 'record'));
    }

    public function store(Request $request, $type)
    {
        $request->validate([
            'description' => 'required|string',
            'status' => 'required|in:Active,Inactive'
        ]);

        $data = $this->getModelData($type);

        $data['model']::create([
            'description' => $request->description,
            'status' => $request->status == 'Active' ? 1 : 0
        ]);

        return redirect()->route('manage_content', $type)->with('success', $data['title'] . ' added successfully.');
    }

    public function edit($type, $id)
    {
        $data = $this->getModelData($type);
        $record = $data['model']::findOrFail($id);
        $title = 'Edit ' . $data['title'];

        return view('admin.content.form', compact('title', 'type', 'record'));
    }

    public function update(Request $request, $type, $id)
    {
        $request->validate([
            'description' => 'required|string',
            'status' => 'required|in:Active,Inactive'
        ]);

        $data = $this->getModelData($type);
        $record = $data['model']::findOrFail($id);

        $record->update([
            'description' => $request->description,
            'status' => $request->status == 'Active' ? 1 : 0
        ]);

        return redirect()->route('manage_content', $type)->with('success', $data['title'] . ' updated successfully.');
    }

    public function delete($type, $id)
    {
        $data = $this->getModelData($type);
        $data['model']::findOrFail($id)->delete();

        return redirect()->back()->with('success', $data['title'] . ' deleted successfully.');
    }
}