<?php
use App\Helper\DefaultText;
$account_id = __post('account_id', 3);

// Set contact based on account_id
if ($account_id == 4) {
    // Encars-MD (Korean cars)
    $contacts = ['37379603161'];
    $defaultPhone = '37379603161';
} else {
    // Sauto-stock-extern (default)
    $contacts = ['37379600326'];
    $defaultPhone = '37379600326';
}

foreach ($contacts as $contact): ?>
    <div class="form-check contact-container">
        <input
                type="checkbox"
                name="feature[<?= $feature_id ?>][]"
                id="contact_<?= $feature_id ?>_<?= md5($contact) ?>"
                value="<?= htmlspecialchars($contact) ?>"
                class="form-check-input contact"
                <?php if((!empty($car999features) && !empty($car999features[$feature_id]['value']) && in_array($contact, $car999features[$feature_id]['value'])) || ($contact == $defaultPhone)) : ?> checked <?php endif; ?>
        >
        <label class="form-check-label" for="contact_<?= $feature_id ?>_<?= md5($contact) ?>">
            <?= htmlspecialchars($contact) ?>
        </label>
    </div>
<?php endforeach; ?>

<script>
(function() {
    const defaultPhone = '<?= $defaultPhone ?>';
    let isProtected = false;
    
    function forceCheckbox() {
        const phoneCheckbox = document.querySelector(`input[value="${defaultPhone}"]`);
        if (phoneCheckbox) {
            phoneCheckbox.checked = true;
            
            if (!isProtected) {
                const originalSetter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'checked').set;
                Object.defineProperty(phoneCheckbox, 'checked', {
                    get: function() { 
                        return true;
                    },
                    set: function(value) {
                        if (value === false) {
                            
                        } else {
                            originalSetter.call(this, true);
                        }
                    }
                });
                
                isProtected = true;
            }
        }
    }
    
    forceCheckbox();
    setInterval(forceCheckbox, 10);
})();
</script>
