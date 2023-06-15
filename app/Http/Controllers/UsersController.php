<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use mysql_xdevapi\Exception;

class UsersController extends Controller
{
    public static function login(Request $request) {
        $email = $request["email"];
        $password = $request["password"];

        $user = DB::table("MyUsers")
            ->where("mail", "=", $email)
            ->first();

        // TODO **************************
//        $request->validate([
//            'title' => ['required', 'unique:posts', 'max:255'],
//            'body' => ['required'],
//        ]);

        if (!isset($user) || !password_verify($password, $user->password)) {
            $res["error"] = "Неправильный логин или пароль";
            return $res;
        }

        $res["user"] = $user;
        return $res;
    }

    public static function createUser(Request $request) {
        $name = $request["name"];
        $email = $request["email"];
        $password = $request["password"];

        // TODO **************************
//        $request->validate([
//            'title' => ['required', 'unique:posts', 'max:255'],
//            'body' => ['required'],
//        ]);

        // check with password_verify()
        $password_hashed = password_hash($password,PASSWORD_DEFAULT);

        if (!self::isUniqueEmail($email)) {
            $res["error"] = "Пользователь с такой почтой уже существует";
            return $res;
        }

        $user = [
            "mail" => $email,
            "password" => $password_hashed,
            "is_admin" => 0,
            "name" => $name
        ];
        DB::table("MyUsers")->insert([$user]);

        $res["user"] = DB::table("MyUsers")
            ->where("mail", "=", $email)
            ->get()[0];

        return $res;
    }

    public static function getUser(string $id) {
        $res["user"] = DB::table("MyUsers")
            ->where("id", "=", $id)
            ->get()[0];

        return $res;
    }

    public static function changeUserData(Request $request, string $id) {
        $name = $request["name"];
        $mail = $request["mail"];
        $newMail = $request["newMail"];
        $birth = $request["birth"];
        $phone = $request["phone"];

        if ($mail !== $newMail && !self::isUniqueEmail($newMail)) {
            $res["error"] = "Данная почта уже занята";
            return $res;
        }

        $userNewData = [
            "name" => $name,
            "mail" => $newMail,
            "birth" => null,
            "phone" => null,
        ];

        if (isset($birth) && strlen($birth) > 0) {
            $userNewData["birth"] = $birth;
        }

        if (isset($phone) && strlen($phone) > 0) {
            $userNewData["phone"] = $phone;
        }

        DB::table("MyUsers")
            ->where("id", "=", $id)
            ->update($userNewData);

        $res["user"] = DB::table("MyUsers")
            ->where("id", "=", $id)
            ->get()[0];

        return $res;
    }

    public static function getWatchedProducts(Request $request, string $id) {
        $products = DB::table("Visited_products")
            ->selectRaw("Products.*, Visited_products.visit_date, COALESCE(AVG(Product_feedback.rating), 0) as rating, count(Product_feedback.id) as rates_count")
            ->where("Visited_products.user_id", "=", $id)
            ->join("Products", "Visited_products.product_code", "=", "Products.code")
            ->leftJoin("Product_feedback", "Products.code", "=", "Product_feedback.product_code")
            ->groupBy("Products.code")
            ->groupBy("Visited_products.id")
            ->orderBy("visit_date", "DESC")
            ->get();

        foreach ($products as $i => $product) {
            $products[$i]->properties = self::getProductProperties($product->code, $product->category_id);
        }

        $res["products"] = $products;
        return $res;
    }

    public static function setWatchedProduct(Request $request, string $id): array {
        $product_code = (int) $request["product_code"];

        if (self::isAlreadyWatched($id, $product_code)) {
            return [];
        }

        $watch_info = [
            "user_id" => (int) $id,
            "product_code" => $product_code,
            "visit_date" => date('Y-m-d h:i:s', time())
        ];

        try {
            DB::table("Visited_products")->insert([$watch_info]);
        } catch(\Illuminate\Database\QueryException $ex){
            return [$ex->getMessage()];
        }

        return [];
    }

    public static function removeWatchedProduct(Request $request, string $id): array {
        $code = (int) $request["product_code"];

        DB::table("Visited_products")
            ->where("user_id", "=", $id)
            ->where("product_code", "=", $code)
            ->delete();

        return [];
    }

    private static function getProductProperties(string $code, string $category_id) {
        return DB::table("Properties")
            ->select(["name", "designation", "value"])
            ->join("Product_property_values", "name", "=", "property_name")
            ->where("product_code", "=", $code)
            ->where("category_id", "=", $category_id)
            ->get();
    }

    private static function isAlreadyWatched(int $user_id, int $code): bool {
        return count(DB::table("Visited_products")
                ->where("user_id", "=", $user_id)
                ->where("product_code", "=", $code)
                -> get()) > 0;
    }

    private static function isUniqueEmail($email): bool {
        $users = DB::table("MyUsers")
            ->where("mail", "=", $email)
            ->get();
        return count($users) === 0;
    }
}
