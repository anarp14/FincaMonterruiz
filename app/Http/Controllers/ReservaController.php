<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use Illuminate\Http\Request;
use App\Models\Actividad;
use App\Models\Horario;

class ReservaController extends Controller
{
    public function index()
    {
        // Método para mostrar una lista de reservas

    }

    public function create()
    {
        // Método para mostrar el formulario de creación de una reserva
    }

    public function store(Request $request)
    {
        // Método para almacenar una nueva reserva en la base de datos
    }


    public function show($horarioId)
    {
        $horario = Horario::findOrFail($horarioId);
        $actividad = Actividad::where('id', $horario->actividad_id)->firstOrFail();

        return view('pages.reservar', compact('actividad', 'horario'));
    }





    public function edit(Reserva $reserva)
    {
        // Método para mostrar el formulario de edición de una reserva
    }

    public function update(Request $request, Reserva $reserva)
    {
        // Método para actualizar una reserva específica
    }

    public function destroy(Reserva $reserva)
    {
        // Método para borrar una reserva específica
    }
}
