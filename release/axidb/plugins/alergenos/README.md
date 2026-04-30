# Plugin AxiDB - Alergenos UE

Catalogo de los 14 alergenos obligatorios de la UE y mapeo
ingrediente -> alergenos. Persiste en
`STORAGE/_system/alergenos_catalog.json` y se autoseed la primera vez.

## Uso

```php
$catalog = new AxiDB\Plugins\Alergenos\AlergenosCatalog();
$ues = $catalog->getAlergenosUE(); // 14 codigos oficiales
$res = $catalog->lookupIngrediente('Gambas'); // ['crustaceos']
$catalog->addIngrediente('chocolate', ['lacteos', 'soja']);
```

## Ampliacion

El catalogo crece automaticamente con cada producto procesado.
La tabla principal vive en `STORAGE/_system/alergenos_catalog.json`
y se puede editar a mano si se quiere ajustar.
