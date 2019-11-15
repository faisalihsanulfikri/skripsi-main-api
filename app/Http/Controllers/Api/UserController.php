<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use DB;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = [];
        $getUsers = DB::connection('mysql')
                ->table('users')
                ->select(
                    "id",
                    "name",
                    "email",                    
                    "phone",
                    "address",
                    "status",
                    "created_at",
                    "updated_at",
                )
                ->get();

        if (count($getUsers) > 0) {
            $users = $getUsers;
        }

        return response()->json([
            'data' => $users
        ]);
    }

    public function get($id)
    {
        $users = DB::connection('mysql')
                ->table('users')
                ->select(
                    "id",
                    "name",
                    "email",                    
                    "phone",
                    "address",
                    "status",
                    "created_at",
                    "updated_at",
                )
                ->where('id',$id)
                ->first();

        if (!$users) {
            return response()->json([
                'error' => 1,
                'message' => "User tidak ada."
            ]);
        }

        return response()->json([
            'success' => 1,
            'message' => 'data user ditemukan.',
            'data' => $users
        ]);
    }
}
