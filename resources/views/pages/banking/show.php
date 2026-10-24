<?php
/** @var App\Modules\Banking\Models\BankAccountType $account */
$this->set('title', $account->name);
?>
<h1 class="mb-6 text-2xl font-semibold"><?= e($account->name) ?></h1>

<?= $this->partial('banking.panel', ['account' => $account, 'player' => $player]) ?>
