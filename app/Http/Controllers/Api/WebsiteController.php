<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use DB;

class WebsiteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    public function getByUserId($id)
    {
        // $web = Website('user_id', $id)->first();

        $web = DB::connection('mysql')
                ->table('websites')
                ->select(
                    'id',
                    'user_id',
                    'name',
                    'database',
                    'username',
                    'password'
                )
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

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        # code...
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
