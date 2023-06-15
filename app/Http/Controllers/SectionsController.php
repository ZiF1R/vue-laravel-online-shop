<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SectionsController extends Controller
{
    public static function getAllSections() {
        $res["sections"] = DB::table("Sections")->get();
        return $res;
    }

    public static function getSection(Request $request, string $id) {
        $res["section"] = DB::table("Sections")
            ->where("id", "=", $id)
            ->get()[0];

        return $res;
    }

    public static function getSectionCategories(Request $request, string $section_id): array {
        $res["categories"] = DB::table("Categories")
            ->where("section_id", "=", $section_id)
            ->get();
        return $res;
    }

    public static function createSection(Request $request): array {
        $name = $request["name"];

        DB::table("Sections")->insert([
            ["name" => $name]
        ]);
        $res["section"] = DB::table("Sections")
            ->where("name", "=", $name)
            ->get();

        return $res;
    }

    public static function deleteSection(Request $request): array {
        $section_id = (int) $request["id"];

        self::deleteSectionProducts($section_id);
        DB::table("Sections")
            ->where("id", "=", $section_id)
            ->delete();

        return [];
    }

    private static function deleteSectionProducts(int $section_id) {
        $categories = DB::table("Categories")
            ->where("section_id", "=", $section_id)
            ->get();

        foreach ($categories as $category) {
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
        }

        DB::table("Categories")
            ->where("section_id", "=", $section_id)
            ->delete();
    }
}
