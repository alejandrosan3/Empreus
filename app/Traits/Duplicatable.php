<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

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



    /**
     * Duplicate the model, its attributes + any relations you list.
     *
     * @param  array       $override          Attributes to override on the new copy.
     * @param  string[]    $relations         Relation names (hasMany) to duplicate.
     * @param  array       $relationOverrides Per-relation attribute overrides.
     * @return Model
     */
    /**
     * Duplicate the model with optional attribute overrides and duplicate specified relations.
     *
     * @param  array   $overrideAttributes    Attributes to override on the new parent copy.
     * @param  string[] $relations            Relation names (hasMany) to duplicate.
     * @param  array   $relationOverrides     Per-relation attribute overrides, keyed by relation name.
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function duplicateWith(
        array $overrideAttributes = [],
        array $relations = [],
        array $relationOverrides = []
    ): Model {
        // 1) Clone the parent
        $copy = $this->replicate();
        $copy->fill($overrideAttributes);
        $copy->save();

        // 2) Clone each specified hasMany relation
        foreach ($relations as $relation) {
            if (! method_exists($this, $relation)) {
                continue;
            }

            // Get the relation query and existing items
            $relationQuery = $this->$relation();
            $foreignKey = method_exists($relationQuery, 'getForeignKeyName')
                ? $relationQuery->getForeignKeyName()
                : $relationQuery->getForeignKey();
            $items = $relationQuery->get();

            foreach ($items as $item) {
                $itemCopy = $item->replicate();

                // Apply overrides for this relation, if any
                if ($over = Arr::get($relationOverrides, $relation, [])) {
                    $itemCopy->fill($over);
                }

                // Reset the foreign key to link to the new parent
                $itemCopy->{$foreignKey} = $copy->getKey();
                $itemCopy->save();
            }
        }

        return $copy;
    }
}
