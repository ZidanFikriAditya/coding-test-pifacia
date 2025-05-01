<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromView;

class SeminarExport implements FromView
{
    public function __construct(
        public $seminars,
        public $headers,

    ) {
        //
    }

    public function view(): \Illuminate\Contracts\View\View
    {
        return view('exports.general', [
            'data' => $this->seminars,
            'headers' => $this->headers,
            'title' => 'Seminar Export',
        ]);
    }
}
