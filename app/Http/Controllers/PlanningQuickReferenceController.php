<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Guide;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlanningQuickReferenceController extends Controller
{
    public function services(Request $request): JsonResponse
    {
        return $this->search($request, Service::query(), 'designation', ['designation', 'description']);
    }

    public function storeService(Request $request): JsonResponse
    {
        $data = $request->validate([
            'designation' => ['required', 'string', 'max:255'],
            'type_service' => ['required', 'integer', Rule::exists('type_services', 'id')],
            'description' => ['nullable', 'string', 'max:5000'],
            'confirm_similar' => ['sometimes', 'boolean'],
        ]);

        return $this->createOrSuggest($request, Service::query(), 'designation', $data, 'designation');
    }

    public function destinations(Request $request): JsonResponse
    {
        return $this->search($request, Destination::query(), 'name', ['name', 'city', 'country']);
    }

    public function storeDestination(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'confirm_similar' => ['sometimes', 'boolean'],
        ]);
        $data['country'] = ($data['country'] ?? null) ?: 'Maroc';
        $data['status'] = 'Actif';

        return $this->createOrSuggest($request, Destination::query(), 'name', $data, 'name');
    }

    public function guides(Request $request): JsonResponse
    {
        return $this->search($request, Guide::query(), 'name', ['name', 'phone', 'email']);
    }

    public function storeGuide(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'confirm_similar' => ['sometimes', 'boolean'],
        ]);
        $data['status'] = ($data['status'] ?? null) ?: 'Disponible';

        return $this->createOrSuggest($request, Guide::query(), 'name', $data, 'name');
    }

    private function search(Request $request, $query, string $label, array $columns): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        $query->when($term !== '', function ($builder) use ($columns, $term) {
            $builder->where(function ($nested) use ($columns, $term) {
                foreach ($columns as $index => $column) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $nested->{$method}($column, 'like', '%'.$term.'%');
                }
            });
        });

        return response()->json(['data' => $query->orderBy($label)->limit(30)->get()]);
    }

    private function createOrSuggest(Request $request, $query, string $column, array $data, string $label): JsonResponse
    {
        unset($data['confirm_similar']);
        $needle = $this->normalized($data[$column]);
        $similar = $query->get()->first(fn (Model $item) => $this->normalized($item->{$column}) === $needle);

        if ($similar) {
            return response()->json([
                'message' => 'Une valeur identique ou très proche existe déjà.',
                'existing' => $similar,
            ], 409);
        }

        $model = $query->create($data);

        return response()->json(['data' => $model, 'label' => $model->{$label}], 201);
    }

    private function normalized(?string $value): string
    {
        return Str::of(Str::ascii((string) $value))->lower()->squish()->toString();
    }
}
