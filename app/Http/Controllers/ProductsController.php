<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductsController extends Controller
{
    public static function getProducts(Request $request) {
        if (!isset($request["search"])) {
            return DB::table("Products")->get();
        } else if ($request["search"] === "") {
            return [];
        }
        $search = $request["search"];

        $res["products"] = DB::table("Products")
            ->selectRaw("Products.*, Brands.name as brand_name, Categories.name as category_name, COALESCE(AVG(Product_feedback.rating), 0) as rating")
            ->join("Brands", "Products.brand_id", "Brands.id")
            ->join("Categories", "Products.category_id", "Categories.id")
            ->leftJoin("Product_feedback", "Products.code", "Product_feedback.product_code")
            ->where("Products.name", "LIKE", "%$search%")
            ->groupBy("Products.code")
            ->get();

        return $res;
    }

    public static function getProduct(Request $request, string $code) {
        if (isset($request["product"])) {
            return self::createProduct($request, $code);
        }

        $product = DB::table("Products")
            ->selectRaw("Products.*, COALESCE(AVG(Product_feedback.rating), 0) as rating, count(Product_feedback.id) as rates_count")
            ->leftJoin("Product_feedback", "Products.code", "Product_feedback.product_code")
            ->where("Products.code", "=", $code)
            ->groupBy("Products.code")
            ->get()[0];

        $product->properties = self::getProductProperties($code, $product->category_id);
        $product->feedback = self::getProductFeedback($code);

        for ($i = 0; $i < count($product->feedback); $i++) {
            $product->feedback[$i]->replies = self::getFeedbackReplies($code, $product->feedback[$i]->id);
        }

        $res["product"] = $product;
        return $res;
    }

    public static function deleteProduct(Request $request, string $code): array {
        DB::table("Product_property_values")
            ->where("product_code", "=", $code)
            ->delete();

        DB::table("Visited_products")
            ->where("product_code", "=", $code)
            ->delete();

        DB::table("Products")
            ->where("code", "=", $code)
            ->delete();

        return [];
    }

    public static function createProduct(Request $request, string $code): array {
        $product = json_decode($request["product"], JSON_UNESCAPED_UNICODE);

        $prod = [
            "code" => $product["code"],
            "name" => $product["name"],
            "category_id" => $product["category_id"],
            "brand_id" => $product["brand_id"],
            "price" => $product["price"],
            "count" => $product["count"],
            "description" => $product["description"],
            "photo_link" => $product["photo_link"],
        ];

        DB::table("Products")
            ->insert([$prod]);

        foreach ($product["properties"] as $property) {
            DB::table("Product_property_values")
                ->insert([[
                    "product_code" => $product["code"],
                    "property_name" => $property["name"],
                    "value" => $property["value"],
                ]]);
        }

        $res["product"] = $product;
        return $res;
    }

    public static function sendFeedback(Request $request, string $code): array {
        $user_id = $request["user_id"];
        $rating = $request["rating"];
        $comment = $request["comment"];
        $reply_comment_id = $request["reply_comment_id"];

        $feedback = [
            "user_id" => $user_id,
            "product_code" => $code,
            "created" => date('Y-m-d', time()),
        ];
        if (isset($rating)) {
            $feedback["rating"] = $rating;
        }
        if (strlen($comment) > 0) {
            $feedback["comment"] = $comment;
        }
        if (isset($reply_comment_id)) {
            $feedback["reply_comment_id"] = $reply_comment_id;
        }

        DB::table("Product_feedback")->insert([$feedback]);

        return [];
    }

    public static function removeFeedback(Request $request, string $code) {
        $id = $request["id"];

        DB::table("Product_feedback")
            ->where("reply_comment_id", "=", $id)
            ->where("product_code", "=", $code)
            ->delete();

        DB::table("Product_feedback")
            ->where("id", "=", $id)
            ->where("product_code", "=", $code)
            ->delete();
    }

    public static function getProductTotalRating(Request $request, string $code) {
        try {
            $rating = DB::table("Product_feedback")
                ->selectRaw("COALESCE(AVG(Product_feedback.rating), 0) as average, count(Product_feedback.rating) as count, sum(case when rating = 1 then 1 else 0 end) as rate_1, sum(case when rating = 2 then 1 else 0 end) as rate_2, sum(case when rating = 3 then 1 else 0 end) as rate_3, sum(case when rating = 4 then 1 else 0 end) as rate_4, sum(case when rating = 5 then 1 else 0 end) as rate_5")
                ->where("Product_feedback.product_code", "=", $code)
                ->join("Products", "Product_feedback.product_code", "=",  "Products.code")
                ->groupBy("Products.code")
                ->get();

            $res["rating"] = $rating;

            if (count($rating) > 0) {
                $res["rating"] = $rating[0];
            } else {
                $res["rating"] = null;
            }

            return $res;
        } catch(\Illuminate\Database\QueryException $ex){
            return [$ex->getMessage()];
        }
    }

    private static function getFeedbackReplies($code, $comment_id) {
        return DB::table("Product_feedback")
            ->select(["Product_feedback.*", "name as user_name"])
            ->join("MyUsers", "user_id", "MyUsers.id")
            ->where("product_code", "=", $code)
            ->where("reply_comment_id", "=", $comment_id)
            ->get();
    }

    private static function getProductFeedback($code) {
        return DB::table("Product_feedback")
            ->select(["Product_feedback.*", "name as user_name"])
            ->join("MyUsers", "user_id", "MyUsers.id")
            ->where("product_code", "=", $code)
            ->whereNull("reply_comment_id")
            ->get();
    }

    private static function getProductProperties(string $code, string $category_id) {
        return DB::table("Properties")
            ->select(["name", "designation", "value"])
            ->join("Product_property_values", "name", "=", "property_name")
            ->where("product_code", "=", $code)
            ->where("category_id", "=", $category_id)
            ->get();
    }
}
