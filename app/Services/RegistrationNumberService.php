<?php

namespace App\Services;

use App\Models\RegistrationNumberSequence;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RegistrationNumberService
{
    public function generate(string $prefix = 'SUM'): string
    {
        $year = Carbon::now()->year;

        $sequence = DB::transaction(function () use ($year) {
            $record = RegistrationNumberSequence::where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($record) {
                $record->update(['sequence' => $record->sequence + 1]);
                return $record->sequence;
            }

            $record = RegistrationNumberSequence::create([
                'year' => $year,
                'sequence' => 1,
            ]);

            return $record->sequence;
        });

        return $prefix.'-'.$year.'-'.str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }
}
