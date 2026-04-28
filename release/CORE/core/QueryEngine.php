<?php

require_once __DIR__ . '/CRUDOperations.php';

class QueryEngine
{

    private $crud;

    public function __construct()
    {
        $this->crud = new CRUDOperations();
    }

    /**
     * Execute a complex query on a collection
     * 
     * @param string $collection
     * @param array $params Query parameters (where, orderBy, limit, offset, search)
     * @return array
     */
    public function query($collection, $params)
    {
        // 1. Fetch All Data (Inefficient for large sets, optimizing later with Indexing)
        $items = $this->crud->list($collection);

        // 2. Filter (WHERE)
        if (isset($params['where']) && is_array($params['where'])) {
            $items = array_filter($items, function ($item) use ($params) {
                foreach ($params['where'] as $condition) {
                    // Expects condition like ['field', 'operator', 'value'] 
                    // or simple key-value for equality: 'status' => 'published'

                    if (is_array($condition)) {
                        $field = $condition[0] ?? ($condition['field'] ?? null);
                        $op = $condition[1] ?? ($condition['operator'] ?? '=');
                        $val = $condition[2] ?? ($condition['value'] ?? null);

                        if (!$field)
                            continue;

                        $itemVal = isset($item[$field]) ? $item[$field] : null;

                        switch ($op) {
                            case '=':
                            case '==':
                                if ($itemVal != $val)
                                    return false;
                                break;
                            case '!=':
                                if ($itemVal == $val)
                                    return false;
                                break;
                            case '>':
                                if ($itemVal <= $val)
                                    return false;
                                break;
                            case '<':
                                if ($itemVal >= $val)
                                    return false;
                                break;
                            case '>=':
                                if ($itemVal < $val)
                                    return false;
                                break;
                            case '<=':
                                if ($itemVal > $val)
                                    return false;
                                break;
                            case 'IN':
                                if (!in_array($itemVal, $val))
                                    return false;
                                break;
                            case 'contains':
                                if (is_string($itemVal) && stripos($itemVal, $val) === false)
                                    return false;
                                if (is_array($itemVal) && !in_array($val, $itemVal))
                                    return false;
                                break;
                        }
                    } else {
                        // Handle simple key => value map if passed differently
                        // But sticking to strict array format is safer for now.
                    }
                }
                return true;
            });
        }

        // 3. Search (Full Text - Naive implementation)
        if (isset($params['search']) && !empty($params['search'])) {
            $term = strtolower($params['search']);
            $items = array_filter($items, function ($item) use ($term) {
                // Search in all string fields
                foreach ($item as $key => $value) {
                    if (is_string($value) && strpos(strtolower($value), $term) !== false) {
                        return true;
                    }
                }
                return false;
            });
        }

        // 4. Sort (ORDER BY)
        if (isset($params['orderBy'])) {
            $field = isset($params['orderBy']['field']) ? $params['orderBy']['field'] : '_createdAt';
            $direction = (isset($params['orderBy']['direction']) && strtolower($params['orderBy']['direction']) === 'asc') ? 1 : -1;

            usort($items, function ($a, $b) use ($field, $direction) {
                $valA = isset($a[$field]) ? $a[$field] : null;
                $valB = isset($b[$field]) ? $b[$field] : null;

                if ($valA == $valB)
                    return 0;
                return ($valA > $valB ? 1 : -1) * $direction;
            });
        }

        // 5. Pagination (Limit/Offset)
        $total = count($items); // Total valid items after filter
        $offset = isset($params['offset']) ? (int) $params['offset'] : 0;
        $limit = isset($params['limit']) ? (int) $params['limit'] : null;

        if ($limit) {
            $items = array_slice($items, $offset, $limit);
        } else if ($offset > 0) {
            $items = array_slice($items, $offset);
        }

        return [
            'items' => array_values($items), // Re-index array
            'total' => $total,
            'params' => $params
        ];
    }
}
