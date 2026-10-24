<?php

declare(strict_types=1);

namespace App\Modules\Staff\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Modules\Staff\Services\StaffLog;

/**
 * Legacy staff_battletent.php: which NPCs are battle tent challengers, and their bounty.
 */
final class ChallengeBotController
{
    public function __construct(
        private readonly Database $db,
        private readonly Validator $validator,
        private readonly StaffLog $staffLog,
        private readonly Session $session,
    ) {
    }

    public function index(): Response
    {
        return view('pages.staff.challenge-bots.index', [
            'bots' => $this->db->fetchAll(
                'SELECT b.user_id, b.reward_money, u.username, u.level,
                        (SELECT COUNT(*) FROM challenge_bot_wins w WHERE w.bot_user_id = b.user_id) AS times_beaten
                 FROM challenge_bots b JOIN users u ON u.id = b.user_id ORDER BY u.username',
            ),
            'npcs' => array_column($this->db->fetchAll(
                "SELECT id, CONCAT(username, ' [', id, ']') AS label FROM users
                 WHERE account_type = 'NPC' AND id NOT IN (SELECT user_id FROM challenge_bots) ORDER BY username",
            ), 'label', 'id'),
        ], 'admin');
    }

    public function store(Request $request): Response
    {
        $data = $this->validator->validate($request->all(), ['user_id' => 'required|integer|min:1', 'reward_money' => 'required|integer|min:1']);
        $user = $this->db->fetch('SELECT id, username, account_type FROM users WHERE id = ?', [(int) $data['user_id']])
            ?? throw ValidationException::withMessages(['user_id' => 'That player does not exist.']);

        if ($user['account_type'] !== 'NPC') {
            throw ValidationException::withMessages(['user_id' => 'Challenge bots must be NPCs.']);
        }
        if ($this->db->fetchColumn('SELECT 1 FROM challenge_bots WHERE user_id = ?', [$user['id']]) !== null) {
            throw ValidationException::withMessages(['user_id' => 'This NPC is already a challenge bot; edit its bounty instead.']);
        }

        $this->db->insert('challenge_bots', ['user_id' => (int) $user['id'], 'reward_money' => (int) $data['reward_money']]);
        $this->staffLog->add("Added challenge bot {$user['username']} [{$user['id']}]");

        return $this->done("Challenge bot {$user['username']} added.");
    }

    public function update(Request $request, int $id): Response
    {
        $bot = $this->bot($id);
        $reward = (int) $this->validator->validate($request->all(), ['reward_money' => 'required|integer|min:1'])['reward_money'];

        $this->db->update('challenge_bots', ['reward_money' => $reward], ['user_id' => $id]);
        $this->staffLog->add("Edited challenge bot {$bot['username']} [{$id}]");

        return $this->done("Challenge bot {$bot['username']} updated.");
    }

    public function destroy(int $id): Response
    {
        $bot = $this->bot($id);

        $this->db->delete('challenge_bots', ['user_id' => $id]);
        $this->staffLog->add("Removed challenge bot {$bot['username']} [{$id}]");

        return $this->done("Challenge bot {$bot['username']} removed.");
    }

    private function bot(int $id): array
    {
        return $this->db->fetch('SELECT b.user_id, u.username FROM challenge_bots b JOIN users u ON u.id = b.user_id WHERE b.user_id = ?', [$id])
            ?? throw new NotFoundException('That challenge bot does not exist.');
    }

    private function done(string $message): Response
    {
        $this->session->flash('status', $message);

        return Response::redirect('/admin/challenge-bots');
    }
}
