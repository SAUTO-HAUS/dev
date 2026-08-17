<?php
use App\Services\Api999Service;
use App\Helper\DefaultText;

$categories = (new Api999Service())->getCategories();
$subcategories = (new Api999Service())->getSubcategories(DefaultText::CATEGORY_AUTO);

// Group + import country: from $car (edit) or $parsing_prefill (parsing_id), so
// the 999 defaults are right even before the car exists in the catalog.
$grForm        = (string)($car['gr'] ?? $parsing_prefill['gr'] ?? '');
$importIdForm  = (int)($car['import_country_id'] ?? $parsing_prefill['import_country_id'] ?? 0);

// Subcategory: commercial → 660 (Микроавтобусы и фургоны), else 659 (Легковые).
$defaultSubcategory = ($grForm === 'com') ? '660' : '659';
$defaultOfferType = ($grForm === 'com') ? '776' : '23844';

if (!empty($car['999'])) {
    $car999 = json_decode($car['999'], true);
    $offer_types = (new Api999Service())->getSubcategoryOfferTypes($car999['category_id'], $car999['subcategory_id']);
} else {
    $offer_types = (new Api999Service())->getSubcategoryOfferTypes(DefaultText::CATEGORY_AUTO, $defaultSubcategory);
}

if ($new999) {
    // Encar (Korea, country 41) → account 4; commercial → 2; USA → 5; rest → 3.
    // The USA id is looked up by code, unlike the legacy Korean 41 constant.
    $is_korea = ($importIdForm == 41);
    $is_com   = ($grForm === 'com');
    $is_usa   = false;
    if (!$is_korea && $importIdForm > 0) {
        try {
            $naIds = array_map('intval', \App\Core\Container::get('db')
                ->query("SELECT id FROM countries WHERE code IN ('CA','US')")->fetchAll(\PDO::FETCH_COLUMN) ?: []);
            $is_usa = in_array($importIdForm, $naIds, true);
        } catch (\Throwable $e) { /* stays false */ }
    }
    // Canada/USA wins over the commercial rule, exactly like the publish path does
    // (order_999_catalog.php and sauto_personal_cron.php both test USA first): every
    // AutoTrader car belongs on SautoSUA, van or not.
    $default999AccountId = $is_korea ? 4 : ($is_usa ? 5 : ($is_com ? 2 : 3));
} else {
    $default999AccountId = null;
}

?>

<form class="main_info" id="main_form_999"
    data-category-id="<?= htmlspecialchars($car999['category_id'] ?? DefaultText::CATEGORY_AUTO) ?>"
    data-subcategory-id="<?= htmlspecialchars($car999['subcategory_id'] ?? $defaultSubcategory) ?>"
    data-offer-type="<?= htmlspecialchars($car999['offer_type'] ?? $defaultOfferType) ?>"
    data-api-id="<?= htmlspecialchars($car['999_api_id'] ?? '') ?>"
    data-announcement-type="<?= htmlspecialchars($car999['announcement_type'] ?? 'sauto_personal') ?>">
    <div class="row">
        <div class="form-group col-md-3">
            <label class="form-label">
                <?= __('cars.category') ?>
                <span class="text-danger">*</span>
            </label>
            <select class="category form-control" <?php if (!$new999) : ?> disabled <?php endif; ?> name="car[category]" title="<?= __('cars.categories') ?>...">
                <?php foreach ($categories['categories'] as $category) : ?>
                    <?php if ($category['id'] == DefaultText::CATEGORY_AUTO) : ?>
                        <option value="<?= $category['id'] ?>" selected><?= $category['title'] ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group col-md-3">
            <label class="form-label">
                <?= __('cars.subcategory') ?>
                <span class="text-danger">*</span>
            </label>
            <select class="subcategory form-control" <?php if (!$new999) : ?> disabled <?php endif; ?> name="car[subcategory]" def_text="<?= __('cars.select_subcategory') ?>...">
                <option value=""><?= __('cars.select_subcategory') ?>...</option>
                <?php foreach ($subcategories['subcategories'] as $subcategory) : ?>
                    <option value="<?= $subcategory['id'] ?>" <?php if((!empty($car999['subcategory_id']) && $car999['subcategory_id'] == $subcategory['id']) || ($subcategory['id'] == $defaultSubcategory && empty($car999['subcategory_id']))) : ?> selected <?php endif; ?>><?= $subcategory['title'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group col-md-3">
            <label class="form-label">
                <?= __('cars.subcategory_offer_types') ?>
                <span class="text-danger">*</span>
            </label>

            <select class="subcategory_offer_types form-control" <?php if (!$new999) : ?> disabled <?php endif; ?> name="car[subcategory_offer_types]" def_text="<?= __('cars.select_subcategory_offer_types') ?>...">
                <option value=""><?= __('cars.select_subcategory_offer_types') ?>...</option>
                <?php if (!empty($offer_types['offer_types'])) : ?>
                    <?php foreach ($offer_types['offer_types'] as $offerType) : ?>
                        <option value="<?= $offerType['id'] ?>" <?php if((!empty($car999['offer_type']) && $car999['offer_type'] == $offerType['id']) || ((string)$offerType['id'] === $defaultOfferType && empty($car999['offer_type']))) : ?> selected <?php endif; ?>><?= $offerType['title'] ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group col-md-3">
            <label class="form-label">
                <?= __('cars.999_account') ?>
                <span class="text-danger">*</span>
            </label>

            <select class="account_999_id form-control" name="999_api_id" def_text="<?= __('cars.select_subcategory_offer_types') ?>...">
                <?php if (!empty(Api999Service::API_KEY)) : ?>
                    <?php foreach (Api999Service::API_KEY as $id => $api_key) : ?>
                        <option value="<?= $id ?>" <?php if(($new999 && $id == $default999AccountId) || (!$new999 && !empty($car['999_api_id']) && $car['999_api_id'] == $id)) : ?> selected <?php endif; ?>><?= $api_key['name'] ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
    </div>
    <div class="features">
        <?php if (!empty($car999)) :
            $types = (new Api999Service())->getSubcategoryFeatures((int)$car999['category_id'], (int)$car999['subcategory_id'], (int)$car999['offer_type']); ?>
            <?php include('order_features_form.php') ?>
        <?php else :
            $types = (new Api999Service())->getSubcategoryFeatures(DefaultText::CATEGORY_AUTO, $defaultSubcategory, $defaultOfferType); ?>
            <?php include('order_features_form.php') ?>
        <?php endif; ?>
    </div>
</form>
<!-- test deployment -->