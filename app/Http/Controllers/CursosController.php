<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Cursos;
use App\Models\Clientes;
use Faker\Provider\Base;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;


class CursosController extends Controller
{
    //GET Mostrar registros
    public function index(Request $request)
    {
        $token = $request->header('Authorization');
        $clientes = Clientes::all();
        $autorizado = false;

        //Verificar token de autentificación
        foreach ($clientes as $key => $value) {
            if ("Basic " . base64_encode($value['id_cliente'] . ":" . $value['llave_secreta']) == $token) {
                $autorizado = true;
                break;
            }
        }

        if (!$autorizado) {
            $json = array(
                "status" => 404,
                "total_registros" => 0,
                "detalles" => 'El usuario no esta autorizado',
            );
            return json_encode($json);
        }

        //Eleccion de paginacion
        // $cursos = Cursos::all();
        if (isset($_GET["page"])) {
            $cursos = DB::table('cursos')
                ->join('clientes', 'cursos.id_creador', '=', 'clientes.id')
                ->select(
                    'cursos.id',
                    'cursos.titulo',
                    'cursos.descripcion',
                    'cursos.instructor',
                    'cursos.id_creador',
                    'clientes.nombre',
                    'clientes.apellido',
                )
                ->paginate(15);
        } else {
            $cursos = DB::table('cursos')
                ->join('clientes', 'cursos.id_creador', '=', 'clientes.id')
                ->select(
                    'cursos.id',
                    'cursos.titulo',
                    'cursos.descripcion',
                    'cursos.instructor',
                    'cursos.id_creador',
                    'clientes.nombre',
                    'clientes.apellido',
                )
                ->get();
        }




        $json = array(
            "status" => 200,
            "total_registros" => count($cursos),
            "detalles" => $cursos,
        );

        if (empty($cursos)) {
            $json['status'] = 404;
            $json['detalles'] = 'No hay ningun curso registrado';
            return json_encode($json);
        };

        // return json_encode($cursos);
        return json_encode($json);
    }
    //Guardar registro
    public function store(Request $request)
    {
        $token = $request->header('Authorization');
        $clientes = Clientes::all();
        $autorizado = false;
        $idClienteAutorizado = 0;
        $json = array(
            "status" => 200,
            "detalles" => 'El curso ha sido guardado',
        );
        foreach ($clientes as $key => $value) {
            if ("Basic " . base64_encode($value['id_cliente'] . ":" . $value['llave_secreta']) == $token) {
                $autorizado = true;
                $idClienteAutorizado = $value['id'];
                break;
            }
        }
        //Verificación de autorización
        if (!$autorizado) {
            $json["status"] = 404;
            $json["detalles"] = 'El usuario no esta autorizado';
            return json_encode($json);
        }

        $datos = array(
            "titulo" => $request->input("titulo"),
            "descripcion" => $request->input("descripcion"),
            "instructor" => $request->input("instructor"),
            "imagen" => $request->input("imagen"),
            "precio" => $request->input("precio"),
        );

        if (empty($datos)) {
            $json["status"] = 404;
            $json["detalles"] = 'Registros vacios';
            return json_encode($json);
        }

        $validator = Validator::make($datos, [
            'titulo' => 'required|string|max:255|unique:cursos',
            'descripcion' => 'required|string|max:255',
            'instructor' => 'required|string|max:255',
            'imagen' => 'required|string|max:255',
            'precio' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            $json["status"] = 404;
            $json["detalles"] = 'Registro con errores: titulo repetido';
            return json_encode($json);
        }

        $cursos = new Cursos();

        $cursos->titulo = $datos['titulo'];
        $cursos->descripcion = $datos['descripcion'];
        $cursos->instructor = $datos['instructor'];
        $cursos->imagen = $datos['imagen'];
        $cursos->precio = $datos['precio'];
        $cursos->id_creador = $idClienteAutorizado;

        $cursos->save();

        return json_encode($json);
    }
    // Mostrar registro
    public function show($id, Request $request)
    {
        $token = $request->header('Authorization');
        $clientes = Clientes::all();
        $autorizado = false;
        $idClienteAutorizado = 0;
        $json = array(
            "status" => 200,
            "detalles" => 'Los datos del curso han sido actualizados',
        );
        foreach ($clientes as $key => $value) {
            if ("Basic " . base64_encode($value['id_cliente'] . ":" . $value['llave_secreta']) == $token) {
                $autorizado = true;
                $idClienteAutorizado = $value['id'];
                break;
            }
        }

        if (!$autorizado) {
            $json["status"] = 404;
            $json["detalles"] = 'El usuario no esta autorizado';
            return json_encode($json);
        }

        $curso = Cursos::where("id_creador", $idClienteAutorizado)->get();

        if (empty($curso)) {
            $json["status"] = 404;
            $json["detalles"] = 'El dato esta vacio';
            return json_encode($json);
        }

        $json['detalles'] = $curso;
        return json_encode($json, true);
    }

    // Editar registro
    public function update($id, Request $request)
    {
        $token = $request->header('Authorization');
        $clientes = Clientes::all();
        $autorizado = false;
        $idClienteAutorizado = 0;
        $json = array(
            "status" => 200,
            "detalles" => 'Los datos del curso han sido actualizados',
        );
        foreach ($clientes as $key => $value) {
            if ("Basic " . base64_encode($value['id_cliente'] . ":" . $value['llave_secreta']) == $token) {
                $autorizado = true;
                $idClienteAutorizado = $value['id'];
                break;
            }
        }

        if (!$autorizado) {
            $json["status"] = 404;
            $json["detalles"] = 'El usuario no esta autorizado';
            return json_encode($json);
        }

        $datos = array(
            "titulo" => $request->input("titulo"),
            "descripcion" => $request->input("descripcion"),
            "instructor" => $request->input("instructor"),
            "imagen" => $request->input("imagen"),
            "precio" => $request->input("precio"),
        );

        $validator = Validator::make($datos, [
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string|max:255',
            'instructor' => 'required|string|max:255',
            'imagen' => 'required|string|max:255',
            'precio' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            $json["status"] = 404;
            $json["detalles"] = 'Registro con errores: titulo repetido';
            return json_encode($json);
        }

        $traer_cursos = Cursos::where('id', $id)->get();

        if (!($idClienteAutorizado == $traer_cursos[0]['id_creador'])) {
            $json["status"] = 404;
            $json["detalles"] = 'No esta autorizado para editar este curso';
            return json_encode($json);
        }

        $cursos = Cursos::where('id', $id)->update($datos);

        return json_encode($json);
    }

    // Eliminar un registro

    public function destroy($id, Request $request)
    {
        $token = $request->header('Authorization');
        $clientes = Clientes::all();
        $autorizado = false;
        $idClienteAutorizado = 0;
        $json = array(
            "status" => 200,
            "detalles" => 'El curso ha sido borrado',
        );

        $validarCurso = Cursos::where('id', $id)->get();

        foreach ($clientes as $key => $value) {
            if ("Basic " . base64_encode($value['id_cliente'] . ":" . $value['llave_secreta']) == $token) {
                $autorizado = true;
                $idClienteAutorizado = $value['id'];
                break;
            }
        }
        if (!$autorizado) {
            $json["status"] = 404;
            $json["detalles"] = 'El usuario no esta autorizado';
            return json_encode($json);
        }

        //Validar existencia en BD
        if ($validarCurso->isEmpty()) {
            $json["status"] = 404;
            $json["detalles"] = 'Este cursos no existe';
            return json_encode($json);
        }

        if (!($idClienteAutorizado == $validarCurso[0]['id_creador'])) {
            $json["status"] = 404;
            $json["detalles"] = 'El usuario no esta autorizado para borrar este curso';
            return json_encode($json);
        }

        $curso = Cursos::where('id', $id)->delete();

        return json_encode($json);
    }
}
