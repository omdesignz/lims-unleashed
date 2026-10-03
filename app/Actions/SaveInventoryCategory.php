<?php

namespace App\Actions;

use App\Models\ItemCategory;
use App\Models\User;
use App\Services\InventoryCategoryValidation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SaveInventoryCategory
{
    public function __construct(private readonly InventoryCategoryValidation $validation) {}

    /** @param array<string,mixed> $data */
    public function execute(int $userId, array $data, ?int $categoryId = null): ItemCategory
    {
        return DB::transaction(function () use ($userId, $data, $categoryId): ItemCategory {
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            $permission = $categoryId === null ? 'add_item_categories' : 'edit_item_categories';
            $actor = User::withTrashed()->whereKey($userId)->lockForUpdate()->toBase()->first();
            abort_unless($actor && $actor->deleted_at === null && $actor->is_active && $actor->email_verified_at, 403);
            $rows = ItemCategory::withTrashed()->orderBy('id')->lockForUpdate()->toBase()->get()->keyBy('id');
            $usage = $this->categoryUsage();
            $category = new ItemCategory;
            $category->forceFill(['description' => null, 'code' => null, 'parent_id' => null, 'deleted_at' => null]);
            if ($categoryId !== null) {
                $row = $rows->get($categoryId);
                abort_unless($row && $row->deleted_at === null, 404);
                $category->setRawAttributes((array) $row, true);
                $category->exists = true;
            }
            $this->authorize(User::query()->find($userId), $permission);
            $this->assertCategoryUsage($usage);
            $this->assertCategories($rows->map(fn (object $row): array => (array) $row)->all());
            abort_unless((array) User::withTrashed()->whereKey($userId)->toBase()->first() === (array) $actor,
                409, 'A identidade ou elegibilidade do operador foi alterada.');
            $data = Validator::make($data, $this->validation->rules($category->exists ? $category : null))->validate();
            if (array_key_exists('parent_id', $data)) {
                $data['parent_id'] = $data['parent_id'] !== null ? (int) $data['parent_id'] : null;
            }
            $parentId = $data['parent_id'] ?? null;
            $seen = [$categoryId];
            while ($parentId !== null) {
                if (in_array((int) $parentId, $seen, true)) {
                    throw ValidationException::withMessages(['parent_id' => 'A hierarquia de categorias não pode conter ciclos.']);
                }
                $seen[] = (int) $parentId;
                $parentId = $rows->get($parentId)?->parent_id;
            }
            $category->fill($data);
            if (! $category->exists || $category->isDirty()) {
                if (! $category->exists) {
                    $category->setCreatedAt($category->freshTimestamp());
                }
                $category->setUpdatedAt($category->freshTimestamp());
                $intended = clone $category;
                abort_unless(ItemCategory::withoutTimestamps(fn (): bool => $category->save()) && $category->exists && $category->id,
                    409, 'Não foi possível guardar a categoria.');
                $intended->id = $category->id;
                $stored = (array) ItemCategory::withTrashed()->whereKey($category->id)->toBase()->first();
                foreach ($intended->getAttributes() as $field => $value) {
                    abort_unless(array_key_exists($field, $stored) && $stored[$field] === $value, 409, 'A categoria guardada não corresponde à operação.');
                }
                $category = $intended;
                $category->exists = true;
                $category->syncOriginal();
            }
            $this->authorize(User::query()->find($userId), $permission);
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            abort_unless((array) User::withTrashed()->whereKey($userId)->toBase()->first() === (array) $actor,
                409, 'A identidade ou elegibilidade do operador foi alterada.');
            $expectedRows = $rows->map(fn (object $row): array => (array) $row)->all();
            $expectedRows[$category->id] = $category->getAttributes();
            $this->assertCategories($expectedRows);
            $this->assertCategoryUsage($usage);

            return $category;
        });
    }

    private function authorize(?User $actor, string $permission): void
    {
        abort_unless($actor && $actor->is_active && $actor->email_verified_at && $actor->can($permission), 403);
    }

    /** @return list<array<string,mixed>> */
    private function categoryUsage(): array
    {
        return DB::table('inventory_category_usage')->orderBy('category_id')->get()
            ->map(fn (object $row): array => (array) $row)->all();
    }

    /** @param list<array<string,mixed>> $expected */
    private function assertCategoryUsage(array $expected): void
    {
        abort_unless($this->categoryUsage() === $expected, 409, 'O histórico de utilização das categorias não foi preservado.');
    }

    /** @param array<int,array<string,mixed>> $expectedRows */
    private function assertCategories(array $expectedRows): void
    {
        $storedRows = ItemCategory::withTrashed()->orderBy('id')->toBase()->get()->keyBy('id')
            ->map(fn (object $row): array => (array) $row)->all();
        abort_unless(count($storedRows) === count($expectedRows), 409, 'As categorias anteriores não foram preservadas.');
        foreach ($expectedRows as $id => $expected) {
            $actual = $storedRows[$id] ?? [];
            foreach ($expected as $field => $value) {
                abort_unless(array_key_exists($field, $actual) && $actual[$field] === $value,
                    409, 'As categorias guardadas não correspondem à operação.');
            }
        }
    }
}
