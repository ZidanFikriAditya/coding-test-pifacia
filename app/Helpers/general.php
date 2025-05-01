<?php

function parsingDataTable($columns, $data)
{
    $data = $data->when(request()->filled('search'), function ($query) use ($columns) {
        $query->where(function ($query) use ($columns) {
            foreach ($columns as $column) {
                if ($column['filter'] ?? false) {
                    $column['filter']($query);
                } else {
                    if ($column['searchable'] ?? true) {
                        $query->orWhere($column['field'] ?? $column['name'], 'like', '%' . request()->search . '%');
                    }
                }
            }
        });
    })
        ->when(request()->filled('order_by'), function ($query) use ($columns) {
            $orderBy = request('order_by');
            foreach ($columns as $column) {
                if ($column['name'] != $orderBy) continue;
                
                if ($column['ordering'] ?? false) {
                    $column['ordering']($query);
                } else {
                    $query->orderBy(request('order_by'), request('order') ?? 'asc');
                }
            }
        }, function ($query) {
            $query->orderBy('created_at', 'desc');
        })
        ->paginate(request('limit') ?? 10);

    $modifyData = [];
    foreach ($data->items() as $item) {
        $temp = [];
        $temp['id'] = $item->id;
        foreach ($columns as $column) {
            $temp[$column['name']] = $item[$column['name']];
            if ($column['render'] ?? false) {
                $temp[$column['name']] = $column['render']($item);
                continue;
            }
        }


        $modifyData[] = $temp;
    }

    $response = [
        'data' => $modifyData,
        'meta' => [
            'currentPage' => $data->currentPage(),
            'perPage' => $data->perPage(),
            'total' => $data->total(),
            'lastPage' => $data->lastPage(),
        ]
    ];

    return $response;
}