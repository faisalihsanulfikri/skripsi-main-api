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

    function randomString() {
        $length = 16;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function getByUserId($id)
    {
        // $header = array (
        //         'Content-Type' => 'application/json; charset=UTF-8',
        //         'charset' => 'utf-8'
        //     );

        // $data = "B4254F00E83A86D9E73F58927A42BDF0";

        // // $hasil = [
        // //     "data" => hex2bin($data)
        // // ];

        // // $hasil[0] = "'".(hex2bin($data))."'";
        // $hasil = utf8_encode(hex2bin($data));

        // $hex = strtoupper(bin2hex(utf8_decode($hasil)));


        // return  response()->json([
        //     "encode" => $hasil,
        //     "hex" => $hex
        // ]);

        // return response()->json(hex2bin($data), 200, ['Content-type'=> 'application/json; charset=utf-8'], JSON_UNESCAPED_UNICODE);


        // return;
        $cipherkey = $this->randomString();

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

        $web->db_name = "tn_00000073undef";
        $web->db_user = "tn_00000073undef";
        $web->db_password = "t3Qeabj4uPmqk40L";
        
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
        

        $dbName = $enc->encryption($web->db_name, $cipherkey);
        $dbUser = $enc->encryption($web->db_user, $cipherkey);
        $dbpass = $enc->encryption($web->db_password, $cipherkey);
        
        $cipher_db_name = $dbName->original['ciphertext'];
        $cipher_db_user = $dbUser->original['ciphertext'];
        $cipher_db_pass = $dbpass->original['ciphertext'];

        $web->db_name = $cipher_db_name;
        $web->db_user = $cipher_db_user;
        $web->db_password = $cipher_db_pass;


        // test decryiption
        $dec = new DecryptionController;

        $decName = $dec->decryption($web->db_name, $cipherkey);
        $decUser = $dec->decryption($web->db_user, $cipherkey);
        $decpass = $dec->decryption($web->db_password, $cipherkey);

        $authTenant = [
            "db_name" => $decName->original['plaintext'],
            "db_user" => $decUser->original['plaintext'],
            "db_password" => $decpass->original['plaintext']
        ];

        $result = [
            'success' => 1,
            'message' => 'data website ditemukan.',
            'enc' => $web,
            'dec' => $authTenant,
            "cipherkey" => $cipherkey
        ];

        return $result;
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
