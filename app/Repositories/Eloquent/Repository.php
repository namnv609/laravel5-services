<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\RepositoryInterface;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Container\Container as App;

abstract class Repository implements RepositoryInterface
{
    /**
     *
     * @var $app
     */
    private $app;

    /**
     *
     * @var $model
     */
    protected $model;

    /**
     *
     * @var $withTrashed
     */
    private $withTrashed;

    /**
     *
     * @var $onlyTrashed
     */
    private $onlyTrashed;

    /**
     *
     * @var $where
     */
    private $where;

    /**
     *
     * @var $orWhere
     */
    private $orWhere;

    /**
     *
     * @var $skip
     */
    private $skip;

    /**
     *
     * @var $take
     */
    private $take;

    /**
     *
     * @var $orderBy
     */
    private $orderBy;

    /**
     *
     * @param App $app
     * @param Collection $collection
     */
    public function __construct(App $app, Collection $collection)
    {
        $this->app = $app;
        $this->makeModel();
    }

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public abstract function model();

    /**
     *
     * @param array $columns
     * @return mixed
     */
    public function all($columns = ['*'])
    {
        $this->newQuery()
             ->eagerLoadTrashed()
             ->eagerLoadWhere()
             ->eagerTakeAndSkip()
             ->eagerOrderBy();

        return $this->model->get($columns);
    }


    /**
     *
     * @param int $perPage
     * @param array $columns
     * @return mixed
     */
    public function paginate($perPage = 20, $columns = ['*'])
    {
        $this->newQuery()
             ->eagerLoadTrashed()
             ->eagerLoadWhere()
             ->eagerOrderBy();

        return $this->model->paginate($perPage, $columns);
    }

    /**
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     *
     * @param array $data
     * @param string|int $id
     * @param string $attribute
     * @param bool $withSoftDel
     * @return mixed
     */
    public function update(array $data, $id, $attribute = 'id', $withSoftDel = false)
    {
        if ($withSoftDel) {
            $this->newQuery()->eagerLoadTrashed();
        }

        return $this->model->where($attribute, '=', $id)->update($data);
    }

    /**
     *
     * @param string|int $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->model->destroy($id);
    }

    /**
     * Truly remove a model from database
     * @return mixed
     */
    public function forceDelete($id)
    {
        return $this->find($id)->forceDelete();
    }

    /**
     *
     * @param string|int $id
     * @param array $columns
     * @return mixed
     */
    public function find($id, $columns = ['*'])
    {
        $this->newQuery()->eagerLoadTrashed();

        return $this->model->findOrFail($id, $columns);
    }

    /**
     *
     * @param string $field
     * @param string $value
     * @param array $columns
     * @return mixed
     */
    public function findBy($field, $value, $columns = ['*'])
    {
        $this->newQuery()->eagerLoadTrashed();

        return $this->model->where($field, '=', $value)->firstOrFail($columns);
    }

    /**
     *
     * @param string $field
     * @param string $value
     * @param array $columns
     * @return mixed
     */
    public function findAllBy($field, $value, $columns = ['*'])
    {
        $this->newQuery()
             ->eagerLoadTrashed()
             ->eagerOrderBy();

        return $this->model->where($field, '=', $value)->get($columns);
    }

    /**
     *
     * @param array $columns
     * @return mixed
     */
    public function firstOrFail($columns = ['*'])
    {
        $this->newQuery()->eagerLoadTrashed()->eagerLoadWhere();

        return $this->model->firstOrFail();
    }

