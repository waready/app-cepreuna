<?php

namespace App\DataTable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class EloquentVueTables
{
    public function get($model, array $fields, array $relations = [], array $structure, $lazyEvent, $pages = 10)
    {
        $lazyEvent = json_decode($lazyEvent);
        // Lazy Params
        $sortField = $lazyEvent->sortField;
        $sortOrder = $lazyEvent->sortOrder;
        $filters = $lazyEvent->filters;
        $rows = $lazyEvent->rows;

        $string = explode(".", $sortField);
        $order = $sortOrder == 1 ? 'asc' : 'desc';

        $data = $model->select($fields)->with($relations);
        $table = $model->getTable();

        if (isset($sortField)) {
            if (count($string) == 3) {
                $data->join($structure[$string[0]]['table'], $structure[$string[0]]['table'] . '.id', $table . '.' . $structure[$string[0]]['key'])
                    ->join($structure[$string[1]]['table'], $structure[$string[1]]['table'] . '.id', $structure[$string[0]]['table'] . '.' . $structure[$string[1]]['key'])
                    ->orderBy($structure[$string[1]]['table'] . '.' . $string[2], $order);
            } else if (count($string) == 2) {

                $data->join($structure[$string[0]]['table'], $structure[$string[0]]['table'] . '.id', $table . '.' . $structure[$string[0]]['key'])
                    ->orderBy($structure[$string[0]]['table'] . '.' . $string[1], $order);
            } else {
                $data->orderBy($sortField, $order);
            }
        }

        foreach ($filters as $key => $value) {
            // var_dump($key); #column name
            // var_dump($value->value); #value: value and matchMode
            // startsWith, contains, notContains, endsWith, equals, notEquals
            switch ($value->matchMode) {
                case 'startsWith':
                    if ($value->value != '') {
                        $column = explode(".", $key);
                        $date = explode("T", $value->value);
                        if (count(explode("-", $date[0])) == 3) {

                            if (count($column) == 3) {
                                $data = $data->WhereHas($column[0], function (Builder $q) use ($column, $value, $date) {
                                    $q->whereHas($column[1], function (Builder $sq) use ($column, $value, $date) {
                                        $sq->where($column[2], 'like', "{$date[0]}%");
                                    });
                                });
                            } else if (count($column) == 2) {
                                // $q->where($string[1],'LIKE', "%{$val}%");
                                $data = $data->WhereHas($column[0], function (Builder $q) use ($column, $value, $date) {
                                    $q->where($column[1], 'like', "{$date[0]}%");
                                });
                            } else {
                                $data->where($key, 'like', "{$date[0]}%");
                            }
                        } else {
                            if (count($column) == 3) {
                                $data = $data->WhereHas($column[0], function (Builder $q) use ($column, $value) {
                                    $q->whereHas($column[1], function (Builder $sq) use ($column, $value) {
                                        $sq->where($column[2], 'LIKE', "{$value->value}%");
                                    });
                                });
                            } else if (count($column) == 2) {
                                // $q->where($string[1],'LIKE', "%{$val}%");
                                $data = $data->WhereHas($column[0], function (Builder $q) use ($column, $value) {
                                    $q->where($column[1], 'LIKE', "{$value->value}%");
                                });
                            } else {
                                $data->where($key, 'LIKE', "{$value->value}%");
                            }
                        }
                    }
                    break;
                case 'contains':
                    if ($value->value != '') {
                        $column = explode(".", $key);
                        if (count($column) == 3) {
                            $data = $data->WhereHas($column[0], function (Builder $q) use ($column, $value) {
                                $q->whereHas($column[1], function (Builder $sq) use ($column, $value) {
                                    $sq->where($column[2], 'LIKE', "%{$value->value}%");
                                });
                            });
                        } else if (count($column) == 2) {
                            // $q->where($string[1],'LIKE', "%{$val}%");
                            $data = $data->WhereHas($column[0], function (Builder $q) use ($column, $value) {
                                $q->where($column[1], 'LIKE', "%{$value->value}%");
                            });
                        } else {
                            $data->where($key, 'LIKE', "%{$value->value}%");
                        }
                    }
                    break;
                case 'notContains':
                    if ($value->value != '') {
                        $column = explode(".", $key);
                        if (count($column) == 3) {
                            $data = $data->WhereHas($column[0], function (Builder $q) use ($column, $value) {
                                $q->whereHas($column[1], function (Builder $sq) use ($column, $value) {
                                    $sq->where($column[2], '!=', $value->value);
                                });
                            });
                        } else if (count($column) == 2) {
                            // $q->where($string[1],'LIKE', "%{$val}%");
                            $data = $data->WhereHas($column[0], function (Builder $q) use ($column, $value) {
                                $q->where($column[1], '!=', $value->value);
                            });
                        } else {
                            $data->where($key, '!=', $value->value);
                        }
                    }
                    break;
                case 'endsWith':
                    if ($value->value != '') {
                        $column = explode(".", $key);
                        if (count($column) == 3) {
                            $data = $data->WhereHas($column[0], function (Builder $q) use ($column, $value) {
                                $q->whereHas($column[1], function (Builder $sq) use ($column, $value) {
                                    $sq->where($column[2], 'LIKE', "%{$value->value}");
                                });
                            });
                        } else if (count($column) == 2) {
                            // $q->where($string[1],'LIKE', "%{$val}%");
                            $data = $data->WhereHas($column[0], function (Builder $q) use ($column, $value) {
                                $q->where($column[1], 'LIKE', "%{$value->value}");
                            });
                        } else {
                            $data->where($key, 'LIKE', "%{$value->value}");
                        }
                    }
                    break;
                case 'equals':
                    // var_dump(checkdate($value->value));

                    if ($value->value != '') {
                        $column = explode(".", $key);
                        if (count($column) == 3) {
                            $data = $data->WhereHas($column[0], function (Builder $q) use ($column, $value) {
                                $q->whereHas($column[1], function (Builder $sq) use ($column, $value) {
                                    $sq->where($column[2], '=', "{$value->value}");
                                });
                            });
                        } else if (count($column) == 2) {
                            // $q->where($string[1],'LIKE', "%{$val}%");
                            $data = $data->WhereHas($column[0], function (Builder $q) use ($column, $value) {
                                $q->where($column[1], '=', "{$value->value}");
                            });
                        } else {
                            $data->where($key, '=', "{$value->value}");
                        }
                    }
                    break;
                case 'notEquals':
                    if ($value->value != '') {
                        $column = explode(".", $key);
                        if (count($column) == 3) {
                            $data = $data->WhereHas($column[0], function (Builder $q) use ($column, $value) {
                                $q->whereHas($column[1], function (Builder $sq) use ($column, $value) {
                                    $sq->where($column[2], '!=', $value->value);
                                });
                            });
                        } else if (count($column) == 2) {
                            // $q->where($string[1],'LIKE', "%{$val}%");
                            $data = $data->WhereHas($column[0], function (Builder $q) use ($column, $value) {
                                $q->where($column[1], '!=', $value->value);
                            });
                        } else {
                            $data->where($key, '!=', $value->value);
                        }
                    }
                    break;
            }
        }

        // if (isset($sortField)) {

        //     if (count($string) == 2) {
        //         $data = $model->select($fields)->with($relations)->paginate($pages);
        //         return $data->load([$string[0] => function ($query) use ($string, $order) {
        //             $query->orderBy($string[1], $order);
        //         }]);
        //     } else {
        //         $data = $model->select($fields)->with($relations);
        //         $data->orderBy($sortField, $order);
        //         return $data->paginate($pages);
        //     }
        // } else {
        //     $data = $model->select($fields)->with($relations);
        //     return $data->paginate($pages);
        // }


        // if (isset($sortField)) {
        //     if (count($string) == 2) {
        //         foreach ($relations as $key => $value) {
        //             // var_dump($value);
        //             // var_dump($key);
        //             if ($string[0] == $value) {
        //                 $data->with([$value => function ($q) use ($string, $order) {
        //                     $q->orderBy($string[1], $order);
        //                 }]);
        //             } else {
        //                 $data->with($value);
        //             }
        //         }
        //     } else {
        //         foreach ($relations as $key => $value) {
        //             // var_dump($value);
        //             // var_dump($key);
        //             $data->with($value);
        //         }
        //         $data->orderBy($sortField, $order);
        //     }
        // } else {
        //     foreach ($relations as $key => $value) {
        //         // var_dump($value);
        //         // var_dump($key);
        //         $data->with($value);
        //     }
        // }
        // } else {
        //     foreach ($relations as $key => $value) {
        //         // var_dump($value);
        //         // var_dump($key);
        //         $data->with([$value => function ($q) use ($string, $value) {
        //             if ($string[0] == $value) {
        //             }
        //             $q->orderBy();
        //         }]);
        //     }
        // }
        // $data = $model->select($fields)->with($relations);

        // if (isset($sortField)) {
        //     $string = explode(".", $sortField);
        //     $order = $sortOrder == 1 ? 'asc' : 'desc';

        //     if (count($string) == 2) {
        //         $data = $data->WhereHas($string[0], function (Builder $query) use ($string, $order) {
        //             // $order = $sortOrder == 1 ? 'asc' : 'desc';
        //             $query->orderBy($string[1], $order);
        //         });
        //         // ->orderBy(Str::plural($string[0]) . '.' . $string[1], $order)
        //     } else {
        //         $data->orderBy($sortField, $order);
        //     }
        // }



        return $data;
    }
}
