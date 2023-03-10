<?php
namespace ICANID\Tests\traits;

trait ErrorHelpers
{

    /**
     * Does an error message contain a specific string?
     *
     * @param \Exception $e   - Error object.
     * @param string     $str - String to find in the error message.
     *
     * @return boolean
     */
    protected function errorHasString(\Exception $e, $str)
    {
        return ! (false === strpos($e->getMessage(), $str));
    }
}
