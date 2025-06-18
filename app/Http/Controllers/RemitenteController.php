<?php

namespace App\Http\Controllers;

use App\Models\Catalogos\CatRemitente;
use App\Models\Catalogos\CatSecretarioJuzgado;
use App\Services\PermisosApiService;
use Illuminate\Http\Request;

class RemitenteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PermisosApiService $permisosApiService)
    {
        try {
            $jwtPayload = $request->attributes->get('jwt_payload');
            $datosUsuario = $permisosApiService->obtenerDatosUsuarioByToken($jwtPayload);

            if (!$datosUsuario || !isset($datosUsuario['idGeneral']) || !isset($datosUsuario['Usr'])) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No se pudo obtener los datos del usuario desde el token.',
                ], 400);
            }

            $idGeneral = $datosUsuario['idGeneral'];
            $idUsr = $datosUsuario['Usr'];

            $idSistema = $permisosApiService->obtenerIdAreaSistemaUsuario($request->bearerToken(), $idGeneral, 4171);

            if (!$idSistema) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No se pudo obtener el idAreaSistemaUsuario.',
                ], 400);
            }

            // Obtener el juzgado asignado al secretario
            $secretario = CatSecretarioJuzgado::where('idGeneral', $idGeneral)
                ->where('idUsr', $idUsr)
                ->first();

            if (!$secretario) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No se encontró juzgado asignado al secretario.',
                ], 404);
            }

            $idJuzgado = $secretario->idCatJuzgado;

            // Término de búsqueda general
            $busqueda = $request->query('busqueda');

            $remitentes = CatRemitente::with(['juzgados']) // Solo si necesitas cargar los juzgados relacionados
                ->whereHas('juzgados', function ($q) use ($idJuzgado) {
                    $q->where('cat_juzgados.idCatJuzgado', $idJuzgado);
                })
                ->when($busqueda, function ($query) use ($busqueda) {
                    $query->where(function ($subquery) use ($busqueda) {
                        $subquery->where('categoria', 'like', '%' . $busqueda . '%')
                            ->orWhere('dependencia', 'like', '%' . $busqueda . '%')
                            ->orWhere('remitente', 'like', '%' . $busqueda . '%')
                            ->orWhere('cargo', 'like', '%' . $busqueda . '%');
                    });
                })
                ->orderBy('remitente')
                ->get();

            return response()->json([
                'status' => 200,
                'message' => 'Listado de remitentes filtrados',
                'data' => $remitentes,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener los remitentes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
