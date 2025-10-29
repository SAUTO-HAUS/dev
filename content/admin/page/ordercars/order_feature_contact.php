<?php
use App\Helper\DefaultText;
// For new order cars, use account 3 as default, otherwise use existing account
$account_id = __post('account_id') ?: ($car['999_api_id'] ?? 3);
$contacts = (new DefaultText)->getContacts($account_id);
foreach ($contacts as $contact): ?>
    <div class="form-check contact-container">
        <input
                type="checkbox"
                name="feature[<?= $feature_id ?>][]"
                id="contact_<?= $feature_id ?>_<?= md5($contact) ?>"
                value="<?= htmlspecialchars($contact) ?>"
                class="form-check-input contact"
                <?php if(!empty($car999features) && !empty($car999features[$feature_id]['value']) && in_array($contact, $car999features[$feature_id]['value'])) : ?> checked <?php endif; ?>
        >
        <label class="form-check-label" for="contact_<?= $feature_id ?>_<?= md5($contact) ?>">
            <?= htmlspecialchars($contact) ?>
        </label>
    </div>
<?php endforeach; ?>
