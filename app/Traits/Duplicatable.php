<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait Duplicatable
 *
 * Adds a reusable duplicate() method to any Eloquent model.
 */
trait Duplicatable
{
    /**
     * Create a copy of the current model.
     *
     * @param  array  $overrideAttributes  Optional attributes to override on the new copy.
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function duplicate(array $overrideAttributes = []): Model
    {
        // Replicate the model (copies all attributes except the primary key)
        $copy = $this->replicate();

        // Apply any overrides provided
        $copy->fill($overrideAttributes);

        // Save the new model instance to the database
        $copy->save();

        return $copy;
    }
}
