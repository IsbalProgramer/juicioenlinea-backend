<?php

namespace Database\Seeders;

use App\Models\Catalogos\CatTipoDocumento;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TipoDocumentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insertar el registro especial con idCatTipoDocumento = -1 al final
        CatTipoDocumento::create([
            'idCatTipoDocumento' => -1,
            'descripcion' => 'OTRO',
            'requiereEscaneo' => 0,
     
        ]);
        CatTipoDocumento::create([
            'idCatTipoDocumento' => 0,
            'descripcion' => 'SIN ANEXOS',
            'requiereEscaneo' => 0,
     
        ]);

        $anexos = [
            ['idCatTipoDocumento' => 1, 'descripcion' => 'ACTA NOTARIAL', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 2, 'descripcion' => 'ADJUDICACION HEREDITARIA EN COPIA CERTIFICADA', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 3, 'descripcion' => 'AUTORIZACION PARA ADOPTAR', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 4, 'descripcion' => 'CARTILLA DE SERVICIO MILITAR NACIONAL', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 5, 'descripcion' => 'CERTIFICACION DE ADEUDO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 6, 'descripcion' => 'CERTIFICACION DE ADEUDOS EN ORIGINAL Y COPIA SIMPLE', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 7, 'descripcion' => 'CERTIFICACION DE AUTENTICIDAD DE FIRMAS', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 8, 'descripcion' => 'CERTIFICACION DE HECHOS', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 9, 'descripcion' => 'CERTIFICACION DE POLIZAS', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 10, 'descripcion' => 'CERTIFICADO DE BACHILLERATO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 11, 'descripcion' => 'CERTIFICADO DE INGRESOS', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 12, 'descripcion' => 'CERTIFICADO DE PRIMARIA', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 13, 'descripcion' => 'CERTIFICADO DE SECUNDARIA', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 14, 'descripcion' => 'CERTIFICADO MEDICO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 15, 'descripcion' => 'CERTIFICADO PROFESIONAL', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 16, 'descripcion' => 'CERTIFICADO(S) DE ANTECEDENTES NO PENALES', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 17, 'descripcion' => 'CONSTANCIA DE ESTUDIOS', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 18, 'descripcion' => 'CONSTANCIA DE ORIGEN Y VECINDAD', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 19, 'descripcion' => 'CONSTANCIAS VARIAS', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 20, 'descripcion' => 'CONTRA RECIBO CON PAGARE INSERTO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 21, 'descripcion' => 'CONTRATO DE APERTURA DE CRED. SIMPLE/GARANTIA PRENDARIA', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 22, 'descripcion' => 'CONTRATO DE APERTURA DE CREDITO SIMPLE/GARANTIA HIPOTECARIA', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 23, 'descripcion' => 'CONTRATO DE ARRENDAMIENTO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 24, 'descripcion' => 'CONTRATO DE COMODATO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 25, 'descripcion' => 'CONTRATO DE COMPRAVENTA', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 26, 'descripcion' => 'CONTRATO DE PRESTACION DE SERVICIOS PROFESIONALES', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 27, 'descripcion' => 'CONTRATO DE PROMESA DE COMPRA VENTA', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 28, 'descripcion' => 'CONTRATO DE SOCIEDAD', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 29, 'descripcion' => 'CONTRATO DE TRANSACCION', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 30, 'descripcion' => 'CONTRATO PRIVADO DE COMPRA VENTA', 'requiereEscaneo' => 1 ],

            ['idCatTipoDocumento' => 31, 'descripcion' => 'CONTRATO SIMPLE DE APERTURA DE CREDITO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 32, 'descripcion' => 'CONVENIO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 33, 'descripcion' => 'COPIA CERTIFICADA DE ACTA DE DEFUNCION', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 34, 'descripcion' => 'COPIA CERTIFICADA DE ACTA DE MATRIMONIO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 35, 'descripcion' => 'COPIA CERTIFICADA DE ACTA DE NACIMIENTO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 36, 'descripcion' => 'COPIA CERTIFICADA DE CARTILLA DE SERVICIO MILITAR NACIONAL', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 37, 'descripcion' => 'COPIA CERTIFICADA DE CEDULA PROFESIONAL', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 38, 'descripcion' => 'COPIA CERTIFICADA DE CONTRATO DE ARRENDAMIENTO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 39, 'descripcion' => 'COPIA CERTIFICADA DE CREDENCIAL DE ELECTOR', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 40, 'descripcion' => 'COPIA CERTIFICADA DE DESIGNACION', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 41, 'descripcion' => 'COPIA CERTIFICADA DE DIARIO OFICIAL', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 42, 'descripcion' => 'COPIA CERTIFICADA DE FE DE HECHOS', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 43, 'descripcion' => 'COPIA CERTIFICADA DE NOMBRAMIENTO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 44, 'descripcion' => 'COPIA CERTIFICADA DE NOTIFICACION', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 45, 'descripcion' => 'COPIA CERTIFICADA DE PERIODICO OFICIAL', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 46, 'descripcion' => 'COPIA CERTIFICADA DE PODER', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 47, 'descripcion' => 'COPIA CERTIFICADA DE RECIBO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 48, 'descripcion' => 'COPIA CERTIFICADA DE REGISTRO DE NACIMIENTO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 49, 'descripcion' => 'COPIA SIMPLE DE ACTA DE DEFUNCION', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 50, 'descripcion' => 'COPIA SIMPLE DE ACTA DE MATRIMONIO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 51, 'descripcion' => 'COPIA SIMPLE DE ACTA DE NAC. CON SELLO Y  FIRMA ORIGINAL', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 52, 'descripcion' => 'COPIA SIMPLE DE ACTA DE NACIMIENTO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 53, 'descripcion' => 'COPIA SIMPLE DE CARTILLA DE SERVICIO MILITAR NACIONAL', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 54, 'descripcion' => 'COPIA SIMPLE DE CEDULA PROFESIONAL', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 55, 'descripcion' => 'COPIA SIMPLE DE CERTIFICACION', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 56, 'descripcion' => 'COPIA SIMPLE DE CONSTANCIA', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 57, 'descripcion' => 'COPIA SIMPLE DE CONTRATO DE ARRENDAMIENTO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 58, 'descripcion' => 'COPIA SIMPLE DE CREDENCIAL DE ELECTOR', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 59, 'descripcion' => 'COPIA SIMPLE DE DIARIO OFICIAL', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 60, 'descripcion' => 'COPIA SIMPLE DE ESTADO DE CUENTA', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 61, 'descripcion' => 'COPIA SIMPLE DE PAGARE', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 62, 'descripcion' => 'COPIA SIMPLE DE PERIODICO OFICIAL', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 63, 'descripcion' => 'COPIA SIMPLE DE PODER', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 64, 'descripcion' => 'COPIA SIMPLE DE RECIBO', 'requiereEscaneo' => 1 ],

            ['idCatTipoDocumento' => 65, 'descripcion' => 'CREDENCIAL DE ELECTOR', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 66, 'descripcion' => 'CREDENCIAL DE IDENTIFICACION', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 67, 'descripcion' => 'CREDENCIAL DE PARTIDO POLITICO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 68, 'descripcion' => 'CREDENCIALES VARIAS', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 69, 'descripcion' => 'CROQUIS DE UBICACION', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 70, 'descripcion' => 'CUESTIONARIOS', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 71, 'descripcion' => 'DOC. QUE ACREDITAN LA LEGAL ESTANCIA EN EL PAIS DE LA PAREJA', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 72, 'descripcion' => 'ESTUDIOS REALIZADOS POR TRABAJADORA SOCIAL DEL D.I.F.', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 73, 'descripcion' => 'FACTURA CON PAGARE INSERTO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 74, 'descripcion' => 'FACTURA REMISION CON PAGARE INSERTO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 75, 'descripcion' => 'FOTOGRAFIAS', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 76, 'descripcion' => 'INTERROGATORIO(S)', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 77, 'descripcion' => 'INVENTARIO Y AVALUO DE BIENES', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 78, 'descripcion' => 'LICENCIA DE AUTOMOVILISTA', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 79, 'descripcion' => 'LICENCIA DE CHOFER', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 80, 'descripcion' => 'NOTA DE REMISION CON PAGARE INSERTO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 81, 'descripcion' => 'PLANO(S)', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 82, 'descripcion' => 'RECIBO SIMPLE', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 83, 'descripcion' => 'RECIBOS VARIOS', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 84, 'descripcion' => 'SOBRE CERRADO QUE DICE CONTENER PLIEGO DE POSICIONES', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 85, 'descripcion' => 'SOBRE(S) CERRADO(S)', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 86, 'descripcion' => 'SOLICITUD DE ADOPCION', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 87, 'descripcion' => 'SOLICITUD DE INSCRIPCION DE R.F.C', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 88, 'descripcion' => 'SOLICITUD DE TARJETA DE CREDITO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 89, 'descripcion' => 'TALON DE CHEQUE', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 90, 'descripcion' => 'TALON DE RECIBO DE PAGO DE AGUA', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 91, 'descripcion' => 'TALON DE RECIBO DE PAGO DE LUZ', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 92, 'descripcion' => 'TALON DE RECIBO DE PAGO DE PREDIAL', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 93, 'descripcion' => 'TALON DE RECIBO DE PAGO DE TELEFONO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 94, 'descripcion' => 'TESTAMENTO PRIVADO', 'requiereEscaneo' => 1 ],
            ['idCatTipoDocumento' => 95, 'descripcion' => 'TESTIMONIO DE ESCRITURA CONSTITUTIVA', 'requiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 96, 'Descripcion' => 'TESTIMONIO DE ESCRITURA DE COMPRA-VENTA', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 97, 'Descripcion' => 'TESTIMONIO DE PODER GENERAL', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 98, 'Descripcion' => 'TESTIMONIO DE TESTAMENTO PUBLICO ABIERTO', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 99, 'Descripcion' => 'TRASLADO(S)', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 100, 'Descripcion' => 'OFICIO EN COPIA SIMPLE', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 101, 'Descripcion' => 'COPIA CERTIFICADA DE ACTA DE RECONOCIMIENTO', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 102, 'Descripcion' => 'CONTRATO DE COMPRAVEN.C/RESERVA DOM. C/CERT NATURAL', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 103, 'Descripcion' => 'COMPROBANTE DE PERCEPCIONES Y DEDUCCIONES DEL EMPLEADO', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 104, 'Descripcion' => 'COPIA AL CARBON DE ACTA DE MATRIMONIO CON SELLO Y FIRMAS', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 105, 'Descripcion' => 'COPIA AL CARBON DE ACTA DE NACIMIENTO CON SELLO Y FIRMAS', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 106, 'Descripcion' => 'CONSTANCIA DE INGRESOS', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 107, 'Descripcion' => 'COPIA CERTIFICADA DE REGISTRO DE MATRIMONIO', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 108, 'Descripcion' => 'COPIA SIMPLE DE CURP', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 109, 'Descripcion' => 'COPIA SIMPLE  CEDULA DE IDENTIFICACION FISCAL', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 110, 'Descripcion' => 'ULTRASONIDO', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 111, 'Descripcion' => 'CONSTANCIA MEDICA', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 112, 'Descripcion' => 'CONSTANCIA DE CONCUBINATO', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 113, 'Descripcion' => 'CREDENCIAL DE INSEN', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 114, 'Descripcion' => 'PROPUESTA DE CONVENIO', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 115, 'Descripcion' => 'COPIA AL CARBON DE ACTA DE DEFUNCION CON SELLO Y FIRMAS', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 116, 'Descripcion' => 'COPIA CERTIFICADA DE ACTA DE DIVORCIO', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 117, 'Descripcion' => 'COPIA SIMPLE DE CONSTANCIA DE IDENTIFICACION FISCAL', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 118, 'Descripcion' => 'IMPRESION ELECTRONICA DE ACTA DE NACIMIENTO', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 119, 'Descripcion' => 'IMPRESION ELECTRONICA DE ACTA DE MATRIMONIO', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 120, 'Descripcion' => 'COPIA CERTIFICADA DE LEGAJO DE INVESTIGACION', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 121, 'Descripcion' => 'PAGARE', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 122, 'Descripcion' => 'JUICIO DE ALIMENTOS', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 123, 'Descripcion' => 'CONTROVERSIAS DEL ORDEN FAMILIAR', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 124, 'Descripcion' => 'ATESTADOS DEL REGISTRO CIVIL DE MATRIMONIO', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 125, 'Descripcion' => 'ATESTADOS DEL REGISTRO CIVIL DE NACIMIENTO', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 126, 'Descripcion' => 'PLIEGO DE POCISIONES E INTERROGATORIO', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 127, 'Descripcion' => 'PAGARE CON COPIA SIMPLE ', 'RequiereEscaneo' => 1 ],
            ['IdCatTipoDocumento' => 128, 'Descripcion' => 'IMPRESION ELECTRONICA DE CEDULA PROFESIONAL', 'RequiereEscaneo' => 1 ]
                ];
        foreach ($anexos as $anexo) {
            CatTipoDocumento::create($anexo);
        }
    }
}
