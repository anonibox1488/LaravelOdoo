<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RepairsController extends Controller
{

    public function index() {
		$models = $this->odoo
                ->fields(['id',
                'product_id',
                'name',
                'product_qty',
                'state',
                'amount_total',
                'product_uom',
                'display_name'
                ])
                ->get('repair.order');

        return response()->json($models, 200);
    }
	
    public function __construct()
    {
        $this->odoo = new \Edujugon\Laradoo\Odoo();
        $this->odoo = $this->odoo->connect();
        //instaciar Odoo
    }
    
	public function store(Request $request) {
		$idOrder = $this->odoo->create(
			'repair.order',
			[ //valores minimos para la reparacion sin detalles
				'name' => $request->name,
				'product_id'=> $request->product,
				'product_uom' =>19 //unidad de medida GALONES
			]
		); 

		foreach ($request->pieces as $piece) {
			$resp = $this->repairLine($idOrder,$piece);
		}

		return response()->json(["message" => 'Operacion Exitosa.Reparacion Regstrada.'], 200)
                ->setStatusCode(200, 'Operacion Exitosa.Reparacion Regstrada.');
    }
    //registar los detalles de la orden de reparacion
    public function repairLine($idRepair, $piece)
    {
    	$id = $this->odoo->create(
			'repair.line',
			[ //valores minimos para la reparacion sin detalles
				'repair_id' => $idRepair,
				'name'=> $piece['product'],
				'type'=> 'add',
				'product_id'=> $piece['product_id'],
				'price_unit'=> $piece['price_unit'],
				'product_uom_qty'=> $piece['product_uom_qty'],
				'price_subtotal'=> ($piece['price_unit'] * $piece['product_uom_qty']),	
				'product_uom' =>1, //tipo UNIDADES
				'location_id'=>12,
				'location_dest_id'=>7
			]);
	}    
	//cambiar estatus de la reparacion
	//Opciones disponibles de estatus[draft,confirmed, under_repair, done, cancel]
	public function update($name, Request $request) {
		$updated = $this->odoo->where('name', $name)
            ->update('repair.order',['state' => $request->state]);
    
        return response()->json($updated, 200);
    }

    public function destroy($name) {
		$result = $this->odoo
			->where('name', $name)
            ->delete('repair.order');
     	
        return response()->json($result, 200);
    }
}
