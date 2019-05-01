<?php

namespace App\Http\Controllers;

use Exception;
use App\Categories;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    public function addCategory()
    {
        try {
            $data = request()->all();
            $categories = new Categories();
            $category = $categories->addCategory($data);
            $response = [ 'error' => 0, 'data' => $category ];

        } catch( Exception $ex ) {
            $response = [ 'error' => 1, 'message' => $ex->getMessage() ];
        }

        return response()->json($response);
    }
}
