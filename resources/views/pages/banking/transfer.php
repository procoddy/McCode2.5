<?php
/**
 * @var App\Modules\Banking\Models\BankAccountType $account
 * @var App\Modules\Players\Models\Player $receiver
 * @var App\Modules\Players\Models\Player $sender
 */
$this->set('title', $account->name . ' transfer');
$balance = $sender->balance($account->column);
?>
<h1 class="mb-6 text-2xl font-semibold"><?= e($account->name) ?> transfer</h1>

<?php if ($balance === null || $receiver->balance($account->column) === null): ?>
    <?= $this->component('alert', ['message' => "You and {$receiver->username} both need a {$account->name} account.", 'type' => 'warning']) ?>
<?php else: ?>
    <form method="post" action="/players/<?= $receiver->id ?>/send/<?= e($account->key) ?>" class="card max-w-md space-y-4"
          hx-confirm="Send this money to <?= e($receiver->username) ?>? Transfers can't be undone.">
        <?= csrf_field() ?>
        <p>Sending to <strong><?= e($receiver->username) ?></strong> (ID <?= $receiver->id ?>).
            You have <strong><?= e(App\Support\Money::of($balance)->format()) ?></strong> available.</p>
        <?= $this->component('form-field', ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'value' => old('amount')]) ?>
        <div class="flex items-center gap-4">
            <?= $this->component('button', ['label' => 'Send', 'type' => 'submit']) ?>
            <a href="/players/<?= $receiver->id ?>" class="link">Cancel</a>
        </div>
    </form>
<?php endif; ?>
