<?php

namespace App\Repositories\Contracts;

interface RepositoryInterface
{
    /**
     *
     * @param array $columns
     */
    public function all($columns = ['*']);

    /**
     *
     * @param int $perPage
     * @param array $columns
     */
    public function paginate($perPage = 20, $columns = ['*']);

    /**
     *
     * @param array $data
     */
    public function create(array $data);

    /**
     *
     * @param array $data
     * @param string|int $id
     * @param string $attribute
     */
    public function update(array $data, $id, $attribute = 'id');

    /**
     *
     * @param string|int $id
     */
    public function delete($id);

    /**
     *
     * @param string|int $id
     */
    public function forceDelete($id);

    /**
     *
     * @param string|int $id
     * @param array $columns
     */
    public function find($id, $columns = ['*']);

    /**
     *
     * @param string $field
     * @param string $value
     * @param array $columns
     */
    public function findBy($field, $value, $columns = ['*']);

    /**
     *
     * @param string $field
     * @param string $value
     * @param array $columns
     */
    public function findAllBy($field, $value, $columns = ['*']);

    /**
     *
     * @param array $columns
     */
    public function firstOrFail($columns = ['*']);
}
