<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\FileImportRequest;
use DB;
use Carbon\Carbon;
use App\Upload;

class PageController extends Controller{

    public function importApi(Request $request){
        $file = $request->file('myfile');
        $data =  Excel::load($file, function($reader) {})->get();
        $info = $this->detect();
        $now = Carbon::now()->toDateTimeString();
        $date = explode(" ", $now);
        $ip = $request->getClientIp();
        $city = $this->detect_city($ip);
        $errs = $this->validationsTitles($data);
        if(count($errs) > 0 ){
            return response()->json(["message" => 'Error al subir el archivo', 'Error'=> $errs], 500)
                ->setStatusCode(500, 'Error al subir el archivo');
        }

        $errs = $this->validationsCount($data);
        if(count($errs) > 0 ){
            return response()->json(["message" => 'Error al subir el archivo', 'Error'=> $errs], 500)
                ->setStatusCode(500, 'Error al subir el archivo');
        }

        $errs = $this->validationsOrder($data);
        if(count($errs) > 0 ){
            return response()->json(["message" => 'Error al subir el archivo', 'Error'=> $errs], 500)
                ->setStatusCode(500, 'Error al subir el archivo');
        }        

        $errs = $this->validations($data);
        if(count($errs) > 0 ){
            return response()->json(["message" => 'Error al subir el archivo', 'Error'=> $errs], 500)
                ->setStatusCode(500, 'Error al subir el archivo');
        }

        $newData = $this->data($data, $date, $ip, $city, $info);
        DB::table('uploads')->insert($newData);
        return response()->json(["message" => "Operacion Exitosa, Archivo Cargado."], 200)
                ->setStatusCode(200, 'Operacion Exitosa, Archivo Cargado.');
    }

    /**
     *  Listado de archivos posibles para descargar
     * @return [array] [objto de los archivos los cuales se pueden descargar]
     */
    public function exportApi() {
        $files = DB::table('uploads')
            ->select('file_name','date','hour','ip', 'os', 'location')
            ->groupBy('file_name','date','hour','ip', 'os','location')
            ->orderBy('file_name', 'desc')
            ->get();
        return response()->json($files, 200);
    }


    public function exportOriginalApi($fileName) {

        Excel::create('APC', function($excel) use($fileName) {
            $excel->sheet('APC_BASE', function($sheet)  use ($fileName){
                $data = Upload::select('loan','borrower_name','borrower_cedula','borrower_passaporte','loan_amount','term','contract_date','last_payment_date', 'principal_balance', 'final_payment_date', 'current_date','next_due_amount', 'last_payment_amt')
                ->where('file_name','=', $fileName)
                ->orderBy('id')
                ->get();

                $sheet->fromArray($data);
                $sheet->setOrientation('landscape');
            });
        })->export('xls');
    }
    /**
     * [exportApcProductoApi description]
     * @param  [string] $fileName [nombre del archivo a procesar]
     * @return [xls]           [documento excel]
     */
    public function exportApcProductoApi($fileName) {
        
        Excel::create('REFERE', function($excel) use($fileName) {
            $excel->sheet('REFERE', function($sheet)  use ($fileName){
                $query = Upload::select('id','loan','borrower_name','borrower_cedula','borrower_passaporte','loan_amount','term','contract_date','last_payment_date', 'principal_balance', 'final_payment_date', 'current_date','next_due_amount', 'last_payment_amt')
                ->where('file_name','=', $fileName)
                ->orderBy('id')
                ->get();
                $data = $this->createApcProducto($query);
                $sheet->fromArray($data);
                $sheet->setOrientation('landscape');
            });
        })->export('xls');

    }

