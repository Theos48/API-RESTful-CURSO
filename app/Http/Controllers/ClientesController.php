<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Clientes;
use Illuminate\Support\Facades\Hash;

class ClientesController extends Controller
{
    //
    public function index()
    {
        $json =  array(
            'status' => 404,
            'detalle' => 'Registro con errores',
        );

        echo json_encode($json, true);
    }

    // Creación de registro
    public function store(Request $request)
    {
        $datos = array(
            "nombre" => $request->input("nombre"),
            "apellido" => $request->input("apellido"),
            "email" => $request->input("email")
        );
        if (empty($datos)) {
            $json =  array(
                'detalle' => 'no encontrado',
            );

            return json_encode($json, true);
        }
        $validator = Validator::make($datos, [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:clientes',
        ]);

        if ($validator->fails()) {
            $json = array(
                'detalles' => 'registros no validos',
            );

            return json_encode($json);
        } else {

            $id_cliente = str_replace('$', 'a', (Hash::make($datos['nombre'] . $datos['apellido'] . $datos['email'])));
            $llave_secreta = str_replace('$', 'a', (Hash::make($datos['email'] . $datos['apellido'] . $datos['nombre'], [
                'rounds' => 12,
            ])));

            $cliente = new Clientes();
            $cliente->nombre = $datos['nombre'];
            $cliente->apellido = $datos['apellido'];
            $cliente->email = $datos['email'];
            $cliente->id_cliente = $id_cliente;
            $cliente->llave_secreta = $llave_secreta;

            $cliente->save();

            $json = array(
                'status' => 200,
                'detalles' => 'Por favor guarde sus credenciales',
                'credenciales' => array(
                    'id_cliente' => $id_cliente,
                    'llave_secreta' => $llave_secreta,
                )

            );

            return json_encode($json);
        }
    }
}
