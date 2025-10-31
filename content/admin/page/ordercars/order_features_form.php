<?php

use App\Services\Api999Service;
use App\Helper\DefaultText;

if(!empty($car999['features'])) {
    foreach ($car999['features'] as $item) {
        $id = $item['id'];
        unset($item['id']);
        $car999features[$id] = $item;
    }
}
if (!isset($new999)) $new999 = true;
?>
<div class="main_info">
    <fieldset class="row">
        <legend><?= __('cars.type_ad') ?></legend>
        <div class="form-group">
            <label for="announcement_type">
                <?= __('cars.type_ad') ?>
                <span class="text-danger">*</span>
            </label>
            <select id="announcement_type" name="announcement_type" <?php if (!$new999) : ?> disabled <?php endif; ?> class="form-control" required>
                <option value=""><?= __('cars.type_ad_hint') ?></option>
                <option value="auto_company" <?php if (!empty($car999['announcement_type']) && $car999['announcement_type'] == "auto_company") : ?> selected <?php endif; ?>><?= __('cars.auto_companies') ?></option>
                <option value="auto_company_min" <?php if (!empty($car999['announcement_type']) && $car999['announcement_type'] == "auto_company_min") : ?> selected <?php elseif (!isset($car999['announcement_type']) || empty($car999['announcement_type'])) : ?> selected <?php endif; ?>><?= __('cars.auto_companies_minimal_promotion') ?></option>
                <option value="auto_realization" <?php if (!empty($car999['announcement_type']) && $car999['announcement_type'] == "auto_realization") : ?> selected <?php endif; ?>><?= __('cars.auto_for_sale') ?></option>
                <option value="auto_realization_min" <?php if (!empty($car999['announcement_type']) && $car999['announcement_type'] == "auto_realization_min") : ?> selected <?php endif; ?>><?= __('cars.auto_for_sale_minimal_promotion') ?></option>
            </select>
        </div>

        <div id="text_options_wrapper" class="form-group" style="display: none;">
            <label>
                <?= __('cars.select_text_option') ?>
                <span class="text-danger">*</span>
            </label>
            <div id="text_options" class="d-flex"></div>
        </div>
    </fieldset>

    <fieldset class="row">
        <legend><?= __('cars.scenarios') ?></legend>
        <div class="form-group">
            <label for="scenario"><?= __('cars.scenarios_title') ?>:</label>
            <div id="text_options" class="d-flex" style="margin-top: 10px;">
                <div style="display: inline-block; text-align: center;">
                    <label>
                        <input type="radio" name="scenario" value="simple" <?php if (!empty($car999['scenario']) && $car999['scenario'] == "simple") : ?> checked disabled <?php endif; ?> class="scenario-option-radio">
                        <span><?= __('cars.minimal') ?></span>
                    </label>
                </div>
                <div style="display: inline-block; text-align: center;">
                    <label>
                        <input type="radio" name="scenario" value="medium" <?php if (!empty($car999['scenario']) && $car999['scenario'] == "medium") : ?> checked disabled <?php endif; ?> class="scenario-option-radio">
                        <span><?= __('cars.middle') ?></span>
                    </label>
                </div>
                <div style="display: inline-block; text-align: center;">
                    <label>
                        <input type="radio" name="scenario" value="maximal" <?php if (!empty($car999['scenario']) && $car999['scenario'] == "maximal") : ?> checked disabled <?php elseif (!isset($car999['scenario']) || empty($car999['scenario'])) : ?> checked <?php endif; ?> class="scenario-option-radio">
                        <span><?= __('cars.maximal') ?></span>
                    </label>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label for="scenario"><?= __('cars.promotions') ?>:</label>
            <span class="text-danger">*</span>
            <div id="text_options" class="d-flex" style="margin-top: 10px;">
                <div style="<?php if (!$new999 && (!empty($car) && $car['promotions'] !== 'basic')) : ?>display: none;<?php endif; ?> text-align: center; margin-right: 5px;">
                    <label>
                        <input type="radio" checked name="promotions" value="basic" class="promotions-option-radio"
                            <?php if (!$new999 && !empty($car) && $car['promotions'] === 'basic') : ?> checked disabled <?php endif; ?>>
                        <span>BASIC</span>
                        <p class="promotion-description" style="font-size: 0.8em; color: #666; margin-top: 5px; padding: 0 20px;">
                            Бесплатное объявление на 30 дней.<br>
                            8 публикаций в неделю (с бюджетом в 32 леев)<br>
                            32 публикаций в месяц (с бюджетом в 128 леев)
                        </p>
                    </label>
                    <div id="schedule-basic" class="schedule-detail" style="<?php if (!$new999 && $car['promotions'] !== 'basic') : ?>display: none;<?php endif; ?>">
                        <?php $scheduleType = 'basic'; include('schedule_detail.php'); ?>
                    </div>
                </div>
                <div style="<?php if (!$new999 && (!empty($car) && $car['promotions'] !== 'lite')) : ?>display: none;<?php endif; ?> text-align: center; margin-right: 5px;">
                    <label>
                        <input type="radio" name="promotions" value="lite" class="promotions-option-radio"
                            <?php if (!$new999 && !empty($car) && $car['promotions'] === 'lite') : ?> checked disabled <?php endif; ?>>
                        <span>LITE</span>
                        <p class="promotion-description" style="font-size: 0.8em; color: #666; margin-top: 5px; padding: 0 20px;">
                            Платное объявление на 7 дней с переопубликованием.<br>
                            8 публикаций в неделю (с бюджетом в 32 леев)<br>
                            32 публикаций в месяц (с бюджетом в 128 леев)
                        </p>
                    </label>
                    <div id="schedule-lite" class="schedule-detail" style="<?php if (!$new999 && $car['promotions'] !== 'plus') : ?>display: none;<?php endif; ?>">
                        <?php $scheduleType = 'lite'; include('schedule_detail.php'); ?>
                    </div>
                </div>
                <div style="<?php if (!$new999 && (!empty($car) && $car['promotions'] !== 'plus')) : ?>display: none;<?php endif; ?> text-align: center; margin-right: 5px;">
                    <label>
                        <input type="radio" name="promotions" value="plus" class="promotions-option-radio"
                            <?php if (!$new999 && !empty($car) && $car['promotions'] === 'plus') : ?> checked disabled <?php endif; ?>>
                        <span>PLUS</span>
                        <p class="promotion-description" style="font-size: 0.8em; color: #666; margin-top: 5px; padding: 0 20px;">
                            Платное объявление на 7 дней с большим количеством переопубликаций.<br>
                            16 публикации в неделю (с бюджетом в 64 лея)<br>
                            64 публикаций в месяц (с бюджетом в 256 лея)
                        </p>
                    </label>
                    <div id="schedule-plus" class="schedule-detail" style="<?php if (!$new999 && $car['promotions'] !== 'plus') : ?>display: none;<?php endif; ?>">
                        <?php $scheduleType = 'plus'; include('schedule_detail.php'); ?>
                    </div>
                </div>
                <div style="<?php if (!$new999 && (!empty($car) && $car['promotions'] !== 'turbo')) : ?>display: none;<?php endif; ?> text-align: center; margin-right: 5px;">
                    <label>
                        <input type="radio" name="promotions" value="turbo" class="promotions-option-radio"
                            <?php if (!$new999 && !empty($car) && $car['promotions'] === 'turbo') : ?> checked disabled <?php endif; ?>>
                        <span>TURBO</span>
                        <p class="promotion-description" style="font-size: 0.8em; color: #666; margin-top: 5px; padding: 0 20px;">
                            Платное объявление на 7 дней с максимальным количеством переопубликаций.<br>
                            54 публикаций в неделю (с бюджетом в 216 леев)<br>
                            216 публикаций в месяц (с бюджетом в 864 леев)
                        </p>
                    </label>
                    <div id="schedule-turbo" class="schedule-detail" style="<?php if (!$new999 && $car['promotions'] !== 'turbo') : ?>display: none;<?php endif; ?>">
                        <?php $scheduleType = 'turbo'; include('schedule_detail.php'); ?>
                    </div>
                </div>
                <div style="display: none; text-align: center;">
                    <label>
                        <input type="radio" name="promotions" value="test" class="promotions-option-radio">
                        <span>TEST</span>
                        <p class="promotion-description" style="font-size: 0.8em; color: #666; margin-top: 5px; padding: 0 20px;">
                            Тестовое.<br>
                        </p>
                    </label>
                </div>
            </div>
        </div>
    </fieldset>

    <?php foreach ($types['features_groups'] as $group): ?>
        <fieldset class="row" class="features">
            <?php if (empty($group['title'])) : ?>
                <legend><?= __('cars.other') ?></legend>
            <?php else: ?>
                <legend><?= htmlspecialchars($group['title']) ?></legend>
            <?php endif; ?>

            <?php foreach ($group['features'] as $feature): ?>
                <?php if (in_array($feature['id'], ['14'])) continue; ?>
                <?php if ($feature['id'] == 2095) $feature['required'] = false; ?>
                <div class="form-group col-md-3 <?php if ($feature['type'] === 'check_box') : ?> form-group-checkbox <?php endif; ?> <?php if ($feature['type'] === 'contacts') : ?> form-group-contacts <?php endif; ?>">
                    <label class="form-label" for="feature_<?= htmlspecialchars($feature['id']) ?>">
                        <?= htmlspecialchars($feature['title']) ?>
                        <?php if ($feature['required']): ?>
                            <span class="text-danger">*</span>
                        <?php endif; ?>
                    </label>

                    <?php if ($feature['type'] === 'textarea_text'): ?>
                        <textarea
                            name="feature[<?= htmlspecialchars($feature['id']) ?>]"
                            id="feature_<?= htmlspecialchars($feature['id']) ?>"
                            class="form-control <?= $feature['required'] ? 'required' : '' ?>"
                            <?= $feature['required'] ? 'required' : '' ?>  style="height: 170px;"
                            start_text="<?= (new DefaultText)->getKeywords($feature['id']) ?>"
                        ><?= $car999features[$feature['id']]['value'] ?? (new DefaultText)->getKeywords($feature['id']) ?></textarea>
                    <?php elseif ($feature['type'] === 'textbox_text'): ?>
                        <input
                                type="text"
                                class="form-control <?= $feature['required'] ? 'required' : '' ?>"
                                name="feature[<?= htmlspecialchars($feature['id']) ?>]"
                                id="feature_<?= htmlspecialchars($feature['id']) ?>"
                                value="<?= $car999features[$feature['id']]['value'] ?? '' ?>"
                            <?= $feature['required'] ? 'required' : '' ?>
                        >
                    <?php elseif ($feature['type'] === 'drop_down_options'): ?>
                        <select
                                class="form-control <?= $feature['required'] ? 'required' : '' ?> feature-select" <?= $feature['required'] ? 'required' : '' ?>
                                data-depends-on="<?= htmlspecialchars($feature['depends_on']) ?>"
                                data-feature-id="<?= htmlspecialchars($feature['id']) ?>"
                                name="feature[<?= htmlspecialchars($feature['id']) ?>]"
                                id="feature_<?= htmlspecialchars($feature['id']) ?>"
                                def_text="<?= __('cars.select') ?>..."
                                <?php if($feature['id'] == 5) :?> disabled <?php endif; ?>
                        >
                            <option value=""><?= __('cars.select') ?> ...</option>
                            <?php if (!empty($feature['options'])) : ?>
                                <?php foreach ($feature['options'] as $option): ?>
                                    <option value="<?= htmlspecialchars($option['id']) ?>" <?php if($feature['id'] == 5 || (!empty($car999features[$feature['id']]) && $car999features[$feature['id']]['value'] == $option['id'])) :?> selected <?php endif; ?>>
                                        <?= htmlspecialchars($option['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php elseif(!empty($feature['depends_on']) && !empty($car999)) : ?>
                                <?php $featureDepends = (new Api999Service())->getDependentOptions($car999['subcategory_id'], $feature['depends_on'], $car999features[$feature['depends_on']]['value']); ?>
                                <?php foreach ($featureDepends['Options'] as $option): ?>
                                    <option value="<?= htmlspecialchars($option['id']) ?>" <?php if(!empty($car999features[$feature['id']]) && $car999features[$feature['id']]['value'] == $option['id']) :?> selected <?php endif; ?>>
                                        <?= htmlspecialchars($option['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    <?php elseif ($feature['type'] === 'textbox_numeric'): ?>
                        <input
                                type="number"
                                class="form-control <?= $feature['required'] ? 'required' : '' ?>"
                                name="feature[<?= htmlspecialchars($feature['id']) ?>]"
                                id="feature_<?= htmlspecialchars($feature['id']) ?>"
                                value="<?= $car999features[$feature['id']]['value'] ?? '' ?>"
                                <?= $feature['required'] ? 'required' : '' ?>
                        >
                    <?php elseif ($feature['type'] === 'textbox_numeric_measurement'): ?>
                        <div class="input-group" style="display: flex;">
                            <input
                                    type="number"
                                    class="form-control <?= $feature['required'] ? 'required' : '' ?> <?php if (!empty($feature['units'])): ?> col-md-80 <?php endif; ?>"
                                    name="feature[<?= htmlspecialchars($feature['id']) ?>]"
                                    id="feature_<?= htmlspecialchars($feature['id']) ?>"
                                    value="<?= $car999features[$feature['id']]['value'] ?? '' ?>"
                                    <?= $feature['required'] ? 'required' : '' ?>
                            >
                            <?php if (!empty($feature['units'])): ?>
                                <select
                                        name="feature_units[<?= htmlspecialchars($feature['id']) ?>]"
                                        id="feature_units_<?= htmlspecialchars($feature['id']) ?>"
                                        class="form-select col-md-20 form-control">
                                    <?php foreach ($feature['units'] as $unit): ?>
                                        <option value="<?= htmlspecialchars($unit) ?>" <?php if (!empty($car999features[$feature['id']]) && $car999features[$feature['id']]['unit'] == $unit) : ?> selected <?php endif; ?>>
                                            <?= htmlspecialchars(strtoupper($unit)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($feature['type'] === 'check_box'): ?>
                        <input
                                type="checkbox"
                                name="feature[<?= htmlspecialchars($feature['id']) ?>]"
                                id="feature_<?= htmlspecialchars($feature['id']) ?>"
                                <?php if (!empty($car999features[$feature['id']]['value'])) : ?> checked <?php endif; ?>
                                <?= $feature['required'] ? 'required' : '' ?>
                        >
                    <?php elseif ($feature['type'] === 'upload_videos'): ?>
                        <textarea
                                name="feature[<?= $feature['id'] ?>]"
                                id="feature_<?= $feature['id'] ?>"
                                class="form-control video-urls"
                                placeholder="<?= __('cars.video_placeholder') ?>"
                                <?= $feature['required'] ? 'required' : '' ?>
                        ><?= (!empty($car999features[$feature['id']]['value'])) ? implode(', ', $car999features[$feature['id']]['value']) : 'https://www.youtube.com/watch?v=_sbHQnaZ9kk, https://www.youtube.com/watch?v=O9CdDeJ9vXs, https://www.youtube.com/watch?v=LDzcvhjTf0w' ?></textarea>
                        <small class="form-text text-muted">
                            <?= __('cars.video_urls_hint') ?>
                        </small>
                    <?php elseif ($feature['type'] === 'contacts'): ?>
                        <div class="feature-contacts" data-feature-id="<?=$feature['id']?>">
                            <?php $feature_id = $feature['id']; ?>
                            <?php include('feature_contact.php') ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </fieldset>
    <?php endforeach; ?>

    <div style="margin: 20px 0;">
        <div class="form-group">
            <div class="form-check">
                <input
                    type="checkbox"
                    name="confirm_rules"
                    id="confirm_rules"
                    class="form-check-input"
                    required
                    <?php if(!empty($car['999_id'])) : ?> checked <?php endif; ?>
                >
                <label class="form-check-label" for="confirm_rules">
                    <?= __('cars.confirm_rules_hint') ?><span class="text-danger">*</span>
                </label>
            </div>
        </div>
    </div>
<!--    --><?php //if (!empty($car['999_id'])): ?>
        <input type="checkbox" class="car-checkbox-n_a_new" data-car-id="<?= isset($car['id']) ? $car['id'] : '' ?>"
            <?= (isset($car['n_a_new']) && $car['n_a_new'] == 1) ? 'checked' : '' ?>>
        <span class="status-text">
    <?= (!isset($car['n_a_new']) || $car['n_a_new'] === null ? 'Не задано, (по дефолту в наличии)' : ($car['n_a_new'] == 0 ? 'Есть в наличии' : 'Нет в наличии')) ?>
        </span>
<!--    --><?php //endif; ?>
    <button class="confirm_999" data-processing="<?= __('cars.processing') ?>..."
            data-origin="<?= mb_strtoupper(__('cars.confirm_publish_999'), "UTF-8") ?>"
            data-fn="public_999">
        <?= (isset($car['999_id']) && !empty($car['999_id'])) ? mb_strtoupper(__('cars.edit_publish_999'), "UTF-8") : mb_strtoupper(__('cars.confirm_publish_999'), "UTF-8") ?>
</div>

<script>
    $(document).ready(function() {
        initializeSchedules();
        
        // Load order-specific texts and trigger announcement_type change event
        let orderTexts = {};
        $.getJSON("/api/order_texts.json", function (data) {
            orderTexts = data;
            // Trigger change event after texts are loaded
            $('#announcement_type').trigger('change');
        });
        
        // Trigger scenario change event to show checkboxes for default maximal
        setTimeout(function() {
            $('.scenario-option-radio:checked').trigger('change');
            // Trigger account change to load contacts for default account
            $('.account_999_id').trigger('change');
        }, 500);
        
        // Override the announcement_type change handler for order cars
        $(document).off('change', '#announcement_type').on('change', '#announcement_type', function() {
            const type = $(this).val();
            const textOptionsWrapper = $("#text_options_wrapper");
            const textOptions = $("#text_options");
            const textArea = $("#feature_13");

            textOptionsWrapper.hide();
            textOptions.empty();
            textArea.val("");

            if (type === "auto_company" || type === "auto_company_min") {
                if (orderTexts['auto_company']) {
                    orderTexts['auto_company'].forEach((item, index) => {
                        const radioButton = `
                            <div class="text-option-wrapper" style="margin-right: 20px; margin-bottom: 10px;">
                                <label style="display: inline-block; text-align: center;">
                                    <input type="radio" name="text_option" value="${index}" class="text-option-radio">
                                    <span>${item.title}</span>
                                </label>
                                <div class="text-preview" style="border: 1px solid #ccc; padding: 10px; margin-top: 5px; border-radius: 5px; background: #f9f9f9;">
                                    ${item.text}
                                </div>
                            </div>
                        `;
                        textOptions.append(radioButton);
                    });
                    textOptionsWrapper.show();
                }
            } else if (type === "auto_realization" || type === "auto_realization_min") {
                if (orderTexts['auto_realization'] && orderTexts['auto_realization'][0]) {
                    textArea.val(orderTexts['auto_realization'][0].text);
                }
            }
        });
        
        // Handle text option selection for order cars
        $(document).off('change', '.text-option-radio').on('change', '.text-option-radio', function() {
            const index = $(this).val();
            if (orderTexts['auto_company'] && orderTexts['auto_company'][index]) {
                const selectedText = orderTexts['auto_company'][index].text;
                $("#feature_13").val(selectedText);
            }
        });

        $(document).on('change', '.promotions-option-radio', function() {
            const selectedValue = $(this).val();
            $('.schedule-detail').hide();
            $(`#schedule-${selectedValue}`).show();
        });
    });

    function initializeSchedules() {
        const checkedRadio = $('.promotions-option-radio:checked');
        if(checkedRadio.length) {
            const initialValue = checkedRadio.val();
            <?php if ($new999) : ?>
                $('.schedule-detail').hide();
            <?php endif; ?>
            $(`#schedule-${initialValue}`).show();
        }
    }
</script>