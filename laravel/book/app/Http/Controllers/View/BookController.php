<?php

namespace App\Http\Controllers\View;

use App\Entity\Category;
use App\Entity\PdtContent;
use App\Entity\Product;
use App\Http\Controllers\Controller;

class BookController extends Controller
{
    public function toCategory()
    {
        $categorys = Category::where('parent_id',0)->get();

        return view('category')->with('categorys',$categorys);
    }

    public function toProduct($category_id)
    {
        $products = Product::where('category_id',$category_id)->get();

        return view('product')->with('products',$products);
    }

    public function toPdtContent($product_id)
    {
        $product = Product::find($product_id);
        $pdt_content = PdtContent::where('product_id',$product_id)->first();

        return view('pdt_content')->with('product',$product)->with('pdt_content',$pdt_content);
    }


}
