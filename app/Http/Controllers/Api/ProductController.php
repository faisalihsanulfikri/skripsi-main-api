<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use DB;
use Validator;

class ProductController extends Controller
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $product = [];

        $getProduct = DB::connection('tenant')->table('products')->get();

        if (count($getProduct) > 0) {
            $product = $getProduct;
        }

        return response()->json([
            'data' => $product
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $body = $this->request->all();
        $now = date("Y-m-d H:i:s");

        $validator = Validator::make($this->request->all(), [
            'name' => 'required',
            'price' => 'required',
            'qty' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 401);            
        }

        $product = DB::connection('tenant')
                ->table('products')
                ->insert([
                    'name' => $body['name'],
                    'price' => $body['price'],
                    'qty' => $body['qty'],
                    'created_at' => $now,
                    'updated_at' => $now
                ]);

        return response()->json([
            'success' => 1,
            'message' => 'Produk berhasil dibuat.'
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
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id)
    {
        $body = $this->request->all();
        $now = date("Y-m-d H:i:s");

        $validator = Validator::make($this->request->all(), [
            'name' => 'required',
            'price' => 'required',
            'qty' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 401);            
        }

        $product = DB::connection('tenant')
                ->table('products')
                ->where('id', $id)
                ->update([
                    'name' => $body['name'],
                    'price' => $body['price'],
                    'qty' => $body['qty'],
                    'updated_at' => $now
                ]);

        return response()->json([
            'success' => 1,
            'message' => 'Produk berhasil diperbaharui.'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $product = DB::connection('tenant')
                ->table('products')
                ->where('id', $id)
                ->first();

        if (!$product) {
            return response()->json([
                'error' => 1,
                'message' => "Data produk tidak ada."
            ]);
        }

        $product = DB::connection('tenant')
                ->table('products')
                ->where('id', $id)
                ->delete();

        return response()->json([
            'success' => 1,
            'message' => "Produk berhasil dihapus."
        ]);
    }
}
