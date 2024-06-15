<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Hash;
use DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $dataBag = [];
        $searchText = $request->has('search') ? $request->get('search') : null;
        $dataBag['users'] = DB::table('users')
            ->when(!empty($searchText), function ($query) use ($searchText) {
                return $query->where('name', 'like', '%' . $searchText . '%')
                    ->orWhere('email', 'like', '%' . $searchText . '%');
            })
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('index', $dataBag);
    }

    public function store(Request $request)
    {
        $isExists = DB::table('users')->where('email', $request->input('email'))->exists();
        
        if ($isExists) {
            return back()
                ->with('msg', 'Email id already exist')
                ->with('alert', 'danger');
        } 
        
        $save = DB::table('users')->insert([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password'))
        ]);

        return back()
            ->with('msg', 'User has been added successfully')
            ->with('alert', 'success');
    }

    public function destroy(Request $request, $id)
    {
        DB::table('users')->where('id', $id)->delete();
        return redirect()
            ->route('user.index')
            ->with('msg', 'User has been deleted successfully')
            ->with('alert', 'info');
    }

    public function edit(Request $request, $id)
    {
        $dataBag = [];
        $dataBag['user'] = DB::table('users')->where('id', $id)->first();
        if (empty($dataBag['user'])) {
            return redirect()
                ->route('user.index')
                ->with('msg', 'User not exist!')
                ->with('alert', 'danger');
        }
        return view('index', $dataBag);
    }

    public function update(Request $request, $id)
    {
        $isExists = DB::table('users')
            ->where('id', '!=', $id)
            ->where('email', $request->input('email'))
            ->exists();

        if ($isExists) {
            return back()
                ->with('msg', 'Email id already exist for another user')
                ->with('alert', 'danger');
        } 
        $save = DB::table('users')
            ->where('id', $id)
            ->update([
                'name' => $request->input('name'),
                'email' => $request->input('email')
            ]);

        return redirect()
            ->route('user.index')
            ->with('msg', 'User has been updated successfully')
            ->with('alert', 'success');
    }
}
