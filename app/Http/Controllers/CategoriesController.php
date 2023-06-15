<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoriesController extends Controller
{
    public static function createCategory(Request $request): array {
        $name = $request["name"];
        $section_id = $request["section_id"];

        DB::table("Categories")->insert([
            [
                "name" => $name,
                "section_id" => $section_id,
            ]
        ]);
        $res["category"] = DB::table("Categories")
            ->where("name", "=", $name)
            ->get();

        return $res;
    }

    public static function deleteCategory(Request $request): array {
        $id = $request["id"];

        $category = DB::table("Categories")
            ->where("id", "=", $id)
            ->get()[0];

        DB::table("Properties")
            ->where("category_id", "=", $category->id)
            ->delete();

        $products = DB::table("Products")
            ->where("category_id", "=", $category->id)
            ->get();

        foreach ($products as $product) {
            DB::table("Product_property_values")
                ->where("product_code", "=", $product->code)
                ->delete();
            DB::table("Visited_products")
                ->where("product_code", "=", $product->code)
                ->delete();
        }

        DB::table("Products")
            ->where("category_id", "=", $category->id)
            ->delete();

        DB::table("Categories")
            ->where("id", "=", $id)
            ->delete();

        return [];
    }

    public static function getCategoryProducts(Request $request, string $category_id) {
        $query = DB::table("Products")
            ->selectRaw("Products.*, COALESCE(AVG(Product_feedback.rating), 0) as rating, count(Product_feedback.id) as rates_count")
            ->leftJoin("Product_feedback", "Products.code", "Product_feedback.product_code")
            ->where("Products.category_id", "=", $category_id)
            ->groupBy("Products.code");

        $res = [];
        $filters = "";
        $order = "";
        $orderBy = "";
        if (isset($request["order"]) && strlen($request["order"]) > 0) {
            $order = $request["order"];
        }

        if (isset($request["orderBy"]) && strlen($request["orderBy"]) > 0) {
            $orderBy = $request["orderBy"];
            $query->orderBy($orderBy, $order);
        }

        $products = $query->get();

        $filteredProducts = [];
        foreach ($products as $i => $product) {
            $product_code = $product->code;
            $q = DB::table("Properties")
                ->select(["name", "designation", "value"])
                ->join("Product_property_values", "name", "=", "property_name")
                ->where("product_code", "=", $product_code)
                ->where("category_id", "=", $category_id);

            $products[$i]->properties = $q->get();

            if (isset($request["filters"]) && strlen($request["filters"]) > 0) {
                $filters = json_decode($request["filters"]);
                $p = $product;
                $p->properties = $q->get();
                if (self::isMatchFilters($p->properties, $filters)) {
                    $filteredProducts[] = $products[$i];
                }
            }
        }
        if (isset($request["filters"]) && strlen($request["filters"]) > 0) {
            $res["products"] = $filteredProducts;
        } else {
            $res["products"] = $products;
        }

        return $res;
    }

    public static function getCategory(Request $request, string $category_id): array {
        $category = DB::table("Categories")
            ->where("id", "=", $category_id)
            ->get();

        $res["category"] = $category;
        if (count($category) === 1) {
            $res["category"] = $category[0];
        }

        return $res;
    }

    public static function getCategoryFilters(Request $request, string $category_id): array {
        $properties = self::getCategoryProperties($category_id);

        $res["filters"] = [];
        foreach ($properties as $property) {
            $values = self::getPropertyValues($category_id, $property);

            $res["filters"][] = [
                "property" => $property,
                "values" => $values,
            ];
        }

        return $res;
    }

    private static function getFilterByName($filters, $name) {
        foreach ($filters as $filter => $value) {
            if ($filter === $name) {
                return $value;
            }
        }
    }

    private static function isMatchFilters($product_properties, $filters): bool {
        foreach ($product_properties as $property) {
            $name = $property->name;
            $prop_value = $property->value;

            $prop_filters = self::getFilterByName($filters, $name);

            $range_values = [];
            if (!is_array($prop_filters)) {
                foreach ($prop_filters as $key => $item) {
                    if (gettype($item) !== "string") {
                        $range_values[$key] = $item;
                    }
                }
            }

            if (is_array($prop_filters) && count($prop_filters) === 0) {
                continue;
            } else if (!is_array($prop_filters) && count($range_values) === 0) {
                continue;
            }

            $isMatch = false;
            $from = -999999;
            $to = 999999;
            if (!is_array($prop_filters)) {
                if (isset($range_values['from']) && gettype($range_values['from']) !== "string") {
                    $from = $range_values['from'];
                    if (isset($range_values['to']) && gettype($range_values['to']) !== "string") {
                        $to = $range_values['to'];
                    }
                } else {
                    $to = $range_values['to'];
                }
            }


            if (is_array($prop_filters)) {
                $isMatch = in_array($prop_value, $prop_filters);
            } else {
                $isMatch = (int)$prop_value >= $from && (int)$prop_value <= $to;
            }

            if (!$isMatch) {
                return false;
            }
        }

        return true;
    }

    private static function getCategoryProperties(int $category_id) {
        return DB::table("Properties")
            ->where("category_id", "=", $category_id)
            ->get();
    }

    private static function getPropertyValues(int $category_id, $property) {
        $query = DB::table("Properties")
            ->select(["Properties.name"])
            ->where("Properties.category_id", "=", $category_id)
            ->where("Products.category_id", "=", $category_id)
            ->where("Properties.name", "=", $property->name)
            ->join("Product_property_values", "Properties.name", "Product_property_values.property_name")
            ->join("Products", "Product_property_values.product_code", "Products.code");

        if ($property->type == "Number") {
            $result = $query
                ->selectRaw("min(Product_property_values.value) as min, max(Product_property_values.value) as max")
                ->groupBy("Properties.name")
                ->get();
            return count($result) > 0 ? $result[0] : [];
        } else {
            return $query->select(["value"])->get();
        }
    }
}
