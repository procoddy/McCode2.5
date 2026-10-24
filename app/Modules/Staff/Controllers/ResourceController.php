<?php

declare(strict_types=1);

namespace App\Modules\Staff\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Exceptions\AuthorizationException;
use App\Exceptions\NotFoundException;
use App\Modules\Staff\Resources\ResourceDefinition;
use App\Modules\Staff\Resources\ResourceRepository;
use App\Modules\Staff\Resources\ResourceService;
use App\Modules\Staff\Services\StaffLog;
use App\Modules\Staff\Services\StaffPermissions;

/**
 * Create / edit / delete for every table in config/staff_resources.php.
 */
final class ResourceController
{
    public function __construct(
        private readonly ResourceRepository $repository,
        private readonly ResourceService $service,
        private readonly StaffPermissions $permissions,
        private readonly StaffLog $staffLog,
        private readonly Session $session,
    ) {
    }

    public function index(Request $request, string $key): Response
    {
        $def = $this->authorize($request, $key);

        return view('pages.staff.resources.index', [
            'def' => $def,
            'rows' => $this->repository->paginate($def, (int) $request->query('page', 1)),
            'options' => $this->optionsFor($def),
        ], 'admin');
    }

    public function create(Request $request, string $key): Response
    {
        $def = $this->authorize($request, $key);

        return $this->form($def, null, array_map(fn (array $f) => $f['default'] ?? null, $def->fields));
    }

    public function store(Request $request, string $key): Response
    {
        $def = $this->authorize($request, $key);
        [$values, $pivots] = $this->service->validate($def, $request->all());
        $id = $this->repository->create($def, $values, $pivots);

        $this->staffLog->add("Created {$def->singular} #{$id}" . (isset($values['name']) ? " ({$values['name']})" : ''));
        $this->session->flash('status', ucfirst($def->singular) . ' created.');

        return Response::redirect($def->url());
    }

    public function edit(Request $request, string $key, int $id): Response
    {
        $def = $this->authorize($request, $key);
        $row = $this->repository->find($def, $id) ?? throw new NotFoundException();

        foreach ($def->pivotFields() as $name => $field) {
            $row[$name] = $this->repository->pivotIds($field, $id);
        }

        return $this->form($def, $id, $row);
    }

    public function update(Request $request, string $key, int $id): Response
    {
        $def = $this->authorize($request, $key);
        $this->repository->find($def, $id) ?? throw new NotFoundException();
        [$values, $pivots] = $this->service->validate($def, $request->all(), $id);
        $this->repository->update($def, $id, $values, $pivots);

        $this->staffLog->add("Edited {$def->singular} #{$id}" . (isset($values['name']) ? " ({$values['name']})" : ''));
        $this->session->flash('status', ucfirst($def->singular) . ' saved.');

        return Response::redirect($def->url());
    }

    public function destroy(Request $request, string $key, int $id): Response
    {
        $def = $this->authorize($request, $key);
        $row = $this->repository->find($def, $id) ?? throw new NotFoundException();

        if (($reason = $this->service->deleteBlockedReason($def, $id)) !== null) {
            $this->session->flash('status', $reason);

            return Response::redirect($def->url("{$id}/edit"));
        }

        $this->repository->delete($def, $id);
        $this->staffLog->add("Deleted {$def->singular} #{$id}" . (isset($row['name']) ? " ({$row['name']})" : ''));
        $this->session->flash('status', ucfirst($def->singular) . ' deleted.');

        return Response::redirect($def->url());
    }

    private function form(ResourceDefinition $def, ?int $id, array $values): Response
    {
        return view('pages.staff.resources.form', [
            'def' => $def,
            'id' => $id,
            'values' => $values,
            'options' => $this->optionsFor($def),
        ], 'admin');
    }

    /** @return array<string, array<int, string>> */
    private function optionsFor(ResourceDefinition $def): array
    {
        $options = [];
        foreach ($def->fields as $name => $field) {
            if (isset($field['source'])) {
                $options[$name] = $this->repository->options($field['source']);
            }
        }

        return $options;
    }

    private function authorize(Request $request, string $key): ResourceDefinition
    {
        $def = ResourceDefinition::find($key);

        if (!$this->permissions->can($request->attribute('user'), $def->permission)) {
            throw new AuthorizationException('You do not have permission to manage ' . strtolower($def->title) . '.');
        }

        return $def;
    }
}
