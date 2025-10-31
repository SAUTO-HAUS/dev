<?php
use App\Helper\DefaultText;
// For order cars, use only the default contact for account 3 (Sauto-stock-extern)
$account_id = 3; // Forțează account_id 3 pentru mașini la comandă
$contacts = ['37379600326']; // Doar contactul default pentru mașini la comandă

foreach ($contacts as $contact): ?>
    <div class="form-check contact-container">
        <input
                type="checkbox"
                name="feature[<?= $feature_id ?>][]"
                id="contact_<?= $feature_id ?>_<?= md5($contact) ?>"
                value="<?= htmlspecialchars($contact) ?>"
                class="form-check-input contact"
                <?php if((!empty($car999features) && !empty($car999features[$feature_id]['value']) && in_array($contact, $car999features[$feature_id]['value'])) || ($contact == '37379600326')) : ?> checked <?php endif; ?>
        >
        <label class="form-check-label" for="contact_<?= $feature_id ?>_<?= md5($contact) ?>">
            <?= htmlspecialchars($contact) ?>
        </label>
    </div>
<?php endforeach; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const defaultPhone = '37379600326';
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
});
</script>
