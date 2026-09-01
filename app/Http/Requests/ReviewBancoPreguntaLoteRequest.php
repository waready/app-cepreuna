<?php

namespace App\Http\Requests;

use App\Models\BancoPreguntaRevision;
use App\Support\DocumentoWord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewBancoPreguntaLoteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'accion' => [
                'required',
                Rule::in([
                    BancoPreguntaRevision::ACCION_APROBAR,
                    BancoPreguntaRevision::ACCION_OBSERVAR,
                    BancoPreguntaRevision::ACCION_RECHAZAR,
                ]),
            ],
            'comentario' => ['nullable', 'required_if:accion,observar,rechazar', 'string', 'max:3000'],
            'archivo_revision' => [
                'nullable',
                'file',
                'max:'.config('features.docente_preguntas_demo.max_file_kb', 10240),
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $archivo = $this->file('archivo_revision');

            if (!$archivo || !$archivo->isValid()) {
                return;
            }

            if (strtolower($archivo->getClientOriginalExtension()) !== 'docx'
                || !DocumentoWord::esDocxValido($archivo->getRealPath())) {
                $validator->errors()->add(
                    'archivo_revision',
                    'La version revisada debe ser un documento Word .docx valido.'
                );
            }
        });
    }

    public function messages()
    {
        return [
            'accion.required' => 'Selecciona una decision.',
            'accion.in' => 'La decision seleccionada no es valida.',
            'comentario.required_if' => 'Escribe el motivo de la observacion o del rechazo.',
            'comentario.max' => 'El comentario no debe superar los 3000 caracteres.',
            'archivo_revision.max' => 'La version revisada no debe superar los 10 MB.',
        ];
    }
}
