<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class testController extends Controller
{
    public function __construct()
    {
        $this->odoo = new \Edujugon\Laradoo\Odoo();
        $this->odoo = $this->odoo->connect();
        //instaciar Odoo
    }

	/**
 	* [Obtener la version de odoo]
 	*  @return [Json] [description]
 	*/
    public function getVersion() {
     	$version = $this->odoo->version();
        return response()->json($version, 200);
    }
    /**
     * [Obtener el Id del usuario conectado]
     * @return [INT] [Id del usuario en odoo]
     */
    public function getIdLogin() {
     	$userId= $this->odoo->getUid();
        return response()->json($userId, 200);
    }
    /**
     * [Obtener los permisos]
     * @param  [string] $model [Nombre del modelo]
     * @return [bool]        [true/false]
     */
    public function getPermisos($model= null) {
    	if(!$model){
    		return response()->json('Debes ingresar el modelo a consultar', 500);
    	}
     	$can = $this->odoo->can('read', $model);
        return response()->json($can, 200);
    }
	
	/**
	 * [Obtener los Ids de los registro del modelo]
	 * @param  [string] $model [Nombre del modelo]
	 * @return [array]        [Ids]
	 */
    public function getProvides($model= null) {
    	if(!$model){
    		return response()->json('Debes ingresar el modelo a consultar', 500);
    	}
        $ids = $this->odoo
        	// ->where('is_company', false)
            // ->where('customer', '=', true)
            // ->limit(3)
            ->search($model);
        return response()->json($ids, 200);
    }
	/**
	 * [Lista todos los registros con todos los campos del modelo]
	 * @param  [string] $model [Nombre del modelo]
	 * @return [JSON]
	 */
    public function getModels($model= null) {
    	if(!$model){
    		return response()->json('Debes ingresar el modelo a consultar', 500);
    	}
		$models = $this->odoo
				// ->where('customer', true)
                // ->limit(1)
                ->get($model);
        return response()->json($models, 200);
    }
    /**
     * [Obtiene los registros con los campos solicitados]
     * [En este caso se trabaja con el modelo de productos]
     * @return [JSON] [campos descritos]
     */
    public function getModelsPro() {
		$models = $this->odoo
				// ->where('customer', false)
                // ->limit(2) //el numero de registros
                ->fields(['name', 'product_id', 'price_unit','default_code', 'product_tmpl_id'])
                ->get('product.product');
        return response()->json($models, 200);
    }
    /**
     * [Creacion de contactos]
     * [Se pasa un array con los campos que se quieren ingresar]
     * @param  Request $request
     * @return [INT]           [Id del usuario registrado]
     */
	public function store(Request $request) {
		$id = $this->odoo->create('res.partner',
			['name' => $request->name, 'email'=>$request->email]);    	
     	
        return response()->json($id, 200);
    }
    /**
     * [Modificacion de datos]
     * [En este paso de modifica el nombre del contacto y se usa el Email para identificarlo]
     * @param  [string]  $emial   [email del usuario al cual se le hace la modificacion]
     * @param  Request $request
     * @return [bool]           [true/false]
     */
    public function update($emial,Request $request) {
		$updated = $this->odoo->where('email', $emial)
            ->update('res.partner',['name' => $request->name]);
     	
        return response()->json($updated, 200);
    }
    /**
     * [Eliminacion de registro]
     * [Se usan los parametros para identificar el registro a eliminar]
     * @param  [String] $field [Nombre del campo]
     * @param  [String] $value [Valor del campo]
     * @return [JSON]        [true/Error]
     */
    public function dropByWhere($field,$value) {
		$result = $this->odoo
			->where($field, $value)
            ->delete('res.partner');
     	
        return response()->json($result, 200);
    }
    /**
     * [Eliminacion de registro]
     * @param  [INT] $id [Id del registro a eliminar]
     * @return [JSON]        [true/Error]
     */
    public function dropById($id) {
		$result = $this->odoo->deleteById('res.partner',$id);
     	
        return response()->json($result, 200);
    }
	/**
	 * [Si se esta familiarizado con el api de odoo]
	 * [Puedes ejecutar las funciones propias como lo intica la documentacion.]
	 * @return [JSON] [$resul]
	 */
    public function testOdoo() {
    	
		$result = $this->odoo->call('res.partner', 'search',[
        [
            ['is_company', '=', true],
            ['customer', '=', true]
        ]
	    ],
	    [
	        'offset'=>1,
	        'limit'=>5
	    ]);
        return response()->json($result, 200);
    }
}
