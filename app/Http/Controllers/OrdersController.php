<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdersController extends Controller
{
    public static function createOrder(Request $request, string $user_id): array {
        $products = $request["products"];

        $maxId = DB::table("Orders")->max("id");

        $orderId = 1;
        if (isset($maxId)) {
            $orderId = (int)$maxId + 1;
        }

        $order = [];
        $created = date('Y-m-d h:i:s', time());
        foreach ($products as $product) {
            $orderProduct = [
                "id" => $orderId,
                "user_id" => $user_id,
                "product_code" => $product["code"],
                "created" => $created,
                "closed" => "1",
                "count" => $product["order_count"],
            ];
            $order[] = $orderProduct;
        }
        DB::table("Orders")
            ->insert($order);

        $res["order"] = DB::table("Orders")
            ->where("id", "=", $orderId)
            ->get();

        return $res;
    }

    public static function getUserOrders(Request $request, string $user_id): array {
        $res["orders"] = DB::table("Orders")
            ->select(["Orders.*", "Products.name", "Products.category_id", "Products.price"])
            ->where("user_id", "=", $user_id)
            ->join("Products", "Orders.product_code", "Products.code")
            ->get();

        return $res;
    }
}