    /**
     *
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws Exception
     */
    public function makeModel()
    {
        $model = $this->app->make($this->model());

        if (!$model instanceof Model) {
            throw new Exception("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $this->model = $model;
    }

    /**
     * Load result with soft delete
     *
     * @return $this
     */
    public function withTrashed()
    {
        $this->withTrashed = func_get_args();
        return $this;
    }

    /**
     * Load result only deleted with soft delete
     *
     * @return $this
     */
    public function onlyTrashed()
    {
        $this->onlyTrashed = func_get_args();

        return $this;
    }

    /**
     * And where
     *
     * @param mixed $condition Array of conditions or string field name
     * @param mixed $value Value of field (if condition is field name string)
     * @param string $operator Condition operator (ie: =, <=, >=, <>, ...)
     * @return $this
     */
    public function where($conditions, $value = '1', $operator = '=')
    {
        $this->where[] = func_get_args($conditions, $value, $operator);

        return $this;
    }

    /**
     * Or where
     *
     * @param mixed $condition Array of conditions or string field name
     * @param mixed $value Value of field (if condition is field name string)
     * @param string $operator Condition operator (ie: =, <=, >=, <>, ...)
     * @return $this
     */
    public function orWhere($conditions, $value = '1', $operator = '=')
    {
        $this->orWhere[] = func_get_args($conditions, $value, $operator);

        return $this;
    }

    /**
     * Offset of cursor in result set
     *
     * @param int $offset Offset number
     * @return $this
     */
    public function skip($offset = 0)
    {
        $this->skip = $offset;

        return $this;
    }

    /**
     * Limit records of result set
     *
     * @param int $limit Limit number
     * @return $this
     */
    public function take($limit = 20)
    {
        $this->take = $limit;

        return $this;
    }

    /**
     * Sort result set
     *
     * @param string $field Field name
     * @param string $direction Sort direction (ASC and DESC)
     * @return $this
     */
    public function orderBy($field, $direction = 'ASC')
    {
        $this->orderBy[] = func_get_args($field, $direction);

        return $this;
    }

    /**
     * Create new query for model
     *
     * @return $this
     */
    private function newQuery()
    {
        $this->model = $this->model->newQuery();

        return $this;
    }

    /**
     * Eager loading trashed
     *
     * @return $this
     */
    private function eagerLoadTrashed()
    {
        if (!is_null($this->withTrashed)) {
            $this->model->withTrashed();
        } elseif (!is_null($this->onlyTrashed)) {
            $this->model->onlyTrashed();
        }

        return $this;
    }

    /**
     * Eager loading for and where & or where
     *
     * @return $this
     */
    private function eagerLoadWhere()
    {
        if (count($this->where) > 0) {
            foreach ($this->where as $where) {
                if (is_array($where[0])) {
                    $this->model->where($where[0]);
                } else {
                    $operator = (isset($where[2]) ? $where[2] : '=');
                    $this->model->where($where[0], $operator, $where[1]);
                }
            }
        }

        if (count($this->orWhere) > 0) {
            foreach ($this->orWhere as $orWhere) {
                if (is_array($orWhere[0])) {
                    $this->model->orWhere($orWhere[0]);
                } else {
                    $operator = (isset($orWhere[2]) ? $orWhere[2] : '=');
                    $this->model->orWhere($orWhere[0], $operator, $orWhere[1]);
                }
            }

            if (!is_null($this->withTrashed)) {
                $this->model->where(function($query) {
                    return $query->whereNull('deleted_at')->orWhereNotNull('deleted_at');
                });
            }

            if (!is_null($this->onlyTrashed)) {
                $this->model->whereNotNull('deleted_at');
            }
        }

        return $this;
    }

    /**
     * Eager loading for take and skip
     *
     * @return $this
     */
    private function eagerTakeAndSkip()
    {
        if (!is_null($this->skip)) {
            $this->model->skip($this->skip);
        }

        if (!is_null($this->take)) {
            $this->model->take($this->take);
        }

        return $this;
    }

    /**
     * Eager loading for order by
     *
     * @return $this
     */
    private function eagerOrderBy()
    {
        if (count($this->orderBy) > 0) {
            foreach ($this->orderBy as $orderBy) {
                $direction = (isset($orderBy[1]) ? $orderBy[1] : 'ASC');
                $this->model->orderBy($orderBy[0], $direction);
            }
        }

        return $this;
    }
}
