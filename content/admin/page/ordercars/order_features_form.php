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
                <option value="sauto_personal" <?php if (empty($car999['announcement_type']) || $car999['announcement_type'] == "sauto_personal") : ?> selected <?php endif; ?>>SAUTO Personal</option>
                <option value="auto_company" <?php if (!empty($car999['announcement_type']) && $car999['announcement_type'] == "auto_company") : ?> selected <?php endif; ?>><?= __('cars.auto_companies') ?></option>
                <option value="auto_company_min" <?php if (!empty($car999['announcement_type']) && $car999['announcement_type'] == "auto_company_min") : ?> selected <?php endif; ?>><?= __('cars.auto_companies_minimal_promotion') ?></option>
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

        <!-- SAUTO Personal Custom Scheduling -->
        <div id="sauto_personal_scheduling" class="form-group" style="display: none;">
            <label><?= __('cars.promotions') ?>:</label>
            <div style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-top: 10px; background: #f9f9f9;">
                <h4 style="margin-bottom: 20px; color: #333; text-align: center;">📅 <?= __('cars.personal_scheduling') ?></h4>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <!-- Calendar Section -->
                    <div style="background: white; border: 1px solid #e0e0e0; border-radius: 4px; padding: 10px;">
                        <h6 style="margin-bottom: 10px; color: #495057; text-align: center; font-size: 14px;">📅 <?= __('cars.select_date') ?></h6>
                        
                        <!-- Calendar Navigation -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <button type="button" id="prev_month" style="background: #6c757d; color: white; border: none; padding: 3px 8px; border-radius: 3px; cursor: pointer; font-size: 12px;">‹</button>
                            <span id="calendar_month_year" style="margin: 0; color: #495057; font-weight: 600; font-size: 13px;"></span>
                            <button type="button" id="next_month" style="background: #6c757d; color: white; border: none; padding: 3px 8px; border-radius: 3px; cursor: pointer; font-size: 12px;">›</button>
                        </div>
                        
                        <!-- Calendar Grid -->
                        <div id="calendar_grid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; text-align: center; font-size: 12px;">
                            <!-- Calendar will be generated by JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Scheduled Publications -->
                    <div style="background: white; border: 1px solid #e0e0e0; border-radius: 4px; padding: 10px;">
                        <h6 style="margin-bottom: 10px; color: #495057; text-align: center; font-size: 14px;">📋 <?= __('cars.scheduled_publications') ?></h6>
                        
                        <!-- Quick Presets Section -->
                        <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 3px; padding: 8px; margin-bottom: 10px;">
                            <h6 style="margin: 0 0 8px 0; color: #495057; font-size: 12px; font-weight: 600;">🚀 <?= __('cars.quick_presets') ?></h6>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 8px;">
                                <div>
                                    <label style="font-size: 11px; color: #6c757d; margin-bottom: 2px; display: block;"><?= __('cars.frequency') ?>:</label>
                                    <select id="preset_frequency" style="width: 100%; padding: 3px; font-size: 11px; border: 1px solid #ced4da; border-radius: 2px;">
                                        <option value="1"><?= __('cars.1x_month') ?></option>
                                        <option value="2"><?= __('cars.2x_month') ?></option>
                                        <option value="3"><?= __('cars.3x_month') ?></option>
                                        <option value="4"><?= __('cars.4x_month') ?></option>
                                        <option value="5"><?= __('cars.5x_month') ?></option>
                                    </select>
                                </div>
                                <div>
                                    <label style="font-size: 11px; color: #6c757d; margin-bottom: 2px; display: block;"><?= __('cars.duration') ?>:</label>
                                    <select id="preset_duration" style="width: 100%; padding: 3px; font-size: 11px; border: 1px solid #ced4da; border-radius: 2px;">
                                        <option value="1" selected><?= __('cars.1_month') ?></option>
                                        <option value="2"><?= __('cars.2_months') ?></option>
                                        <option value="3"><?= __('cars.3_months') ?></option>
                                        <option value="4"><?= __('cars.4_months') ?></option>
                                        <option value="5"><?= __('cars.5_months') ?></option>
                                    </select>
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 8px;">
                                <label style="font-size: 11px; color: #6c757d; margin-bottom: 2px; display: block;"><?= __('cars.time') ?> (Random):</label>
                                <?php
                                // Generate random 999.md posting time using configurable settings
                                require_once __DIR__ . '/../../../../App/Helper/RandomTimeHelper.php';
                                $random_999md_time = \App\Helper\RandomTimeHelper::generateRandom999mdTime();
                                ?>
                                <input type="time" id="preset_time" value="<?= $random_999md_time ?>" style="width: 100%; padding: 3px; font-size: 11px; border: 1px solid #ced4da; border-radius: 2px;">
                                <small style="font-size: 10px; color: #6c757d; display: block; margin-top: 2px;">🎲 Timp generat automat din setări</small>
                            </div>
                            
                            <button type="button" id="generate_presets" style="width: 100%; background: #28a745; color: white; border: none; padding: 6px; border-radius: 3px; cursor: pointer; font-size: 11px; font-weight: 600;">
                                🎯 <?= __('cars.generate_schedules') ?>
                            </button>
                        </div>
                        
                        <!-- Manual Schedules List -->
                        <div>
                            <h6 style="margin: 0 0 8px 0; color: #495057; font-size: 12px; font-weight: 600;">📅 Programări Manuale</h6>
                            <div id="schedules_list" style="max-height: 150px; overflow-y: auto;">
                                <div id="no_schedules_message" style="text-align: center; color: #6c757d; font-style: italic; padding: 15px; font-size: 12px;">
                                    📝 Nu există programări setate.<br>Fă click pe o dată din calendar.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Time Selection Modal (Hidden by default) -->
                <div id="time_modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); min-width: 300px;">
                        <h5 style="margin-bottom: 20px; text-align: center; color: #495057;">🕐 <?= __('cars.set_time') ?></h5>
                        <p id="selected_date_display" style="text-align: center; margin-bottom: 20px; font-weight: 500; color: #007bff;"></p>
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #495057;"><?= __('cars.time') ?>:</label>
                            <input type="time" id="modal_time" style="width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 4px; font-size: 16px;">
                        </div>
                        
                        <div style="display: flex; gap: 10px; justify-content: center;">
                            <button type="button" id="save_schedule" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 500;">
                                ✅ <?= __('cars.save') ?>
                            </button>
                            <button type="button" id="cancel_schedule" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 500;">
                                ❌ <?= __('cars.cancel') ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Hidden fields for schedule data -->
            <input type="hidden" id="sauto_schedules_data" name="sauto_schedules" value="">
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
                                    <option value="<?= htmlspecialchars($option['id']) ?>" <?php if($feature['id'] == 5 || (!empty($car999features[$feature['id']]) && $car999features[$feature['id']]['value'] == $option['id']) || (($option['id'] == '18594' && $option['title'] == 'Другое') || ($option['id'] == '29677' && $option['title'] == 'Еврозона') || ($option['id'] == '18668' && $option['title'] == 'С пробегом') || ($option['id'] == '29672' && $option['title'] == 'Под заказ') || ($option['id'] == '12900' && $option['title'] == 'Кишинёв мун.') || ($option['id'] == '23241' && $option['title'] == 'Автодилер') || ($option['id'] == '21979' && $option['title'] == 'Левый') || ($option['id'] == '19119' && $option['title'] == '5') || ($option['id'] == '19086' && $option['title'] == '5')) && empty($car999features[$feature['id']]['value'])) :?> selected <?php endif; ?>>
                                        <?= htmlspecialchars($option['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php elseif(!empty($feature['depends_on']) && !empty($car999)) : ?>
                                <?php $featureDepends = (new Api999Service())->getDependentOptions($car999['subcategory_id'], $feature['depends_on'], $car999features[$feature['depends_on']]['value']); ?>
                                <?php foreach ($featureDepends['Options'] as $option): ?>
                                    <option value="<?= htmlspecialchars($option['id']) ?>" <?php if((!empty($car999features[$feature['id']]) && $car999features[$feature['id']]['value'] == $option['id']) || (($option['id'] == '18594' && $option['title'] == 'Другое') || ($option['id'] == '29677' && $option['title'] == 'Еврозона') || ($option['id'] == '18668' && $option['title'] == 'С пробегом') || ($option['id'] == '29672' && $option['title'] == 'Под заказ') || ($option['id'] == '12900' && $option['title'] == 'Кишинёв мун.') || ($option['id'] == '23241' && $option['title'] == 'Автодилер') || ($option['id'] == '21979' && $option['title'] == 'Левый') || ($option['id'] == '19119' && $option['title'] == '5') || ($option['id'] == '19086' && $option['title'] == '5')) && empty($car999features[$feature['id']]['value'])) :?> selected <?php endif; ?>>
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
                            <?php include('order_feature_contact.php') ?>
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
                    checked
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
    
    setTimeout(function() {
        $('.scenario-option-radio:checked').trigger('change');
    }, 50);
</script>