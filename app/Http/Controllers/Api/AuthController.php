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
}
