<?php

namespace App\Http\Controllers;

use App\Services\FirmaElectronica;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class FirmaController extends Controller
{
    public function store(Request $request, FirmaElectronica $firmaElectronica)
    {
        $validator = Validator::make($request->all(), [
            'archivoPfx_Efirma' => 'required|file',
            'password_Efirma'   => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status'  => 400,
                'message' => 'Error de validación',
                'errors'  => $validator->errors(),
            ], 400);
        }

        $token = $request->bearerToken();
        $archivoPfx = $request->file('archivoPfx_Efirma');
        $password   = $request->input('password_Efirma');

        // Usar archivo local de prueba en storage/app/test.pdf
        $documentoPath = storage_path('app/test.pdf');
        if (!file_exists($documentoPath)) {
            return response()->json([
                'success' => false,
                'status'  => 500,
                'message' => 'No se encontró el archivo de prueba en storage/app/test.pdf',
            ], 500);
        }

        // Crear UploadedFile desde el archivo local
        $documentoFile = new UploadedFile(
            $documentoPath,
            'test.pdf',
            'application/pdf',
            null,
            true // $test mode
        );

        try {
            $firmaElectronica->firmarDocumento($archivoPfx, $password, $documentoFile, $token);
            return response()->json([
                'success' => true,
                'status'  => 200,
                'message' => 'Firma validada correctamente',
            ]);
        } catch (\RuntimeException $ex) {
            return response()->json([
                'success' => false,
                'status'  => 422,
                'message' => 'Las credenciales de firma no son válidas, intente de nuevo',
            ]);
        } catch (\Throwable $ex) {
            return response()->json([
                'success' => false,
                'status'  => 500,
                'message' => 'Error al validar credenciales de firma',
                'error'   => $ex->getMessage(),
            ]);
        }
    }
}