    /**
     * [exportApcClienteApi description]
     * @param  [string] $fileName [nombre del archivo a procesar]
     * @return [xls]           [documento excel]
     */
    public function exportApcClienteApi($fileName) {
        Excel::create('CLIENTE', function($excel) use($fileName) {
            $excel->sheet('CLIENTE', function($sheet)  use ($fileName){
                $query = Upload::select('loan','borrower_name','borrower_cedula','borrower_passaporte','loan_amount','term','contract_date','last_payment_date', 'principal_balance', 'final_payment_date', 'current_date','next_due_amount', 'last_payment_amt')
                ->where('file_name','=', $fileName)
                ->orderBy('id')
                ->get();

                $data = $this->createApcCliente($query);
                $sheet->fromArray($data);
                $sheet->setOrientation('landscape');
            });
        })->export('xls');
    }
    /**
     * [exportDatosJuridicosApi description]
     * @param  [type] $fileName [description]
     * @return [type]           [description]
     */
    public function exportDatosJuridicosApi($fileName) {
        $query = Upload::select('borrower_name')
                ->where('file_name','=', $fileName)
                ->orderBy('id')
                ->get();
        $data ='';
        $saltoLinea = "\r\n";
        foreach ($query as $value) {
            $data =$data.$value->borrower_name.' '.$saltoLinea;
        }

        $headers = array(
          'Content-Type' => 'plain/txt',
          'Content-Disposition' => sprintf('attachment; filename="%s"', 'DATAJURIDICA.txt'),
      );
      return \Response::make($data, 200, $headers);
    }
    /**
     * [createApcProducto description]
     * @param  [array] $query [datos a procesar]
     * @return [array]        [array de datos a exportar a xls]
     */
    public function createApcProducto($query) {   
    
        $data = [];
        $i = 1;
        foreach ($query as $value) {
            $bcg = explode("-", $value['borrower_cedula']);
            $bc =str_split($value['borrower_cedula']);
            $identificacion1 = (is_numeric($bc[0])) ? $bcg[0]: '';
            $identificacion2 = ($bc[0] == 'E') ? 'E': '';
            if($value['borrower_cedula'] !== ''){$pos = strpos($value['borrower_cedula'], '-');}
            $identificacion3 = (is_numeric($identificacion1)) ? $bcg[1]: (($identificacion2 == 'E') ? $bc[$pos-1]: '');
            if( is_numeric($identificacion1)){
                $bcg = explode("-", $value['borrower_cedula']);
                $identificacion4 = $bcg[2];
            }elseif ($identificacion2 === 'E') {
                $bcg = explode("-", $value['borrower_cedula']);
                $identificacion4 = $bcg[1];
            }elseif ($identificacion1 === '' && $identificacion2 === '') {
                $identificacion4 = $value['borrower_passaporte'];
            }
            $tipoClie = ($identificacion1 === '' && $identificacion1 === '')? '3': '1' ;
            $fecLiquidacion = ($value['principal_balance'] == '0') ? $value['last_payment_date'] : '';
            $lpd = explode("/", $value['last_payment_date']);
            $end = new Carbon('last day of last month');
            $start = Carbon::createFromDate($lpd[2],$lpd[0],$lpd[1]);
            $diferencia = $start->diffInDays($end);
            switch ($diferencia) {
                case ($diferencia == 0):
                    $estatusRef = '1';                    
                    $numDiasAtraso = '0';
                    break;
                case ($diferencia <= 30):
                    $estatusRef = '2';                    
                    $numDiasAtraso = '30';
                    break;
                case ($diferencia <= 60):
                    $estatusRef = '3';
                    $numDiasAtraso = '60';
                    break;
                case ($diferencia <= 90):
                    $estatusRef = '4';
                    $numDiasAtraso = '90';
                    break;
                case ($diferencia <= 120):
                    $estatusRef = '5';
                    $numDiasAtraso = '120';
                    break;
                case ($diferencia <= 150):
                    $estatusRef = '6';
                    $numDiasAtraso = '150';
                    break;
                case ($diferencia <= 180):
                    $estatusRef = '7';
                    $numDiasAtraso = '180';
                    break;
                case ($diferencia <= 365):
                    $estatusRef = '8';
                    $numDiasAtraso = '365';
                    break;
                default:
                   $estatusRef = '9';
                   $numDiasAtraso = 'mayor de 365';
            }
            $fc = explode(' ', $end);
            $fcf = explode('-', $fc[0]);
            $fecCorte = $fcf[1].'/'.$fcf[2].'/'.$fcf[0];

            $row = [
                'Identificacion1' => $identificacion1,
                'Identificacion2' => $identificacion2,
                'Identificacion3' => $identificacion3,
                'Identificacion4' => $identificacion4,
                'Tipo_Clie' => $tipoClie,
                'Cod_Grupo_Econ' => 'H',
                'Tipo_Asoc' =>'2',
                'Ident_Asoc' => '170044', 
                'Cuenta' => $i,
                'User_ID' => 'DM170044',
                'Tipo_Forma_Pago' => 'P30',
                'Tipo_Relacion' => 'esperando informacion',
                'Fec_Inicio_Rel' => $value['contract_date'],
                'Fec_Fin_Rel' => $value['final_payment_date'],
                'Monto_Original' => $value['loan_amount'],
                'Saldo_Actual' => $value['principal_balance'],
                'Num_Pagos' => $value['term'],
                'Importe_Pago' => 'esperando informacion',
                'Fec_Ultimo_Pago' => $value['last_payment_date'],
                'Monto_Ultimo_Pago' => $value['last_payment_amt'],
                'Fec_Liquidacion' => $fecLiquidacion,
                'Tipo_Comporta' => '',
                'Estatus_Ref' => $estatusRef,
                'Num_Dias_Atraso' => $numDiasAtraso,
                'Monto_Codificado' => '',
                'Tipo_Cifra' => '',
                'Observacion' => '',
                'Fec_Corte' => $fecCorte,
                'Nom_Fiador' => '',
                'Ced_Fiador' => ''
            ];
            array_push($data, $row);
            $i ++;
        }
        return $data;
    }

