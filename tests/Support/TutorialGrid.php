<?php

namespace App\Tests\Support;

final class TutorialGrid
{
    /**
     * Load the tutorial grid from the json file.
     *
     * @return array
     */
    public static function load(): array
    {
        $json = file_get_contents(dirname(__DIR__, 2) . '/src/DataFixtures/tutorial-grid.json');

        return json_decode($json, true, flags: \JSON_THROW_ON_ERROR);
    }
}
