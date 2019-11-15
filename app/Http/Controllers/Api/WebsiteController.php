<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use DB;

class WebsiteController extends Controller
{
    public function index()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function getByUserId($id)
    {
        $web = DB::connection('mysql')
                ->table('user_websites as uw')
                ->join('websites as w','uw.id_website', '=', 'w.id')
                ->select(
                    'w.domain',
                    'w.db_name',
                    'w.db_user',
                    'w.db_password',
                    'w.status'
                )
                ->where('uw.id_user', $id)
                ->first();

        if (!$web) {
            return response()->json([
                'error' => 1,
                'message' => 'data website tidak ditemukan.'
            ]);
        }

        return response()->json([
            'success' => 1,
            'message' => 'data website ditemukan.',
            'data' => $web
        ]);
    }

    public function show($id)
    {
        # code...
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
