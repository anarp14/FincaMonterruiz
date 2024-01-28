<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use Validator;
use App\Models\Reserva;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class ActividadController extends Controller
{
    public function index()
    {
        $actividades = Actividad::paginate(4);

        // Obtener datos de reservas
        $datosReservas = Reserva::select(DB::raw("to_char(created_at, 'YYYY-MM-DD') as fecha"), DB::raw('count(*) as total'))
            ->groupBy('fecha')
            ->orderBy('fecha', 'asc')
            ->get();

        return view('admin.actividades.index', [
            'actividades' => $actividades,
            'datosReservas' => $datosReservas,
        ]);
    }

    /**
     * MUestra el formulario para crear una actividad.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // $categorias = Categoria::all(); // Obtén todas las categorías para el formulario
        // return view('admin.actividades.create', compact('categorias'));
        return view('admin.actividades.create');
    }

    /**
     * Almacena una actividad recien creada en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validaciones básicas
        $validatedData = $request->validate([
            'nombre' => 'required',
            'descripcion' => 'required',
            'duracion' => 'required|integer',
            'aforo' => 'required|integer',
            'activa' => 'required|in:0,1',
            'imagen' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'precio_adulto' => 'required|numeric',
            'precio_nino' => 'nullable|numeric',
        ]);
        if ($request->hasFile('imagen')) {
            $imagen = $request->file('imagen');
            // Preparar el nombre del archivo, reemplazando espacios con guiones bajos para evitar problemas en S3
            $nombreImagen = time() . '_' . str_replace(' ', '_', $imagen->getClientOriginalName());

            // Especificar la ruta completa incluyendo la carpeta /public/images dentro de tu bucket de S3
            $rutaCompleta = 'public/images/' . $nombreImagen;

            try {
                // Usar storeAs para subir el archivo a la ruta deseada
                $imagen->storeAs($rutaCompleta);

                // Obtener la URL pública del archivo almacenado en S3, si es necesario
                $urlImagen = Storage::disk('s3')->url($rutaCompleta);

                // Guardar la URL de la imagen en la base de datos
                $validatedData['imagen'] = $urlImagen;
            } catch (\Exception $e) {
                \Log::error("Error al subir el archivo a S3: " . $e->getMessage());
                // Manejar el error según sea necesario, como enviar un mensaje de error al usuario
            }
        }


        // Asignar valores nulos para precios si los campos están vacíos
        $validatedData['precio_adulto'] = $request->filled('precio_adulto') ? $request->precio_adulto : null;
        $validatedData['precio_nino'] = $request->filled('precio_nino') ? $request->precio_nino : null;

        // Crear la actividad con los datos validados
        Actividad::create($validatedData);

        // Redireccionar con un mensaje de éxito
        return redirect()
            ->route('admin.actividades.index')
            ->with('success', '¡Actividad agregada con éxito!');
    }

    /**
     * Mostrar la actividad especificada.
     *
     * @param  \App\Models\Actividad  $articulo
     * @return \Illuminate\Http\Response
     */
    public function show($actividad)
    {
        // Desencriptar el ID
        $realId = Crypt::decrypt($actividad);

        // Obtener la actividad
        $actividad = Actividad::find($realId);

        // Pasar la variable a la vista
        return view('admin.actividades.delete', compact('actividad'));
    }

    /**
     * Este metodo nos sirve para traer los datos que se van a editar y los coloca en un formulario.
     *
     * @param  \App\Models\Actividad  $actividad
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        /// Desencriptar el ID
        $realId = Crypt::decrypt($id);

        // Buscar la actividad
        $actividad = Actividad::find($realId);

        // Comprueba si la actividad tiene reservas
        $tieneReservas = $actividad->reservas()->exists();

        // Verificar si se encontró la actividad
        if (!$actividad) {
            return redirect()
                ->route('admin.actividades.index')
                ->with('error', 'Actividad no encontrada');
        }

        // Mostrar el formulario de edición con los datos de la actividad
        return view('admin.actividades.edit', compact('actividad', 'tieneReservas'));
    }
    /**
     *Este metodo actualiza los datos en la bd
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Actividad  $actividad
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $actividad = Actividad::find($id);
        if (!$actividad) {
            return redirect()
                ->route('admin.actividades.index')
                ->with('error', '¡Actividad no encontrada!');
        }

        // Validar solo los campos que se han proporcionado
        $data = $request->validate([
            'nombre' => 'sometimes|required',
            'descripcion' => 'sometimes|required',
            'duracion' => 'sometimes|required|integer',
            'precio_adulto' => 'sometimes|required',
            'precio_nino' => 'nullable',
            'aforo' => 'sometimes|required|integer',
            'activa' => 'sometimes|required|in:0,1',
            'imagen' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Actualizar la imagen si se ha proporcionado una nueva
        if ($request->hasFile('imagen')) {
            $imagen = $request->file('imagen');
            $nombreImagen = time() . '_' . $imagen->getClientOriginalName();
            $rutaImagen = $imagen->storeAs('public/img', $nombreImagen);
            $rutaRelativa = 'storage/img/' . $nombreImagen;
            $actividad->imagen = $rutaRelativa;
        }

        // Actualizar solo los campos proporcionados
        $actividad->fill($data);
        $actividad->save();

        return redirect()
            ->route('admin.actividades.index')
            ->with('success', 'Actividad modificada con éxito.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Actividad $articulo
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Desencriptar el ID
        $realId = Crypt::decrypt($id);

        // Buscar la actividad
        $actividad = Actividad::find($realId);

        // Verificar si la actividad existe antes de intentar eliminar
        if ($actividad) {
            // Verificar si la actividad tiene reservas
            if ($actividad->reservas()->count() > 0) {
                return redirect()
                    ->route('admin.actividades.index')
                    ->with('error', 'No se puede eliminar la actividad porque ya tiene reservas.');
            }

            $rutaImagen = parse_url($actividad->imagen, PHP_URL_PATH);
            $rutaImagen = ltrim($rutaImagen, '/');

            // Elimina el archivo de imagen de S3
            Storage::disk('s3')->delete($rutaImagen);

            // Si no hay reservas, eliminar la actividad
            $actividad->delete();
            return redirect()
                ->route('admin.actividades.index')
                ->with('success', 'Actividad eliminada con éxito.');
        } else {
            // Manejar el caso en el que la actividad no se encuentra
            return redirect()
                ->route('admin.actividades.index')
                ->with('error', 'La actividad no existe o ya ha sido eliminada.');
        }
    }

    public function search(Request $request)
    {
        $query = $request->input('buscador');
        $actividades = Actividad::where('nombre', 'LIKE', "%{$query}%")->get();

        return view('pages.catalogo', compact('actividades'));
    }

    public function detalleActividad($id)
    {
        // Obtén los detalles de la actividad con el ID proporcionado.
        $actividad = Actividad::find($id);

        // Muestra la vista de detalles de la actividad y pasa los detalles de la actividad.
        return view('pages.detalleActividad', ['actividad' => $actividad]);
    }
}
