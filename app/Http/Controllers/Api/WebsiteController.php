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
        
        $plaintext[0] = $web->db_name;
        $plaintext[1] = $web->db_user;
        $plaintext[2] = $web->db_password;

        foreach ($plaintext as $i => $el) {
            if (!$el) {
                return response()->json([
                    'success' => '0',
                    'plaintext' => 'plaintext (db name, db user atau db password) tidak ditemukan.'
                ]);
            } elseif (strlen($el) != 16) {
                return response()->json([
                    'success' => '0',
                    'plaintext' => 'plaintext (db name, db user atau db password) harus 16 byte atau 16 karakter.'
                ]);
            }
        }
        

        $dbName = $enc->encryption($web->db_name);
        $dbUser = $enc->encryption($web->db_user);
        $dbpass = $enc->encryption($web->db_password);
        
        $cipher_db_name = $dbName->original['ciphertext'];
        $cipher_db_user = $dbUser->original['ciphertext'];
        $cipher_db_pass = $dbpass->original['ciphertext'];

        $web->db_name = $cipher_db_name;
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
