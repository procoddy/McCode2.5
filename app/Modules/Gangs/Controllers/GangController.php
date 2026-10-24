<?php

declare(strict_types=1);

namespace App\Modules\Gangs\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Exceptions\NotFoundException;
use App\Modules\Gangs\Repositories\GangRepository;
use App\Modules\Gangs\Services\GangService;

/**
 * The public gang list and pages, applications, and creategang.php.
 */
final class GangController
{
    public function __construct(
        private readonly GangRepository $gangs,
        private readonly GangService $service,
        private readonly Validator $validator,
        private readonly Session $session,
    ) {
    }

    public function index(): Response
    {
        return view('pages.gangs.index', ['gangs' => $this->gangs->all()]);
    }

    public function show(int $id): Response
    {
        $gang = $this->gangs->find($id) ?? throw new NotFoundException('That gang does not exist.');

        return view('pages.gangs.show', ['gang' => $gang, 'members' => $this->gangs->members($id)]);
    }

    public function apply(Request $request, int $id): Response
    {
        $body = (string) ($this->validator->validate($request->all(), ['body' => 'string|max:1000'])['body'] ?? '');
        $this->session->flash('status', $this->service->apply($request->attribute('player'), $id, trim($body)));

        return Response::redirect("/gangs/{$id}");
    }

    public function createForm(): Response
    {
        return view('pages.gangs.create', ['cost' => (int) config('game.gangs.create_cost')]);
    }

    public function create(Request $request): Response
    {
        $data = $this->validator->validate($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'string|max:5000',
        ]);
        $this->session->flash('status', $this->service->create($request->attribute('player'), trim($data['name']), trim((string) ($data['description'] ?? ''))));

        return Response::redirect('/gang');
    }
}
