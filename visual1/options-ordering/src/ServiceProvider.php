<?php

namespace Visual1\OptionsOrdering;

use App\Data\CustomDataReferenceUpdater;
use Illuminate\Support\Facades\Event;
use Statamic\Events\BlueprintSaved;
use Statamic\Events\FormBlueprintFound;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    public function bootAddon(): void
    {
        Event::listen(BlueprintSaved::class, function (BlueprintSaved $event) {
            $blueprint = $event->blueprint;

            $updater = new CustomDataReferenceUpdater($blueprint);
            $updater->update();
        });

        Event::listen(FormBlueprintFound::class, function (FormBlueprintFound $event) {
            $blueprint = $event->blueprint;
            $tabs = $blueprint->contents();

            // Convert the options back to an associative array
            if (isset($tabs['tabs']) && is_array($tabs['tabs'])) {
                foreach ($tabs['tabs'] as &$tab) {
                    if (isset($tab['sections']) && is_array($tab['sections'])) {
                        foreach ($tab['sections'] as &$section) {
                            if (isset($section['fields']) && is_array($section['fields'])) {
                                foreach ($section['fields'] as &$existingField) {
                                    if (isset($existingField['field']['type'])
                                        && $existingField['field']['type'] === 'select' || $existingField['field']['type'] === 'checkboxes'
                                    ) {
                                        if (isset($existingField['field']['options'])
                                            && is_array(
                                                $existingField['field']['options']
                                            )
                                        ) {
                                            $existingField['field']['options'] = $this->getOptions(
                                                $existingField['field']['options']
                                            );
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Set the updated data back into the blueprint
            $blueprint->setContents($tabs);
        });
    }

    protected function getOptions($options)
    {
        return array_reduce($options, function ($carry, $option) {
            if (is_array($option) && isset($option['key']) && isset($option['value'])) {
                $carry[$option['key']] = $option['value'];
            }
            return $carry;
        }, []);
    }
}
