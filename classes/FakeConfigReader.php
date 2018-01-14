<?php


class FakeConfigReader implements Kohana_Config_Reader
{
    /**
     * Tries to load the specificed configuration group
     *
     * Returns FALSE if group does not exist or an array if it does
     *
     * @param  string $group Configuration group
     *
     * @return boolean|array
     */
    public function load($group)
    {
        return false;
    }
}
