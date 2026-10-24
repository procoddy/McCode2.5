<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Database;

/**
 * Starter crimes in the style of the McCodes defaults. Edit or replace freely.
 * Formulas may use LEVEL, CRIMEXP, EXP, WILL and IQ.
 */
final class CrimeSeeder
{
    public function run(Database $db): void
    {
        if ((int) $db->fetchColumn('SELECT COUNT(*) FROM crime_groups') > 0) {
            return;
        }

        $petty = $db->insert('crime_groups', ['name' => 'Petty crimes', 'sort_order' => 1]);
        $theft = $db->insert('crime_groups', ['name' => 'Theft', 'sort_order' => 2]);

        $crimes = [
            [$petty, 'Search the streets for change', 1, '((WILL*0.8)/2.5)+(LEVEL/4)', 50, 0, 1, 2,
                'You walk around town looking at the ground.', 'You found {money} in loose change!',
                'You found nothing but chewing gum.', 'A cop thinks you are acting suspiciously and arrests you.',
                'Acting suspiciously'],
            [$petty, 'Sell illegal CDs', 2, '((WILL*0.7)/2.5)+(LEVEL/4)', 150, 0, 2, 5,
                'You set up a stall in the market.', 'You sold a box of CDs for {money}.',
                'Nobody was interested.', 'An undercover officer bought a CD and then your stall.',
                'Selling pirated goods'],
            [$theft, 'Shoplift', 3, '((WILL*0.6)/2.5)+(LEVEL/3)+(IQ/50)', 400, 1, 4, 10,
                'You walk into a corner shop with big pockets.', 'You walk out with goods worth {money}.',
                'The owner watched you the whole time, so you left empty-handed.', 'The alarm went off at the door.',
                'Shoplifting'],
            [$theft, 'Pickpocket a tourist', 5, '((WILL*0.5)/2.5)+(LEVEL/3)+(CRIMEXP/20)', 1000, 2, 6, 15,
                'You spot a tourist with a fat wallet.', 'You lift {money} without them noticing.',
                'They clutch their bag and walk away.', 'The tourist was an off-duty police officer.',
                'Pickpocketing'],
        ];

        foreach ($crimes as [$group, $name, $brave, $formula, $money, $crystals, $exp, $jail, $initial, $success, $failure, $jailText, $reason]) {
            $db->insert('crimes', [
                'group_id' => $group,
                'name' => $name,
                'brave_cost' => $brave,
                'success_formula' => $formula,
                'success_money' => $money,
                'success_crystals' => $crystals,
                'crime_exp' => $exp,
                'jail_minutes' => $jail,
                'jail_reason' => $reason,
                'initial_text' => $initial,
                'success_text' => $success,
                'failure_text' => $failure,
                'jail_text' => $jailText,
            ]);
        }
    }
}
