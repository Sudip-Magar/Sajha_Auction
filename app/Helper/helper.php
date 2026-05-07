<?php
if (!function_exists('backedEnumAsArray')) {
    function backedEnumAsArray($enum)
    {
        return collect($enum)
            ->map(function ($state) {
                return [
                    'id'    => $state->value,
                    'name'  => str($state->name)->lower()->ucfirst()->value(),
                ];
            })
            ->values()
            ->all();
    }
}