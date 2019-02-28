<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductsController extends Controller
{
	public function __construct()
    {
        $this->odoo = new \Edujugon\Laradoo\Odoo();
        $this->odoo = $this->odoo->connect();
        //instaciar Odoo
    }
    //Lsitar Productos
    public function index() {
		$product = $this->odoo
                ->fields(
                [	
                	'name',
                	'product_id',
                	'price_unit',
                	'default_code',
                	'product_tmpl_id',
                	'lst_price',
                	'standard_price'
                ])
                ->get('product.product');
        return response()->json($product, 200);
    }

    // Crear Producto
    public function store(Request $request) {
        $id = $this->odoo->create(
            'product.product',
            [
                'name' => $request->name,
                'type'=> 'product',
                'sale_ok' => $request->sale_ok, // true/flase,
                'purchase_ok' => $request->purchase_ok, // true/flase,
                'lst_price' => $request->lst_price, //2.50,
                'standard_price' =>$request->standard_price, // 3.00,
                'categ_id'=> 1
            ]
        );      
        
        return response()->json($id, 200);
    }
    //Modificar Productos
    public function update($id, Request $request) {
    	$updates =[];
    	foreach ($request->data as $value) {
    		$updates[$value['field']] = $value['value'];
    	}
		$updated = $this->odoo->where('id', $id)
            ->update('product.product',$updates);

        return response()->json($updated, 200);
    }
    //Eliminar productos
    public function destroy($id) {
        $result = $this->odoo->deleteById('product.product',$id);
        return response()->json($result, 200);
    }

}