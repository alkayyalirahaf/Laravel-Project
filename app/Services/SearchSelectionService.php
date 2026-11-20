<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SearchSelectionService
{
    public function select(array $input, FlightSearchService $search): array
    {
        $filters = $search->validateFilters(array_merge($input, ['leg' => 'outbound']));
        $ids = Validator::make($input, [
            'outbound_class_id' => ['required', 'integer', 'min:1'],
            'return_class_id' => [$filters['trip'] === 'round_trip' ? 'required' : 'prohibited', 'integer', 'min:1', 'different:outbound_class_id'],
        ])->validate();

        $outbound = $this->leg($filters, (int) $ids['outbound_class_id'], $search);
        $return = null;

        if ($filters['trip'] === 'round_trip') {
            $returnFilters = array_merge($filters, ['leg' => 'return', 'outbound_class_id' => $ids['outbound_class_id']]);
            $return = $this->leg($returnFilters, (int) $ids['return_class_id'], $search);
        }

        return [
            'trip' => $filters['trip'], 'passengers' => (int) $filters['passengers'],
            'filters' => $filters, 'outbound' => $outbound, 'return' => $return,
        ];
    }

    private function leg(array $filters, int $classId, FlightSearchService $search): array
    {
        $flight = $search->query($filters)->whereHas('flightClasses', function ($query) use ($classId, $search, $filters) {
            $query->whereKey($classId);
            ($search->classFilter($filters))($query);
        })->first();

        if (!$flight) {
            throw ValidationException::withMessages(['selection' => 'A selected cabin no longer matches the search or has insufficient available seats. Search again.']);
        }

        $class = $flight->flightClasses->firstWhere('id', $classId);

        return ['flight_id' => $flight->id, 'flight_class_id' => $class->id, 'price' => $class->price];
    }
}
