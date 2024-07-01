<?php

namespace Visual1\OptionsOrdering;

use Illuminate\Support\Facades\DB;
use Statamic\Data\DataReferenceUpdater;

class CustomDataReferenceUpdater extends DataReferenceUpdater
{
    public function update(): void
    {
        $fields = $this->item->fields();
        $this->recursivelyUpdateFields($fields);

        if ($this->updated) {
            $this->item->save();
        }
    }

    protected function recursivelyUpdateFields($fields, $dottedPrefix = null): void
    {
        if ($fields && $fields->items()) {
            foreach ($fields->items() as $field) {
                if ($field['field']['type'] === 'select' || $field['field']['type'] === 'checkboxes') {
                    $orderedOptions = $field['field']['options'];
                    $blueprint = DB::table('blueprints')
                        ->where('handle', $this->item->handle)
                        ->first();

                    if ($blueprint) {
                        // Decode the existing data
                        $existingData = json_decode($blueprint->data, true);

                        // Update the options
                        if (isset($existingData['tabs'])) {
                            foreach ($existingData['tabs'] as &$tab) {
                                if (isset($tab['sections'])) {
                                    foreach ($tab['sections'] as &$section) {
                                        if (isset($section['fields'])) {
                                            foreach ($section['fields'] as &$existingField) {
                                                if ($existingField['field']['type'] === $field['field']['type']) {
                                                    // Convert the associative array to an array of objects
                                                    $existingField['field']['options'] = array_map(
                                                        function ($key, $value) {
                                                            return (object)['key' => $key, 'value' => $value];
                                                        },
                                                        array_keys($orderedOptions),
                                                        $orderedOptions
                                                    );
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        // Encode the updated data
                        $updatedData = ['data' => json_encode($existingData)];

                        // Update the blueprint in the database
                        DB::table('blueprints')
                            ->where('handle', $this->item->handle)
                            ->update($updatedData);
                    }
                }
            }
        }
    }
}
