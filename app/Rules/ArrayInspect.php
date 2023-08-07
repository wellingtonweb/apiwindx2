<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;

/**
 * Valida in arreglo de objetos
 *
 * Si bien Laravel puede validar arrays, solo funciona
 * si sus elementos son arreglos asociativos, cuando son
 * objetos la regla no se aplica
 */
class ArrayInspect implements Rule
{
    /**
     * Reglas de validacion
     */
    private array $rules;

    /**
     * Mensaje de error
     */
    private string $error;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        foreach ($this->rules as $property => $property_rules)
        {
            $rules = [$property => $property_rules];

            if (isset($value->{$property}))
            {
                $data = [$property => $value->{$property}];

                $validator = Validator::make($data, $rules);

                if ($validator->fails())
                {
                    $this->error = $validator->errors()->first($property);

                    // Detener el loop si hay un error
                    return false;
                }
            }
            else
            {
                if (in_array('required', $property_rules))
                {
                    $this->error = __('validation.required', ['attribute' => $property]);

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->error;
    }
}
