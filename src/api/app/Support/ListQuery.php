<?php

namespace App\Support;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Applies the list conventions every index endpoint shares, so controllers
 * don't re-implement them by hand:
 *
 *   ?search=…              LIKE over the declared columns
 *   ?sort_by=…&sort_dir=…  only whitelisted columns; anything else → default
 *   ?<filter>=…            only declared filters, each with its own closure
 *   ?page=…&per_page=…     per_page clamped to MAX_PER_PAGE
 *
 * Pair it with a JsonResource collection to get the standard envelope
 * ({data, links, meta}) that useDataTable on the SPA consumes:
 *
 *   $users = ListQuery::for(User::query(), $request)
 *       ->search(['name', 'email'])
 *       ->sortable(['name', 'email', 'created_at'], default: 'name')
 *       ->filter('role', fn ($q, $role) => $q->role($role))
 *       ->paginate();
 *
 *   return UserResource::collection($users);
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
final class ListQuery
{
    public const DEFAULT_PER_PAGE = 20;

    public const MAX_PER_PAGE = 100;

    /** @var list<string> */
    private array $searchColumns = [];

    /** @var list<string> */
    private array $sortable = [];

    private ?string $defaultSort = null;

    private string $defaultDir = 'asc';

    /** @var array<string, Closure> */
    private array $filters = [];

    /**
     * @param  Builder<TModel>  $query
     */
    private function __construct(private Builder $query, private Request $request) {}

    /**
     * @param  Builder<TModel>  $query
     * @return self<TModel>
     */
    public static function for(Builder $query, Request $request): self
    {
        return new self($query, $request);
    }

    /**
     * @param  list<string>  $columns
     */
    public function search(array $columns): self
    {
        $this->searchColumns = $columns;

        return $this;
    }

    /**
     * @param  list<string>  $columns
     */
    public function sortable(array $columns, ?string $default = null, string $dir = 'asc'): self
    {
        $this->sortable = $columns;
        $this->defaultSort = $default;
        $this->defaultDir = $dir === 'desc' ? 'desc' : 'asc';

        return $this;
    }

    /**
     * Register a filter read from ?{$name}=. Empty values are ignored.
     *
     * @param  Closure(Builder<TModel>, string): mixed  $apply
     */
    public function filter(string $name, Closure $apply): self
    {
        $this->filters[$name] = $apply;

        return $this;
    }

    /**
     * @return Builder<TModel>
     */
    public function query(): Builder
    {
        $term = trim((string) $this->request->query('search', ''));
        if ($term !== '' && $this->searchColumns !== []) {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $this->query->where(function (Builder $q) use ($like) {
                foreach ($this->searchColumns as $column) {
                    $q->orWhere($column, 'like', $like);
                }
            });
        }

        foreach ($this->filters as $name => $apply) {
            $value = $this->request->query($name);
            if (is_string($value) && $value !== '') {
                $apply($this->query, $value);
            }
        }

        $sortBy = $this->request->query('sort_by');
        $sortDir = $this->request->query('sort_dir') === 'desc' ? 'desc' : 'asc';
        if (is_string($sortBy) && in_array($sortBy, $this->sortable, true)) {
            $this->query->orderBy($sortBy, $sortDir);
        } elseif ($this->defaultSort !== null) {
            $this->query->orderBy($this->defaultSort, $this->defaultDir);
        }

        return $this->query;
    }

    /**
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(): LengthAwarePaginator
    {
        $perPage = (int) $this->request->query('per_page', (string) self::DEFAULT_PER_PAGE);
        $perPage = max(1, min($perPage, self::MAX_PER_PAGE));

        return $this->query()->paginate($perPage)->withQueryString();
    }
}
