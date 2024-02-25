<?php

namespace App\Http\Controllers\Api\v1;

use App\Filters\v1\UserFilter;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\UserCollection;
use App\Http\Resources\v1\UserResource;
use App\Models\User;
use App\Services\v1\UserQuery;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return UserCollection
     */
    public function index(Request $request)
    {
        $filter = new UserFilter();
        $queryItems = $filter->transform($request);

        if(count($queryItems) == 0){
            return new UserCollection(User::paginate(5));
        }else{
            $users = User::where($queryItems)->paginate(5);

            return new UserCollection($users->appends($request->query()));
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return UserResource
     */
    public function show(User $user)
    {
        return new UserResource($user);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