    public function createApcCliente($query) {

        $data = [];
        foreach ($query as $value) {
            $bcg = explode("-", $value['borrower_cedula']);
            $bc =str_split($value['borrower_cedula']);
            $identificacion1 = (is_numeric($bc[0])) ? $bcg[0]: '';
            $identificacion2 = ($bc[0] == 'E') ? 'E': '';
            if($value['borrower_cedula'] !== ''){$pos = strpos($value['borrower_cedula'], '-');}

            $identificacion3 = (is_numeric($identificacion1)) ? $bcg[1]: (($identificacion2 == 'E') ? $bc[$pos-1]: '');
            if( is_numeric($identificacion1)){
                $bcg = explode("-", $value['borrower_cedula']);
                $identificacion4 = $bcg[2];
            }elseif ($identificacion2 === 'E') {
                $bcg = explode("-", $value['borrower_cedula']);
                $identificacion4 = $bcg[1];
            }elseif ($identificacion1 === '' && $identificacion2 === '') {
                $identificacion4 = $value['borrower_passaporte'];
            }
            $tipoClie = ($identificacion1 === '' && $identificacion1 === '')? '3': '1' ;
            $fullName = explode(" ", $value['borrower_name']);

            switch (count($fullName)) {
                case 1:
                    $apelPaterno = '';
                    $apelMaterno = '';
                    $apelCasada = '';
                    $primerNom = $fullName[0];
                    $segundoNom = '';
                    break;
                case 2:
                    $apelPaterno = $fullName[1];
                    $apelMaterno = '';
                    $apelCasada = '';
                    $primerNom = $fullName[0];
                    $segundoNom = '';
                    break;
                case 3:
                    $apelPaterno = $fullName[1];
                    $apelMaterno = $fullName[2];
                    $apelCasada = '';
                    $primerNom = $fullName[0];
                    $segundoNom = '';
                    break;
                case 4:
                    $apelPaterno = $fullName[2];
                    $apelMaterno = $fullName[3];
                    $apelCasada = '';
                    $primerNom = $fullName[0];
                    $segundoNom = $fullName[1];
                    break;
                case 5:
                    if($fullName[3]=="de"){
                        $apelPaterno = $fullName[2];
                        $apelMaterno = '';
                        $apelCasada = $fullName[3].' '.$fullName[4];
                        $primerNom = $fullName[0];
                        $segundoNom = $fullName[1];
                    }else{
                        $apelPaterno = $fullName[3];
                        $apelMaterno = $fullName[4];
                        $apelCasada = '';
                        $primerNom = $fullName[0];
                        $segundoNom = $fullName[1].' '.$fullName[2];
                    }                    
                    break;
                default:
                    $resto = '';
                    for ($i=3; $i < count($fullName); $i++) { 
                        $resto =  $resto .' '.$fullName[$i];
                    }

                    $apelPaterno = $fullName[2];
                    $apelMaterno = $resto;
                    $apelCasada = '';
                    $primerNom = $fullName[0];
                    $segundoNom = $fullName[1];
            }
            
            $row = [
                'Identificacion1' => $identificacion1,
                'Identificacion2' => $identificacion2,
                'Identificacion3' => $identificacion3,
                'Identificacion4' => $identificacion4,
                'Tipo_Clie' => $tipoClie,
                'Apel_Paterno' =>  $apelPaterno,
                'Apel_Materno' => $apelMaterno,
                'Apel_Casada' => $apelCasada,
                'Primer_Nom' => $primerNom,
                'Segundo_Nom' => $segundoNom,
                'Sexo_Clie' => '',
                'Seguro_Social' => '',
                'Estado_Civil' => '',
                'Nom_Legal' => '',
                'Nom_Comercial' => '',
                'Direc_Clie' => '',
                'Fec_Nac_Ini_Op' => '',
                'Telef_Casa' => '',
                'Telef_Fax1' => '',
                'Telef_Fax2' => '',
                'Telef_Otro' => '',
                'Telef_Celular' => '',
                'Lug_Trab' => '',
                'Direc_Trab' => '',
                'Posicion_Trab' => '',
                'Ingreso_Mensual' => '',
                'Fec_Ingreso_Trab' => '',
                'Telef_Oficina' => '',
                'Telef_Oficina_Ext' => '',
                'Nom_Conyuge' => '',
                'Apel_Conyuge' => '',
                'Ced_Conyuge' => '',
                'Nom_Padre' => '',
                'Apel_Padre' => '',
                'Nom_Madre' => '',
                'Apel_Madre' => ''
            ];
            array_push($data, $row);
        }
        return $data;
    }

