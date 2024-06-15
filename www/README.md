# Laravel SQLite Application

```php
<!DOCTYPE html>
<html lang="en">
<head>
  <title>User</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="{{ asset('assets/bootstrap.min.css') }}">
</head>
<body>

<div class="container">
    <div class="row mt-3">
        <div class="col-md-4">
            @if(Session::has('msg') && !empty(Session::get('msg')) && !empty(Session::get('alert')))
                <div class="alert alert-{{Session::get('alert')}} alert-dismissible fade show" role="alert">
                    {{ Session::get('msg') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            <form name="frm" id="frmx" action="@if(isset($user)){{ route('user.update', array('id' => $user->id)) }}@else{{ route('user.add') }}@endif" method="POST">
                @csrf
                @if(isset($user))
                    @method('put')
                @endif
                <div class="form-group">
                    <label class="onex-frmlb" id="name">Name:<em>*</em></label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="Name" required="required" value="@if(isset($user)){{ $user->name }}@endif"/>
                </div>
                <div class="form-group">
                    <label class="onex-frmlb" id="email">Email:<em>*</em></label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="Email" required="required" value="@if(isset($user)){{ $user->email }}@endif"/>
                </div>
                @if(!isset($user))
                <div class="form-group">
                    <label class="onex-frmlb" id="password">Password:<em>*</em></label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Password" required="required"/>
                </div>
                @endif
                <div class="form-group mt-3">
                    <button type="submit" class="btn @if(isset($user)) btn-warning @else btn-primary @endif">@if(isset($user)) Save Changes @else Add User @endif</button>
                    @if(isset($user))
                        <a href="{{ route('user.index') }}" class="btn btn-danger">Cancel</a>
                    @endif
                </div>
            </form>
        </div>
        <div class="col-md-8">
            @if(isset($users))
            <div class="row">
                <div class="col-md-9"></div>
                <div class="col-md-3">
                    <form action="" method="GET">
                        <input type="text" name="search" class="form-control" id="searchText" placeholder="Search" @if(isset($_GET['search'])) value="{{ $_GET['search'] }}" @endif />
                    </form>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12">
                    <table class="table table-bordered table-striped table-hover table-sm">
                        <thead>
                            <tr>
                                <th>SL</th>
                                <th>NAME</th>
                                <th>EMAIL</th>
                                <th>#</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(count($users))
                                @foreach($users as $k => $v)
                                    <tr>
                                        <td>{{ $k + 1 }}</td>
                                        <td>{{ $v->name }}</td>
                                        <td>{{ $v->email }}</td>
                                        <td>
                                            <form name="user-delete-frm{{ $v->id }}" id="userDeleteFrm{{ $v->id }}" action="{{ route('user.delete', array('id' => $v->id)) }}" method="POST">
                                                @method('delete')
                                                @csrf
                                                <button type="submit" class="btn btn btn-sm btn-danger" onclick="return confirm('Are you sure? You want to delete this user?');">Delete</button>
                                                <a href="{{ route('user.edit', array('id' => $v->id)) }}" class="btn btn-sm btn-success">Edit</a>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="4">No User Found!</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                    <div style="text-align:right;">
                        {{ $users->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script src="{{ asset('assets/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
```

```php
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

```