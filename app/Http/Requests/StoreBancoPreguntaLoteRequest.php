<?php

namespace App\Http\Requests;

use App\Models\BancoPreguntaLote;
use App\Support\DocumentoWord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBancoPreguntaLoteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'curso_id' => ['required', 'integer', 'min:1'],
            'semana' => ['required', 'integer', 'between:1,30'],
            'nivel' => [
                'required',
                Rule::in([
                    BancoPreguntaLote::NIVEL_BASICO,
                    BancoPreguntaLote::NIVEL_INTERMEDIO,
                    BancoPreguntaLote::NIVEL_AVANZADO,
                ]),
            ],
            'confirmacion_dos_preguntas' => ['accepted'],
            'archivo' => [
                'required',
                'file',
                'max:'.config('features.docente_preguntas_demo.max_file_kb', 10240),
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $archivo = $this->file('archivo');

            if (!$archivo || !$archivo->isValid()) {
                return;
            }

            if (strtolower($archivo->getClientOriginalExtension()) !== 'docx'
                || !DocumentoWord::esDocxValido($archivo->getRealPath())) {
                $validator->errors()->add(
                    'archivo',
                    'El archivo debe ser un documento Word .docx valido.'
                );
            }
        });
    }

    public function messages()
    {
        return [
            'curso_id.required' => 'Selecciona uno de tus cursos asignados.',
            'semana.required' => 'Indica la semana de la entrega.',
            'semana.between' => 'La semana debe estar entre 1 y 30.',
            'nivel.required' => 'Selecciona el nivel de las preguntas.',
            'nivel.in' => 'El nivel seleccionado no es valido.',
            'confirmacion_dos_preguntas.accepted' => 'Confirma que el Word contiene exactamente 2 preguntas.',
            'archivo.required' => 'Adjunta el archivo Word con las preguntas.',
            'archivo.file' => 'No se pudo leer el archivo adjunto.',
            'archivo.max' => 'El archivo Word no debe superar los 10 MB.',
        ];
    }
}
