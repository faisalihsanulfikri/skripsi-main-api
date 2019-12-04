<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Api\EncryptionController;

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
        $enc = new EncryptionController;

        // return $enc->testGet();

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

        $dataWeb = $web;

        $dbUser = $enc->encryption($web->db_user);
        $dbpass = $enc->encryption($web->db_password);
        $cipher_db_user = $dbUser->original['ciphertext'];
        $cipher_db_pass = $dbpass->original['ciphertext'];


        $web->db_user = $cipher_db_user;
        $web->db_password = $cipher_db_pass;


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
