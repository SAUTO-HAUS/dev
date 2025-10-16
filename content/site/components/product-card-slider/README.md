# Product Card Slider Component

Această componentă implementează funcționalitatea de slider pentru imaginile din cardurile de produse auto.

## Structura Folderului

```
product-card-slider/
├── product-card-slider.css    # Stiluri CSS pentru slider
├── product-card-slider.js     # Funcționalitate JavaScript
├── product-card-slider.php    # Funcții PHP pentru generarea HTML
└── README.md                  # Documentația componentei
```

## Funcționalități

### CSS (product-card-slider.css)
- Stiluri responsive pentru slider
- Animații smooth pentru tranziții
- Suport pentru touch/swipe pe mobile
- Indicatori vizuali (săgeți, dots, counter)
- Optimizări pentru diferite dimensiuni de ecran

### JavaScript (product-card-slider.js)
- Navigare cu săgeți (prev/next)
- Navigare cu dots
- Suport touch pentru mobile (swipe)
- Auto-inițializare la încărcarea paginii
- Prevenirea click-urilor accidentale pe link-ul cardului

### PHP (product-card-slider.php)
- Funcția `car_card_with_slider()` - versiune îmbunătățită a funcției originale
- Funcția `generateImageSliderHTML()` - generează HTML-ul pentru slider
- Funcția `include_product_card_slider_assets()` - include CSS și JS

## Utilizare

### 1. Include componentele în pagină

```php
<?php 
// Include funcțiile slider-ului
require_once 'content/site/components/product-card-slider/product-card-slider.php';

// Include CSS și JS în header
include_product_card_slider_assets();
?>
```

### 2. Folosește funcția îmbunătățită

```php
<?php
// În loc de car_card(), folosește car_card_with_slider()
$cards = car_card_with_slider('new', 8, null, 'av', true);
echo $cards['txt'];
?>
```

### 3. Parametrii funcției car_card_with_slider()

- `$v1` - Tipul de carduri ('new', 'archive', 'top', 'smlr', 'fltr')
- `$lmt` - Limita de carduri de afișat
- `$zreq` - Parametrii de filtrare
- `$stts` - Filtru status ('av', 'na')
- `$enable_slider` - Activează/dezactivează slider-ul (default: true)

## Caracteristici Tehnice

### Preluarea Imaginilor
- Imaginile se preiau din tabelul `{prefx}_car_pht`
- Pentru slider: `SELECT name FROM {prefx}_car_pht WHERE it_id=:it_id ORDER BY main DESC, pos ASC`
- Pentru imagine unică: `SELECT name FROM {prefx}_car_pht WHERE it_id=:it_id AND main="1" LIMIT 1`

### Calea Imaginilor
```
/media/images/upload/car/{p_path}/{id}/med/{nume_imagine}.jpg
```

### Comportament
- **1 imagine**: Se afișează normal, fără slider
- **2+ imagini**: Se activează slider-ul cu toate funcționalitățile
- **Fără imagini**: Se afișează placeholder-ul standard

### Responsive Design
- **Desktop**: Săgeți vizibile la hover, dots pentru max 5 imagini
- **Mobile**: Suport touch/swipe, dimensiuni optimizate

## Integrare cu Sistemul Existent

Componenta este compatibilă cu sistemul existent și poate fi folosită ca înlocuire directă pentru funcția `car_card()` originală, fără a afecta funcționalitatea existentă.

### Pentru a activa slider-ul pe o pagină existentă:

1. Include fișierul PHP
2. Înlocuiește apelurile `car_card()` cu `car_card_with_slider()`
3. Include CSS și JS assets
4. Testează funcționalitatea

## Browser Support

- Chrome/Edge/Safari: Full support
- Firefox: Full support  
- Mobile browsers: Full support cu touch gestures
- IE11+: Basic support (fără unele animații moderne)
