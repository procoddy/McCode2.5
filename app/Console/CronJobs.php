<?php

declare(strict_types=1);

namespace App\Console;

use App\Contracts\ClockInterface;
use App\Core\Database;
use App\Modules\Gangs\Services\GangService;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;
use Throwable;

/**
 * Scheduled jobs (legacy cron_fivemins.php, cron_hour.php, cron_day.php),
 * shared by `php bin/cron.php <job>` and the staff panel's "Run cron".
 * Each run is recorded in cron_runs.
 */
final class CronJobs
{
    public const JOBS = [
        'five-minutes' => ['Five-minute tick', 'Regenerate energy, brave, HP and will.'],
        'hourly' => ['Hourly tick', 'Carry out gang organised crimes whose planning time is up.'],
        'daily' => ['Daily tick', 'Pay bank and cyber bank interest.'],
    ];

    public function __construct(
        private readonly PlayerRepositoryInterface $players,
        private readonly GangService $gangs,
        private readonly Database $db,
        private readonly ClockInterface $clock,
    ) {
    }

    /** @return list<string> lines describing what happened */
    public function run(string $job): array
    {
        if (!isset(self::JOBS[$job])) {
            throw new \InvalidArgumentException("Unknown cron job [{$job}].");
        }

        $runId = $this->db->insert('cron_runs', ['name' => $job, 'started_at' => $this->now()]);

        try {
            [$lines, $count] = match ($job) {
                'five-minutes' => $this->regenerate(),
                'hourly' => $this->organisedCrimes(),
                'daily' => $this->interest(),
            };
        } catch (Throwable $e) {
            $this->db->update('cron_runs', ['finished_at' => $this->now(), 'error' => $e->getMessage()], ['id' => $runId]);
            throw $e;
        }

        $this->db->update('cron_runs', ['finished_at' => $this->now(), 'updated_count' => $count], ['id' => $runId]);

        return $lines;
    }

    private function regenerate(): array
    {
        $regen = config('game.regen');
        $count = $this->players->regenerateAll($regen['energy_percent'], $regen['brave_percent'], $regen['hp_percent'], $regen['will']);

        return [["Regenerated {$count} players."], $count];
    }

    private function organisedCrimes(): array
    {
        $count = $this->gangs->runReadyCrimes();

        return [["Carried out {$count} organised crimes."], $count];
    }

    private function interest(): array
    {
        $lines = [];
        $total = 0;
        foreach (config('game.banks') as $bank) {
            $count = $this->players->applyInterest($bank['column'], $bank['daily_interest_percent']);
            $total += $count;
            $lines[] = "{$bank['name']}: interest paid to {$count} accounts.";
        }

        return [$lines, $total];
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
