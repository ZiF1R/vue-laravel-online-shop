<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BrandsController extends Controller
{
    public static function getAllBrands() {
        $res["brands"] = DB::table("Brands")->get();
        return $res;
    }
}