    public function createDataJuriico($query) {
    
        $data = [];
        foreach ($query as $value) {
            $row = [$value['borrower_name']];
            array_push($data, $row);
        }
        return $data;
    }
    /**
     * [validationsCount]
     * @param  [arrya] $file [archivo]
     * @return [array]       [errores por la cantidad de registros]
     */
    public function validationsCount($file){
        $erros = [];
        $records = Upload::all();
        if(count($records) > 0){
            $last = $records->last();
            $countFile = count($file);
            $data = Upload::where('file_name',$last->file_name)->count();
            if($countFile < $data){
                array_push($erros, 'El archivo no contiene todos los registros');
            }
        }
        return $erros;
    }
    /**
     * [validationsOrder]
     * @param  [array] $file [archivo]
     * @return [array]       [errores en la posicion de los registross]
     */
    public function validationsOrder($file){
        $erros = [];
        $records = Upload::all();
        if(count($records) > 0){
            $last = $records->last();
            $data = Upload::where('file_name',$last->file_name)
                ->orderBy('id')
                ->get();
            for ($i=0; $i <count($data) ; $i++) {
                if( strtoupper($file[$i]['borrower_name']) !== strtoupper($data[$i]['borrower_name'])){
                    array_push($erros, 'El registro '.$file[$i]['borrower_name']. ' no corresponde a la posicion '. $i);
                }
            }
        }
        return $erros;
    }
    /**
     * [validationsTitles]
     * @param  [array] $file [archivo]
     * @return [array]       [errores en los titulos de los campos]
     */
    public function validationsTitles($file){
        $erros = [];
        $keys = [];
        for ($i=0; $i < 1 ; $i++) { 
            foreach ($file[$i] as $key => $value) {
                array_push($keys, $key);
            }
        }

        if (!in_array('loan',$keys)) {
            array_push($erros, 'Debe existir el campo Loan');   
        }
        if (!in_array('borrower_name',$keys)) {
            array_push($erros, 'Debe existir el campo Borrower Name');
        }
        if (!in_array('borrower_cedula',$keys)) {
            array_push($erros, 'Debe existir el campo Borrower Cedula');
        }
        if (!in_array('borrower_passaporte',$keys)) {
            array_push($erros, 'Debe existir el campo Borrower Passaporte');
        }
        if (!in_array('loan_amount',$keys)) {
            array_push($erros, 'Debe existir el campo Loan Amount');
        }
        if (!in_array('term',$keys)) {
            array_push($erros, 'Debe existir el campo Term');
        }
        if (!in_array('contract_date',$keys)) {
            array_push($erros, 'Debe existir el campo Loan');   
        }
        if (!in_array('principal_balance',$keys)) {
            array_push($erros, 'Debe existir el campo Principal Balance');
        }
        if (!in_array('last_payment_date',$keys)) {
            array_push($erros, 'Debe existir el campo Last Payment Date');
        }
        if (!in_array('last_payment_amt',$keys)) {
            array_push($erros, 'Debe existir el campo Last Payment Amt');
        }
        if (!in_array('final_payment_date',$keys)) {
            array_push($erros, 'Debe existir el campo Final Payment Date');
        }
        if (!in_array('current_date',$keys)) {
            array_push($erros, 'Debe existir el campo Current Date');
        }
        if (!in_array('next_due_amount',$keys)) {
            array_push($erros, 'Debe existir el campo Next Due Amount');
        }
        return $erros;
    }
    /**
     * [validations]
     * @param  [array] $file [archivo]
     * @return [array]       [errores en los formatos de los campos]
     */
    public function validations($file){   

        $erros = [];
        $i=1;
        $saltoLinea = "\r\n";
        foreach ($file as $row) {
            if (!(preg_match("/^[a-zA-Z0-9ñÑ\s-]{3,50}$/", $row['borrower_name']))) { 
                array_push($erros, 'Borrower Name en la fila '.$i. ' contiene  errores de caracteres');
            }
            if (!(preg_match("/^[0-9\.,]{1,50}$/", $row['loan_amount']))) { 
                array_push($erros, 'Loan Amount en la fila '.$i. ' debe ser un numero');
            }
            if (!(preg_match("/^[0-9\.,]{1,50}$/",$row['term']))) { 
                array_push($erros, 'Term en la fila '.$i. ' debe ser un numero');
            }
            
            if (!(preg_match("/^(0[1-9]|1[0-2])\/(0[1-9]|1\d|2\d|3[01])\/(19|20)\d{2}$/", $this->getDate($row['contract_date']) ))) { 
                array_push($erros, 'Contract Date en la fila '.$i. ' debe tener el formato mm/dd/aaaa');
            }
            if (!(preg_match("/^[0-9\.,]{1,50}$/", $row['principal_balance']))) { 
                array_push($erros, 'Principal Balance en la fila '.$i. ' debe ser un numero');
            }
            
            if (!(preg_match("/^(0[1-9]|1[0-2])\/(0[1-9]|1\d|2\d|3[01])\/(19|20)\d{2}$/", $this->getDate($row['last_payment_date']) ))) { 
                array_push($erros, 'Last Payment Date en la fila '.$i. ' debe tener el formato mm/dd/aaaa');
            }
            if (!(preg_match("/^[0-9\.,]{1,50}$/",$row['last_payment_amt']))) { 
                array_push($erros, 'Last Payment Amt en la fila '.$i. ' debe ser un numero');
            }
            if (!(preg_match("/^(0[1-9]|1[0-2])\/(0[1-9]|1\d|2\d|3[01])\/(19|20)\d{2}$/",$this->getDate($row['final_payment_date'])))) { 
                array_push($erros, 'Last Payment Date en la fila '.$i. ' debe tener el formato mm/dd/aaaa');
            }
            
            if (!(preg_match("/^(0[1-9]|1[0-2])\/(0[1-9]|1\d|2\d|3[01])\/(19|20)\d{2}$/", $this->getDate($row['current_date']) ))) {array_push($erros, 'Current Date en la fila '.$i. ' debe tener el formato mm/dd/aaaa');
            }
                    // 'next_due_amount' => $row['next_due_amount'],
            $i++;
        }
        return $erros;
    }
    /**
     * [detect_city]
     * @param  [string] $ip [ip del cliente]
     * @return [string]     [ciudad]
     */
    public function detect_city($ip) {
        
        $ch = curl_init('http://www.geoplugin.net/php.gp?ip='.$ip);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $resp = curl_exec($ch);
        curl_close($ch);

        $data = unserialize($resp);

        $city = ($data['geoplugin_city']) ? $data['geoplugin_city'] :  'Desconocido';

        return $city;
    }

