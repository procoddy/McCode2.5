<?php

declare(strict_types=1);

namespace App\Modules\Community\Repositories;

use App\Core\Database;

/**
 * polls (choices as a JSON list) and poll_votes (one vote per player per poll).
 */
class PollRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /** @return list<array> polls with decoded choices and vote counts */
    public function list(bool $active): array
    {
        $polls = $this->db->fetchAll('SELECT * FROM polls WHERE active = ? ORDER BY created_at DESC, id DESC', [$active ? 1 : 0]);

        return array_map(fn (array $poll) => $this->hydrate($poll), $polls);
    }

    public function find(int $id): ?array
    {
        $poll = $this->db->fetch('SELECT * FROM polls WHERE id = ?', [$id]);

        return $poll === null ? null : $this->hydrate($poll);
    }

    /** @return list<int> poll ids this player voted in */
    public function votedIn(int $userId): array
    {
        return array_map('intval', array_column($this->db->fetchAll('SELECT poll_id FROM poll_votes WHERE user_id = ?', [$userId]), 'poll_id'));
    }

    /** False if they already voted. */
    public function vote(int $pollId, int $userId, int $choice, string $now): bool
    {
        return $this->db->query(
            'INSERT IGNORE INTO poll_votes (poll_id, user_id, choice, created_at) VALUES (?, ?, ?, ?)',
            [$pollId, $userId, $choice, $now],
        )->rowCount() === 1;
    }

    /** @param list<string> $choices */
    public function create(string $question, array $choices, bool $hidden, string $now): int
    {
        return $this->db->insert('polls', [
            'question' => $question,
            'choices' => json_encode(array_values($choices), JSON_THROW_ON_ERROR),
            'active' => 1,
            'hidden' => $hidden ? 1 : 0,
            'created_at' => $now,
        ]);
    }

    public function close(int $id, ?int $winningChoice, string $now): bool
    {
        return $this->db->query(
            'UPDATE polls SET active = 0, closed_at = ?, winning_choice = ? WHERE id = ? AND active = 1',
            [$now, $winningChoice, $id],
        )->rowCount() === 1;
    }

    private function hydrate(array $poll): array
    {
        $poll['choices'] = json_decode($poll['choices'], true) ?: [];
        $counts = array_column($this->db->fetchAll('SELECT choice, COUNT(*) AS total FROM poll_votes WHERE poll_id = ? GROUP BY choice', [$poll['id']]), 'total', 'choice');
        $poll['votes'] = array_map(fn (int $i) => (int) ($counts[$i] ?? 0), array_keys($poll['choices']));
        $poll['total_votes'] = array_sum($poll['votes']);

        return $poll;
    }
}
