<?php

namespace App\Http\Controllers;

use Exception;
use App\Categories;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    // Add Category
    public function addCategory()
    {
        try {
            $data = request()->all();
            $data['admin'] = request()->get('admin');
            $categories = new Categories();
            $category = $categories->addCategory($data);
            $response = [ 'error' => 0, 'data' => $category ];

        } catch( Exception $ex ) {
            $response = [ 'error' => 1, 'message' => $ex->getMessage() ];
        }

        return response()->json($response);
    }

    // Add Sub Category
    public function addSubCategory()
    {
        try {
            $data = request()->all();
            $data['admin'] = request()->get('admin');
            $categories = new Categories();
            $sub_category = $categories->addSubCategory($data);
            $response = [ 'error' => 0, 'data' => $sub_category ];

        } catch( Exception $ex ) {
            $response = [ 'error' => 1, 'message' => $ex->getMessage() ];
        }

        return response()->json($response);
    }

    // Delete Category
    public function deleteCategory()
    {
        try {
            $data = [];
            $data['id'] = request()->id;
            $data['admin'] = request()->get('admin');
            $categories = new Categories();
            $category = $categories->deleteCategory($data);
            $response = [ 'error' => 0, 'data' => $category ];

        } catch( Exception $ex ) {
            $response = [ 'error' => 1, 'message' => $ex->getMessage() ];
        }

        return response()->json($response);
    }
}
