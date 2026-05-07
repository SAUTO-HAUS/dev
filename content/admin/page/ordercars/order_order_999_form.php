<?php
use App\Services\Api999Service;
use App\Helper\DefaultText;

$categories = (new Api999Service())->getCategories();
$subcategories = (new Api999Service())->getSubcategories(DefaultText::CATEGORY_AUTO);
$defaultSubcategory = (!empty($car['gr']) && $car['gr'] == 'com') ? '660' : '659';

if (!empty($car['999'])) {
    $car999 = json_decode($car['999'], true);
    $offer_types = (new Api999Service())->getSubcategoryOfferTypes($car999['category_id'], $car999['subcategory_id']);
} else {
    $offer_types = (new Api999Service())->getSubcategoryOfferTypes(DefaultText::CATEGORY_AUTO, $defaultSubcategory);
}

if ($new999) {
    $is_korea = !empty($car['import_country_id']) && $car['import_country_id'] == 41;
    $is_com = !empty($car['gr']) && $car['gr'] == 'com';
    $default999AccountId = $is_korea ? 4 : ($is_com ? 2 : 3);
} else {
    $default999AccountId = null;
}

?>

<form class="main_info" id="main_form_999">
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
                        <option value="<?= $offerType['id'] ?>" <?php if((!empty($car999['offer_type']) && $car999['offer_type'] == $offerType['id']) || ($offerType['id'] == '776' && empty($car999['offer_type']))) : ?> selected <?php endif; ?>><?= $offerType['title'] ?></option>
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
            $types = (new Api999Service())->getSubcategoryFeatures(DefaultText::CATEGORY_AUTO, $defaultSubcategory, '776'); ?>
            <?php include('order_features_form.php') ?>
        <?php endif; ?>
    </div>
</form>
<!-- test deployment -->