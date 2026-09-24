<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\NoticeRequest;
use App\Models\Branch;
use App\Models\Notice;

class NoticeController extends Controller
{
    public function index()
    {
        return view('admin.notices.index', ['notices' => Notice::with('branch')->latest('published_on')->paginate(20)]);
    }

    public function create()
    {
        return view('admin.notices.form', ['notice' => new Notice(['published_on' => today()]), 'branches' => Branch::pluck('name', 'id')]);
    }

    public function store(NoticeRequest $request)
    {
        Notice::create($request->validated());

        return redirect()->route('admin.notices.index')->with('success', 'Notice published.');
    }

    public function edit(Notice $notice)
    {
        return view('admin.notices.form', ['notice' => $notice, 'branches' => Branch::pluck('name', 'id')]);
    }

    public function update(NoticeRequest $request, Notice $notice)
    {
        $notice->update($request->validated());

        return redirect()->route('admin.notices.index')->with('success', 'Notice updated.');
    }

    public function destroy(Notice $notice)
    {
        $notice->delete();

        return back()->with('success', 'Notice deleted.');
    }
}
