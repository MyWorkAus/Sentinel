<?php

/**
 * Part of the Sentinel package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.
 *
 * @package    Sentinel
 * @version    2.0.13
 * @author     Cartalyst LLC
 * @license    BSD License (3-clause)
 * @copyright  (c) 2011-2016, Cartalyst LLC
 * @link       http://cartalyst.com
 */

namespace Cartalyst\Sentinel\Permissions;

trait PermissionsTrait
{
    /**
     * The permissions.
     *
     * @var array
     */
    protected $permissions = [];

    /**
     * The secondary permissions.
     *
     * @var array
     */
    protected $secondaryPermissions = [];

    /**
     * An array of cached, prepared permissions.
     *
     * @var array
     */
    protected $preparedPermissions;

    /**
     * Create a new permissions instance.
     *
     * @param  array  $permissions
     * @param  array  $secondaryPermissions
     * @return void
     */
    public function __construct(array $permissions = null, array $secondaryPermissions = null)
    {
        if (isset($permissions)) {
            $this->permissions = $permissions;
        }

        if (isset($secondaryPermissions)) {
            $this->secondaryPermissions = $secondaryPermissions;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function hasAccess($permissions)
    {
        if (is_string($permissions)) {
            $permissions = func_get_args();
        }

        $prepared = $this->getPreparedPermissions();

        foreach ($permissions as $permission) {
            if (! $this->checkPermission($prepared, $permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function hasAnyAccess($permissions)
    {
        if (is_string($permissions)) {
            $permissions = func_get_args();
        }

        $prepared = $this->getPreparedPermissions();

        foreach ($permissions as $permission) {
            if ($this->checkPermission($prepared, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the secondary permissions.
     *
     * @return array
     */
    public function getSecondaryPermissions()
    {
        return $this->secondaryPermissions;
    }

    /**
     * Sets secondary permissions.
     *
     * @param  array  $secondaryPermissions
     * @return void
     */
    public function setSecondaryPermissions(array $secondaryPermissions)
    {
        $this->secondaryPermissions = $secondaryPermissions;

        $this->preparedPermissions = null;
    }

    /**
     * Lazily grab the prepared permissions.
     *
     * @return array
     */
    protected function getPreparedPermissions()
    {
        if ($this->preparedPermissions === null) {
            $this->preparedPermissions = $this->createPreparedPermissions();
        }

        return $this->preparedPermissions;
    }

    /**
     * Does the heavy lifting of preparing permissions.
     *
     * @param  array  $prepared
     * @param  array  $permissions
     * @return void
     */
    protected function preparePermissions(array &$prepared, array $permissions)
    {
        foreach ($permissions as $keys => $value) {
            foreach ($this->extractClassPermissions($keys) as $key) {
                // If the value is not in the array, we're opting in
                if (! array_key_exists($key, $prepared)) {
                    $prepared[$key] = $value;

                    continue;
                }

                // If our value is in the array and equals false, it will override
                if ($value === false) {
                    $prepared[$key] = $value;
                }
            }
        }
    }

    /**
     * Takes the given permission key and inspects it for a class & method. If
     * it exists, methods may be comma-separated, e.g. Class@method1,method2.
     *
     * @param  string  $key
     * @return array
     */
    protected function extractClassPermissions($key)
    {
        if (! str_contains($key, '@')) {
            return (array) $key;
        }

        $keys = [];

        list($class, $methods) = explode('@', $key);

        foreach (explode(',', $methods) as $method) {
            $keys[] = "{$class}@{$method}";
        }

        return $keys;
    }

    /**
     * Checks a permission in the prepared array, including wildcard checks and permissions.
     *
     * @param  array  $prepared
     * @param  string  $permission
     * @return bool
     */
    protected function checkPermission(array $prepared, $permission)
    {
        if (array_key_exists($permission, $prepared) && $prepared[$permission] === true) {
            return true;
        }

        foreach ($prepared as $key => $value) {
            if (($this->str_is($permission, $key) || $this->str_is($key, $permission)) && $value === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Taken from str_is/Str::is
     * @param $pattern
     * @param $value
     * @return bool
     */
    protected function str_is($pattern, $value)
    {
        if ($pattern == $value) {
            return true;
        }
        $on = false;
//        if(strpos($pattern, 'contact') && strpos($pattern, 'view') && $value == '4.company.*.contact.6.view'){
//            $on = true;
//        }

        $pattern = preg_quote($pattern, '#');

        // Asterisks are translated into zero-or-more regular expression wildcards
        // to make it convenient to check if the strings starts with the given
        // pattern such as "library/*", making any string check convenient.
        $pattern = str_replace('\*', '.*', $pattern);
        $pattern = str_replace('\..*\.', '\.([0-9]+|id)\.', $pattern);
        $value = str_replace('.*', '.id', $value);
//        $pattern = str_replace('.id', '.[0-9]+', $pattern);
        if($on) {
//            dd([$pattern,$value]);

//            dd(preg_match('#^'.$pattern.'\z#u', $value));
        }

        return (bool) preg_match('#^'.$pattern.'\z#u', $value);
    }

    /**
     * Returns the prepared permissions.
     *
     * @return void
     */
    abstract protected function createPreparedPermissions();
}
