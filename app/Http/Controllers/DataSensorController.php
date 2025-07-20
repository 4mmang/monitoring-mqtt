<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DataSensorController extends Controller
{
    public function store(Request $request)
    {

        try {
            $dataSensor = new \App\Models\DataSensor();
            $dataSensor->suhu = $request->input('suhu');
            $dataSensor->kelembaban = $request->input('kelembaban');
            $dataSensor->gas_amonia = $request->input('gas_amonia');

            $gerakan = $request->input('gerakan');
            if ($gerakan === 'on') {
                $dataSensor->gerakan = 'Gerakan Terdeteksi'; // Store as string
            } else {
                $dataSensor->gerakan = 'Tidak Ada Gerakan'; // Store as string
            }

            $dataSensor->save();

            return response()->json(['message' => 'Data sensor saved successfully'], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Failed to save data sensor', 'error' => $th->getMessage()], 500);
        }
    }
}
