<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

use App\User;
use App\Website;
use DB;
use Config;

use Validator;

class AuthController extends Controller
{
    public $successStatus = 200;
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function authToken()
    {
        $client = \Laravel\Passport\Client::find(2);

        $this->request->request->add([
            'grant_type' => 'password',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'username' => $this->request->email,
            'password' => $this->request->password,
            'scope' => '*'
        ]);

        $proxy = Request::create('oauth/token', 'POST');

        return \Route::dispatch($proxy);
    }

    public function login()
    {
        $user = User::where('email', $this->request->email)->first();

        if (is_null($user)) {
            return response()->json([
                'errorValidation' => 1,
                'message' => 'Invalid data.',
                'errors' => [
                    'email' => [
                        'Email incorrect or check email for confirm your account.'
                    ]
                ]
            ], 422);
        }

        return $this->authToken();
    }

    public function register()
    {
        $validator = Validator::make($this->request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 401);            
        }

        $input = $this->request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        
        return $this->authToken();
    }

    public function connection()
    {
        $body = $this->request->all();

        $webname = $body['name'];
        $database = $body['database'];
        $username = $body['username'];
        $password = $body['password'];

        Config::set('database.connections.tenant.database', $database);
        Config::set('database.connections.tenant.username', $username);
        Config::set('database.connections.tenant.password', $password);

        DB::purge('tenant');
        DB::reconnect('tenant');

        return response()->json([
            'success' => 1,
            'message' => "Berhasil terkoneksi dengan ".$webname
        ]);
    }

    public function test()
    {

        $newDB = [
            "database" => "patisa",
            "username" => "root",
            "password" => ""
        ];

        $newDB2 = [
            "database" => "denayla_db",
            "username" => "root",
            "password" => ""
        ];
        
        // dd(\Config::get('database.connections'));
        Config::set('database.connections.tenant.database', $newDB['database']);
        Config::set('database.connections.tenant.username', $newDB['username']);
        Config::set('database.connections.tenant.password', $newDB['password']);
        
        
        DB::purge('tenant');
        DB::reconnect('tenant');

        
        // dd(\DB::connection('tenant'));

        $patisa = DB::connection('tenant')->table('barang')->get();

        Config::set('database.connections.tenant.database', $newDB2['database']);
        Config::set('database.connections.tenant.username', $newDB2['username']);
        Config::set('database.connections.tenant.password', $newDB2['password']);

        DB::purge('tenant');
        DB::reconnect('tenant');

        $denayla = DB::connection('tenant')->table('customer')->get();

        return response()->json([
            'patisa' => $patisa,
            'denayla' => $denayla
        ]);
    }
}