    /**
     * [detect]
     * @return [array] [informacion del navegador, SO, ip...]
     */
    public function detect(){

        $browser=array("IE","OPERA","MOZILLA","NETSCAPE","FIREFOX","SAFARI","CHROME",'EDGE');
        $os=array("WINDOWS","MAC OS","LINUX");

        $info['browser'] = "OTHER";
        $info['os'] = "OTHER";

        foreach($browser as $parent)
        {
            $s = strpos(strtoupper($_SERVER['HTTP_USER_AGENT']), $parent);
            $f = $s + strlen($parent);
            $version = substr($_SERVER['HTTP_USER_AGENT'], $f, 15);
            $version = preg_replace('/[^0-9,.]/','',$version);
            if ($s)
            {
                $info['browser'] = $parent;
                $info['version'] = $version;
            }
        }

        foreach($os as $val){
            if (strpos(strtoupper($_SERVER['HTTP_USER_AGENT']),$val)!==false)
                $info['os'] = $val;
        }
        return $info;
    }

    public function data($file, $date, $ip, $city, $info){
        $newData = [];
        foreach ($file as $key => $row) {
            $registro = [
                'file_name' => 'APC_'.$date[0].'_'.$date[1],
                'date' => $date[0],
                'hour' => $date[1],
                'path' => 'path',
                'ip' => $ip,
                'location' =>$city,
                'os' =>$info["os"],
                'browser' => $info["browser"] . ' ' .$info["version"],
                'loan' =>(string) $row['loan'],
                'borrower_name' => (string)$row['borrower_name'],
                'borrower_cedula' =>(string) $row['borrower_cedula'],
                'borrower_passaporte' =>(string) $row['borrower_passaporte'],
                'loan_amount' =>(string)(string) $row['loan_amount'],
                'term' =>(string) $row['term'],
                'contract_date' => $this->getDate($row['contract_date']),
                'principal_balance' =>(string) $row['principal_balance'],
                'last_payment_date' => $this->getDate($row['last_payment_date']),
                'last_payment_amt' =>(string) $row['last_payment_amt'],
                'final_payment_date' => $this->getDate($row['final_payment_date']),
                'current_date' => $this->getDate($row['current_date']),
                'next_due_amount' => (string)$row['next_due_amount'],
            ];
            array_push($newData, $registro);
        }
        return $newData;
    }

    public function getDate($date){
        if(gettype($date)  == 'object'){
            $date = explode(' ', (string) $date);
            $new = explode('-',$date[0]);
            $resul = $new[2].'/'.$new[1].'/'.$new[0];
        }else{
            $resul = (string) $date;    
        }
        return $resul;
    }
}